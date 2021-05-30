<?php

class Ccc_Order_Block_Adminhtml_Order_Cart extends Mage_Core_Block_Template {
    protected $cart = null;
    public function __construct() {
        parent::__construct();
    }

    public function getSaveUrl() {
        return $this->getUrl('*/*/placeOrder',array('_current'=>true));
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