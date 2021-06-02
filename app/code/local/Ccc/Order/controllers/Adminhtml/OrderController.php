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
        $cart->setCreatedAt(date('Y-m-d h:i:s'));
        $cart->save();
        return $cart;
	}

	public function addProductAction() {
        $ids = $this->getRequest()->getParam('id');
        $cart = $this->_getCart();
        $itemIds = $cart->getItemIds();
        $quantity = 0;
        if(!$ids){
            Mage::getSingleton('adminhtml/session')->addError('Do select the product');
            $this->_redirect('*/order/new');
            return;
        }
        foreach($ids as $key=>$id){
            $product = Mage::getModel('catalog/product')->load($id);
            if(in_array($id,$itemIds)){
                $cartItem = Mage::getModel('order/cart_item')->load(array_search($id,$itemIds));
                $quantity = $cartItem->getQuantity() + 1;
                $cartItem->setQuantity($quantity);
                $cartItem->setBasePrice($product->getPrice());
                $price = $this->price($cartItem->getBasePrice(),$cartItem->getQuantity());
                $cartItem->setPrice($price);
            }else{
                $quantity = $quantity + 1;
                $cartItem = Mage::getModel('order/cart_item');
                $cartItem->setCartId($cart->getId());
                $cartItem->setQuantity($quantity);
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
    	$qtyItems = $this->getRequest()->getPost('quantity');           
        foreach ($qtyItems as $cartItemId => $quantity) {
            if(!is_numeric($quantity) || $quantity<0){
                Mage::getSingleton('adminhtml/session')->addError('Invalid Quantity');
                $this->_redirect('*/order/new');
                return;
            }
            $cartItem = Mage::getModel('order/cart_item')->load($cartItemId);
            $cartItem->quantity = $quantity; 
            $price = $this->price($cartItem->getBasePrice(), $quantity); 
            $cartItem->setPrice($price);  
            $cartItem->save();
        }
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

    public function saveBillingAddressAction() {
        $cart = $this->_getCart();
        $billingAddress = $this->getRequest()->getPost('billing'); 
        $cartAddressModel = Mage::getModel('order/cart_address');
        $saveInAddressBook = $this->getRequest()->getPost('billingSaveAddressBook');

        $cartBillingAddress = $cart->getCartBillingAddress();
        /*echo "<pre>";
        print_r($cartBillingAddress->getCartAddressId());
        die();*/
        
        if($cartBillingAddress->getId()) {
            $cartAddressModel = $cartAddressModel->load($cartBillingAddress->getCartId());
            $cartAddressModel->setCartId($cartBillingAddress->getCartId());
            $cartAddressModel->setAddress($cartBillingAddress->getAddress());
            $cartAddressModel->setCity($cartBillingAddress->getCity());
            $cartAddressModel->setZipcode($cartBillingAddress->getZipcode());
            $cartAddressModel->setTelephone($cartBillingAddress->getTelephone());
        }else{
            $cartAddressModel->addData($billingAddress);
            $cartAddressModel->setAddressType('billing');
            $cartAddressModel->setCreatedAt(date('Y-m-d h:i:s'));
            $cartAddressModel->setCartId($cart->getCartId());
        }
        $cartAddressModel->save();
        $customer = $cart->getCustomer();
        if($saveInAddressBook){
             $customerBillingAddress = $customer->getDefaultBillingAddress();
            /*if (!$customerBillingAddress) {
                $customerBillingAddress = Mage::getModel('customer/address');
                $customerBillingAddress->setEntityTypeId($customerBillingAddress->getEntityTypeId());
                $customerBillingAddress->setParentId($customerId); 
                $customerBillingAddress->setCustomerId($customerId);
                $customerBillingAddress->setIsDefaultBilling(1);
            }*/
            $customerBillingAddress->setFirstname($cartAddressModel->getFirstName());
            $customerBillingAddress->setLastname($cartAddressModel->getLastName());
            $customerBillingAddress->setStreet($cartAddressModel->getAddress());
            $customerBillingAddress->setCity($cartAddressModel->getCity());
            $customerBillingAddress->setRegion($cartAddressModel->getState());
            $customerBillingAddress->setCountryId($cartAddressModel->getCountry());
            $customerBillingAddress->setPostcode($cartAddressModel->getZipcode());
            $customerBillingAddress->save();
        }
        Mage::getSingleton('adminhtml/session')->addSuccess('Billing Address is saved successfully');
        $this->_redirect('*/order/new');
    }

    public function shippingAddressAction(){
        $cart = $this->_getCart();
        $address = $this->getRequest()->getPost('shipping');
        $model = Mage::getModel('order/cart_address');
        $saveToAddress = 0;
        if(array_key_exists('save_to_address',$address)){
            $saveToAddress = 1;
            unset($address['save_to_address']);
        }

        $cartAddress = $cart->getCartShippingAddress();
        if(array_key_exists('same_as_billing',$address)){
            $billingAddress = $cart->getCartBillingAddress();
            if(!$billingAddress->getId()){
                Mage::getSingleton('adminhtml/session')->addError('Please Save Billing Address First');
                $this->_redirect('*/order/new');
                return;
            }
            if($cartAddress->getId()){
                $model = $model->load($cartAddress->getAddressId());
                $model->setAddress($billingAddress->getAddress());
                $model->setCity($billingAddress->getCity());
                $model->setZipcode($billingAddress->getZipcode());
                $model->setCountry($billingAddress->getCountry());
                $model->setState($billingAddress->getState());
                $model->setFirstName($billingAddress->getFirstName());
                $model->setLastName($billingAddress->getLastName());
            }else{
                $model->setData($billingAddress->getData());
                $model->setAddressType('shipping');
                unset($model['address_id']);
            }
            $model->setSameAsBilling('1');
        }else{
            if($cartAddress->getId()){
                $model = $model->load($cartAddress->getAddressId());
                $model->addData($address);
            }else{
                $model->addData($address);
                $model->setAddressType('shipping');
                $model->setCreatedData(date('Y-m-d h:i:s'));
                $model->setCartId($cart->getId());
            }
            $model->setSameAsBilling('0');
        }
        $model->save();
        $customer = $cart->getCustomer();
        if($saveToAddress){
            $customerBillingAddress = $customer->getDefaultBillingAddress();
            if (!$customerShippingAddress->getId()) {
                $customerShippingAddress = Mage::getModel('customer/address');
                $customerShippingAddress->setEntityTypeId($customerShippingAddress->getEntityTypeId());
                $customerShippingAddress->setParentId($customerId); 
                $customerShippingAddress->setCustomerId($customerId);
                $customerShippingAddress->setIsDefaultShipping(1);
            }
            $customerShippingAddress->setFirstname($model->getFirstName());
            $customerShippingAddress->setLastname($model->getLastName());
            $customerShippingAddress->setStreet($model->getAddress());
            $customerShippingAddress->setCity($model->getCity());
            $customerShippingAddress->setRegion($model->getState());
            $customerShippingAddress->setCountryId($model->getCountry());
            $customerShippingAddress->setPostcode($model->getZipcode());
            $customerShippingAddress->save();
        }
        Mage::getSingleton('adminhtml/session')->addSuccess('Shipping Address saved successfully');
        $this->_redirect('*/order/new');
    }

    public function paymentMethodAction(){
        $billingMethod = $this->getRequest()->getPost('paymentMethod');
        $cart = $this->_getCart();
        $cart-> payment_method_code = $billingMethod;
        $cart->save();
        $this->_redirect('*/order/new');
    }

    public function shippingMethodAction(){
        $data = $this->getRequest()->getPost('shippingMethod');
        if(!$data){
            Mage::getSingleton('adminhtml/session')->addError('Please Select Shipping Method');
            $this->_redirect('*/order/new');
            return;
        }
        $data = explode('_',$data);
        $cart = $this->_getCart();
        $cart->setShippingMethodCode($data[0]);
        $cart->setShippingAmount($data[1]);
        $cart->save();
        Mage::getSingleton('adminhtml/session')->addSuccess('Shipping Method Saved');
        $this->_redirect('*/order/new');
    }

    public function placeOrderAction() {
        $cart = $this->_getCart();
        $cartItems = $cart->getItems();
        $billingAddress = $cart->getCartBillingAddress();
        $shippingAddress = $cart->getCartShippingAddress();
        /*echo "<pre>";
        print_r($billingAddress);
        die();*/

        if($cartItems->count() <= 0){
            Mage::getSingleton('adminhtml/session')->addError('Please Add At Least One Item');
            $this->_redirect('*/order/new');
            return;
        }
        if(!$billingAddress->getCartAddressId()){
            Mage::getSingleton('adminhtml/session')->addError('Please Fill The Billing Address');
            $this->_redirect('*/order/new');
            return;
        }
        if(!$shippingAddress->getCartAddressId()){
            Mage::getSingleton('adminhtml/session')->addError('Please Fill The Shipping Address');
            $this->_redirect('*/order/new');
            return;
        }

        $cart->setTotal($cart->getFinalTotal());
        $cart->save();

        $orderModel = Mage::getModel('order/order');
        $orderModel->setData($cart->getData());
        unset($orderModel['cart_id']);
        $orderModel->setCreatedDate(date('Y-m-d h:i:s'));
        $orderModel->save();


        foreach($cartItems as $key=>$item){
            $orderItemModel = Mage::getModel('order/order_item')
                                ->setData($item->getData());

            unset($orderItemModel['item_id']);
            unset($orderItemModel['cart_id']);
            $orderItemModel->setOrderId($orderModel->getId());
            $orderItemModel->save();
            $item->delete();
            
        }

        $orderAddress = Mage::getModel('order/order_address');
        $orderAddress->setData($billingAddress->getData());
        unset($orderAddress['cart_id']);
        unset($orderAddress['address_id']);
        $orderAddress->setOrderId($orderModel->getId());
        $orderAddress->setCreatedDate(date('Y-m-d h:i:s'));
        $orderAddress->save();
        Mage::getModel('order/cart_address')->load($billingAddress->getAddressId())->delete();


        $orderAddress = Mage::getModel('order/order_address');
        $orderAddress->setData($shippingAddress->getData());
        unset($orderAddress['cart_id']);
        unset($orderAddress['address_id']);
        $orderAddress->setOrderId($orderModel->getId());
        $orderAddress->setCreatedDate(date('Y-m-d h:i:s'));
        $orderAddress->save();
        $addressModel = Mage::getModel('order/cart_address')->load($shippingAddress->getAddressId())->delete();

        $cart->delete();
        Mage::getSingleton('adminhtml/session')->addSuccess("Your Order Is Placed");
        $this->_redirect('*/adminhtml_order/index');
    }
} 