<?php

class Ccc_Vendor_Block_Widget_Name extends Ccc_Vendor_Block_Widget_Abstract {
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('vendor/widget/name.phtml');
    }

    protected function _showConfig($key) {
        return (bool)$this->getConfig($key);
    }


    public function showMiddlename() {
        return (bool)$this->_getAttribute('middlename')->getIsVisible();
    }

    public function isMiddlenameRequired() {
        return (bool)$this->_getAttribute('middlename')->getIsRequired();
    }

    public function getClassName() {
        if (!$this->hasData('class_name')) {
            $this->setData('class_name', 'vendor-name');
        }
        return $this->getData('class_name');
    }

    protected function _getAttribute($attributeCode) {
        if ($this->getForceUseVendorAttributes() || $this->getObject() instanceof Ccc_Vendor_Model_Vendor) {
            return parent::_getAttribute($attributeCode);
        }

        $attribute = Mage::getSingleton('eav/config')->getAttribute('vendor_address', $attributeCode);

        if ($this->getForceUseVendorRequiredAttributes() && $attribute && !$attribute->getIsRequired()) {
            $vendorAttribute = parent::_getAttribute($attributeCode);
            if ($vendorAttribute && $vendorAttribute->getIsRequired()) {
                $attribute = $vendorAttribute;
            }
        }
        return $attribute;
    }

    public function getStoreLabel($attributeCode) {
        $attribute = $this->_getAttribute($attributeCode);
        return $attribute ? $this->__($attribute->getStoreLabel()) : '';
    }

    public function showSuffix() {
        return (bool)$this->_getAttribute('suffix')->getIsVisible();
    }

    public function showPrefix() {
        return (bool)$this->_getAttribute('prefix')->getIsVisible();
    }

    public function getContainerClassName() {
        $class = $this->getClassName();
        $class .= $this->showPrefix() ? '-prefix' : '';
        $class .= $this->showMiddlename() ? '-middlename' : '';
        $class .= $this->showSuffix() ? '-suffix' : '';
        return $class;
    }
}
