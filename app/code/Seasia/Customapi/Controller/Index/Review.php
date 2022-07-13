<?php

namespace Seasia\Customapi\Controller\Index;

class Review extends \Magento\Framework\App\Action\Action
{
	public function execute()
	{
		try {
			$id  				=  $this->getRequest()->getParam('id');
	        $item_id  			=  $this->getRequest()->getParam('item_id');
	        
			$objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
			$collection = $objectManager->create('Seasia\Customnotifications\Model\Notifications');
				
			$collection = $collection->getCollection()->addFieldToFilter('entity_id',$id)->addFieldToFilter('status','unread');
			$notifyItem = $collection->getFirstItem();

			$notifyItem->setStatus('read');
			$notifyItem->save();
   
			$sellerId = $notifyItem->getNotificationTo();
			$marketplaceHelper = $objectManager->create('Webkul\Marketplace\Helper\Data');
			$partner  = $marketplaceHelper->getSellerDataBySellerId($sellerId)->getFirstItem();
			$shop_url = $marketplaceHelper ->getRewriteUrl(
			                        'marketplace/seller/collection/shop/'.
			                        $partner->getShopUrl()
			                    );
			$shop_url = $shop_url.'#agorae_mp_review';
  
			$this->_redirect($shop_url);
		} catch(\Exception $e) {
			return $e->getMessage();
		}
	}
}