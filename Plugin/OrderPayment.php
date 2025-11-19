<?php

namespace Asaas\Magento2\Plugin;

class OrderPayment extends \Magento\Sales\Model\Order\Payment
{
    public function aroundPlace(\Magento\Sales\Model\Order\Payment $subject, callable $proceed)
    {
        $subject->_eventManager->dispatch('sales_order_payment_place_start', ['payment' => $subject]);
        $order = $subject->getOrder();

        $subject->setAmountOrdered($order->getTotalDue());
        $subject->setBaseAmountOrdered($order->getBaseTotalDue());
        $subject->setShippingAmount($order->getShippingAmount());
        $subject->setBaseShippingAmount($order->getBaseShippingAmount());

        $methodInstance = $subject->getMethodInstance();
        $methodInstance->setStore($order->getStoreId());

        $orderState = \Magento\Sales\Model\Order::STATE_NEW;
        $orderStatus = $methodInstance->getConfigData('order_status');
        $isCustomerNotified = $order->getCustomerNoteNotify();

        // Do order payment validation on payment method level
        $methodInstance->validate();
        $action = $methodInstance->getConfigPaymentAction();

        if ($action) {
            if ($methodInstance->isInitializeNeeded()) {
                $stateObject = new \Magento\Framework\DataObject();
                // For method initialization we have to use original config value for payment action
                $methodInstance->initialize($methodInstance->getConfigData('payment_action'), $stateObject);
                $orderState = $stateObject->getData('state') ?: $orderState;
                $orderStatus = $stateObject->getData('status') ?: $orderStatus;
                $isCustomerNotified = $stateObject->hasData('is_notified')
                    ? $stateObject->getData('is_notified')
                    : $isCustomerNotified;
            } else {
                $subject->processAction($action, $order);
            }
        } else {
            $order->setState($orderState)
                ->setStatus($orderStatus);
        }

        $isCustomerNotified = $isCustomerNotified ?: $order->getCustomerNoteNotify();

        if (!array_key_exists($orderStatus, $order->getConfig()->getStateStatuses($orderState))) {
            $orderStatus = $order->getConfig()->getStateDefaultStatus($orderState);
        }

        $subject->updateOrder($order, $orderState, $orderStatus, $isCustomerNotified);

        $subject->_eventManager->dispatch('sales_order_payment_place_end', ['payment' => $subject]);

        return $subject;
    }
}
