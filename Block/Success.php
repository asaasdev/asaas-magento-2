<?php

namespace Asaas\Magento2\Block;

class Success extends \Magento\Sales\Block\Order\Totals {
  protected $checkoutSession;
  protected $customerSession;
  protected $_orderFactory;

  public function __construct(
    \Magento\Checkout\Model\Session $checkoutSession,
    \Magento\Customer\Model\Session $customerSession,
    \Magento\Sales\Model\OrderFactory $orderFactory,
    \Magento\Framework\View\Element\Template\Context $context,
    \Magento\Framework\Registry $registry,
    array $data = []
  ) {
    parent::__construct($context, $registry, $data);
    $this->checkoutSession = $checkoutSession;
    $this->customerSession = $customerSession;
    $this->_orderFactory = $orderFactory;
  }

  public function getBoleto() {
    return $this->checkoutSession->getBoleto();
  }

  public function getPixQrCode() {
    return $this->checkoutSession->getPixQrCode();
  }

  public function getPixPayload() {
    return $this->checkoutSession->getPixPayload();
  }
  
  public function getOrder() {
    return  $this->_order = $this->_orderFactory->create()->loadByIncrementId(
      $this->checkoutSession->getLastRealOrderId()
    );
  }

  public function getCustomerId() {
    return $this->customerSession->getCustomer()->getId();
  }
}
