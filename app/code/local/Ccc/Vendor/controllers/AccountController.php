<?php
class Ccc_Vendor_AccountController extends Mage_Core_Controller_Front_Action
{
    const VENDOR_ID_SESSION_NAME = "vendorId";
    const TOKEN_SESSION_NAME = "token";

    /**
     * Action list where need check enabled cookie
     *
     * @var array
     */
    protected $_cookieCheckActions = array('loginPost', 'createpost');

    /**
     * Retrieve vendor session model object
     *
     * @return Ccc_Vendor_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('vendor/session');
    }

    /**
     * Action predispatch
     *
     * Check Vendor authentication for some actions
     */
    public function preDispatch()
    {
        // a brute-force protection here would be nice

        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return;
        }

        $action = strtolower($this->getRequest()->getActionName());
        $openActions = array(
            'create',
            'login',
            'logoutsuccess',
            'forgotpassword',
            'forgotpasswordpost',
            'changeforgotten',
            'resetpassword',
            'resetpasswordpost',
            'confirm',
            'confirmation'
        );
        $pattern = '/^(' . implode('|', $openActions) . ')/i';

        if (!preg_match($pattern, $action)) {
            if (!$this->_getSession()->authenticate($this)) {
                $this->setFlag('', 'no-dispatch', true);
            }
        } else {
            $this->_getSession()->setNoReferer(true);
        }
    }

    /**
     * Action postdispatch
     *
     * Remove No-referer flag from vendor session after each action
     */
    public function postDispatch()
    {
        parent::postDispatch();
        $this->_getSession()->unsNoReferer(false);
    }

    /**
     * Default vendor account page
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('vendor/session');
        // $this->_initLayoutMessages('venodr/session');

        $this->getLayout()->getBlock('content')->append(
            $this->getLayout()->createBlock('vendor/account_dashboard')
        );
        $this->getLayout()->getBlock('head')->setTitle($this->__('My Account'));
        $this->renderLayout();
    }

    /**
     * Vendor login form page
     */
    public function loginAction()
    {
        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        $this->getResponse()->setHeader('Login-Required', 'true');
        $this->loadLayout();
        $this->_initLayoutMessages('vendor/session');
        // $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }

    /**
     * Login post action
     */
    public function loginPostAction()
    {
        if (!$this->_validateFormKey()) {
            $this->_redirect('*/*/');
            return;
        }

        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        $session = $this->_getSession();

        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $session->login($login['username'], $login['password']);
                    // if ($session->getVendor()->getIsJustConfirmed()) {
                    //     $this->_welcomeVendor($session->getVendor(), true);
                    // }
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Ccc_Vendor_Model_Vendor::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $session->addError($message);
                    $session->setUsername($login['username']);
                } catch (Exception $e) {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose vendor password
                }
            } else {
                $session->addError($this->__('Login and password are required.'));
            }
        }

        $this->_loginPostRedirect();
    }

    /**
     * Define target URL and redirect vendor after logging in
     */
    protected function _loginPostRedirect()
    {
        $session = $this->_getSession();

        if (!$session->getBeforeAuthUrl() || $session->getBeforeAuthUrl() == Mage::getBaseUrl()) {
            // Set default URL to redirect vendor to
            $session->setBeforeAuthUrl($this->_getHelper('vendor')->getAccountUrl());
            // Redirect vendor to the last page visited after logging in
            // if ($session->isLoggedIn()) {
            //     if (!Mage::getStoreConfigFlag(
            //         Ccc_Vendor_Helper_Data::XML_PATH_CUSTOMER_STARTUP_REDIRECT_TO_DASHBOARD
            //     )) {
            //         $referer = $this->getRequest()->getParam(Ccc_Vendor_Helper_Data::REFERER_QUERY_PARAM_NAME);
            //         if ($referer) {
            //             // Rebuild referer URL to handle the case when SID was changed
            //             $referer = $this->_getModel('core/url')
            //                 ->getRebuiltUrl($this->_getHelper('core')->urlDecodeAndEscape($referer));
            //             if ($this->_isUrlInternal($referer)) {
            //                 $session->setBeforeAuthUrl($referer);
            //             }
            //         }
            //     } else if ($session->getAfterAuthUrl()) {
            //         $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
            //     }
            // } else {
            //     $session->setBeforeAuthUrl($this->_getHelper('vendor')->getLoginUrl());
            // }
        } else if ($session->getBeforeAuthUrl() ==  $this->_getHelper('vendor')->getLogoutUrl()) {
            $session->setBeforeAuthUrl($this->_getHelper('vendor')->getDashboardUrl());
        } else {
            if (!$session->getAfterAuthUrl()) {
                $session->setAfterAuthUrl($session->getBeforeAuthUrl());
            }
            if ($session->isLoggedIn()) {
                $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
            }
        }
        $this->_redirectUrl($session->getBeforeAuthUrl(true));
    }

    /**
     * Vendor logout action
     */
    public function logoutAction()
    {
        $session = $this->_getSession();
        $session->logout()->renewSession();

        if (Mage::getStoreConfigFlag(Ccc_Vendor_Helper_Data::XML_PATH_VENDOR_STARTUP_REDIRECT_TO_DASHBOARD)) {
            $session->setBeforeAuthUrl(Mage::getBaseUrl());
        } else {
            $session->setBeforeAuthUrl($this->_getRefererUrl());
        }
        $this->_redirect('*/*/logoutSuccess');
    }

    /**
     * Logout success page
     */
    public function logoutSuccessAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Vendor register form page
     */
    public function createAction()
    {
        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirect('*/*');
            return;
        }

        $this->loadLayout();
        $this->_initLayoutMessages('vendor/session');
        $this->renderLayout();
    }

    /**
     * Create vendor account action
     */
    public function createPostAction()
    {
        $errUrl = $this->_getUrl('*/*/create', array('_secure' => true));

        if (!$this->_validateFormKey()) {
            $this->_redirectError($errUrl);
            return;
        }

        /* @var $session Mage_Vendor_Model_Session */
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }

        if (!$this->getRequest()->isPost()) {
            $this->_redirectError($errUrl);
            return;
        }

        $vendor = $this->_getVendor();
        $data = $this->getRequest()->getPost();
        try {
            $id = $vendor->loadByEmail($data['email'])->getEntityId();
            if ($id) {
                throw new Mage_Core_Exception("There is already an account with this email address.", 3);
            }
            $vendor->setData($data);
            $errors = $this->_getVendorErrors($vendor);
            if (empty($errors)) {
                $vendor->cleanPasswordsValidationData();
                $vendor->save();
                $this->_dispatchRegisterSuccess($vendor);
                $this->_successProcessRegistration($vendor);
                return;
            } else {
                $this->_addSessionError($errors);
            }
        } catch (Mage_Core_Exception $e) {
            $session->setVendorFormData($this->getRequest()->getPost());
            if ($e->getCode() === Ccc_Vendor_Model_Vendor::EXCEPTION_EMAIL_EXISTS) {
                $url = $this->_getUrl('vendor/account/forgotpassword');
                $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
            } else {
                $message = $this->_escapeHtml($e->getMessage());
            }
            $session->addError($message);
        } catch (Exception $e) {
            $session->setVendorFormData($this->getRequest()->getPost());
            $session->addException($e, $this->__('Cannot save the vendor.'));
        }
        $this->_redirectError($errUrl);
    }

    /**
     * Success Registration
     *
     * @param Ccc_Vendor_Model_Vendor $vendor
     * @return Ccc_Vendor_AccountController
     */
    protected function _successProcessRegistration(Ccc_Vendor_Model_Vendor $vendor)
    {
        $session = $this->_getSession();
        if ($vendor->isConfirmationRequired()) {
            /** @var $app Mage_Core_Model_App */
            $app = $this->_getApp();
            /** @var $store  Mage_Core_Model_Store*/
            $store = $app->getStore();
            $vendor->sendNewAccountEmail(
                'confirmation',
                $session->getBeforeAuthUrl(),
                $store->getId(),
                $this->getRequest()->getPost('password')
            );
            $vendorHelper = $this->_getHelper('vendor');
            $session->addSuccess($this->__(
                'Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.',
                $vendorHelper->getEmailConfirmationUrl($vendor->getEmail())
            ));
            $url = $this->_getUrl('*/*/index', array('_secure' => true));
        } else {
            $session->setVendorAsLoggedIn($vendor);
            $url = $this->_welcomeVendor($vendor);
        }
        $this->_redirectSuccess($url);
        return $this;
    }

    /**
     * Get Vendor Model
     *
     * @return Mage_Vendor_Model_Vendor
     */
    protected function _getVendor()
    {
        $vendor = $this->_getFromRegistry('current_vendor');
        if (!$vendor) {
            $vendor = $this->_getModel('vendor/vendor')->setId(null);
        }
        if ($this->getRequest()->getParam('is_subscribed', false)) {
            $vendor->setIsSubscribed(1);
        }
        /**
         * Initialize vendor group id
         */
        //$vendor->getGroupId();

        return $vendor;
    }

    /**
     * Add session error method
     *
     * @param string|array $errors
     */
    protected function _addSessionError($errors)
    {
        $session = $this->_getSession();
        $session->setVendorFormData($this->getRequest()->getPost());
        if (is_array($errors)) {
            foreach ($errors as $errorMessage) {
                $session->addError($this->_escapeHtml($errorMessage));
            }
        } else {
            $session->addError($this->__('Invalid vendor data'));
        }
    }

    /**
     * Escape message text HTML.
     *
     * @param string $text
     * @return string
     */
    protected function _escapeHtml($text)
    {
        return Mage::helper('core')->escapeHtml($text);
    }

    /**
     * Validate vendor data and return errors if they are
     *
     * @param Ccc_Vendor_Model_Vendor $vendor
     * @return array|string
     */
    protected function _getVendorErrors($vendor)
    {
        $errors = array();
        $request = $this->getRequest();
        // if ($request->getPost('create_address')) {
        //     $errors = $this->_getErrorsOnVendorAddress($vendor);
        // }
        $vendorForm = $this->_getVendorForm($vendor);

        $vendorData = $request->getPost();
        unset($vendorData['success_url']);
        unset($vendorData['error_url']);
        unset($vendorData['form_key']);

        $vendorErrors = $vendorForm->validateData($vendorData);
        if ($vendorErrors !== true) {
            $errors = array_merge($vendorErrors, $errors);
        } else {
            $vendorForm->compactData($vendorData);
            $vendor->setPassword($request->getPost('password'));
            $vendor->setPasswordConfirmation($request->getPost('confirmation'));
            $vendorErrors = $vendor->validate();
            if (is_array($vendorErrors)) {
                $errors = array_merge($vendorErrors, $errors);
            }
        }
        return $errors;
    }

    /**
     * Get Vendor Form Initalized Model
     *
     * @param Mage_Vendor_Model_Vendor $vendor
     * @return Mage_Vendor_Model_Form
     */
    protected function _getVendorForm($vendor)
    {
        /* @var $vendorForm Mage_Vendor_Model_Form */
        $vendorForm = $this->_getModel('vendor/form');
        $vendorForm->setFormCode('vendor_account_create');
        $vendorForm->setEntity($vendor);
        return $vendorForm;
    }

    /**
     * Get Helper
     *
     * @param string $path
     * @return Mage_Core_Helper_Abstract
     */
    protected function _getHelper($path)
    {
        return Mage::helper($path);
    }

    /**
     * Get App
     *
     * @return Mage_Core_Model_App
     */
    protected function _getApp()
    {
        return Mage::app();
    }

    /**
     * Dispatch Event
     *
     * @param Mage_Vendor_Model_Vendor $vendor
     */
    protected function _dispatchRegisterSuccess($vendor)
    {
        Mage::dispatchEvent(
            'vendor_register_success',
            array('account_controller' => $this, 'vendor' => $vendor)
        );
    }

    /**
     * Gets vendor address
     *
     * @param $vendor
     * @return array $errors
     */
    protected function _getErrorsOnVendorAddress($vendor)
    {
        $errors = array();
        /* @var $address Mage_Vendor_Model_Address */
        $address = $this->_getModel('vendor/address');
        /* @var $addressForm Mage_Vendor_Model_Form */
        $addressForm = $this->_getModel('vendor/form');
        $addressForm->setFormCode('vendor_register_address')
            ->setEntity($address);

        $addressData = $addressForm->extractData($this->getRequest(), 'address', false);
        $addressErrors = $addressForm->validateData($addressData);
        if (is_array($addressErrors)) {
            $errors = array_merge($errors, $addressErrors);
        }
        $address->setId(null)
            ->setIsDefaultBilling($this->getRequest()->getParam('default_billing', false))
            ->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false));
        $addressForm->compactData($addressData);
        $vendor->addAddress($address);

        $addressErrors = $address->validate();
        if (is_array($addressErrors)) {
            $errors = array_merge($errors, $addressErrors);
        }
        return $errors;
    }

    /**
     * Get model by path
     *
     * @param string $path
     * @param array|null $arguments
     * @return false|Mage_Core_Model_Abstract
     */
    public function _getModel($path, $arguments = array())
    {
        return Mage::getModel($path, $arguments);
    }

    /**
     * Get model from registry by path
     *
     * @param string $path
     * @return mixed
     */
    protected function _getFromRegistry($path)
    {
        return Mage::registry($path);
    }

    /**
     * Add welcome message and send new account email.
     * Returns success URL
     *
     * @param Mage_Vendor_Model_Vendor $vendor
     * @param bool $isJustConfirmed
     * @return string
     */
    protected function _welcomeVendor(Ccc_Vendor_Model_Vendor $vendor, $isJustConfirmed = false)
    {
        $this->_getSession()->addSuccess(
            $this->__('Thank you for registering with %s.', Mage::app()->getStore()->getFrontendName())
        );
        // if ($this->_isVatValidationEnabled()) {
        //     // Show corresponding VAT message to vendor
        //     // $configAddressType =  $this->_getHelper('vendor/address')->getTaxCalculationAddressType();
        //     // $userPrompt = '';
        //     // switch ($configAddressType) {
        //     //     case Ccc_Vendor_Model_Address_Abstract::TYPE_SHIPPING:
        //     //         $userPrompt = $this->__('If you are a registered VAT vendor, please click <a href="%s">here</a> to enter you shipping address for proper VAT calculation',
        //     //             $this->_getUrl('vendor/address/edit'));
        //     //         break;
        //     //     default:
        //     //         $userPrompt = $this->__('If you are a registered VAT vendor, please click <a href="%s">here</a> to enter you billing address for proper VAT calculation',
        //     //             $this->_getUrl('vendor/address/edit'));
        //     // }
        //     $this->_getSession()->addSuccess($userPrompt);
        // }

        $vendor->sendNewAccountEmail(
            $isJustConfirmed ? 'confirmed' : 'registered',
            '',
            Mage::app()->getStore()->getId(),
            $this->getRequest()->getPost('password')
        );

        $successUrl = $this->_getUrl('*/*/index', array('_secure' => true));
        if ($this->_getSession()->getBeforeAuthUrl()) {
            $successUrl = $this->_getSession()->getBeforeAuthUrl(true);
        }
        return $successUrl;
    }

    /**
     * Confirm vendor account by id and confirmation key
     */
    public function confirmAction()
    {
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
            $this->_getSession()->logout()->regenerateSessionId();
        }
        try {
            $id      = $this->getRequest()->getParam('id', false);
            $key     = $this->getRequest()->getParam('key', false);
            $backUrl = $this->getRequest()->getParam('back_url', false);
            if (empty($id) || empty($key)) {
                throw new Exception($this->__('Bad request.'));
            }

            // load vendor by id (try/catch in case if it throws exceptions)
            try {
                $vendor = $this->_getModel('vendor/vendor')->load($id);
                if ((!$vendor) || (!$vendor->getId())) {
                    throw new Exception('Failed to load vendor by id.');
                }
            } catch (Exception $e) {
                throw new Exception($this->__('Wrong vendor account specified.'));
            }

            // check if it is inactive
            if ($vendor->getConfirmation()) {
                if ($vendor->getConfirmation() !== $key) {
                    throw new Exception($this->__('Wrong confirmation key.'));
                }

                // activate vendor
                try {
                    $vendor->setConfirmation(null);
                    $vendor->save();
                } catch (Exception $e) {
                    throw new Exception($this->__('Failed to confirm vendor account.'));
                }

                // log in and send greeting email, then die happy
                $session->setVendorAsLoggedIn($vendor);
                $successUrl = $this->_welcomeVendor($vendor, true);
                $this->_redirectSuccess($backUrl ? $backUrl : $successUrl);
                return;
            }

            // die happy
            $this->_redirectSuccess($this->_getUrl('*/*/index', array('_secure' => true)));
            return;
        } catch (Exception $e) {
            // die unhappy
            $this->_getSession()->addError($e->getMessage());
            $this->_redirectError($this->_getUrl('*/*/index', array('_secure' => true)));
            return;
        }
    }

    /**
     * Send confirmation link to specified email
     */
    public function confirmationAction()
    {
        $vendor = $this->_getModel('vendor/vendor');
        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }

        // try to confirm by email
        $email = $this->getRequest()->getPost('email');
        if ($email) {
            try {
                $vendor->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email);
                if (!$vendor->getId()) {
                    throw new Exception('');
                }
                if ($vendor->getConfirmation()) {
                    $vendor->sendNewAccountEmail('confirmation', '', Mage::app()->getStore()->getId());
                    $this->_getSession()->addSuccess($this->__('Please, check your email for confirmation key.'));
                } else {
                    $this->_getSession()->addSuccess($this->__('This email does not require confirmation.'));
                }
                $this->_getSession()->setUsername($email);
                $this->_redirectSuccess($this->_getUrl('*/*/index', array('_secure' => true)));
            } catch (Exception $e) {
                $this->_getSession()->addException($e, $this->__('Wrong email.'));
                $this->_redirectError($this->_getUrl('*/*/*', array('email' => $email, '_secure' => true)));
            }
            return;
        }

        // output form
        $this->loadLayout();

        $this->getLayout()->getBlock('accountConfirmation')
            ->setEmail($this->getRequest()->getParam('email', $email));

        $this->_initLayoutMessages('vendor/session');
        $this->renderLayout();
    }

    /**
     * Get Url method
     *
     * @param string $url
     * @param array $params
     * @return string
     */
    protected function _getUrl($url, $params = array())
    {
        return Mage::getUrl($url, $params);
    }

    /**
     * Forgot vendor password page
     */
    public function forgotPasswordAction()
    {
        $this->loadLayout();

        $this->getLayout()->getBlock('forgotPassword')->setEmailValue(
            $this->_getSession()->getForgottenEmail()
        );
        $this->_getSession()->unsForgottenEmail();

        $this->_initLayoutMessages('vendor/session');
        $this->renderLayout();
    }

    /**
     * Forgot vendor password action
     */
    public function forgotPasswordPostAction()
    {
        $email = (string) $this->getRequest()->getPost('email');
        if ($email) {
            /**
             * @var $flowPassword Mage_Vendor_Model_Flowpassword
             */
            $flowPassword = $this->_getModel('vendor/flowpassword');
            $flowPassword->setEmail($email)->save();

            if (!$flowPassword->checkVendorForgotPasswordFlowEmail($email)) {
                $this->_getSession()
                    ->addError($this->__('You have exceeded requests to times per 24 hours from 1 e-mail.'));
                $this->_redirect('*/*/forgotpassword');
                return;
            }

            if (!$flowPassword->checkVendorForgotPasswordFlowIp()) {
                $this->_getSession()->addError($this->__('You have exceeded requests to times per hour from 1 IP.'));
                $this->_redirect('*/*/forgotpassword');
                return;
            }

            if (!Zend_Validate::is($email, 'EmailAddress')) {
                $this->_getSession()->setForgottenEmail($email);
                $this->_getSession()->addError($this->__('Invalid email address.'));
                $this->_redirect('*/*/forgotpassword');
                return;
            }

            /** @var $vendor Mage_Vendor_Model_Vendor */
            $vendor = $this->_getModel('vendor/vendor')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);

            if ($vendor->getId()) {
                try {
                    $newResetPasswordLinkToken =  $this->_getHelper('vendor')->generateResetPasswordLinkToken();
                    $vendor->changeResetPasswordLinkToken($newResetPasswordLinkToken);
                    $vendor->sendPasswordResetConfirmationEmail();
                } catch (Exception $exception) {
                    $this->_getSession()->addError($exception->getMessage());
                    $this->_redirect('*/*/forgotpassword');
                    return;
                }
            }
            $this->_getSession()
                ->addSuccess($this->_getHelper('vendor')
                    ->__(
                        'If there is an account associated with %s you will receive an email with a link to reset your password.',
                        $this->_getHelper('vendor')->escapeHtml($email)
                    ));
            $this->_redirect('*/*/');
            return;
        } else {
            $this->_getSession()->addError($this->__('Please enter your email.'));
            $this->_redirect('*/*/forgotpassword');
            return;
        }
    }

    /**
     * Display reset forgotten password form
     *
     */
    public function changeForgottenAction()
    {
        try {
            list($vendorId, $resetPasswordLinkToken) = $this->_getRestorePasswordParameters($this->_getSession());
            $this->_validateResetPasswordLinkToken($vendorId, $resetPasswordLinkToken);
            $this->loadLayout();
            $this->renderLayout();
        } catch (Exception $exception) {
            $this->_getSession()->addError($this->_getHelper('vendor')->__('Your password reset link has expired.'));
            $this->_redirect('*/*/forgotpassword');
        }
    }

    /**
     * Checks reset forgotten password token
     *
     * User is redirected on this action when he clicks on the corresponding link in password reset confirmation email.
     *
     */
    public function resetPasswordAction()
    {
        try {
            $vendorId = (int)$this->getRequest()->getQuery("id");
            $resetPasswordLinkToken = (string)$this->getRequest()->getQuery('token');

            $this->_validateResetPasswordLinkToken($vendorId, $resetPasswordLinkToken);
            $this->_saveRestorePasswordParameters($vendorId, $resetPasswordLinkToken)
                ->_redirect('*/*/changeforgotten');
        } catch (Exception $exception) {
            $this->_getSession()->addError($this->_getHelper('vendor')->__('Your password reset link has expired.'));
            $this->_redirect('*/*/forgotpassword');
        }
    }

    /**
     * Reset forgotten password
     * Used to handle data recieved from reset forgotten password form
     */
    public function resetPasswordPostAction()
    {
        list($vendorId, $resetPasswordLinkToken) = $this->_getRestorePasswordParameters($this->_getSession());
        $password = (string)$this->getRequest()->getPost('password');
        $passwordConfirmation = (string)$this->getRequest()->getPost('confirmation');

        try {
            $this->_validateResetPasswordLinkToken($vendorId, $resetPasswordLinkToken);
        } catch (Exception $exception) {
            $this->_getSession()->addError($this->_getHelper('vendor')->__('Your password reset link has expired.'));
            $this->_redirect('*/*/');
            return;
        }

        $errorMessages = array();
        if (iconv_strlen($password) <= 0) {
            array_push($errorMessages, $this->_getHelper('vendor')->__('New password field cannot be empty.'));
        }
        /** @var $vendor Mage_Vendor_Model_Vendor */
        $vendor = $this->_getModel('vendor/vendor')->load($vendorId);

        $vendor->setPassword($password);
        $vendor->setPasswordConfirmation($passwordConfirmation);
        $validationErrorMessages = $vendor->validateResetPassword();
        if (is_array($validationErrorMessages)) {
            $errorMessages = array_merge($errorMessages, $validationErrorMessages);
        }

        if (!empty($errorMessages)) {
            $this->_getSession()->setVendorFormData($this->getRequest()->getPost());
            foreach ($errorMessages as $errorMessage) {
                $this->_getSession()->addError($errorMessage);
            }
            $this->_redirect('*/*/changeforgotten');
            return;
        }

        try {
            // Empty current reset password token i.e. invalidate it
            $vendor->setRpToken(null);
            $vendor->setRpTokenCreatedAt(null);
            $vendor->cleanPasswordsValidationData();
            $vendor->save();

            $this->_getSession()->unsetData(self::TOKEN_SESSION_NAME);
            $this->_getSession()->unsetData(self::VENDOR_ID_SESSION_NAME);

            $this->_getSession()->addSuccess($this->_getHelper('vendor')->__('Your password has been updated.'));
            $this->_redirect('*/*/login');
        } catch (Exception $exception) {
            $this->_getSession()->addException($exception, $this->__('Cannot save a new password.'));
            $this->_redirect('*/*/changeforgotten');
            return;
        }
    }

    /**
     * Check if password reset token is valid
     *
     * @param int $vendorId
     * @param string $resetPasswordLinkToken
     * @throws Mage_Core_Exception
     */
    protected function _validateResetPasswordLinkToken($vendorId, $resetPasswordLinkToken)
    {
        if (
            !is_int($vendorId)
            || !is_string($resetPasswordLinkToken)
            || empty($resetPasswordLinkToken)
            || empty($vendorId)
            || $vendorId < 0
        ) {
            throw Mage::exception('Mage_Core', $this->_getHelper('vendor')->__('Invalid password reset token.'));
        }

        /** @var $vendor Mage_Vendor_Model_Vendor */
        $vendor = $this->_getModel('vendor/vendor')->load($vendorId);
        if (!$vendor || !$vendor->getId()) {
            throw Mage::exception('Mage_Core', $this->_getHelper('vendor')->__('Wrong vendor account specified.'));
        }

        $vendorToken = $vendor->getRpToken();
        if (strcmp($vendorToken, $resetPasswordLinkToken) != 0 || $vendor->isResetPasswordLinkTokenExpired()) {
            throw Mage::exception('Mage_Core', $this->_getHelper('vendor')->__('Your password reset link has expired.'));
        }
    }

    /**
     * Forgot vendor account information page
     */
    public function editAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('vendor/session');
        // $this->_initLayoutMessages('catalog/session');

        $block = $this->getLayout()->getBlock('vendor_edit');
        if ($block) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $data = $this->_getSession()->getVendorFormData(true);
        $vendor = $this->_getSession()->getVendor();
        if (!empty($data)) {
            $vendor->addData($data);
        }
        if ($this->getRequest()->getParam('changepass') == 1) {
            $vendor->setChangePassword(1);
        }

        $this->getLayout()->getBlock('head')->setTitle($this->__('Account Information'));
        $this->getLayout()->getBlock('messages')->setEscapeMessageFlag(true);
        $this->renderLayout();
    }

    /**
     * Change vendor password action
     */
    public function editPostAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/edit');
        }

        if ($this->getRequest()->isPost()) {
            /** @var $vendor Mage_Vendor_Model_Vendor */
            $vendor = $this->_getSession()->getVendor();
            $vendor->setOldEmail($vendor->getEmail());
            /** @var $vendorForm Mage_Vendor_Model_Form */
            $vendorForm = $this->_getModel('vendor/form');
            $vendorForm->setFormCode('vendor_account_edit')
                ->setEntity($vendor);

            $vendorData = $this->getRequest()->getPost();

            // echo "<pre>";
            // print_r($vendorData);
            // die;
            $errors = array();
            $vendorErrors = $vendorForm->validateData($vendorData);
            if ($vendorErrors !== true) {
                $errors = array_merge($vendorErrors, $errors);
            } else {
                $vendorForm->compactData($vendorData);
                $errors = array();

                if (!$vendor->validatePassword($this->getRequest()->getPost('current_password'))) {
                    $errors[] = $this->__('Invalid current password');
                }

                // If email change was requested then set flag
                $isChangeEmail = ($vendor->getOldEmail() != $vendor->getEmail()) ? true : false;
                $vendor->setIsChangeEmail($isChangeEmail);

                // If password change was requested then add it to common validation scheme
                $vendor->setIsChangePassword($this->getRequest()->getParam('change_password'));

                if ($vendor->getIsChangePassword()) {
                    $newPass    = $this->getRequest()->getPost('password');
                    $confPass   = $this->getRequest()->getPost('confirmation');

                    if (strlen($newPass)) {
                        /**
                         * Set entered password and its confirmation - they
                         * will be validated later to match each other and be of right length
                         */
                        $vendor->setPassword($newPass);
                        $vendor->setPasswordConfirmation($confPass);
                    } else {
                        $errors[] = $this->__('New password field cannot be empty.');
                    }
                }

                // Validate account and compose list of errors if any
                $vendorErrors = $vendor->validate();
                if (is_array($vendorErrors)) {
                    $errors = array_merge($errors, $vendorErrors);
                }
            }

            if (!empty($errors)) {
                $this->_getSession()->setVendorFormData($this->getRequest()->getPost());
                foreach ($errors as $message) {
                    $this->_getSession()->addError($message);
                }
                $this->_redirect('*/*/edit');
                return $this;
            }

            try {
                $vendor->cleanPasswordsValidationData();

                // Reset all password reset tokens if all data was sufficient and correct on email change
                if ($vendor->getIsChangeEmail()) {
                    $vendor->setRpToken(null);
                    $vendor->setRpTokenCreatedAt(null);
                }
                $data = $this->getRequest()->getPost();

                $vendor->setfirstname($data['firstname']);
                $vendor->setmiddlename($data['middlename']);
                $vendor->setlastname($data['lastname']);
                $vendor->save();

                $this->_getSession()->setVendor($vendor)
                    ->addSuccess($this->__('The account information has been saved.'));

                if ($vendor->getIsChangeEmail() || $vendor->getIsChangePassword()) {
                    $vendor->sendChangedPasswordOrEmail();
                }

                $this->_redirect('vendor/account');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->setVendorFormData($this->getRequest()->getPost())
                    ->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->setVendorFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save the vendor.'));
            }
        }

        $this->_redirect('*/*/edit');
    }

    /**
     * Filtering posted data. Converting localized data if needed
     *
     * @param array
     * @return array
     */
    protected function _filterPostData($data)
    {
        $data = $this->_filterDates($data, array('dob'));
        return $data;
    }

    /**
     * Check whether VAT ID validation is enabled
     *
     * @param Mage_Core_Model_Store|string|int $store
     * @return bool
     */
    protected function _isVatValidationEnabled($store = null)
    {
        return  $this->_getHelper('vendor/address')->isVatValidationEnabled($store);
    }

    /**
     * Get restore password params.
     *
     * @param Mage_Vendor_Model_Session $session
     * @return array array ($vendorId, $resetPasswordToken)
     */
    protected function _getRestorePasswordParameters(Ccc_Vendor_Model_Session $session)
    {
        return array(
            (int) $session->getData(self::VENDOR_ID_SESSION_NAME),
            (string) $session->getData(self::TOKEN_SESSION_NAME)
        );
    }

    /**
     * Save restore password params to session.
     *
     * @param int $vendorId
     * @param  string $resetPasswordLinkToken
     * @return $this
     */
    protected function _saveRestorePasswordParameters($vendorId, $resetPasswordLinkToken)
    {
        $this->_getSession()
            ->setData(self::VENDOR_ID_SESSION_NAME, $vendorId)
            ->setData(self::TOKEN_SESSION_NAME, $resetPasswordLinkToken);

        return $this;
    }
}