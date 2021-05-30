<?php

class Ccc_Order_Block_Adminhtml_Order_Cart_Shipping extends Ccc_Order_Block_Adminhtml_Order_Cart {
    public function __construct() {
    	parent::__construct();
        $this->_blockGroup = 'order';
        $this->_controller = 'adminhtml_order_cart_shipping';
    }

    public function getShippingAddress() {
        $cart = $this->getCart();
        if($cart->getCartShippingAddress()->getId()){
            return $cart->getCartShippingAddress();
        }
        /*echo "<pre>";
        print_r($cart->getCustomer());
        die();*/
        if($cart->getCustomer()->getCustomerShippingAddress()/*->getId()*/){
            $address = $cart->getCustomer()->getCustomerShippingAddress();
            $address->setCountry($address->getCountryId());
            $address->setAddress($address->getStreet());
            $address->setState($address->getRegion());
            $address->setZipcode($address->getPostcode());
            $address->setFirstName($address->getFirstname());
            $address->setLastName($address->getLastname());
            return $address;
        }
        return Mage::getModel('order/cart_address');
    }

    public function getValue($address, $value) {
        if(array_key_exists($value,$address)){
            return $address[$value];
        }
        return false;
    }

    public function getShippingMethodUrl() {
        return $this->getUrl('*/*/shipping',array('_current'=>true));
    }
}