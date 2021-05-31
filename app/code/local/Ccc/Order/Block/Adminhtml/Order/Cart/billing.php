<?php

class Ccc_Order_Block_Adminhtml_Order_Cart_Billing extends Mage_Core_Block_Template {
    protected $cart = null;
    public function getBillingAddress()
    { 
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

    public function getCart() {
        if(!$this->cart) {
            $this->setCart();
        }
        return $this->cart; 
    }

    public function setCart() {
        $this->cart = Mage::registry('cart');
        return $this;
    }
}
