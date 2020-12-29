<?php

namespace Asaas\Magento2\Model;

class Payment extends \Magento\Payment\Model\Method\Cc {
  const CODE = 'boleto';
  protected $_isGateway                   = true;
  protected $_canCapture                  = true;
  protected $_canCapturePartial           = true;
  protected $_canRefund                   = true;
  protected $_canRefundInvoicePartial     = true;
  protected $_countryFactory;
  protected $_minAmount = null;
  protected $_maxAmount = null;
  protected $_supportedCurrencyCodes = array('BRL');
  protected $_code = self::CODE;
  public function __construct(
    \Magento\Framework\Model\Context $context,
    \Magento\Framework\Registry $registry,
    \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
    \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
    \Magento\Payment\Helper\Data $paymentData,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    \Magento\Payment\Model\Method\Logger $logger,
    \Magento\Framework\Module\ModuleListInterface $moduleList,
    \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
    \Magento\Directory\Model\CountryFactory $countryFactory,
    \Magento\Backend\Model\Auth\Session $adminSession,
    array $data = array()
  ) {
    parent::__construct(
      $context,
      $registry,
      $extensionFactory,
      $customAttributeFactory,
      $paymentData,
      $scopeConfig,
      $logger,
      $moduleList,
      $localeDate,
      null,
      null,
      $data
    );
    $this->_countryFactory = $countryFactory;
  }
}
