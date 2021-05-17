<?php
class Ccc_Vendor_Model_Session extends Mage_Core_Model_Session_Abstract
{
    /**
     * Vendor object
     *
     * @var Ccc_Vendor_Model_Vendor
     */
    protected $_vendor;

    /**
     * Flag with vendor id validations result
     *
     * @var bool
     */
    protected $_isVendorIdChecked = null;

    /**
     * Persistent vendor group id
     *
     * @var null|int
     */
    protected $_persistentVendorGroupId = null;

    /**
     * Retrieve vendor sharing configuration model
     *
     * @return Ccc_Vendor_Model_Config_Share
     */
    public function getVendorConfigShare()
    {
        return Mage::getSingleton('vendor/config_share');
    }

    public function __construct()
    {
        $this->init('adminhtml');
    }

    /**
     * Set vendor object and setting vendor id in session
     *
     * @param   Mage_Vendor_Model_Vendor $vendor
     * @return  Mage_Vendor_Model_Session
     */
    public function setVendor(Ccc_Vendor_Model_Vendor $vendor)
    {
        // check if vendor is not confirmed
        /*if ($vendor->isConfirmationRequired()) {
            if ($vendor->getConfirmation()) {
                return $this->_logout();
            }
        }*/
        $this->_vendor = $vendor;
        $this->setId($vendor->getId());
        // save vendor as confirmed, if it is not
        /*if ((!$vendor->isConfirmationRequired()) && $vendor->getConfirmation()) {
            $vendor->setConfirmation(null)->save();
            $vendor->setIsJustConfirmed(true);
        }*/
        return $this;
    }

    /**
     * Retrieve vendor model object
     *
     * @return Ccc_Vendor_Model_Vendor
     */
    public function getVendor()
    {
        if ($this->_vendor instanceof Ccc_Vendor_Model_Vendor) {
            return $this->_vendor;
        }

        $vendor = Mage::getModel('vendor/vendor')
            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        if ($this->getId()) {
            $vendor->load($this->getId());
        }
        $this->setVendor($vendor);
        return $this->_vendor;
    }

    /**
     * Set vendor id
     *
     * @param int|null $id
     * @return Ccc_Vendor_Model_Session
     */
    public function setVendorId($id)
    {
        $this->setData('vendor_id', $id);
        return $this;
    }

    /**
     * Retrieve vendor id from current session
     *
     * @return int|null
     */
    public function getVendorId()
    {
        if ($this->getData('vendor_id')) {
            return $this->getData('vendor_id');
        }
        return ($this->isLoggedIn()) ? $this->getId() : null;
    }

    /**
     * Checking vendor login status
     *
     * @return bool
     */
    public function isLoggedIn() {
        return (bool) $this->getId() && (bool) $this->checkVendorId($this->getId());
    }

    /**
     * Check exists vendor (light check)
     *
     * @param int $vendorId
     * @return bool
     */
    public function checkVendorId($vendorId)
    {
        if ($this->_isVendorIdChecked === null) {
            $this->_isVendorIdChecked = Mage::getResourceSingleton('vendor/vendor')->checkVendorId($vendorId);
        }
        return $this->_isVendorIdChecked;
    }

    /**
     * Vendor authorization
     *
     * @param   string $username
     * @param   string $password
     * @return  bool
     */
    public function login($username, $password)
    {
        /* @var $vendor Mage_Vendor_Model_Vendor */
        $vendor = Mage::getModel('vendor/vendor')
            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId());

        if ($vendor->authenticate($username, $password)) {
            $this->setVendorAsLoggedIn($vendor);
            return true;
        }
        return false;
    }

    public function setVendorAsLoggedIn($vendor)
    {
        $this->setVendor($vendor);
        $this->renewSession();
        Mage::getSingleton('core/session')->renewFormKey();
        Mage::dispatchEvent('Vendor_login', array('vendor' => $vendor));
        return $this;
    }

    /**
     * Authorization vendor by identifier
     *
     * @param   int $vendorId
     * @return  bool
     */
    public function loginById($vendorId)
    {
        $vendor = Mage::getModel('vendor/vendor')->load($vendorId);
        if ($vendor->getId()) {
            $this->setVendorAsLoggedIn($vendor);
            return true;
        }
        return false;
    }

    /**
     * Logout vendor
     *
     * @return Mage_Vendor_Model_Session
     */
    public function logout()
    {
        if ($this->isLoggedIn()) {
            Mage::dispatchEvent('Vendor_logout', array('vendor' => $this->getVendor()));
            $this->_logout();
        }
        return $this;
    }

    /**
     * Authenticate controller action by login vendor
     *
     * @param   Mage_Core_Controller_Varien_Action $action
     * @param   bool $loginUrl
     * @return  bool
     */
    public function authenticate(Mage_Core_Controller_Varien_Action $action, $loginUrl = null)
    {
        if ($this->isLoggedIn()) {
            return true;
        }

        $this->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_current' => true)));
        if (isset($loginUrl)) {
            $action->getResponse()->setRedirect($loginUrl);
        } else {
            $action->setRedirectWithCookieCheck(Ccc_Vendor_Helper_Data::ROUTE_ACCOUNT_LOGIN,
                Mage::helper('vendor')->getLoginUrlParams()
            );
        }

        return false;
    }

    /**
     * Set auth url
     *
     * @param string $key
     * @param string $url
     * @return Mage_Vendor_Model_Session
     */
    protected function _setAuthUrl($key, $url)
    {
        $url = Mage::helper('core/url')
            ->removeRequestParam($url, Mage::getSingleton('core/session')->getSessionIdQueryParam());
        // Add correct session ID to URL if needed
        $url = Mage::getModel('core/url')->getRebuiltUrl($url);
        return $this->setData($key, $url);
    }

    /**
     * Logout without dispatching event
     *
     * @return Mage_Vendor_Model_Session
     */
    protected function _logout()
    {
        $this->setId(null);
        $this->getCookie()->delete($this->getSessionName());
        Mage::getSingleton('core/session')->renewFormKey();
        return $this;
    }

    /**
     * Set Before auth url
     *
     * @param string $url
     * @return Mage_Vendor_Model_Session
     */
    public function setBeforeAuthUrl($url)
    {
        return $this->_setAuthUrl('before_auth_url', $url);
    }

    /**
     * Set After auth url
     *
     * @param string $url
     * @return Mage_Vendor_Model_Session
     */
    public function setAfterAuthUrl($url)
    {
        return $this->_setAuthUrl('after_auth_url', $url);
    }

    /**
     * Reset core session hosts after reseting session ID
     *
     * @return Mage_Vendor_Model_Session
     */
    public function renewSession()
    {
        parent::renewSession();
        Mage::getSingleton('core/session')->unsSessionHosts();

        return $this;
    }
}
