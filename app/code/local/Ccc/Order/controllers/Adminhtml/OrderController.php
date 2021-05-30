<?php

class Ccc_Order_Adminhtml_OrderController extends Mage_Adminhtml_Controller_Action {
	public function indexAction() {
		$this->loadlayout();
		$this->_title($this->__('Orders'));
        $this->_setActiveMenu('order');
        $this->renderLayout();
        /*echo Zend_Debug::dump($this->getLayout()->getUpdate()->getHandles());
        die();*/
	}

	public function customerAction() {
		$this->_title($this->__('New Order'));
        $this->loadLayout();
        $this->_setActiveMenu('order');
        $this->renderLayout();
	}

	public function newAction() {
		$this->loadLayout();
        Mage::register('cart',$this->_getCart());
        $this->_setActiveMenu('order');
        $this->renderLayout();
	}

	protected function _getCart() {
		$id = (int)$this->getRequest()->getParam('id');
        if(!$id){
            $id = $this->_getSession()->getData('id');
        }else{
            $this->_getSession()->setData('id',$id);
        }

        $cart = Mage::getModel('order/cart');
        if(!$id){
            return $cart;
        }

        $collection = $cart->getCollection()->addFieldToFilter('customer_id',['eq'=>$id]);
        if(sizeof($collection->getItems()) == 1){
            $cart = $collection->getFirstItem();
            return $cart;
        }
        
        $customer = Mage::getModel('customer/customer')->load($id);
        $cart->setCustomerName($customer->getFirstname().' '.$customer->getLastname());
        $cart->setCustomerId($id);
        $cart->setCreatedDate(date('Y-m-d h:i:s'));
        $cart->save();
        return $cart;
	}

	public function addProductAction() {
        $ids = $this->getRequest()->getParam('id');
        $cart = $this->_getCart();
        $itemIds = $cart->getItemIds();
        if(!$ids){
            Mage::getSingleton('adminhtml/session')->addError('Do select the product');
            $this->_redirect('*/order/new');
            return;
        }
        foreach($ids as $key=>$id){
            $product = Mage::getModel('catalog/product')->load($id);
            if(in_array($id,$itemIds)){
                $cartItem = Mage::getModel('order/cart_item')->load(array_search($id,$itemIds));
                $cartItem->quantity++;
                $cartItem->setBasePrice($product->getPrice());
                $price = $this->calculatePrice($cartItem->getBasePrice(),$cartItem->getQuantity());
                $cartItem->setPrice($price);
            }else{
                $cartItem = Mage::getModel('order/cart_item');
                $cartItem->setCartId($cart->getId());
                $cartItem->setProductId($id);
                $cartItem->setBasePrice($product->getPrice());
                $cartItem->setPrice($product->getPrice());
                $cartItem->setCreatedDate(date('Y-m-d h:i:s'));
            }
            $cartItem->save();
        }
        Mage::getSingleton('adminhtml/session')->addSuccess('Product Added Successfully');
        $this->_redirect('*/order/new');
    }

    public function quantityAction() {
    	$increase = $this->getRequest()->getPost('quantity');
        if(!$increase){
            Mage::getSingleton('adminhtml/session')->addError('No Item added');
            $this->_redirect('*/*/new');
            return;
        }
        foreach($increase as $itemId => $quantity){
            $model = Mage::getModel('order/cart_item')->load($itemId);
            if(!is_numeric($quantity) || $quantity<0){
                Mage::getSingleton('adminhtml/session')->addError('Keep quantity more than 0!');
                $this->_redirect('*/order/new');
                return;
            }
            if($quantity==0){
                $model->delete();
                continue;
            }
            $model->setQuantity($quantity);
            $price = $this->price($model->getBasePrice(), $quantity);
            $model->setPrice($price);
            $model->save();
        }
        Mage::getSingleton('adminhtml/session')->addSuccess('Quantity Updated');
        $this->_redirect('*/order/new');
    }

    protected function price($price, $quantity){
        return $price * $quantity;
    }

    public function deleteItemAction() {
        $id = (int)$this->getRequest()->getParam('itemId');
        try{
            $model = Mage::getModel('order/cart_item')->load($id);
            if(!$model){
                throw new Exception("Product Not Found");
            }
            $model->delete();
        }catch(Exception $e){
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/order/new');
            return;
        }
        Mage::getSingleton('adminhtml/session')->addSuccess('Product Deleted Successfully');
        $this->_redirect('*/order/new');
    }
} 