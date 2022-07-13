<?php

namespace Seasia\Customapi\Controller\Index;

class Setoldproduct extends \Magento\Framework\App\Action\Action
{
	public function execute()
	{
		
		try {
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			/** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
			$productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
			/** Apply filters here */
			$collection = $productCollection->addAttributeToSelect('*');

			$genders = array('Womens','Mens','Kids','Unisex','Equiptment');
			foreach($genders as $eachGender){
				$attributeArray[$eachGender] = array();
			}
			$attributeArray['Womens'] = array('Girls Size' => 'girls_size','Ladies Size' => 'ladies_size');
			$attributeArray['Mens'] = array('Mens Size'=> 'mens_size','Boys Size' => 'boys_size');
			$attributeArray['Kids'] = array('Age Worn'=> 'age');
			$attributeArray['Unisex'] = array();
			$attributeArray['Equiptment'] = array('Kids Shoe Size'=> 'kids_show_size', 'Mens Shoe Size'=> 'mens_show_size', 'Womens Shoe Size'=> 'womens_show_size');

			$collection->setPageSize($_GET['length'])->setCurPage($_GET['page']);
                        //$collection->setPageSize(1);
			$attrArray = array(
				'Girls Size' => 'girls_size',
				'Ladies Size' => 'ladies_size',
				'Mens Size'=> 'mens_size',
				'Boys Size' => 'boys_size',
				'Age Worn'=> 'age',
				'Kids Shoe Size'=> 'kids_show_size',
				'Mens Shoe Size'=> 'mens_show_size',
				'Womens Shoe Size'=> 'womens_show_size'

			);
			foreach ($collection as $product){
				

				foreach ($attrArray as $key => $value) {
					$varValue =  $product->getData($value);
					if(isset($varValue) && $varValue != "" ){
						// echo $product->getHiddenSizeType();
						// echo "<br>";
						//if($product->getId() == 1133){
							$product->setHiddenSizeType($value);
							$frock_type =  $key;
							foreach ($attributeArray as $attr => $attrvalue) {
								foreach ($attrvalue as $attrKey => $Val) {
									if($attrKey == $key){
										$sizeId = $this->getOptionText($product,"frock_size_type", $attr);
										$genderId = $this->getOptionText($product,"frock_gender", $attr);
										$product->setFrockSizeType($sizeId);
										$product->setFrockGender($genderId);
									}
								}
								
							}
							//$product->save();
							
						}
						

					//}
				}



			}  


			die("Done");
		} catch(\Exception $e) {
			return $e->getMessage();
		}

	}

	private function getOptionText($product, $attributeCode, $label){
		$isAttributeExist = $product->getResource()->getAttribute($attributeCode);
		$optionId = '';
		if ($isAttributeExist && $isAttributeExist->usesSource()) {
			$optionId = $isAttributeExist->getSource()->getOptionId($label);
		}
		return $optionId;
		
	}
}