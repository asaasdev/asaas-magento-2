<?php
namespace Asaas\Magento2\Observer;

use Magento\Framework\Event\ObserverInterface;

class OrderObserver implements ObserverInterface
{
    public function __construct(
       
        \Magento\Checkout\Model\Session $session
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