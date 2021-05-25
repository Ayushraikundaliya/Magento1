<?php

class Ccc_Vendor_Block_Adminhtml_Product_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('productGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('product_filter');

    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    protected function _prepareCollection()
    {

        $collection = Mage::getModel('vendor/product')->getResourceCollection();
        /*$collection->joinAttribute(
            'id',
            'vendor_product/entity_id',
            'entity_id',
            null,
            'inner'
        );*/
        $collection = Mage::getModel('vendor/product_request')->getResourceCollection();
        $collection->getSelect()->join(
             array(
                'vendor_product'=> 'vendor_product_entity'),
                'vendor_product.entity_id = main_table.product_id',
             array('*'));
        //$collection->getSelect()->where('request != 1');
    
        $this->setCollection($collection);

        return parent::_prepareCollection();
    
        $this->setCollection($collection);

        return parent::_prepareCollection();
        
    }

    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection()) {
            if ($column->getId() == 'websites') {
                $this->getCollection()->joinField('websites',
                    'catalog/product_website',
                    'website_id',
                    'product_id=entity_id',
                    null,
                    'left');
            }
        }
        return parent::_addColumnFilterToCollection($column);
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id',
            array(
                'header' => Mage::helper('catalog')->__('ID'),
                
                'type' => 'number',
                'index' => 'entity_id',
            ));

        $this->addColumn('type',
            array(
                'header' => Mage::helper('catalog')->__('Type'),
                
                'index' => 'type_id',
                'type' => 'options',
                'options' => Mage::getSingleton('catalog/product_type')->getOptionArray(),
            ));
        $this->addColumn('sku',
            array(
                'header' => Mage::helper('catalog')->__('SKU'),
                
                'index' => 'sku',
            ));
        $this->addColumn('vendor Id',
            array(
                'header' => $this->__('Vendor Id'),
                'index' => 'vendor_id',
            ));
        $this->addColumn('Request type',
            array(
                'header' => $this->__('Request Type'),
                'index' => 'request_type',
            ));
        $this->addColumn('Approve Status',
            array(
                'header' => $this->__('Approve Status'),
                'index' => 'approve_status',
            ));
        $this->addColumn('approved',
            array(
                'header' => Mage::helper('catalog')->__('Approved'),
                
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('catalog')->__('Approved'),
                        'url' => array(
                            'base' => '*/*/approved',
                            'params' => array('store' => $this->getRequest()->getParam('store')),
                        ),
                        'field' => 'id',
                    ),
                ),
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
            ));
        $this->addColumn('unapproved',
            array(
                'header' => Mage::helper('catalog')->__('Reject'),
                
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('catalog')->__('Reject'),
                        'url' => array(
                            'base' => '*/*/unApproved',
                            'params' => array('store' => $this->getRequest()->getParam('store')),
                        ),
                        'field' => 'id',
                    ),
                ),
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
            ));

        if (Mage::helper('catalog')->isModuleEnabled('Mage_Rss')) {
            $this->addRssList('rss/catalog/notifystock', Mage::helper('catalog')->__('Notify Low Stock RSS'));
        }

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('product');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('catalog')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('catalog')->__('Are you sure?'),
        ));

        $statuses = Mage::getSingleton('catalog/product_status')->getOptionArray();

        array_unshift($statuses, array('label' => '', 'value' => ''));
        $this->getMassactionBlock()->addItem('status', array(
            'label' => Mage::helper('catalog')->__('Change status'),
            'url' => $this->getUrl('*/*/massStatus', array('_current' => true)),
            'additional' => array(
                'visibility' => array(
                    'name' => 'status',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('catalog')->__('Status'),
                    'values' => $statuses,
                ),
            ),
        ));

        if (Mage::getSingleton('admin/session')->isAllowed('catalog/update_attributes')) {
            $this->getMassactionBlock()->addItem('attributes', array(
                'label' => Mage::helper('catalog')->__('Update Attributes'),
                'url' => $this->getUrl('*/catalog_product_action_attribute/edit', array('_current' => true)),
            ));
        }

        Mage::dispatchEvent('adminhtml_catalog_product_grid_prepare_massaction', array('block' => $this));
        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    public function getRowUrl($row)
    {
       
        return $this->getUrl('*/*/edit', array(
            'id' => $row->getEntityId())
        );
    }
}
