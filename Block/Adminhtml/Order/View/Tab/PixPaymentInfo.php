<?php

namespace Asaas\Magento2\Block\Adminhtml\Order\View\Tab;

class PixPaymentInfo extends \Magento\Framework\View\Element\Template
{
    protected $request;
    protected $orderRepository;

    protected $_template = 'Asaas_Magento2::order/view/tab/pix_payment_info.phtml';

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Request\Http $http,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface,
        array $data = []
    ) {
		parent::__construct($context, $data);
        $this->request = $http;
        $this->orderRepository = $orderRepositoryInterface;
    }
    public function getOrder()
    {
        $orderId = $this->request->getParam('order_id');
        $order = $this->orderRepository->get($orderId);
        return $order;
    }


    public function getPaymentMethodByOrder()
    {
        $order = $this->getOrder();
        $payment = $order->getPayment();
		return $payment->getMethod();
    }
}