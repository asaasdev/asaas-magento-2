<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Asaas\Magento2\Model\Payment;

use Exception;
use Magento\Sales\Model\Order;

class Pix extends \Magento\Payment\Model\Method\AbstractMethod {

  protected $_code = "pix";
  protected $_isOffline = true;
  protected $_isInitializeNeeded = false;

  public function __construct(
      \Magento\Framework\Model\Context $context,
      \Magento\Framework\Registry $registry,
      \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
      \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
      \Magento\Payment\Helper\Data $paymentData,
      \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
      \Magento\Payment\Model\Method\Logger $logger,
      \Asaas\Magento2\Helper\Data $helper,
      \Magento\Checkout\Model\Session $checkout,
      \Magento\Store\Model\StoreManagerInterface $store,
      \Magento\Framework\Message\ManagerInterface $message,
      \Magento\Framework\Encryption\EncryptorInterface $encryptor,
      \Magento\Customer\Model\Customer $customerRepositoryInterface,
      $resource = null,
      $resourceCollection = null,
      array $data = []
  ) {
      if ($resource !== null && !($resource instanceof \Magento\Framework\Model\ResourceModel\AbstractResource)) {
          throw new \InvalidArgumentException('Parâmetro $resource deve ser null ou instância de AbstractResource');
      }

      if ($resourceCollection !== null && !($resourceCollection instanceof \Magento\Framework\Data\Collection\AbstractDb)) {
          throw new \InvalidArgumentException('Parâmetro $resourceCollection deve ser null ou instância de AbstractDb');
      }

      parent::__construct(
          $context,
          $registry,
          $extensionFactory,
          $customAttributeFactory,
          $paymentData,
          $scopeConfig,
          $logger,
          $resource,
          $resourceCollection,
          $data
      );

      $this->helperData = $helper;
      $this->checkoutSession = $checkout;
      $this->store = $store;
      $this->messageInterface = $message;
      $this->_decrypt = $encryptor;
      $this->_customerRepositoryInterface = $customerRepositoryInterface;
  }


  public function isAvailable($quote = null)
  {
      if ($quote !== null && !($quote instanceof \Magento\Quote\Api\Data\CartInterface)) {
          throw new \InvalidArgumentException('Parâmetro $quote deve ser null ou instância de CartInterface');
      }
  
      if (!$this->helperData->getStatusBillet()) {
          return false;
      }
  
      return parent::isAvailable($quote);
  }
  

