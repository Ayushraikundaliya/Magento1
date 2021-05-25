<?php

class Ccc_Vendor_Adminhtml_ProductController extends Mage_Adminhtml_Controller_Action
{

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('vendor/product');
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('vendor');
        $this->_title('Product Grid');

        $this->_addContent($this->getLayout()->createBlock('vendor/adminhtml_product'));

        $this->renderLayout();
    }

    protected function _initProduct()
    {
        $this->_title($this->__('Product'))
            ->_title($this->__('Manage products'));

        $productId = (int) $this->getRequest()->getParam('id');
        $product   = Mage::getModel('vendor/product')
            ->load($productId);
        if (!$productId) {
            if ($setId = (int) $this->getRequest()->getParam('set')) {
                Mage::getModel('adminhtml/session')->setId($setId);
            }
        }
        Mage::register('current_product', $product);
        Mage::getSingleton('cms/wysiwyg_config')->setStoreId($this->getRequest()->getParam('store'));
        return $product;
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $productId = (int) $this->getRequest()->getParam('id');
        $product = $this->_initVendor();

        if ($productId && !$product->getId()) {
            $this->_getSession()->addError(Mage::helper('vendor')->__('This vendor no longer exists.'));
            $this->_redirect('*/*/');
            return;
        }

        $this->_title($product->getName());

        $this->loadLayout();

        $this->_setActiveMenu('vendor/vendor');

        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $this->renderLayout();

    }

    public function saveAction()
    {

    }

    public function deleteAction()
    {
        try {

            $productModel = Mage::getModel('vendor/vendor');

            if (!($productId = (int) $this->getRequest()->getParam('id'))) {
                throw new Exception('Id not found');
            }

            if (!$productModel->load($productId)) {
                throw new Exception('vendor does not exist');
            }

            if (!$productModel->delete()) {
                throw new Exception('Error in delete record', 1);
            }

            Mage::getSingleton('core/session')->addSuccess($this->__('The vendor has been deleted.'));

        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }

        $this->_redirect('*/*/');
    }
    public function approvedAction()
    {   
        

        $productId = Mage::getModel('vendor/product_request')->load($this->getRequest()->getParam('id'))->getProductId();
        $vendorId = Mage::getModel('vendor/product_request')->load($this->getRequest()->getParam('id'))->getVendorId();

        $productRequestModel = Mage::getResourceModel('vendor/product_request_collection')->addFieldToFilter('product_id', array('eq', $productId))->load()->getLastItem();
        $vendorProduct = Mage::getModel('vendor/product')->load($productId)->getData();

        if ($productRequestModel->getRequestType() == 'Deleted') {
                $this->_forward('deleteRequest');
                return;
            }

            $catalogProductModel = Mage::getModel('catalog/product');

        $attributeSetId = $catalogProductModel->getResource()->getEntityType()->getDefaultAttributeSetId();
        $entityTypeId = $catalogProductModel->getResource()->getEntityType()->getEntityTypeId();

        $catalogProductModel->setData($vendorProduct);
        $catalogProductModel->setAttributeSetId($attributeSetId);
        $catalogProductModel->setEntityTypeId($entityTypeId);
        $catalogProductModel->setVendorId($vendorId);

            if ($catalogProductModel->save()) {
                $productRequestModel = Mage::getModel('vendor/product_request');
                $productRequestModel->setRequestId($this->getRequest()->getParam('id'));
                $productRequestModel->setCatalogProductId($catalogProductModel->getId());
                $productRequestModel->setApproveStatus('Approved');
                //$productRequestModel->setCreatedAt($product->getCreatedAt());
                $productRequestModel->setApprovedAt(time());
                $productRequestModel->save();
            }
            Mage::getSingleton('core/session')->addSuccess($this->__('The product has been Approved.'));

        $this->_redirect('*/*/');
    }

    public function unApprovedAction()
    {
        $productRequestModel = Mage::getModel('vendor/product_request');
        $productRequestModel->setRequestId($this->getRequest()->getParam('id'));
        $productRequestModel->setApproveStatus('Rejected');
            //$productRequestModel->setCreatedAt($product->getCreatedAt());
            $productRequestModel->setApprovedAt(time());
            $productRequestModel->save();
            Mage::getSingleton('core/session')->addSuccess($this->__('The product has been Rejected.'));
        $this->_redirect('*/*/');
    }
   public function deleteRequestAction()
    {
         $productRequest = Mage::getModel('vendor/product_request');
        if(!$requestId = $this->getRequest()->getParam('id')){
            $this->_redirect('*/*');
            return ;
        }
        $productRequest->load($requestId);
        
        $product = Mage::getModel('vendor/product');
        $product->load($productRequest->getProductId());
    
        $product->delete();

        $this->_redirect('*/*/');
    }
    public function _getSession()
    {
        return Mage::getSingleton('vendor/session');
    }
}
