<?php

namespace Seasia\Customapi\Controller\Index;

class Updateproductsize extends \Magento\Framework\App\Action\Action
{
        public function execute()
        {
                try {
                        //$collection = $this->_productCollectionFactory->create();
                        //$collection->addAttributeToSelect('*');
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
                        $productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
                        /** Apply filters here */
                        $collection = $productCollection->addAttributeToSelect('*');



                        $collection->setPageSize($_GET['length'])->setCurPage($_GET['page']);
                        //$collection->setPageSize(1);
                        $attributeArray['Womens'] = array('Girls Size' => 'girls_size','Ladies Size' => 'ladies_size');
                        $attributeArray['Mens'] = array('Mens Size'=> 'mens_size','Boys Size' => 'boys_size');
                        //$attributeArray['Kids'] = array('Age Worn'=> 'age');
                        $attributeArray['Unisex'] = array();
                        $attributeArray['Equiptment'] = array('Kids Shoe Size'=> 'kids_show_size', 'Mens Shoe Size'=> 'mens_show_size', 'Womens Shoe Size'=> 'womens_show_size');

                        echo "TotalCount--->".$collection->getSize();

                        $frockGender = array("Womens" => '635',"Mens" => '636',"Kids" => '637',"Unisex" => '638',"Equipment" => '639');

                        $frockSizeType = array("Womens" => '640',"Mens" => '641',"Kids" => '642',"Unisex" => '643',"Equipment" => '644');

                        $sizeCount = 0;
                        $ageCount = 0;
                        foreach ($collection as $product){
                                $hasSize = false;
                                $saveStr = "";
                                
                                foreach ($attributeArray as $key => $value) {
                                        //echo $key."<br>";
                                        //echo "<pre>"; print_r($value);
                                        $sizeNotSet = true;
                                        foreach($value as $sizeAttr){
                                                $varValue =  $product->getData($sizeAttr);
                                                if(isset($varValue) && $varValue != ""){
                                                        echo "Product Id->".$product->getId()."-->";
                                                        echo $sizeAttr."--->";
                                                        echo $varValue."<br>";
                                                        $key;
                                                        echo $frockSizeType[$key];

                                                        if($sizeNotSet){
                                                                $product->setHiddenSizeType($sizeAttr);

                                                                $product->setFrockGender($frockGender[$key]);


                                                                $product->setFrockSizeType($frockSizeType[$key]);
                                                                
                                                                $product->setMetaTitle($product->getName());
                                                                $product->save();
                                                                $sizeNotSet = false;

                                                        }
                                                        $sizeCount++;
                                                        $hasSize = true;
                                                }

                                        }
                                        if(!$hasSize){
                                                $ageVal =  $product->getData("age");
                                                echo "Product Id->".$product->getId()."-->";
                                                echo "Age"."--->";
                                                echo $ageVal."<br>";
                                                
                                                $ageCount++;
                                                $product->setHiddenSizeType("age");
                                                
                                                $product->setFrockGender($frockGender['Kids']);
                                                                $product->setFrockSizeType($frockSizeType['Kids']);
                                                $product->setMetaTitle($product->getName());
                                                $product->save();
                                        }
                                        
                                        // if(isset($varValue) && $varValue != ""){
                                        //         $productData = $product->getData($value);
                                        //         // preg_match_all('!\d+!', $productData, $matches);
                                        //         $integers = $matches[0];
                                        //         if(count($integers) > 0){
                                        //                 $replacedIntegers = array();
                                        //                 foreach ($integers as $value) {
                                        //                         if(!in_array($value, $replacedIntegers)){
                                        //                                 $productData = str_replace($value, $value."\"", $productData);
                                        //                                 array_push($replacedIntegers, $value);
                                        //                         }
                                        //                 }
                                        //         }
                                        //         $saveStr .= $key.' '.$productData.", ";

                                        // }

                                }

                                echo "sizeCount--->".$sizeCount;
                                echo "ageCount-->".$ageCount;

                                // if($saveStr != ""){
                                //         $saveStr = str_replace("in", "", $saveStr);
                                //         echo "<b>".'Name  =  '.$product->getName()."</b><br>";
                                //         $saveStr = trim($saveStr, ', ');
                                //         $saveStr = str_replace('"/', "/", $saveStr);

                                //         echo $saveStr."<br>";
                                //         $product->setCustomSize($saveStr);
                                //         $product->save();

                                // }
                                
                                
                        }  


                        die("Done");
                } catch(\Exception $e) {
                        echo  $e->getMessage();
                }
                
        }
}