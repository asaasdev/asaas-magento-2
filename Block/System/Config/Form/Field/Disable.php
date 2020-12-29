<?php

namespace Asaas\Magento2\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
/** * Order Status source model */
class Disable extends \Magento\Config\Block\System\Config\Form\Field
{

  protected $_storeManager;
  protected $_urlInterface;

    public function __construct(
      \Magento\Backend\Block\Template\Context $context,        
      \Magento\Store\Model\StoreManagerInterface $storeManager,
      \Magento\Framework\UrlInterface $urlInterface,    
      array $data = []
    )
    {        
      $this->_storeManager = $storeManager;
      $this->_urlInterface = $urlInterface;
      parent::__construct($context, $data);
    }

  /**
   * @var string[] 
   */  
   
  

   protected function _getElementHtml(AbstractElement $element)
   {
       $element->setData('readonly',1);
       $element->setData('value',$this->_storeManager->getStore()->getBaseUrl().'index.php/rest/V1/asaas/update');
       return $element->getElementHtml();

   }
}
