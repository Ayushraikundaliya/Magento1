<?php

Class Ccc_Order_Model_Resource_Order_Address extends Mage_Core_Model_Resource_Db_Abstract {
	protected function _construct() {
		$this->_init('order/order_address','address_id');
	}
}