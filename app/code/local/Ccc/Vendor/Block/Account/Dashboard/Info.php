<?php

class Ccc_Vendor_Block_Account_Dashboard_Info extends Mage_Core_Block_Template
{
    public function getVendor() {
        return Mage::getSingleton('vendor/session')->getVendor();
    }

    public function getChangePasswordUrl() {
        return Mage::getUrl('*/account/edit/changepass/1');
    }

    public function getSubscriptionObject() {
        if(is_null($this->_subscription)) {
            $this->_subscription = Mage::getModel('newsletter/subscriber')->loadByVendor(
                Mage::getSingleton('vendor/session')->getVendor()
            );
        }
        return $this->_subscription;
    }

    public function getIsSubscribed() {
        return $this->getSubscriptionObject()->isSubscribed();
    }

    public function isNewsletterEnabled() {
        return $this->getLayout()->getBlockSingleton('vendor/form_register')->isNewsletterEnabled();
    }
}
