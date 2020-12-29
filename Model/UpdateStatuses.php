<?php

/**
 * Copyright 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Asaas\Magento2\Model;

use Asaas\Magento2\Api\UpdateStatusesInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Defines the implementaiton class of the calculator service contract.
 */
class UpdateStatuses implements UpdateStatusesInterface {
  protected $orderFactory;
  public function __construct(
    OrderRepositoryInterface $orderRepository,
    \Magento\Sales\Model\OrderFactory $orderFactory,
    \Asaas\Magento2\Helper\Data $helper
  ) {
    $this->orderFactory = $orderFactory;
    $this->orderRepository = $orderRepository;
    $this->helperData = $helper;
  }

  /** 
   * Post Company.
   *
   * @api
   * @param  mixed $event 
   * @param  mixed $payment
   * @return  mixed 
   */
  public function doUpdate($event, $payment) {

    $token_magento = $this->helperData->getTokenWebhook();
    $asaas_token = apache_request_headers();

    if ((isset($asaas_token['Asaas-Access-Token']) && isset($token_magento))) {
      if ($token_magento !== $asaas_token['Asaas-Access-Token']) {
        throw new \Magento\Framework\Webapi\Exception(__("Token Webhook inválido. Favor veriricar!"), 0, \Magento\Framework\Webapi\Exception::HTTP_UNAUTHORIZED);
      }
      $this->updateOrder($event, $payment);
    } else if ((!isset($asaas_token['Asaas-Access-Token']) && !isset($token_magento))) {
      $this->updateOrder($event, $payment);
    } else {
      throw new \Magento\Framework\Webapi\Exception(__("Token Webhook inválido. Favor veriricar!"), 0, \Magento\Framework\Webapi\Exception::HTTP_UNAUTHORIZED);
    }
  }

  private function updateOrder($event, $payment) {
    $paymentobj = (array) $payment;
    $orderId =  $this->orderFactory->create()->loadByIncrementId($paymentobj['externalReference']);
    if (!$orderId->getId()) {
      throw new \Magento\Framework\Webapi\Exception(__("Order Id not found"), 0, \Magento\Framework\Webapi\Exception::HTTP_NOT_FOUND);
    }
    if ($event == "PAYMENT_CONFIRMED" or $event == "PAYMENT_RECEIVED") {
      $order = $this->orderRepository->get($orderId->getId());
      $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
      $this->orderRepository->save($order);
    } elseif (
      $event == "PAYMENT_OVERDUE" or
      $event == "PAYMENT_DELETED" or
      $event == "PAYMENT_RESTORED" or
      $event == "PAYMENT_REFUNDED" or
      $event == "PAYMENT_AWAITING_CHARGEBACK_REVERSAL"
    ) {
      $order = $this->orderRepository->get($orderId->getId());
      $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
      $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
      $this->orderRepository->save($order);
      return http_response_code(200);
    }
  }
}
