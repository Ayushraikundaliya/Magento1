<?php

class Ccc_Shipping_Model_Express extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
	protected $_code = 'express';
	public function getAllowedMethods() {
		return array(
			'express'  => $this->getConfigData('name')
		);
	}

	public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
		if(!Mage::getStoreConfig('carriers/'.$this->_code.'/active')) {
			return false;
		}

		$handling = Mage::getStoreConfig('carriers/'.$this->_code.'/handling');
		$result = Mage::getModel('shipping/rate_result');
		$show = true;

		if($show) {
			$method = Mage::getModel('shipping/rate_result_method');
			$method->setCarrier($this->_code);
			$method->setCarrierTitle($this->getConfigData('title'));
			$method->setMethod($this->_code);
			$method->setMethodTitle($this->getConfigData('name'));
			$method->setPrice($this->getConfigData('price'));
			$method->setCost($this->getConfigData('price'));

			$result->append($method);

		} else {
			$error = Mage::getModel('shipping/rate_result_error');
			$error->setCarrier($this->_code);
			$error->setCarrierTitle($this->getConfigData('name'));
			$error->setErrorMessage($this->getConfigData('specificerrmsg'));

			$result->append($error);	
		}
		return $result;
	}

	/*protected function _getStandardRate() {
		$rate = Mage::getModel('shipping/rate_result_method');
		$rate->setCarrier($this->$_code);
		$rate->setCarrierTitle($this->getConfigData('title'));
		$rate->setMethod('large');
		$rate->setMethodTitle('Standard Delivery');
		$rate->setPrice(1.23);
		$rate->setCost(0);

		return $rate;
	}*/

	protected function _getStandardRate() {
		$rate = Mage::getModel('shipping/rate_result_method');
		$rate->setCarrier($this->$_code);
		$rate->setCarrierTitle($this->getConfigData('title'));
		$rate->setMethod('express');
		$rate->setMethodTitle('Express Delivery');
		$rate->setPrice(1.23);
		$rate->setCost(0);

		return $rate;
	}
}

?>