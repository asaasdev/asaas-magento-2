<?php
namespace Asaas\Magento2\Model\Order;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

class Cancel
{
  protected $orderRepository;

  public function __construct(
    OrderRepositoryInterface $orderRepository
  ) {
    $this->orderRepository = $orderRepository;
  }

  /**
   * Cancel the order
   *
   * @param int $orderId
   * @throws LocalizedException
   */
  public function cancelOrder($orderId)
  {
    $order = $this->orderRepository->get($orderId);

    if (!$order->getId()) {
      throw new LocalizedException(__('Order not found.'));
    }

    if (!$order->canCancel()) {
      throw new LocalizedException(__('Order cannot be canceled.'));
    }
    
    $order->cancel();

    $this->orderRepository->save($order);
  }
}
