<?php

namespace Seasia\Customapi\Controller\Index;

class Updatecolor extends \Magento\Framework\App\Action\Action
{
	public function execute()
	{
		try {

			

			$main  = array();
			$main['White'] = array();
			$main['White']['name'] = "White";
			$main['White']['id'] = "650";
			$main['White']['colorcode'] = "FFFFFF";
			$main['White']['children'] = array(
				array(
					'name' => "White",
					'id' => "664",
					'colorcode' => "FFFFFF"
				),
				array(
					'name' => "Off-White",
					'id' => "665",
					'colorcode' => "FFFAF1"
				),
				array(
					'name' => "Ivory",
					'id' => "666",
					'colorcode' => "FFFFF0"
				),

			);

			$main['Yellow'] = array();
			$main['Yellow']['name'] = "Yellow";
			$main['Yellow']['id'] = "651";
			$main['Yellow']['colorcode'] = "EBE23C";

			$main['Yellow']['children'] = array(
				array(
					'name' => "Lemon",
					'id' => "667",
					'colorcode' => "fff44f"
				),
				array(
					'name' => "Yellow",
					'id' => "668",
					'colorcode' => "FFFF00"
				),
				array(
					'name' => "Neon Yellow",
					'id' => "669",
					'colorcode' => "E7FF00"
				),
				array(
					'name' => "Mustard",
					'id' => "670",
					'colorcode' => "ffdb58"
				),
				array(
					'name' => "Gold",
					'id' => "671",
					'colorcode' => "F2B71F"
				),

			);
			$main['Orange'] = array();
			$main['Orange']['name'] = "Orange";
			$main['Orange']['id'] = "652";
			$main['Orange']['colorcode'] = "F07954";
			$main['Orange']['children'] = array(
				array(
					'name' => "Peach",
					'id' => "672",
					'colorcode' => "EDA863"
				),
				array(
					'name' => "Orange",
					'id' => "673",
					'colorcode' => "FFA500"
				),
				array(
					'name' => "Neon Orange",
					'id' => "674",
					'colorcode' => "FF7300"
				),
				array(
					'name' => "Rust",
					'id' => "675",
					'colorcode' => "E5761E"
				),
				array(
					'name' => "Copper",
					'id' => "676",
					'colorcode' => "B87333"
				),


			);
			$main['Red'] = array();
			$main['Red']['name'] = "Red";
			$main['Red']['id'] = "653";
			$main['Red']['colorcode'] = "C60E21";
			$main['Red']['children'] = array(
				array(
					'name' => "Red",
					'id' => "677",
					'colorcode' => "C60E21"
				),
				array(
					'name' => "Brick",
					'id' => "678",
					'colorcode' => "C62D42"
				),
				array(
					'name' => "Burgandy",
					'id' => "679",
					'colorcode' => "800020"
				),

			);

			$main['Pink'] = array();
			$main['Pink']['name'] = "Pink";
			$main['Pink']['id'] = "654";
			$main['Pink']['colorcode'] = "FFC0CB";

			$main['Pink']['children'] = array(
				array(
					'name' => "Light Pink",
					'id' => "680",
					'colorcode' => "FFC0CB"
				),
				array(
					'name' => "Coral",
					'id' => "681",
					'colorcode' => "F88379"
				),
				array(
					'name' => "Pink",
					'id' => "682",
					'colorcode' => "FFB6C1"
				),
				array(
					'name' => "Hot Pink",
					'id' => "683",
					'colorcode' => "FF69B4"
				),
				array(
					'name' => "Neon Pink",
					'id' => "684",
					'colorcode' => "FF00FF"
				),
				array(
					'name' => "Fuschia",
					'id' => "685",
					'colorcode' => "ca2c92"
				),

			);
			$main['Purple'] = array();
			$main['Purple']['name'] = "Purple";
			$main['Purple']['id'] = "655";
			$main['Purple']['colorcode'] = "800080";

			$main['Purple']['children'] = array(
				array(
					'name' => "Lavender",
					'id' => "694",
					'colorcode' => "967BB6"
				),
				array(
					'name' => "Plum",
					'id' => "695",
					'colorcode' => "8E4585"
				),
				array(
					'name' => "Purple",
					'id' => "696",
					'colorcode' => "800080"
				),
				array(
					'name' => "Grape",
					'id' => "697",
					'colorcode' => "6F2DA8"
				),

			);

			$main['Blue'] = array();
			$main['Blue']['name'] = "Blue";
			$main['Blue']['id'] = "656";
			$main['Blue']['colorcode'] = "0000FF";

			$main['Blue']['children'] = array(
				array(
					'name' => "Baby Blue",
					'id' => "686",
					'colorcode' => "89CFF0"
				),
				array(
					'name' => "Turquois",
					'id' => "687",
					'colorcode' => "40E0D0"
				),
				array(
					'name' => "Teal",
					'id' => "688",
					'colorcode' => "008080"
				),
				array(
					'name' => "Blue",
					'id' => "689",
					'colorcode' => "0000FF"
				),
				array(
					'name' => "Periwinkle",
					'id' => "690",
					'colorcode' => "CCCCFF"
				),
				array(
					'name' => "Royal Blue",
					'id' => "691",
					'colorcode' => "080099"
				),
				array(
					'name' => "Neon Blue",
					'id' => "692",
					'colorcode' => "5FE8FF"
				),
				array(
					'name' => "Navy Blue",
					'id' => "693",
					'colorcode' => "000080"
				),

			);

			$main['Green'] = array();
			$main['Green']['name'] = "Green";
			$main['Green']['id'] = "657";
			$main['Green']['colorcode'] = "008000";

			$main['Green']['children'] = array(
				array(
					'name' => "Mint",
					'id' => "698",
					'colorcode' => "98FF98"
				),
				array(
					'name' => "Aqua",
					'id' => "699",
					'colorcode' => "00FFFF"
				),
				array(
					'name' => "Lime",
					'id' => "700",
					'colorcode' => "00FF00"
				),
				array(
					'name' => "Green",
					'id' => "701",
					'colorcode' => "008000"
				),
				array(
					'name' => "Neon Green",
					'id' => "702",
					'colorcode' => "64FF00"
				),
				array(
					'name' => "Emerald",
					'id' => "703",
					'colorcode' => "046307"
				),
				array(
					'name' => "Olive",
					'id' => "704",
					'colorcode' => "BAB86C"
				),


			);

			$main['Brown'] = array();
			$main['Brown']['name'] = "Brown";
			$main['Brown']['id'] = "658";
			$main['Brown']['colorcode'] = "A52A2A";

			$main['Brown']['children'] = array(
				array(
					'name' => "Natural",
					'id' => "704",
					'colorcode' => "E3C688"
				),
				array(
					'name' => "Beige",
					'id' => "705",
					'colorcode' => "F5F5DC"
				),
				array(
					'name' => "Tan",
					'id' => "706",
					'colorcode' => "D2B48C"
				),
				array(
					'name' => "Brown",
					'id' => "707",
					'colorcode' => "654321"
				),
				array(
					'name' => "Mocha",
					'id' => "708",
					'colorcode' => "A38068"
				),

			);

			$main['Gray'] = array();
			$main['Gray']['name'] = "Gray";
			$main['Gray']['id'] = "659";
			$main['Gray']['colorcode'] = "808080";


			$main['Gray']['children'] = array(
				array(
					'name' => "Light Gray",
					'id' => "710",
					'colorcode' => "D3D3D3"
				),
				array(
					'name' => "Silver",
					'id' => "711",
					'colorcode' => "C0C0C0"
				),
				array(
					'name' => "Gray",
					'id' => "712",
					'colorcode' => "808080"
				),
				array(
					'name' => "Charcoal",
					'id' => "713",
					'colorcode' => "464646"
				),

			);

			$main['Black'] = array();
			$main['Black']['name'] = "Black";
			$main['Black']['id'] = "660";
			$main['Black']['colorcode'] = "000000";
			$main['Black']['children'] = array(
				array(
					'name' => "Black",
					'id' => "714",
					'colorcode' => "000000"
				)

			);

			$main['Black & White'] = array();
			$main['Black & White']['name'] = "Black & White";
			$main['Black & White']['id'] = "661";
			$main['Black & White']['colorcode'] = "000000";
			//$main['Black & White']['imageUrl'] = $mediaUrl."color_obj/bw.png";
			$main['Black & White']['children'] = array(
				array(
					'name' => "Black & White",
					'id' => "715",
					'colorcode' => "000000",
					//'imageUrl' => $mediaUrl."color_obj/bw_r.png"
				)

			);

			$main['MultiColor'] = array();
			$main['MultiColor']['name'] = "MultiColor";
			$main['MultiColor']['id'] = "662";
			$main['MultiColor']['colorcode'] = "000000";
			//$main['MultiColor']['imageUrl'] = $mediaUrl."color_obj/multi.png";
			$main['MultiColor']['children'] = array(
				array(
					'name' => "MultiColor",
					'id' => "716",
					'colorcode' => "000000",
					//'imageUrl' => $mediaUrl."color_obj/multi_r.png"
				)

			);
			//echo "<pre>"; print_r($main);


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

			$eavConfig = $objectManager->get('\Magento\Eav\Model\Config');
			$attribute = $eavConfig->getAttribute('catalog_product', 'color');
			$options = $attribute->getSource()->getAllOptions();

			$prevColor = [];

			$newColor = [];


			$newattribute = $eavConfig->getAttribute('catalog_product', 'color_type_subcolor');
			$newoptions = $newattribute->getSource()->getAllOptions();

			foreach($options as $prevOption){
				if($prevOption['value'] != "")
					$prevColor[$prevOption['value']] =  $prevOption['label'];
			}

			foreach($newoptions as $newOption){
				if($newOption['value'] != "")
					$newColor[$newOption['value']] =  $newOption['label'];
			}

			// echo "<pre>"; print_r($prevColor);

			// echo "<pre>"; print_r($newColor);


			// die("Heeeeeeeeeeee");

			$invalidColors = array(
				"132" => "689",
				"133" => "699",
				"341" => "687",
				"135" => "701",
				"136" => "700",
				"138" => "682",
				"140" => "685",
				"143" => "679",
			);

			$ignoreProductIds = array('694');
			//echo count($response);
			//echo "<pre>"; print_r($response);
			//die("DDDDDDDDDDDDD");
			foreach($response as $item){

				$product = $objectManager->get('Magento\Catalog\Model\Product')->load($item['mageproduct_id']);

				


				if($product->getColor() ){

					echo "ProductId-->".$item['mageproduct_id']."--->".$product->getColor()."--->".$product->getColorTypeSubcolor();
					echo "<br>";
					
					$selectedColor = $product->getColor();
					if($selectedColor != ""){
						if(array_key_exists($selectedColor, $invalidColors)){
							$newKey = $invalidColors[$selectedColor];
						}else{
							$prevName = $prevColor[$selectedColor];
							$newKey = array_search($prevName,$newColor);
						}


						if($newKey != ""){
							$product->setColorTypeSubcolor($newKey);
							foreach($main as $mainCol){
								foreach($mainCol['children'] as $childCol){
									if($childCol['id'] == $newKey){
										$product->setColorType($mainCol['id']);
									}
								}
							}
							$product->save();
						}

					}
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