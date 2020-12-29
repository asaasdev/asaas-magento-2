<?php

namespace Asaas\Magento2\Model\Config\Source;

/** * Order Status source model */
class Discount {
  /**
   * @var string[] 
   */      public function toOptionArray() {
    return [
      ['value' => 'FIXED', 'label' => __('Valor Fixo')],
      ['value' => 'PERCENTAGE', 'label' => __('Percentual')], 
    ];
  }
}
