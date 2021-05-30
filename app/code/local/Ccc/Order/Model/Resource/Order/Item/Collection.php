<?php

Class Ccc_Order_Model_Resource_Order_Item_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {
	public function _construct() {
		$this->_init('order/order_item');
	}
}