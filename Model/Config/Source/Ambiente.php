<?php
namespace Asaas\Magento2\Model\Config\Source;
/** * Order Status source model */
class Ambiente
{
  /**
   * @var string[] 
   */      public function toOptionArray()
  {
    return [['value' => 'production', 'label' => __('Production')], ['value' => 'dev', 'label' => __('Development')],];
  }
}
