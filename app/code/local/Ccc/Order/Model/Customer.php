<?php

class Ccc_Order_Model_Customer extends Mage_Customer_Model_Customer {

    protected $customerBillingAddress = null;
    protected $customerShippingAddress = null;

    public function getCustomerBillingAddress() {    
        if(!$this->getId()){
            return false;
        }
        if(!$this->customerBillingAddress){
            $this->setCustomerBillingAddress();
        }
        return $this->customerBillingAddress;
    }
    public function setCustomerBillingAddress() {
        $addressId = $this->getResource()->getAttribute('default_billing')->getFrontend()->getValue($this);
        $address =  Mage::getModel('customer/address')->load($addressId);
        $this->customerBillingAddress = $address;
        return $this;
    }

    public function getCustomerShippingAddress() {  
        if(!$this->getId()){
            return false;
        }
        if(!$this->customerShippingAddress){
            $this->setCustomerShippingAddress();
        }
        return $this->customerShippingAddress;
    }
    public function setCustomerShippingAddress() {
        $addressId = $this->getResource()->getAttribute('default_shipping')->getFrontend()->getValue($this);
        $address =  Mage::getModel('customer/address')->load($addressId);
        $this->customerShippingAddress = $address;
        return $this;
    }
}