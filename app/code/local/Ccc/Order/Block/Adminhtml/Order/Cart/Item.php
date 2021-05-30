<?php

class Ccc_Order_Block_Adminhtml_Order_Cart_Item extends Ccc_Order_Block_Adminhtml_Order_Cart {
    public function __construct() {
        parent::__construct();
        $this->_blockGroup = 'order';
        $this->_controller = 'adminhtml_order_cart_item';
    }

    public function getCollection() {
        return $this->getCart()->getItems();
    }

    public function getUpdateUrl() {
        return $this->getUrl('*/order/quantity');
    }

    public function getDeleteItemUrl($id) {
        return $this->getUrl('*/order/deleteItem',array('itemId'=>$id));
    }
}