<?php
namespace Seasia\Customapi\Model;
use Seasia\Customapi\Api\BundleDiscountInterface;
use Seasia\Bundlediscount\Model\BundlediscountFactory;

class BundleDiscount implements BundleDiscountInterface
{  

    /**
     * Return data.
     *
     * @api
     */
    protected $dataFactory;
    protected $_objectManager;
    protected $_bundlediscountFactory;

    public function __construct(
        \Seasia\Customapi\Api\Data\ProductdataInterfaceFactory $dataFactory,
        BundlediscountFactory $bundlediscountFactory) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->dataFactory = $dataFactory;
        $this->_bundlediscountFactory = $bundlediscountFactory;
    }



    //Add Edit Bundle Discount
    public function addEditBundleDiscount($id,$sellerId,$condition,$type, $value,$discount_type,$discount_name,$discount_value,$combined_amount) {
        try {
            $collection = $this->_objectManager->create('Seasia\Bundlediscount\Model\Bundlediscount');
            if(!empty($id)) {
                $collection = $collection->getCollection()->addFieldToFilter('id', array('eq' => $id));  
                if($collection->getSize() > 0) {
                    $bundleData = $collection->getFirstItem();              
                    $bundleData->setSellerId($sellerId);
                    $bundleData->setCondition($condition);
                    $bundleData->setType($type);
                    $bundleData->setValue($value);
                    $bundleData->setDiscountType($discount_type);
                    $bundleData->setDiscountName($discount_name);
                    $bundleData->setDiscountValue($discount_value);
                    
                    if(!empty($combined_amount)) {
                        $bundleData->setCombinedAmount($combined_amount);
                    }

                    $bundleData->setCreatedAt(date("Y-m-d H:i:s"));
                    $bundleData->save();
                    //if($bundleData->save()) {
                    $response['status'] = "Success";
                    $response['message'] = __('Bundle Discount Updated Successfully.');  
                    //} else {                       
                } else {
                    $response['status'] = "Error";
                    $response['message'] = "Bundle Discount does not exist.";
                }
            } else {
                $collection->setSellerId($sellerId);
                $collection->setCondition($condition);
                $collection->setType($type);
                $collection->setValue($value);
                $collection->setDiscountType($discount_type);
                $collection->setDiscountName($discount_name);
                $collection->setDiscountValue($discount_value);
                
                //if(!empty($combined_amount)) {
                $collection->setCombinedAmount($combined_amount);
                //}

                $collection->setCreatedAt(date("Y-m-d H:i:s"));
                if($collection->save()) {
                    $response['status'] = "Success";
                    $response['message'] = __('Bundle Discount Added Successfully.');  
                }
            }
            return $this->getResponseFormat($response);
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    /* Add Delete Bundle Discount Size bu id and seller id. */
    public function deleteBundleDiscount($id,$sellerId) {      
        try {    
            $collection = $this->_objectManager->create('Seasia\Bundlediscount\Model\Bundlediscount');
            if(!empty($id)) {
                $collection = $collection->getCollection()->addFieldToFilter('id', array('eq' => $id))->addFieldToFilter('seller_id', array('eq' => $sellerId)); 
                if($collection->getSize() > 0) {
                    $bundleData = $collection->getFirstItem();
                    $bundleData->delete();
                    /*$discount_value = null;
                    $bundleData->setDiscountValue($discount_value);*/
                    $bundleData->save();
                    $response['status'] = "Success";
                    $response['message'] = __('Bundle Discount Deleted Successfully.');                    
                } else {
                    $response['status'] = "Error";
                    $response['message'] = "Bundle Discount does not exist.";
                }
            } else {
                $response['status'] = "Error";
                $response['message'] = "Error while deleting response.";
            }
            return $this->getResponseFormat($response);
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    /* Get Seller Single Family Size by id */

    public function bundleDiscountbyid($id,$sellerId) {
        try {    
            $collection = $this->_objectManager->create('Seasia\Bundlediscount\Model\Bundlediscount');
            
            $collection = $collection->getCollection()->addFieldToFilter('id', array('eq' => $id))->addFieldToFilter('seller_id', array('eq' => $sellerId))->getFirstItem(); 

            $response = $collection->getData();           

            return $this->getResponseFormat($response);
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }


    public function bundleDiscounts($sellerId,  $length, $pageNum, $orderBy, $orderDir, $searchStr){
        try {      
            $response = array();
            $collection = $this->_objectManager->create('Seasia\Bundlediscount\Model\Bundlediscount');
            $collection = $collection->getCollection()->addFieldToFilter('seller_id', array('eq' => $sellerId));
            if($searchStr != ""){
                $collection->getSelect()
                ->where(' discount_name like "%'.$searchStr.'%" ');
            } 
            $collection->setPageSize($length)->setCurPage($pageNum);
            $collection->setOrder($orderBy, $orderDir);
            
            $discountArray = array();
             
            $priceHelper = $this->_objectManager->create('Magento\Framework\Pricing\Helper\Data');
            foreach ($collection as $eachDiscount) {
                $eachDiscountData =array();
                $eachDiscountData = $eachDiscount->getData();
                
                $typeStr = "";
                $msgStr = "";
                if($eachDiscount->getDiscountType() == "fixed"){
                    $typeStr = $priceHelper->currency($eachDiscount->getDiscountValue(), true, false);
                }
                if($eachDiscount->getDiscountType() == "percent"){
                    $typeStr = $eachDiscount->getDiscountValue()."%";
                }
                if($eachDiscount->getType() == "subtotal"){
                    $msgStr = "When I sell the order for ".$priceHelper->currency($eachDiscount->getValue(), true, false)." or more then discount  by ".$typeStr;
                    //array_push($allMessages, $msgStr);
                }
                if($eachDiscount->getType() == "qty"){
                    $msgStr = "When I sell  ".$eachDiscount->getValue()." or more items then discount by ".$typeStr;
                    //array_push($allMessages, $msgStr);
                }
                if($eachDiscount->getType() == "combined"){
                    $msgStr = "When I sell  ".$eachDiscount->getValue()." or more items for ".$priceHelper->currency($eachDiscount->getCombinedAmount(), true, false).""." then discount the Order by ".$typeStr;
                    //array_push($allMessages, $msgStr);
                }
                $eachDiscountData['message'] = $msgStr;
                array_push($discountArray, $eachDiscountData);
            }

            $response['discounts'] = $discountArray;
            $response['totalBundleCount'] = $collection->getSize();
 
            return $this->getResponseFormat($response);
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }

    }

    // Return Response
    public function getResponseFormat($content){
        $page_object = $this->dataFactory->create();
        $page_object->setName($content);
        return $page_object;  
    }

    public function errorMessage($e){
        $responseArray = array();
        $responseArray['status'] = __('Error');
        $responseArray['message'] = $e->getMessage();
        return $this->getResponseFormat($responseArray);
    }
}