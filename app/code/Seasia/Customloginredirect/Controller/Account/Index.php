<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Seasia\Customloginredirect\Controller\Account;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Customer\Controller\Account\Index
{

	public function execute()
	{

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
		$resultRedirect = $this->resultRedirectFactory->create();
		$resultRedirect->setPath($reactUrl);
		return $resultRedirect;
	}
}
