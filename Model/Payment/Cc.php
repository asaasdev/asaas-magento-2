<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Asaas\Magento2\Model\Payment;

use Magento\Sales\Model\Order;
use Magento\TestFramework\ObjectManager;

class Cc extends \Magento\Payment\Model\Method\AbstractMethod {

  protected $_code = "cc";

  protected $_isGateway                   = true;
  protected $_canCapture                  = true;
  protected $_canCapturePartial           = true;
  protected $_canRefund                   = true;
  protected $_canRefundInvoicePartial     = true;

  /** @var \Magento\Framework\Message\ManagerInterface */
  protected $messageManager;

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
    \Magento\Framework\Message\ManagerInterface $messageManager,
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
      $data
    );
    $this->helperData = $helper;
    $this->checkoutSession = $checkout;
    $this->messageManager = $messageManager;
    $this->_decrypt = $encryptor;
    $this->_customerRepositoryInterface = $customerRepositoryInterface;
  }

  public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {
    if (!$this->helperData->getStatusCc()) {
      return false;
    }
    return parent::isAvailable($quote);
  }

  public function order(\Magento\Payment\Model\InfoInterface $payment, $amount) {
    try {

      $date = new \DateTime();
      $notification = $this->helperData->getNotifications();

      //Info do CC
      $info = $this->getInfoInstance();
      $paymentInfo = $info->getAdditionalInformation();

      //pegando dados do pedido do clioente
      $order = $payment->getOrder();
      $shippingaddress = $order->getBillingAddress();
      $customer = $this->_customerRepositoryInterface->load($order->getCustomerId());

      if (!isset($shippingaddress->getStreet()[2])) {
        throw new \Exception("Por favor, preencha seu endereço corretamente.", 1);
      }

      if (!$customer->getTaxvat()) {
        $cpfCnpj = $paymentInfo['cc_owner_cpf'];
      } else {
        $cpfCnpj = $customer->getTaxvat();
      }

      //Verifica a existência do usuário na Asaas obs: colocar cpf aqui
      $user = (array)$this->userExists(preg_replace('/\D/', '', $cpfCnpj));
      if (!$user) {
        throw new \Exception("Por favor, verifique suas Credenciais (Ambiente, ApiKey)", 1);
      }

      if (count($user['data']) >= 1) {
        $currentUser = $user['data'][0]->id;
      } else {
        //Pega os dados do usuário necessários para a criação da conta na Asaas
        $dataUser['name'] = $shippingaddress->getFirstName() . ' ' . $shippingaddress->getLastName();
        $dataUser['email'] = $shippingaddress->getEmail();
        $dataUser['cpfCnpj'] = preg_replace('/\D/', '', $cpfCnpj);
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
        if (!$newUser) {
          throw new \Exception("Por favor, verifique suas Credenciais (Ambiente, ApiKey)", 1);
        }
        $currentUser = $newUser['id'];
      }

      $values = explode("-", $paymentInfo['installments']);

      //Monta o Array para o envio das informações ao Asaas
      $request = [
        'origin' => 'Magento',
        'customer' => $currentUser,
        'billingType' => 'CREDIT_CARD',
        'installmentCount' => (int)$values[0],
        'installmentValue' => (float)$values[1],
        'dueDate' => $date->format('Y-m-d'),
        'description' => "Pedido " . $order->getIncrementId(),
        'externalReference' => $order->getIncrementId(),
        'creditCard' => [
          'holderName' => $paymentInfo['credit_card_owner'],
          'number' => $paymentInfo['credit_card_number'],
          'expiryMonth' => $paymentInfo['credit_card_exp_month'],
          'expiryYear' => $paymentInfo['credit_card_exp_year'],
          'ccv' => $paymentInfo['credit_card_cid'],
        ],
        'creditCardHolderInfo' => [
          'name' => $shippingaddress->getFirstName() . ' ' . $shippingaddress->getLastName(),
          'email' => $shippingaddress->getEmail(),
          'cpfCnpj' => $paymentInfo['cc_owner_cpf'],
          'postalCode' => $shippingaddress->getPostcode(),
          'addressNumber' => $shippingaddress->getStreet()[1],
          'addressComplement' => null,
          'phone' => $shippingaddress->getTelephone(),
          'mobilePhone' => $paymentInfo['credit_card_phone'],
        ],
        'remoteIp' => $order["remote_ip"],
      ];

      $paymentDone = (array)$this->doPayment($request);

      if (isset($paymentDone['errors'])) {
        throw new \Exception($paymentDone['errors'][0]->description);
      } else {
        $linkBoleto = $paymentDone['invoiceUrl'];
        $this->checkoutSession->setBoleto($linkBoleto);
        return $this;
      }
    } catch (\Exception $e) {
      $this->messageManager->addErrorMessage($e->getMessage());
      throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
    }
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

  public function assignData(\Magento\Framework\DataObject $data) {
    $info = $this->getInfoInstance();
    $info->setAdditionalInformation('cc_owner_cpf', $data['additional_data']['cc_owner_cpf'] ?? null)
      ->setAdditionalInformation('credit_card_type', $data['additional_data']['cc_type'] ?? null)
      ->setAdditionalInformation('credit_card_cid', $data['additional_data']['cc_cid'] ?? null)
      ->setAdditionalInformation('installments', $data['additional_data']['cc_installments'] ?? null)
      ->setAdditionalInformation('credit_card_number', $data['additional_data']['cc_number'] ?? null)
      ->setAdditionalInformation('credit_card_exp_year', $data['additional_data']['cc_exp_year'] ?? null)
      ->setAdditionalInformation('credit_card_exp_month', $data['additional_data']['cc_exp_month'] ?? null)
      ->setAdditionalInformation('credit_card_phone', $data['additional_data']['cc_phone'] ?? null)
      ->setAdditionalInformation('credit_card_owner', $data['additional_data']['cc_owner_name'] ?? null);

    return $this;
  }
}
