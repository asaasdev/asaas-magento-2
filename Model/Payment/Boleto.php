<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Asaas\Magento2\Model\Payment;

use Exception;
use Magento\Sales\Model\Order;

class Boleto extends \Magento\Payment\Model\Method\AbstractMethod {

  protected $_code = "boleto";
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
    \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
    \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
    array $data = [],
    \Asaas\Magento2\Helper\Data $helper,
    \Magento\Checkout\Model\Session $checkout,
    \Magento\Store\Model\StoreManagerInterface $store,
    \Magento\Framework\Message\ManagerInterface $message,
    \Magento\Framework\Encryption\EncryptorInterface $encryptor,
    \Magento\Customer\Model\Customer $customerRepositoryInterface
  ) {
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
      $data,
    );
    $this->helperData = $helper;
    $this->checkoutSession = $checkout;
    $this->store = $store;
    $this->messageInterface = $message;
    $this->_decrypt = $encryptor;
    $this->_customerRepositoryInterface = $customerRepositoryInterface;
  }

  public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {
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

      //pegando dados do pedido do clioente
      $order = $payment->getOrder();
      $billingaddress = $order->getBillingAddress();
      $shippingaddress = $order->getShippingAddress();

      if (!isset($shippingaddress->getStreet()[2])) {
        throw new \Exception("Por favor, preencha seu endereço corretamente.", 1);
      }

      //Verifica a existência do usuário na Asaas obs: colocar cpf aqui
      $user = (array)$this->userExists($paymentInfo['boleto_owner_cpf']);

      if (count($user['data']) >= 1) {
        $currentUser = $user['data'][0]->id;
      } else {
        //Pega os dados do usuário necessários para a criação da conta na Asaas
        $dataUser['name'] = $shippingaddress->getFirstName() . ' ' . $shippingaddress->getLastName();
        $dataUser['email'] = $shippingaddress->getEmail();
        $dataUser['cpfCnpj'] = $paymentInfo['boleto_owner_cpf'];
        $dataUser['postalCode'] = $shippingaddress->getPostcode();

        //Habilita notificações entre o Asaas e o comprador
        if (isset($notification)) {
          $dataUser['notificationDisabled'] = 'false';
        } else {
          $dataUser['notificationDisabled'] = 'true';
        }

        //Verifica se foi informado o número foi informado
        if (isset($shippingaddress->getStreet()[1])) {
          $dataUser['addressNumber'] = $shippingaddress->getStreet()[1];
        }

        $newUser = (array)$this->createUser($dataUser);
        $currentUser = $user['data'][0]->id;
      }

      //Monta os dados para uma cobrança simples com boleto
      $request['customer'] = $currentUser;
      $request['billingType'] = "BOLETO";
      $request['value'] = $amount;
      $request['externalReference'] = $order->getIncrementId();
      $request['dueDate'] = $date->format('Y-m-d');
      $request['description'] = "Pedido " . $order->getIncrementId();

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
      $linkBoleto = $paymentDone['bankSlipUrl'];
      $this->checkoutSession->setBoleto($linkBoleto);
    } catch (\Exception $e) {
      throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
    }
  }
  public function assignData(\Magento\Framework\DataObject $data) {
    $info = $this->getInfoInstance();
    $info->setAdditionalInformation('boleto_owner_cpf', $data['additional_data']['boleto_owner_cpf'] ?? null);
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
}
