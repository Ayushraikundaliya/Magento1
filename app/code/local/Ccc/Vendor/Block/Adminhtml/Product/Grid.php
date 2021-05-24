<?php

class Ccc_Vendor_Block_Adminhtml_Product_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('vendorProductGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        //$this->setUseAjax(true);
        //$this->setVarNameFilter('vendor_product_filter');

    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('vendor/product_request')->getResourceCollection();
$collection->getSelect()->join(
array(
'vendor_product'=> 'vendor_product_entity'),
'vendor_product.entity_id = main_table.product_id',
array('*'));

$this->setCollection($collection);

return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn('entity_id',
            array(
                'header' => Mage::helper('vendor')->__('id'),
                'width'  => '50px',
                'index'  => 'entity_id',
            ));
        $this->addColumn('name',
            array(
                'header' => Mage::helper('vendor')->__('Name'),
                'width'  => '50px',
                'index'  => 'name',
            ));

        $this->addColumn('sku',
            array(
                'header' => Mage::helper('vendor')->__('SKU'),
                'width'  => '50px',
                'index'  => 'sku',
            ));

        $this->addColumn('price',
            array(
                'header' => Mage::helper('vendor')->__('Price'),
                'width'  => '50px',
                'index'  => 'price',
        ));

        $this->addColumn('request_type',
            array(
                'header' => Mage::helper('vendor')->__('Request'),
                'width'  => '50px',
                'index'  => 'request_type',
        ));

        $this->addColumn('approve_status',
            array(
                'header'   => Mage::helper('vendor')->__('Approve Status'),
                'width'    => '50px',
                'index'  => 'approve_status',
                'type' => 'text'
        ));

        $this->addColumn('action1',
        array(
            'header' => Mage::helper('vendor')->__('Approve Request'),
            'width' => '50px',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
            array(
                'caption'=>$this->__('Approve'),
                'url' => array(
                    'base' => '*/*/approved',
                ),
                'field' => 'id',
                ),
            ),
                'filter' => false,
                'sortable' =>false,
            ));
        
        $this->addColumn('action2',
            array(
                'header'   => Mage::helper('vendor')->__('Reject Request'),
                'width'    => '50px',
                'type'     => 'action',
                'getter'   => 'getId',
                'actions'  => array(
                    array(
                        'caption'=>$this->__('Reject'),
                        'url' => array(
                            'base' => '*/*/unApproved', 
                        ),
                        'field' => 'id',
                    ),
                ),
                'filter'    => false,
                'sortable'  =>false,
        )); 
        parent::_prepareColumns();
        return $this;
    }


    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    
}
