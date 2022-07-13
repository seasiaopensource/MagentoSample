<?php
namespace Seasia\Customapi\Model;
use Seasia\Customapi\Api\CustomerNewInterface;
use Seasia\Sellerdiscount\Model\DiscountFactory;
use Seasia\Sellerdiscount\Model\ProductdiscountFactory;
use Webkul\Marketplace\Model\Product as SellerProduct;

class CustomerNew implements CustomerNewInterface
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
    

    /*** Code By Villiam **/
    // Get Customer Following Data

    public function customerFollowing($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr){
        try {
            $helper = $this->_objectManager->create('Webkul\Agorae\Helper\Data');
            $response = $helper->getFollowingCustomer($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr);
            return $this->getResponseFormat($response);
            
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    // Get Customer Followers Data

    public function customerFollowers($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr){
        try { 
            $helper = $this->_objectManager->create('Webkul\Agorae\Helper\Data');
            $response = $helper->getFollowersCustomer($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr);
            return $this->getResponseFormat($response);
            
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    // Get Customer Offer Made Data

    public function offerMade($sellerId){
        try { 

            $reviewFactory = $this->_objectManager->create('Seasia\Selleroffer\Model\ResourceModel\Offer\CollectionFactory');
            
            $collection = $reviewFactory->create()
            ->addFieldToFilter(
                'buyer_id',
                $sellerId
            );

            $joinTable = $this->_objectManager->create(
                'Webkul\Agorae\Model\ResourceModel\Mpfavouriteseller\Collection'
            )->getTable('customer_entity');

            $collection->getSelect()->join(
                $joinTable.' as cgf',
                'main_table.seller_id = cgf.entity_id',
                array('firstname','lastname')
            );
            
            $response['offermade'] = $collection->getData();
          /* $offerData = $collection;
            foreach ($collection as $key => $value) {
               $product =  $this->_objectManager->create('Magento\Catalog\Model\Product')->load($value['product_id']);
                $offerData[$key]['product_name'] = $product->getName();
                echo $product->getName();die;
            }
            
            echo '<pre>';print_r($offerData);die;*/

            return $this->getResponseFormat($response);
            
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    // Get Customer Offers Received Data

    public function offerReceived($sellerId){
        try {

            $reviewFactory = $this->_objectManager->create('Seasia\Selleroffer\Model\ResourceModel\Offer\CollectionFactory');
            $collection = $reviewFactory->create()
            ->addFieldToFilter(
                'seller_id',
                $sellerId
            );

            $joinTable = $this->_objectManager->create(
                'Webkul\Agorae\Model\ResourceModel\Mpfavouriteseller\Collection'
            )->getTable('customer_entity');

            $collection->getSelect()->join(
                $joinTable.' as cgf',
                'main_table.buyer_id = cgf.entity_id',
                array('firstname','lastname')
            );

            $response['offerreceived'] = $collection->getData();

            return $this->getResponseFormat($response);            
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    /**
     * [getSellerProducts used to get seller products from seller Id]
     * @param  int $sellerId [seller Id]
     * @return object
     */
    public function getSellerProducts($sellerId, $pageNum, $length, $orderBy, $orderDir)
    {      
        try {
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

            $collection->setPageSize($length)->setCurPage($pageNum);
            $collection->setOrder($orderBy, $orderDir);
            $totalCount = $collection->getSize();

            $response = [];
            //$collectionConfigurable = $collection->getData();
            foreach($collection as $key => $val) {
                $store = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
                $response['products'][$val->getId()]['product_id'] = $val->getId();
                $response['products'][$val->getId()]['title'] = $val->getName();
                $response['products'][$val->getId()]['price'] = $val->getPrice();
                $response['products'][$val->getId()]['description'] = $val->getDescription();
                $response['products'][$val->getId()]['image'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $val->getImage();
            }
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