<?php

class Ccc_Order_Block_Adminhtml_Order_Cart_Customer extends Mage_Core_Block_Template {
    public function __construct() {
        $this->_blockGroup = 'order';
        $this->_controller = 'adminhtml_cart_index_customer';
        //$this->setTemplate('order/adminhtml/cart/index/customer.phtml');
    }
}