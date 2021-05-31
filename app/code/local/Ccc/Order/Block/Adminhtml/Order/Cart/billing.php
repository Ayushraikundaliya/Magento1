<?php

class Ccc_Order_Block_Adminhtml_Order_Cart_Billing extends Ccc_Order_Block_Adminhtml_Order_Cart {
    protected $cart = null;
    public function getBillingAddress() { 
        $address = $this->getCart()->getCartBillingAddress();
        if ($address->getId()) {
            return $address;
        }
        $customerAddress = $this->getCart()->getCustomer()->getDefaultBillingAddress();
       if ($customerAddress == NULL) {
           return $address;
       }
        return $customerAddress;
    }
}
