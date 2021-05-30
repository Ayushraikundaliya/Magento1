<?php

Class Ccc_Order_Model_Cart extends Mage_Core_Model_Abstract {
	protected $customer = null;
    protected $cartBillingAddress = null;
    protected $cartShippingAddress = null;
    protected $items = null;
    protected $subtotal = null;
    protected $finalTotal = null;
    protected $itemIds = [];

	public function _construct() {
		$this->_init('order/cart');
	}

	public function setCustomer(Mage_Customer_Model_Customer $customer) {
		$this->customer = $customer;
		return $this;
	}

	public function getCustomer() {
		if($this->customer) {
			return $this->customer;
		}
		$customer = Mage::getModel('customer/customer')->load($this->customer_id);
		$this->setCustomer($customer);
		return $this->customer;
	}

	public function setCartBillingAddress() {
        $cartId = $this->getId();
        if(!$cartId){
            return false;
        }
        $collection = Mage::getResourceModel('order/cart_address_collection')
                        ->addFieldToFilter('cart_id',['eq'=>$cartId])
                        ->addFieldToFilter('address_type',['eq'=>'billing']);
        $this->cartBillingAddress = $collection->getFirstItem();
        return $this;
    }
    public function getCartBillingAddress() {
        if(!$this->cartBillingAddress){
            $this->setCartBillingAddress();
        }
        return $this->cartBillingAddress;
    }

    public function setCartShippingAddress() {
        $cartId = $this->getId();
        if(!$cartId){
            return false;
        }
        $collection = Mage::getResourceModel('order/cart_address_collection')
                        ->addFieldToFilter('cart_id',['eq'=>$cartId])
                        ->addFieldToFilter('address_type',['eq'=>'shipping']);
        $this->cartShippingAddress = $collection->getFirstItem();
        return $this;
    }
    public function getCartShippingAddress() {
        if(!$this->cartShippingAddress){
            $this->setCartShippingAddress();
        }
        return $this->cartShippingAddress;
    }

    public function setItems() {
        $collection = Mage::getResourceModel('order/cart_item_collection')
                        ->addFieldToFilter('cart_id',['eq' => $this->getId()]);
        $this->items = $collection;
        return $this;
    }

    public function getItems() {
        if(!$this->items){
            $this->setItems();
        }
        return $this->items;
    }

    public function setSubtotal(){
        $items = $this->getItems();
        $this->subtotal =0;
        foreach($items as $key=>$item){
            $this->subtotal += $item->getTotalByQuantityPrice();
        }
        return $this;
    }
    public function getSubtotal(){
        if(!$this->subtotal){
            $this->setSubtotal();
        }
        return $this->subtotal;
    }
    
    public function setFinalTotal(){
        $this->finalTotal = $this->getSubTotal() + $this->getShippingAmount();
        return $this;
    }
    public function getFinalTotal(){
        if(!$this->finalTotal){
            $this->setFinalTotal();
        }
        return $this->finalTotal;
    }

    public function setItemIds(){
        $ids = [];
        /*echo "string";
        die();*/
        $collection = Mage::getResourceModel('order/cart_item_collection')
                        ->addFieldToFilter('cart_id',['eq'=>$this->getId()]);
        if($collection->count()){
            foreach($collection->getData() as $key=>$value){
                $ids[$value['cart_item_id']] = $value['product_id'];
            }
        }
        $this->itemIds = $ids;
        return $this;
    }

    public function getItemIds(){
        if(!$this->itemIds){
            $this->setItemIds();
        }
        return $this->itemIds;
    }
}