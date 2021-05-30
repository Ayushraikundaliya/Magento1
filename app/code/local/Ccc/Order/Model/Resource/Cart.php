<?php

Class Ccc_Order_Model_Resource_Cart extends Mage_Core_Model_Resource_Db_Abstract {
	protected function _construct() {
		$this->_init('order/cart','cart_id');
	}
}