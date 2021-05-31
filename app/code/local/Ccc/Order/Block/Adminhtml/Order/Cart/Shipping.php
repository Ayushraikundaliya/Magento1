<?php

class Ccc_Order_Block_Adminhtml_Order_Cart_Shipping extends Ccc_Order_Block_Adminhtml_Order_Cart {
    protected $cart = null;

    public function getShippingAddress() { 
        $address = $this->getCart()->getCartShippingAddress();
        if ($address->getId()) {
            return $address;
        }
        $customerAddress = $this->getCart()->getCustomer()->getDefaultShippingAddress();
        if ($customerAddress == NULL) {
           return $address;
        }
        return $customerAddress;
    }

    public function getSameAsBilling() {
        if ($this->getCart()->getShippingAddress()->getId()) {
            return null;
        }
       $billing = $this->getCart()->getBillingAddress();
        if ($billing->same_as_billing) {
            return $billing->getSameAsBilling();
        }
    }

}
