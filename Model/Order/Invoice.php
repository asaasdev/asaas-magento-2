<?php
namespace Asaas\Magento2\Model\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;

class Invoice
{
  protected $orderRepository;

  public function __construct(
    OrderRepositoryInterface $orderRepository
  ) {
    $this->orderRepository = $orderRepository;
  }
  
  /**
   * Create the order invoice and capture
   *
   * @param int $orderId
   * @throws LocalizedException
   */
  public function createInvoice($orderId)
  {
    $order = $this->orderRepository->get($orderId);
    
    $payment = $order->getPayment();

    if (!$payment) {
      throw new LocalizedException(__('Payment not found.'));
    }
    
    $payment->setTransactionId($order->getIncrementId() ?? null);
    $payment->setIsTransactionClosed(true);
    
    $payment->registerCaptureNotification($order->getGrandTotal());
    
    $this->orderRepository->save($order);
  }
}
