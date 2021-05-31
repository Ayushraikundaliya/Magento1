<?php

class Ccc_Order_Block_Adminhtml_Order_Cart_PaymentMethod extends Ccc_Order_Block_Adminhtml_Order_Cart {
    public function __construct() {
        parent::__construct();
    }

    public function getPaymentMethod() {
        $paymentMethods = Mage::getModel('payment/config')->getActiveMethods();
        return $paymentMethods;
    }

    public function getPaymnetMethodUrl() {
        return $this->getUrl('*/order/paymentMethod',array('_current'=>true));
    }

    public function fetchPaymentMethod() {
        return $this->getCart()->getPaymentMethodCode();
    }
}