<?php

Class Ccc_Order_Model_Resource_Order_Item extends Mage_Core_Model_Resource_Db_Abstract {
	protected function _construct() {
		$this->_init('order/order_item','cart_item_id');
	}
}