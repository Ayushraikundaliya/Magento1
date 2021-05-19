<?php

class Ccc_Vendor_Model_Config_Share extends Mage_Core_Model_Config_Data {
    const XML_PATH_VENDOR_ACCOUNT_SHARE = 'vendor/account_share/scope';
    const SHARE_GLOBAL  = 0;
    const SHARE_WEBSITE = 1;

    public function isGlobalScope() {
        return !$this->isWebsiteScope();
    }

    public function isWebsiteScope() {
        return Mage::getStoreConfig(self::XML_PATH_VENDOR_ACCOUNT_SHARE) == self::SHARE_WEBSITE;
    }

    public function toOptionArray() {
        return array(
            self::SHARE_GLOBAL  => Mage::helper('vendor')->__('Global'),
            self::SHARE_WEBSITE => Mage::helper('vendor')->__('Per Website'),
        );
    }

    public function _beforeSave() {
        $value = $this->getValue();
        if ($value == self::SHARE_GLOBAL) {
            if (Mage::getResourceSingleton('vendor/vendor')->findEmailDuplicates()) {
                Mage::throwException(
                    Mage::helper('vendor')->__('Cannot share vendor accounts globally because some vendor accounts with the same emails exist on multiple websites and cannot be merged.')
                );
            }
        }
        return $this;
    }
}
