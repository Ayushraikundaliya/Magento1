<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()
		->addColumn($installer->getTable('vendor/product'),'store_id',
		Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '0',
    ), 'store id');

$installer->endSetup();

?>