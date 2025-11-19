<?php

namespace Asaas\Magento2\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
  /**
   * @var \Magento\Framework\App\Config\ScopeConfigInterface
   */

  /**
   * returning config value
   **/

  public function getConfig($path)
  {
    $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

    return $this->scopeConfig->getValue($path, $storeScope);
  }
  /**
   * Return url  
   **/

  public function getUrl()
  {
    if ($this->getConfig('payment/asaasmagento2/general_options/ambiente') === 'production') {
      return !$this->getConfig('payment/asaasmagento2/general_options/url_prod') ?
        "https://www.asaas.com" :
        $this->getConfig('payment/asaasmagento2/general_options/url_prod');
    } else {
      return !$this->getConfig('payment/asaasmagento2/general_options/url_dev') ?
        "https://sandbox.asaas.com" :
        $this->getConfig('payment/asaasmagento2/general_options/url_dev');
    }
  }

  public function getDiscout()
  {
    return $this->getConfig('payment/asaasmagento2/options_boleto/options_boleto_discount');
  }

  public function getFine()
  {
    return $this->getConfig('payment/asaasmagento2/options_boleto/options_boleto_fine/value_fine');
  }

  public function getTokenWebhook()
  {
    return $this->getConfig('payment/asaasmagento2/general_options/token_webhook');
  }

  public function getInterest()
  {
    return $this->getConfig('payment/asaasmagento2/options_boleto/options_boleto_interest/value_interest');
  }

  public function getAcessToken()
  {
    return $this->getConfig('payment/asaasmagento2/general_options/api_key');
  }

  public function getNotifications()
  {
    return $this->getConfig('payment/asaasmagento2/general_options/active_notifications');
  }

  public function getDays()
  {
    return $this->getConfig('payment/asaasmagento2/options_boleto/validade');
  }

  private function getModuleEnabled()
  {
    return $this->getConfig('payment/asaasmagento2/active');
  }

  public function getStatusBillet()
  {
    if ($this->getModuleEnabled() && $this->getConfig('payment/asaasmagento2/options_boleto/active_billet')) {
      return true;
    } else {
      return false;
    }
  }

  public function getStatusCc()
  {
    if ($this->getModuleEnabled() && $this->getConfig('payment/asaasmagento2/options_cc/active_cc')) {
      return true;
    } else {
      return false;
    }
  }

  public function getInstrucoes()
  {
    return $this->getConfig('payment/asaasmagento2/options_boleto/instrucoes');
  }
  public function getInstallments()
  {
    $installmentsConfig = $this->getConfig('payment/asaasmagento2/options_cc/parcelas');
    if (empty($installmentsConfig)) {
      return false;
    }
    $installments = json_decode($this->getConfig('payment/asaasmagento2/options_cc/parcelas'));
    if (!$installments) {
      return false;
    }
    $i = 1;
    $array = (array)$installments;
    foreach ($array as $k => $v) {
      unset($array[$k]);
      $new_key =  $i;
      $array[$new_key] = $v;
      $i++;
    }
    foreach ($array as $key => $value) {
      $installmentss[$key] = $value->from_qty;
    }
    return $installmentss;
  }

  public function getMinParcela()
  {
    return $this->getConfig('payment/asaasmagento2/options_cc/min_parcela');
  }

  public function getPixInstructions()
  {
    return $this->getConfig('payment/asaasmagento2/options_pix/instrucoes');
  }
}
