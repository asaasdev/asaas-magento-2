<?php

namespace Asaas\Magento2\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class AdditionalConfigProvider implements ConfigProviderInterface
{
  private $helperData;
  private $cart;
  private $ccConfig;
  private $customerRepositoryInterface;

  public function __construct(
    \Asaas\Magento2\Helper\Data $helper,
    \Magento\Checkout\Model\Cart $cart,
    \Magento\Payment\Model\CcConfig $ccConfig,
    \Magento\Customer\Model\Customer $customerRepositoryInterface
  ) {
    $this->helperData = $helper;
    $this->cart = $cart;
    $this->ccConfig = $ccConfig;
    $this->customerRepositoryInterface = $customerRepositoryInterface;
  }

  public function getInstallments()
  {
    $installments = $this->helperData->getInstallments();
    return $installments;
  }

  public function getCpfCnpj()
  {
    $customerId = $this->cart->getQuote()->getCustomerId();
    $customer = $this->customerRepositoryInterface->load($customerId);
    return $customer->getTaxvat();
  }

  public function getGrandTotal()
  {
    return $this->cart->getQuote()->getGrandTotal();
  }

  public function getMinParcelas()
  {
    return $this->helperData->getMinParcela();
  }

  public function getConfig()
  {
    $config = [
      'payment' => [
        'cc' => [
          'installments' => $this->getInstallments(),
          'grand_total' => $this->getGrandTotal(),
          'min_parcela' => $this->getMinParcelas(),
          'hasVerification' => $this->ccConfig->hasVerification(),
          'cc_types' => $this->helperData->getConfig('payment/asaasmagento2/options_cc/cctypes'),
          'cpf_cnpj' => $this->getCpfCnpj()
        ],
      ],
    ];
    return $config;
  }
}
