<?php

namespace Seasia\Customloginredirect\Controller\Account;

use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Data\Form\FormKey\Validator;
/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoginPost extends \Magento\Customer\Controller\Account\LoginPost {

    public function execute() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $custom_helper = $objectManager->create('Seasia\Customapi\Helper\Data');
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $baseUrl = $storeManager->getStore()->getBaseUrl();


        $counter = $objectManager->create('\Magento\Checkout\Helper\Cart'); 
        $itemCount = $counter->getItemsCount();
        if($itemCount == 0){
                //$reactUrl = $custom_helper->getReactUrl()."profile";
                $reactUrl = $custom_helper->getReactUrl();
            }else{
                $reactUrl = $baseUrl."checkout/cart";
            }

        
        if ($this->session->isLoggedIn() || !$this->formKeyValidator->validate($this->getRequest())) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();

            

            $resultRedirect->setPath($reactUrl);
            return $resultRedirect;
        }

        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);
                    $this->session->setCustomerDataAsLoggedIn($customer);
                    $this->session->regenerateId();
                } catch (EmailNotConfirmedException $e) {
                    $value = $this->customerUrl->getEmailConfirmationUrl($login['username']);
                    $message = __(
                        'This account is not confirmed.' .
                        ' <a href="%1">Click here</a> to resend confirmation email.', $value
                    );
                    $this->messageManager->addError($message);
                    $this->session->setUsername($login['username']);
                    return $this->accountRedirect->getRedirect();
                } catch (AuthenticationException $e) {
                    $message = __('Invalid login or password.');
                    $this->messageManager->addError($message);
                    $this->session->setUsername($login['username']);
                    return $this->accountRedirect->getRedirect();
                } catch (\Exception $e) {
                    $this->messageManager->addError(__('Invalid login or password.'));
                    return $this->accountRedirect->getRedirect();
                }
            } else {
                $this->messageManager->addError(__('A login and a password are required.'));
            }
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($reactUrl);
        return $resultRedirect;
    }

}