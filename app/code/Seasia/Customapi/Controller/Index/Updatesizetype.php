<?php

namespace Seasia\Customapi\Controller\Index;

class Updatesizetype extends \Magento\Framework\App\Action\Action
{
	public function execute()
	{
		try {

			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			/** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
			$productCollection = $objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
			/** Apply filters here */
			//$collection = $productCollection->create()->getCollection();
			//$collection = $productCollection->addAttributeToSelect('*');

			$_mpproduct = $this->_objectManager->get('\Webkul\Marketplace\Model\ProductFactory');
			$collection=$_mpproduct->create()->getCollection();


			$collection->setPageSize($_GET['length'])->setCurPage($_GET['page']);

			$response = $collection->getData();
   
 
			foreach($response as $item){

				$product = $objectManager->get('Magento\Catalog\Model\Product')->load($item['mageproduct_id']);
				if($product->getId() && ($product->getHiddenSizeType() == "girls_size" || $product->getHiddenSizeType() == "boys_size") && $product->getFrockGender() != "637"){
					 $product->setFrockGender('637');
					 $product->save();
					 echo "ProductId-->".$item['mageproduct_id']."--->". $product->getFrockSizeType();
					echo "<br>";
					 
				}
			}

			die("Updated products");



			// foreach($response as $item){
			// 	if($item['entity_id'] == 1299){
			// 		$product = $objectManager->get('Magento\Catalog\Model\Product')->load($item['entity_id']);
			// 		echo "<pre>"; print_r($product->getData());
			// 	}

			// }

			//echo "<pre>"; print_r($prevColor);

			//echo "<pre>"; print_r($newColor);

			// foreach($response as $item){
			// 	$product = $objectManager->get('Magento\Catalog\Model\Product')->load($item['entity_id']);
			// }

			// $response = $collection->getData();



			die("DDDDDDDDDd");
			//$this->_redirect($shop_url);
		} catch(\Exception $e) {
			echo $e->getMessage();
		}
	}
}