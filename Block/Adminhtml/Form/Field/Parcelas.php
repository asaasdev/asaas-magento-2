<?php

namespace Asaas\Magento2\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Vendor\Module\Block\Adminhtml\Form\Field\TaxColumn;

/**
 * Class Ranges
 */
class Parcelas extends AbstractFieldArray
{

  protected $_template = 'Asaas_Magento2::form/array.phtml';

  /**
   * @var TaxColumn
   */
  private $taxRenderer;
  /**
   * Prepare rendering the new field by adding all the needed columns
   */
  protected function _prepareToRender()
  {
    $this->addColumn('from_qty', ['label' => 'Porcentagem de Juros', 'class' => 'required-entry validate-length maximum-length-5 minimum-length-0 validate-number']);
    $this->_addAfter = false;
    $this->_addButtonLabel = __('Add');
  }
  /**
   * Prepare existing row data object
   *
   * @param DataObject $row
   * @throws LocalizedException
   */
  protected function _prepareArrayRow(DataObject $row): void
  {
    $options = [];
    $tax = $row->getTax();
    if ($tax !== null) {
      $options['option_' . $this->getTaxRenderer()->calcOptionHash($tax)] = 'selected="selected"';
    }
    $row->setData('option_extra_attrs', $options);
  }
  /**
   * @return TaxColumn
   * @throws LocalizedException
   */
  private function getTaxRenderer()
  {
    if (!$this->taxRenderer) {
      $this->taxRenderer = $this->getLayout()->createBlock(
        TaxColumn::class,
        '',
        ['data' => ['is_render_to_js_template' => true]]
      );
    }
    return $this->taxRenderer;
  }
}