  public function order(\Magento\Payment\Model\InfoInterface $payment, $amount) {
    try {
      //Pegando informações adicionais do pagamento (CPF) (Refatorar)
      $info = $this->getInfoInstance();
      $paymentInfo = $info->getAdditionalInformation();
      $discount = $this->helperData->getDiscout();

      $days = $this->helperData->getDays();
      $date = new \DateTime("+$days days");
      $notification = $this->helperData->getNotifications();

      //pegando dados do pedido do cliente
      $order = $payment->getOrder();
      $shippingaddress = $order->getBillingAddress();

      if (!isset($shippingaddress->getStreet()[2])) {
        throw new \Exception("Por favor, preencha seu endereço corretamente.", 1);
      }

      //Verifica a existência do usuário na Asaas obs: colocar cpf aqui
      $user = (array)$this->userExists($paymentInfo['pix_cpf']);
      if (!$user) {
        throw new \Exception("Por favor, verifique suas Credenciais (Ambiente, ApiKey)", 1);
      }
      if (count($user['data']) >= 1) {
        $currentUser = $user['data'][0]->id;
      } else {
        //Pega os dados do usuário necessários para a criação da conta na Asaas
        $dataUser['name'] = $shippingaddress->getFirstName() . ' ' . $shippingaddress->getLastName();
        $dataUser['email'] = $shippingaddress->getEmail();
        $dataUser['cpfCnpj'] = $paymentInfo['pix_cpf'];
        $dataUser['postalCode'] = $shippingaddress->getPostcode();

        //Habilita notificações entre o Asaas e o comprador
        $dataUser['notificationDisabled'] = $notification ? 'false' : 'true';

        //Verifica se foi informado o número foi informado
        if (isset($shippingaddress->getStreet()[1])) {
          $dataUser['addressNumber'] = $shippingaddress->getStreet()[1];
        }

        $newUser = (array)$this->createUser($dataUser);
        if (!$newUser) {
          throw new \Exception("Por favor, verifique suas Credenciais (Ambiente, ApiKey)", 1);
        }
        $currentUser = $newUser['id'];
      }

      //Monta os dados para uma cobrança com pix
      $request['customer'] = $currentUser;
      $request['billingType'] = "PIX";
      $request['value'] = $amount;
      $request['externalReference'] = $order->getIncrementId();
      $request['dueDate'] = $date->format('Y-m-d');
      $request['description'] = "Pedido " . $order->getIncrementId();
      $request['origin'] = 'Magento';

      //Informações de Desconto
      $request['discount']['value'] = $discount['value_discount'];
      $request['discount']['type'] = $discount['type_discount'];
      $request['discount']['dueDateLimitDays'] = $discount['due_limit_days'];

      //Informações de Multa
      $request['interest']['value'] = $this->helperData->getInterest();

      //Informações de juros
      $request['fine']['value'] = $this->helperData->getFine();      
      
      $paymentDone = (array)$this->doPayment($request);
      if (isset($paymentDone['errors'])) {
        throw new \Exception($paymentDone['errors'][0]->description, 1);
      }

     $qrCode = (array)$this->getQrCode($paymentDone['id']);

     if (!isset($qrCode['encodedImage']) || !isset($qrCode['payload'])) {
      throw new \Exception($paymentDone['errors'][0]->description, 1);
    }

     $this->checkoutSession->setPixQrCode($qrCode['encodedImage']);
     $this->checkoutSession->setPixPayload($qrCode['payload']);

    $order->setData('pix_asaas_qrcode', $qrCode['encodedImage']);
    $order->setData('pix_asaas_payload', $qrCode['payload']);
    $order->save();

    } catch (\Exception $e) {
      throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
    }
  }

  public function assignData(\Magento\Framework\DataObject $data) {
    $info = $this->getInfoInstance();
    $info->setAdditionalInformation('pix_cpf', $data['additional_data']['pix_cpf'] ?? null);
    return $this;
  }

  public function userExists($cpf) {
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $this->helperData->getUrl() . "/api/v3/customers?cpfCnpj=" . $cpf,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_USERAGENT => "magento",
      CURLOPT_HTTPHEADER => array(
        "access_token: " . $this->_decrypt->decrypt($this->helperData->getAcessToken()),
        "Content-Type: application/json"
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return json_decode($response);
  }

  public function createUser($data) {
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $this->helperData->getUrl() . "/api/v3/customers",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_USERAGENT => "magento",
      CURLOPT_POSTFIELDS => json_encode($data),
      CURLOPT_HTTPHEADER => array(
        "access_token: " . $this->_decrypt->decrypt($this->helperData->getAcessToken()),
        "Content-Type: application/json"
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return json_decode($response);
  }
  public function doPayment($data) {
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $this->helperData->getUrl() . "/api/v3/payments",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_USERAGENT => "magento",
      CURLOPT_POSTFIELDS => json_encode($data),
      CURLOPT_HTTPHEADER => array(
        "access_token: " . $this->_decrypt->decrypt($this->helperData->getAcessToken()),
        "Content-Type: application/json"
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    return json_decode($response);
  }

  public function getQrCode($paymentId) {
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $this->helperData->getUrl() . "/api/v3/payments/{$paymentId}/pixQrCode",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_USERAGENT => "magento",
      CURLOPT_HTTPHEADER => array(
        "access_token: " . $this->_decrypt->decrypt($this->helperData->getAcessToken()),
        "Content-Type: application/json"
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    return json_decode($response);
  }
}
