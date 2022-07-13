<?php

namespace Seasia\Customapi\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
	public function execute()
	{
		try {
			$id =  $this->getRequest()->getParam('id');
			$item_id =  $this->getRequest()->getParam('item_id');
			$actual_item_id  =  $this->getRequest()->getParam('actual_item_id');

			$objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
			$collection = $objectManager->create('Seasia\Customnotifications\Model\Notifications');

			$collection = $collection->getCollection()->addFieldToFilter('entity_id',$id)->addFieldToFilter('status','unread');
			$notifyItem = $collection->getFirstItem();
			$notifyItem->setStatus('read');
			$notifyItem->save();

			$product = $objectManager->create('Magento\Catalog\Model\Product')->load($actual_item_id);                     

			$this->_redirect($product->getProductUrl());


		} catch(\Exception $e) {
			return $e->getMessage();
		}
	}
}