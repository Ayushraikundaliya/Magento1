<?php

class Ccc_Order_Block_Adminhtml_Order_Cart_Total extends Ccc_Order_Block_Adminhtml_Order_Cart {
    public function __construct() {
        parent::__construct();
    }

    public function getSubTotal() {
        return $this->getCart()->getSubTotal();
    }

    public function getShippingAmount() {
        return $this->getCart()->getShippingAmount();
    }

    public function getTotal() {
        return $this->getCart()->getFinalTotal();
    }

    public function getSaveUrl(){
        return $this->getUrl('*/*/placeOrder');
    }
}