<?php

class Ccc_Order_Block_Adminhtml_Order_Cart_ShippingMethod extends Ccc_Order_Block_Adminhtml_Order_Cart {
    public function __construct() {
        parent::__construct();
    }

    public function getShippingMethod() {
        $shippingMethods = Mage::getModel('shipping/config')->getActiveCarriers();
        return $shippingMethods;
    }

    public function getShippingMethodUrl() {
        return $this->getUrl('*/order/shippingMethod');
    }

    public function fetchShippingMethod() {
        return $this->getCart()->getShippingMethodCode();
    }
}