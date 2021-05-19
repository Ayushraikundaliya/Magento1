<?php

class Ccc_Vendor_Block_Product_Attribute extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
    	$this->_blockGroup = 'vendor';
        $this->_controller = 'product_attribute';
        parent::__construct();
    }

}
