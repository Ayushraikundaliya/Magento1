<?php

class Ccc_Order_Block_Adminhtml_Order_Cart_Product extends Mage_Core_Block_Template {
    public function __construct() {
        $this->_blockGroup = 'order';
        $this->_controller = 'adminhtml_cart_index_product';
        //$this->setTemplate('order/adminhtml/cart/index/product.phtml');
    }

    public function getProducts()
    {
        $collection = Mage::getModel('catalog/product')->getResourceCollection();
        $collection->addAttributeToSelect('name');
        $collection->joinAttribute(
            'name',
            'catalog_product/name',
            'entity_id',
             null,
            'inner'
        );
        $collection->joinAttribute(
            'price',
            'catalog_product/price',
            'entity_id',
             null,
            'inner'
        );
       return $collection->getData();
    }
}