<?php

namespace Asaas\Magento2\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class OrderObserver implements ObserverInterface
{
    private $session;

    public function __construct(
        CheckoutSession $session
    ) {
        $this->session = $session;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $boleto = $this->session->getBoleto();
        $order->setBoletoAsaas($boleto);
        $order->setState("pending")->setStatus("pending");
        $order->save();
    }
}