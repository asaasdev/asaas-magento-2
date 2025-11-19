<?php

namespace Asaas\Magento2\Model;

use Asaas\Magento2\Helper\Data;
use Asaas\Magento2\Model\Order\Invoice;
use Asaas\Magento2\Model\Order\Cancel;
use Asaas\Magento2\Logger\Logger;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;

class UpdateStatuses implements \Asaas\Magento2\Api\UpdateStatusesInterface
{
  protected $orderFactory;
  protected $orderRepository;
  protected $helperData;
  protected $invoiceCreator;
  protected $orderCancel;

  protected $logger;

  public function __construct(
    OrderRepositoryInterface $orderRepository,
    OrderFactory             $orderFactory,
    Data                     $helper,
    Invoice                  $invoiceCreator,
    Cancel                   $orderCancel,
    Logger                   $logger
  )
  {
    $this->orderFactory = $orderFactory;
    $this->orderRepository = $orderRepository;
    $this->helperData = $helper;
    $this->invoiceCreator = $invoiceCreator;
    $this->orderCancel = $orderCancel;
    $this->logger = $logger;
  }

  /**
   * @param mixed $event
   * @param mixed $payment
   * @return  false|string
   * @api
   */
  public function doUpdate($event, $payment)
  {
    $token_magento = $this->helperData->getTokenWebhook();
    $asaas_token = apache_request_headers();

    try {
      if ((isset($asaas_token['Asaas-Access-Token']) && isset($token_magento))) {
        if ($token_magento !== $asaas_token['Asaas-Access-Token']) {
          throw new \Magento\Framework\Webapi\Exception(
            __("Token Webhook not valid."),
            0,
            \Magento\Framework\Webapi\Exception::HTTP_UNAUTHORIZED);
        }
        $this->updateOrder($event, $payment);
      } else if ((!isset($asaas_token['Asaas-Access-Token']) && !isset($token_magento))) {
        $this->updateOrder($event, $payment);
      } else {
        throw new \Magento\Framework\Webapi\Exception(
          __("Token Webhook not valid."),
          0,
          \Magento\Framework\Webapi\Exception::HTTP_UNAUTHORIZED);
      }
      $response = ['request' => 'successful', 'dataRequest' => ['externalReference' => $payment['externalReference']]];
    } catch (\Exception $e) {
      $response = ['request' => ['error' => ['error' => true, 'message' => $e->getMessage()]]];
    }

    return json_encode($response);
  }

  /**
   * @param $event
   * @param $payment
   * @return \Magento\Sales\Model\Order
   * @throws \Magento\Framework\Webapi\Exception
   */
  private function updateOrder($event, $payment)
  {
    // Log do payload recebido
    $this->logger->info('RECEIVED PAYLOAD', [
      'event' => $event,
      'payment' => $payment
    ]);

    try {
      $paymentObj = (array)$payment;

      $this->logger->info('Loading order by externalReference', [
        'externalReference' => $paymentObj['externalReference'] ?? null
      ]);

      $order = $this->orderFactory->create()->loadByIncrementId($paymentObj['externalReference']);
      $orderId = $order->getId();

      if (!$orderId) {
        $this->logger->error('Order not found for externalReference', [
          'externalReference' => $paymentObj['externalReference'] ?? null
        ]);

        throw new \Magento\Framework\Webapi\Exception(
          __("Order Id not found"),
          0,
          \Magento\Framework\Webapi\Exception::HTTP_NOT_FOUND
        );
      }

      $this->logger->info('Order loaded successfully', [
        'orderId' => $orderId,
        'currentState' => $order->getState(),
        'currentStatus' => $order->getStatus(),
        'event' => $event
      ]);

      switch ($event) {
        case "PAYMENT_CONFIRMED":
        case "PAYMENT_RECEIVED":
          $this->logger->info('Processing payment event, updating order to PROCESSING', [
            'orderId' => $orderId,
            'event' => $event
          ]);

          $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
          $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);

          // Verifica se precisa criar invoice
          if ($this->helperData->getConfig('payment/asaasmagento2/general_options/active_invoice')) {
            $this->logger->info('Creating invoice for order', ['orderId' => $orderId]);
            try {
              $this->invoiceCreator->createInvoice($orderId);
              $this->logger->info('Invoice created successfully', ['orderId' => $orderId]);
            } catch (\Exception $invoiceException) {
              $this->logger->error('Error creating invoice', [
                'orderId' => $orderId,
                'error' => $invoiceException->getMessage()
              ]);
              throw $invoiceException;
            }
          }

          $order->setTotalInvoiced($order->getGrandTotal());
          $order->setBaseTotalInvoiced($order->getGrandTotal());
          $order->setTotalPaid($order->getGrandTotal());
          $order->setBaseTotalPaid($order->getGrandTotal());
          $order->setTotalDue(0);
          $order->setBaseTotalDue(0);
          
          break;

        case "PAYMENT_OVERDUE":
        case "PAYMENT_DELETED":
        case "PAYMENT_RESTORED":
        case "PAYMENT_REFUNDED":
        case "PAYMENT_AWAITING_CHARGEBACK_REVERSAL":
          $this->logger->info('Processing cancel event, updating order to CANCELED', [
            'orderId' => $orderId,
            'event' => $event
          ]);

          $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
          $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);

          try {
            $this->logger->info('Calling cancelOrder method', ['orderId' => $orderId]);
            $this->orderCancel->cancelOrder($orderId);
            $this->logger->info('Order canceled successfully', ['orderId' => $orderId]);
          } catch (\Exception $cancelException) {
            $this->logger->error('Error canceling order', [
              'orderId' => $orderId,
              'error' => $cancelException->getMessage()
            ]);
            throw $cancelException;
          }
          break;

        default:
          $this->logger->warning('Unhandled event received', [
            'event' => $event,
            'orderId' => $orderId
          ]);
          break;
      }

      
      $this->orderRepository->save($order);
      
      return $order;

    } catch (\Exception $e) {
      $this->logger->error('Exception caught in updateOrder', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'event' => $event,
        'payment' => $payment
      ]);

      throw new \Magento\Framework\Webapi\Exception(__($e->getMessage()));
    }
  }

}
