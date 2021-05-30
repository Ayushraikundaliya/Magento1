<?php

Class Ccc_Order_Model_Resource_Order extends Mage_Core_Model_Resource_Db_Abstract {
	protected function _construct() {
		$this->_init('order/order','order_id');
	}
}