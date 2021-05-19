<?php

class Ccc_Vendor_Block_Account_Dashboard_Hello extends Mage_Core_Block_Template {

    public function getVendorName()
    {
        return Mage::getSingleton('vendor/session')->getVendor()->getName();
    }

}
