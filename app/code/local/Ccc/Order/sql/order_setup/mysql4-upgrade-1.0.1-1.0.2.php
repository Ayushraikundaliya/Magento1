<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()
		->addColumn($installer->getTable('order/order'),'billing_name',
		Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), );

$installer->endSetup();