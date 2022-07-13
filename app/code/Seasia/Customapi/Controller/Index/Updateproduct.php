<?php

namespace Seasia\Customapi\Controller\Index;

class Updateproduct extends \Magento\Framework\App\Action\Action
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

                        $response = $collection->getData();


                        $attrArray = array(
                                "Bust" => "bust",
                                "Arm Length" => "arm_length",
                                "Skirt Length" => "skirt_length",
                                "Width" => "ts_dimensions_width",
                                "Height" => "ts_dimensions_height",
                                "Waist" => "waist",
                                "Hips" => "hips",

                        );

                        foreach($response as $item){

                                //echo $item['entity_id'];
                                if($item['entity_id'] == 170){
                                        $saveStr = "";
                                        
                                        $product = $objectManager->get('Magento\Catalog\Model\Product')->load($item['entity_id']);

                                        foreach ($attrArray as $key => $value) {
                                                $varValue =  $product->getData($value);
                                                if(isset($varValue) && $varValue != ""){
                                                        $productData = $product->getData($value);
                                                        preg_match_all('!\d+!', $productData, $matches);
                                                        $integers = $matches[0];
                                                        if(count($integers) > 0){
                                                                $replacedIntegers = array();
                                                                foreach ($integers as $value) {
                                                                        if(!in_array($value, $replacedIntegers)){
                                                                                $productData = str_replace($value, $value."\"", $productData);
                                                                                array_push($replacedIntegers, $value);
                                                                        }
                                                                }
                                                        }
                                                        $saveStr .= $key.' '.$productData.", ";

                                                }
                                        }

                                        if($saveStr != ""){
                                                $saveStr = str_replace("in", "", $saveStr);
                                                echo "<b>".'Name  =  '.$product->getName()."</b><br>";
                                                $saveStr = trim($saveStr, ', ');
                                                $saveStr = str_replace('"/', "/", $saveStr);

                                                echo $saveStr."<br>";
                                                $product->setCustomSize($saveStr);
                                                $product->save();
                                        }

                                       // echo $product->getName();
                                }
                                
                        }

                        

                        die("helllll");
                        //$collection->setPageSize(1);
                        
                        foreach ($collection as $product){
                                echo "Helloooo";
                                echo $product->getId();
                                
                                
                                


                                if($saveStr != ""){
                                        $saveStr = str_replace("in", "", $saveStr);
                                        echo "<b>".'Name  =  '.$product->getName()."</b><br>";
                                        $saveStr = trim($saveStr, ', ');
                                        $saveStr = str_replace('"/', "/", $saveStr);

                                        echo $saveStr."<br>";
                                        $product->setCustomSize($saveStr);
                                        $product->save();
                                }
                                
                                
                        }  


                        die("Doasdasdasdfgdfgfdgfdggdfgdsane");
                } catch(\Exception $e) {
                        echo $e->getMessage();
                        //return $e->getMessage();
                }
                
        }
}