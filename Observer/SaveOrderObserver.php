<?php

namespace Asaas\Magento2\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class SaveOrderObserver implements ObserverInterface {
  public function execute(\Magento\Framework\Event\Observer $observer) {
    $order = $observer->getEvent()->getOrder();
    $quote = $observer->getEvent()->getQuote();

    $order->setData('cpf_cnpj', $quote->getCpfCnpj());

    return $this;
  }
}
