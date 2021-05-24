<?php

class Ccc_Vendor_Adminhtml_ProductController extends Mage_Adminhtml_Controller_Action {

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('vendor/vendor');
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('product');
        $this->_title('Product Grid');

        $this->_addContent($this->getLayout()->createBlock('vendor/adminhtml_product'));

        $this->renderLayout();
    }

    protected function _initProduct()
    {
        $this->_title($this->__('Product'))
            ->_title($this->__('Manage Products'));

        $productId = (int) $this->getRequest()->getParam('id');
        $product = Mage::getModel('vendor/product')
            ->setStoreId($this->getRequest()->getParam('store', 0))
            ->load($productId);

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
        $product   = $this->_initProduct();
        /*echo $productId;
        die();*/

        if ($productId && !$product->getId()) {
            $this->_getSession()->addError(Mage::helper('product')->__('This product no longer exists.'));
            $this->_redirect('*/*/');
            return;
        }

        $this->_title($product->getName());

        $this->loadLayout();

        $this->_setActiveMenu('vendor/product');

        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $this->renderLayout();

    }

    public function saveAction()
    {

        try {

            $productData = $this->getRequest()->getPost('account');

            $product = Mage::getSingleton('vendor/product');

            if ($productId = $this->getRequest()->getParam('id')) {

                if (!$product->load($productId)) {
                    throw new Exception("No Row Found");
                }
                Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

            }

            $product->addData($productData);

            $product->save();

            Mage::getSingleton('core/session')->addSuccess("product data added.");
            $this->_redirect('*/*/');

        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirect('*/*/');
        }

    }

    public function deleteAction()
    {
        try {

            $productModel = Mage::getModel('vendor/product');

            if (!($productId = (int) $this->getRequest()->getParam('id')))
                throw new Exception('Id not found');

            if (!$productModel->load($productId)) {
                throw new Exception('product does not exist');
            }

            if (!$productModel->delete()) {
                throw new Exception('Error in delete record', 1);
            }

            Mage::getSingleton('core/session')->addSuccess($this->__('The product has been deleted.'));

        } catch (Exception $e) {
            Mage::logException($e);
            $Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        
        $this->_redirect('*/*/');
    }

    public function approvedAction()
    {
        $productId = Mage::getModel('vendor/product_request')->load($this->getRequest()->getParam('id'))->getProductId();
        $vendorId = Mage::getModel('vendor/product_request')->load($this->getRequest()->getParam('id'))->getVendorId();

        $vendorProduct = Mage::getModel('vendor/product')->load($productId)->getData();

        
        $catalogProduct = Mage::getModel('catalog/product');

        $attributeSetId = $catalogProduct->getResource()->getEntityType()->getDefaultAttributeSetId();
        $entityTypeId = $catalogProduct->getResource()->getEntityType()->getEntityTypeId();

        $catalogProduct->setData($vendorProduct);
        $catalogProduct->setAttributeSetId($attributeSetId);
        $catalogProduct->setEntityTypeId($entityTypeId);
        $catalogProduct->setVendorId($vendorId);
        
        $catalogProduct->save();

        $productRequest = Mage::getModel('vendor/product_request');
        $productRequest->setRequestId($this->getRequest()->getParam('id'));
        $productRequest->setCatalogProductId($catalogProduct->getId());
        $productRequest->setRequest('1');
        $productRequest->setApproved(1);

        $productRequest->setRequestApprovedDate(Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));
        $productRequest->save();

        $productRequest->load($productRequest->getRequestId());
        
        if($productRequest->getRequestType() == 'delete')
        {
            $this->_forward('vendorDelete');
        }

       Mage::getSingleton('core/session')->addSuccess($this->__('The Product Approved Successfully...'));
       $this->_redirect('*/*/');
    }
    public function unApprovedAction()
    {
        $productRequest = Mage::getModel('vendor/product_request');
        $productRequest->setRequestId($this->getRequest()->getParam('id'));
        $productRequest->setRequest('1');
        $productRequest->setApproved(0);

        $productRequest->setRequestApprovedDate(Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s'));
        
        $productRequest->save();

       Mage::getSingleton('core/session')->addNotice($this->__('The Product Un Approved Successfully...'));
       $this->_redirect('*/*/');
    }

}
