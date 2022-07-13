<?php
namespace Seasia\Customapi\Model;
use Seasia\Customapi\Api\DiscountInterface;
use Seasia\Sellerdiscount\Model\DiscountFactory;
use Seasia\Sellerdiscount\Model\ProductdiscountFactory;
use Webkul\Marketplace\Model\Product as SellerProduct;

class Discount implements DiscountInterface
{

    /**
     * Return data.
     *
     * @api
     */
    protected $dataFactory;
    protected $_objectManager;
    protected $_discountFactory;
    protected $_productdiscountFactory;



    

    public function __construct(\Seasia\Customapi\Api\Data\ProductdataInterfaceFactory $dataFactory,DiscountFactory $discountFactory,ProductdiscountFactory $productdiscountFactory) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->dataFactory = $dataFactory;
        $this->_discountFactory = $discountFactory;
        $this->_productdiscountFactory = $productdiscountFactory;
    }
    
    // Get Seller Discounts
    public function discounts($id, $status, $length, $pageNum, $orderBy, $orderDir){
        $responseArray = array();
        $discountCollection = $this->_objectManager->create('Seasia\Sellerdiscount\Model\ResourceModel\Discount\Collection');

        if ($status != "") {
            $discountCollection->addFieldToFilter('status', array('eq' => $status));
        }
        $discountCollection->setPageSize($length)->setCurPage($pageNum);
        $discountCollection->setOrder($orderBy, $orderDir);
        $totalCount = $discountCollection->getSize();
        $responseArray['discounts'] = $discountCollection->getData();
        $responseArray['totalCount'] = $totalCount;
        return $this->getResponseFormat($responseArray);
    }
    
    /**
     * [getSellerProducts used to get seller products from seller Id]
     * @param  int $sellerId [seller Id]
     * @return object
     */
    public function getSellerProducts($sellerId, $orderBy, $orderDir, $searchStr)
    {      
        try {
            $collection = $this->sellerProducts($sellerId, $orderBy, $orderDir, $searchStr);
            $collection->addFieldToFilter('created_at', array('lt' => date('Y-m-d H:i:s', strtotime('-7 day'))));
            $response = [];
            $store = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
            foreach($collection as $key => $val) {
                $productArray = array();
                $productArray['product_id'] = $val->getId();
                $productArray['name'] = $val->getName();
                $productArray['price'] = $val->getPriceInfo()->getPrice('regular_price')->getValue();
                $productArray['description'] = $val->getDescription();
                $productArray['image'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $val->getImage();
                array_push($response, $productArray);
            }
            return $this->getResponseFormat($response);
        }
        
        catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    private function sellerProducts($sellerId, $orderBy, $orderDir, $searchStr){
        $helper = $this->_objectManager->create('Webkul\Agorae\Helper\Data');
        $sellerProductcollection = $helper->getSellerProductDataBySellerId($sellerId);
        $querydata = $sellerProductcollection->addFieldToFilter('status', ['neq' => SellerProduct::STATUS_DISABLED])
        ->addFieldToSelect('mageproduct_id')
        ->setOrder('mageproduct_id');

        $collection = $this->_objectManager->create(
            'Magento\Catalog\Model\Product'
        )->getCollection();


        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('entity_id', ['in' => $querydata->getData()]);
        $collection->addAttributeToFilter('visibility', ['in' => [4]]);
        $collection->addStoreFilter();            

        $collectionConfigurable = $this->_objectManager->create(
            'Magento\Catalog\Model\Product'
        )->getCollection()
        ->addAttributeToFilter('type_id', 'configurable')
        ->addAttributeToFilter('entity_id', ['in' => $querydata->getData()]);

        $outOfStockConfis = [];
        foreach ($collectionConfigurable as $_configurableproduct) {
            $product = $this->_objectManager->create(
                'Magento\Catalog\Model\Product'
            )->load($_configurableproduct->getId());
            if (!$product->getData('is_salable')) {
                $outOfStockConfis[] = $product->getId();
            }
        }
        if (count($outOfStockConfis)) {
            $collection->addAttributeToFilter('entity_id', ['nin' => $outOfStockConfis]);
        }

        $collectionBundle = $this->_objectManager->create(
            'Magento\Catalog\Model\Product'
        )
        ->getCollection()
        ->addAttributeToFilter('type_id', 'bundle')
        ->addAttributeToFilter('entity_id', ['in' => $querydata->getData()]);
        $outOfStockConfis = [];
        foreach ($collectionBundle as $_bundleproduct) {
            $product = $this->_objectManager->create(
                'Magento\Catalog\Model\Product'
            )->load($_bundleproduct->getId());
            if (!$product->getData('is_salable')) {
                $outOfStockConfis[] = $product->getId();
            }
        }
        if (count($outOfStockConfis)) {
            $collection->addAttributeToFilter('entity_id', ['nin' => $outOfStockConfis]);
        }

        $collectionGrouped = $this->_objectManager->create(
            'Magento\Catalog\Model\Product'
        )
        ->getCollection()
        ->addAttributeToFilter('type_id', 'grouped')
        ->addAttributeToFilter('entity_id', ['in' => $querydata->getData()]);
        $outOfStockConfis = [];
        foreach ($collectionGrouped as $_groupedproduct) {
            $product = $this->_objectManager->create(
                'Magento\Catalog\Model\Product'
            )->load($_groupedproduct->getId());
            if (!$product->getData('is_salable')) {
                $outOfStockConfis[] = $product->getId();
            }
        }
        if (count($outOfStockConfis)) {
            $collection->addAttributeToFilter(
                'entity_id',
                ['nin' => $outOfStockConfis]
            );
        } 

        $eavAttribute = $this->_objectManager->get(
            'Magento\Eav\Model\ResourceModel\Entity\Attribute'
        );
        $proAttId = $eavAttribute->getIdByCode('catalog_product', 'name');

        $catalogProductEntityVarchar = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Product\Collection'
        )->getTable('catalog_product_entity_varchar');
        if (isset($searchStr)) {
            $filter = $searchStr != '' ? $searchStr : '';
        }
        $collection->getSelect()
        ->join($catalogProductEntityVarchar.' as cpev','e.entity_id = cpev.entity_id')
        ->where(' cpev.value like "%'.$filter.'%" AND cpev.attribute_id = '.$proAttId);


        $collection->setOrder($orderBy, $orderDir);


        return $collection;
    }
    public function getDiscount($sellerId, $discountId){
        try{
            $response = array();
            $discounts = $this->_objectManager->create('Seasia\Sellerdiscount\Model\ResourceModel\Discount\Collection');
            $discounts->addFieldToFilter('id', array('eq' => $discountId));
            $discounts->addFieldToFilter('seller_id', array('eq' => $sellerId));

            $productDiscountFactory = $this->_objectManager->create('Seasia\Sellerdiscount\Model\ProductdiscountFactory');
            $discountModel = $productDiscountFactory->create();
            $productIds = array();
            foreach($discounts as $discount){
                $discountCollection = $discountModel->getCollection()->addFieldToFilter('discount_id', array('eq' => $discount->getId()));
                foreach($discountCollection as $eachDiscount){
                    array_push($productIds, $eachDiscount->getProductId());
                }
                $response = $discount->getData();
                $response['productIds'] = implode(",", $productIds);

            }

            return $this->getResponseFormat($response);
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    public function deleteDiscount($sellerId, $discountId){
        try{
            $response = array();
            $discounts = $this->_objectManager->create('Seasia\Sellerdiscount\Model\ResourceModel\Discount\Collection');
            $discounts->addFieldToFilter('id', array('eq' => $discountId));
            $productDiscountFactory = $this->_objectManager->create('Seasia\Sellerdiscount\Model\ProductdiscountFactory');

            foreach($discounts as $discount){

                if($discount->getId() && $discount->getSellerId() == $sellerId){

                    $discountModel = $productDiscountFactory->create();

                    $discountCollection = $discountModel->getCollection()->addFieldToFilter('discount_id', array('eq' => $discount->getId()));
                    $productModel = $this->_objectManager->create('Magento\Catalog\Model\Product');
                    foreach($discountCollection as $eachProductDiscount){
                        $product = $productModel->load($eachProductDiscount->getProductId());
                        $product->setSpecialPrice(null);
                        if($product->save()){
                            $eachProductDiscount->delete();
                        }

                    }
                    $discount->delete();
                }


            }

            $response['status'] = "Success";
            $response['message'] = __('Discount Deleted Successfully.');


            return $this->getResponseFormat($response);
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }

    }

    public function addEditDiscount($sellerId,$discountId,$title,$percent,$status,$productids) {  
        $response = []; 
        try {    
            $_helper = $this->_objectManager->create('Seasia\Customapi\Helper\Data');  
            if(!empty($discountId)) {                                 
                $collection = $_helper->getDiscountCollection($discountId);
                if($collection->getSize() > 0) {
                    $discountData = $collection->getFirstItem();
                    $discountData->setSellerId($sellerId);
                    $discountData->setDiscountName($title);
                    $discountData->setDiscountPercent($percent);
                    $discountData->setStatus($status);
                    $discountData->setUpdatedAt(date("Y-m-d H:i:s")); 
                    $discountData->save();

                    $discount_id = $discountData->getId();
                    $response['discount_id'] = $discount_id; 
                    if($discount_id) {
                        $productid = explode(",",$productids);
                        $getAllProductId = $_helper->getDiscountProductId($discount_id)->addFieldToSelect('product_id')->getData();

                        $getAllProductIds = array_column($getAllProductId, 'product_id');  

                        $productIdArray = array_diff($getAllProductIds,$productid);

                        $getSelectedProductId = $_helper->getDiscountSelectedProductId($productIdArray,$discount_id);

                        foreach($getSelectedProductId as $rec) {
                            $rec->delete();
                        }    

                        if(is_array($productid)) {
                            $productdiscountfactory = $this->_productdiscountFactory->create();
                            foreach($productid as $val) {
                                $p_collection = $_helper->getProductDiscountCollection($val);


                                if(count($p_collection) > 0) { 

                                    foreach($p_collection as $value) {         
                                        $value->setDiscountId($discount_id);
                                        $value->save();


                                        $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($value->getProductId());
                                        if($product->getId()){
                                            $price = $product->getPrice();
                                            $discountPercent = $percent;
                                            $specialPrice = $price - ($price * $percent/100);
                                            $product->setSpecialPrice($specialPrice);
                                            $product->save();
                                        }
                                    }
                                } else { 

                                    $productdiscountfactory->setDiscountId($discount_id );
                                    $productdiscountfactory->setProductId($val);
                                    $productdiscountfactory->setCreatedAt(date("Y-m-d H:i:s"));
                                    $productdiscountfactory->save();

                                    $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($val);
                                    if($product->getId()){
                                        $price = $product->getPrice();
                                        $discountPercent = $percent;
                                        $specialPrice = $price - ($price * $percent/100);
                                        $product->setSpecialPrice($specialPrice);
                                        $product->save();
                                    }
                                }
                            }
                        }
                    }
                    $response['status'] = 'success';
                } else {
                    $response['discount_id'] = $discountId; 
                    $response['status'] = 'fail';
                }
            } else {                  
                $discountData = $this->_discountFactory->create();
                $discountData->setSellerId($sellerId);
                $discountData->setDiscountName($title);
                $discountData->setDiscountPercent($percent);
                $discountData->setStatus($status);
                $discountData->setCreatedAt(date("Y-m-d H:i:s")); 

                $res = $discountData->save();
                $discount_id = $res->getId();
                $response['discount_id'] = $discount_id;

                if($discount_id){
                    $productdiscount = $this->_productdiscountFactory->create();
                    $productid = explode(",",$productids);
                    if(is_array($productid)) {

                        foreach($productid as $val) {
                            if(!empty($val)) {
                                $p_collection = $_helper->getProductDiscountCollection($val);

                                //$p_collection = $p_collection->getData();
                                if(count($p_collection) > 0) {    
                                    $productdiscountfactory = $this->_productdiscountFactory->create();
                                    foreach($p_collection as $value) {        
                                        $value->setDiscountId($discount_id);
                                        $value->save();

                                        $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($val);
                                        if($product->getId()){
                                            $price = $product->getPrice();
                                            $discountPercent = $percent;
                                            $specialPrice = $price - ($price * $percent/100);
                                            $product->setSpecialPrice($specialPrice);
                                            $product->save();
                                        }     
                                    }
                                } else {
                                    $productdiscount->setDiscountId($discount_id );
                                    $productdiscount->setProductId($val);
                                    $productdiscount->setCreatedAt(date("Y-m-d H:i:s"));
                                    $productdiscount->save();

                                    $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($val);
                                    if($product->getId()){
                                        $price = $product->getPrice();
                                        $discountPercent = $percent;
                                        $specialPrice = $price - ($price * $percent/100);
                                        $product->setSpecialPrice($specialPrice);
                                        $product->save();
                                    }
                                }
                            }
                        }
                    }
                } 
                $response['status'] = 'success';
            }
            return $this->getResponseFormat($response);
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }    
    }



    public function applyDiscount($sellerId, $discount, $productids){
        try{
            $response = array();
            $rmaHelper = $this->_objectManager->create('Webkul\MpRmaSystem\Helper\Data');
            if($discount != ""){
                if($productids == "all"){
                    $collection  = $this->sellerProducts($sellerId, "entity_id", "DESC", "");
                    $collection->addFieldToFilter('created_at', array('lt' => date('Y-m-d H:i:s', strtotime('-7 day'))));
                    foreach($collection as $key => $product) {
                        $price = $product->getPriceInfo()->getPrice('regular_price')->getValue();
                        $specialPrice = $price - ($price * $discount/100);
                        $product->setSpecialPrice($specialPrice);
                        $product->setProductDiscount($discount);
                        $product->save();

                    }

                    $response['status'] = "Success";
                    $response['message'] = "Discount applied to all products.";
                }elseif($productids !=""){

                    $productArray = explode(",", $productids);
                    foreach($productArray as $productId){
                       $productObj = $this->_objectManager->get('Magento\Catalog\Model\Product');
                       $productSellerId = $rmaHelper->getSellerIdByproductid($productId);
                       if($productSellerId == $sellerId){
                        $product = $productObj->load($productId);
                        if($product->getId()){
                            $price = $product->getPriceInfo()->getPrice('regular_price')->getValue();
                            $specialPrice = $price - ($price * $discount/100);
                            $product->setSpecialPrice($specialPrice);
                            $product->setProductDiscount($discount);
                            $product->save();
                        }
                        


                    }

                }

                $response['status'] = "Success";
                $response['message'] = "Discount applied to selected products.";


            }else{
                $response['status'] = "Error";
                $response['message'] = "Please select the products.";
            }
        }else{
            $response['status'] = "Error";
            $response['message'] = "Please select the discount.";
        }


        return $this->getResponseFormat($response);
    } catch(\Exception $e) {
        return $this->errorMessage($e);
    }    
}


public function removeDiscount($sellerId, $discount, $productids){
    try{
        $response = array();
        $rmaHelper = $this->_objectManager->create('Webkul\MpRmaSystem\Helper\Data');
        
        if($productids == "all"){
            $collection  = $this->sellerProducts($sellerId, "entity_id", "DESC", "");
            foreach($collection as $key => $product) {
                $product->setSpecialPrice(null);
                $product->setProductDiscount("");
                $product->save();

            }

            $response['status'] = "Success";
            $response['message'] = "Discount removed from all products.";
        }elseif($productids !=""){
            $productObj = $this->_objectManager->create('Magento\Catalog\Model\Product');
            $productArray = explode(",", $productids);
            foreach($productArray as $productId){
                $productSellerId = $rmaHelper->getSellerIdByproductid($productId);
                if($productSellerId == $sellerId){
                    $product = $productObj->load($productId);
                    $product->setSpecialPrice(null);
                    $product->setProductDiscount("");
                    $product->save();
                }

            }

            $response['status'] = "Success";
            $response['message'] = "Discount removed from selected products.";


        }else{
            $response['status'] = "Error";
            $response['message'] = "Please select the products.";
        }
        


        return $this->getResponseFormat($response);
    } catch(\Exception $e) {
        return $this->errorMessage($e);
    } 
}

public function getDiscountedProducts($sellerId,  $length, $pageNum, $orderBy, $orderDir, $searchStr){
    try{
        $response = array();
        $responseArray = array();
        $collection = $this->sellerProducts($sellerId, $orderBy, $orderDir, $searchStr);
        $collection->addAttributeToFilter('product_discount', ['neq' => ""]);
        $collection->setPageSize($length)->setCurPage($pageNum);
        $totalCount = $collection->getSize();
        $store = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
        foreach($collection as $key => $val) {
            $productArray = array();
            $productArray['product_id'] = $val->getId();
            $productArray['name'] = $val->getName();
            $productArray['original_price'] = $val->getCost();
            $productArray['regular_price'] = $val->getPriceInfo()->getPrice('regular_price')->getValue();
            $productArray['discounted_price'] = $val->getPriceInfo()->getPrice('final_price')->getValue();
            $productArray['product_discount'] = $val->getProductDiscount();
            $productArray['description'] = $val->getDescription();
            $productArray['image'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $val->getImage();
            array_push($responseArray, $productArray);
        }

        $response['products'] = $responseArray;
        $response['totalCount'] = $totalCount;
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