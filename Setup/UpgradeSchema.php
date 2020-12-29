<?php

namespace Asaas\Magento2\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface {
  public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {

    $setup->startSetup();
    $orderTable = 'sales_order';
    //Order table
    $setup->getConnection()
      ->addColumn(
        $setup->getTable($orderTable),
        'boleto_asaas',
        [
          'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
          'length' => '500',
          'default' => null,
          'nullable' => true,
          'comment' => 'Boleto asaas'
        ]
      );

    $setup->endSetup();
  }
}
