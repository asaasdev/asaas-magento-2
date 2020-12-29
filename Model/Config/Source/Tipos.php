<?php

namespace Asaas\Magento2\Model\Config\Source;

/** * Order Status source model */
class Tipos {
  /**
   * @var string[] 
   */      public function toOptionArray() {
    return [
      ['value' => 'VI', 'label' => __('Visa')],
      ['value' => 'MC', 'label' => __('MasterCard')], 
      ['value' => 'ELO', 'label' => __('Elo')],
      ['value' => 'AE', 'label' => __('American Express')],
      ['value' => 'DI', 'label' => __('Discover')],
      ['value' => 'HC', 'label' => __('Hipercard')],
    ];
  }
}
