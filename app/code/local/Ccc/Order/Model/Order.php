<?php 

Class Ccc_Order_Model_Order extends Mage_Core_Model_Abstract {
	public function _construct() {
		$this->_init('order/order');
	}
}