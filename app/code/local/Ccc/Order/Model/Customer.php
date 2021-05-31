<?php

class Ccc_Order_Model_Customer extends Mage_Customer_Model_Customer {

    protected $billingAddress = null;
    protected $customerShippingAddress = null;

    public function setBillingAddress(Mage_Customer_Model_Address $billingAddress) {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    public function getBillingAddress()
    {
        if ($this->billingAddress) {
            return $this->billingAddress;
        }
        if (!$this->getId()) {
            return false;
        }
        $addressId  = $this->getResource()->getAttribute('default_billing')
            ->getFrontend()->getValue($this);
        
        $address = Mage::getModel('customer/address')->load($addressId);
        return $address;    
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