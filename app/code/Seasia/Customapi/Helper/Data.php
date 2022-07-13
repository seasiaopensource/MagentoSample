<?php
/**
 * Webkul Software.
 *
 * @category   Webkul
 * @package    Webkul_Agorae
 * @author     Webkul Software Private Limited
 * @copyright  Copyright (c) 2010-2017 Webkul Software Private Limited (
 https://webkul.com)
 * @license    https://store.webkul.com/license.html
 */
 namespace Seasia\Customapi\Helper;
 use Seasia\Sellerdiscount\Model\ProductdiscountFactory;
 use Seasia\Sellerdiscount\Model\DiscountFactory;
 use Webkul\Marketplace\Model\Product as SellerProduct;
 use Seasia\Selleroffer\Model\OfferFactory;

/**
 * Webkul Agorae Helper Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_modelproductdiscountFactory;
    protected $_modeldiscountFactory;
    protected $_objectManager;
    protected $_customerRepositoryInterface;
    protected $_offerFactory;

    public function __construct(\Magento\Framework\App\Helper\Context $context,ProductdiscountFactory $modelProductdiscountFactory,DiscountFactory $modelDiscountFactory, \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,OfferFactory $offerFactory) 
    {
        $this->_modelproductdiscountFactory = $modelProductdiscountFactory;
        $this->_modeldiscountFactory        = $modelDiscountFactory;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_offerFactory = $offerFactory;
        parent::__construct($context);
    }

    /**
       * get Discount Collection
       * @return object
    */
    public function getDiscountCollection($discountId) {
        $discountModel = $this->_modeldiscountFactory->create();
        $discountCollection = $discountModel->getCollection()->addFieldToFilter('id', array('eq' => $discountId));
        return $discountCollection;   
    }

    /**
       * get Product Discount Collection
       * @return object
    */
    public function getProductDiscountCollection($productId) {
        $discountModel = $this->_modelproductdiscountFactory->create();
        $discountCollection = $discountModel->getCollection()->addFieldToFilter('product_id', array('eq' => $productId));
        return $discountCollection;   
    }    

    /**
       * get  Discount Product Id
       * @return object
    */
    public function getDiscountProductId($discountId) {
        $discountModel = $this->_modelproductdiscountFactory->create();
        $discountCollection = $discountModel->getCollection()->addFieldToFilter('discount_id', array('eq' => $discountId));
        return $discountCollection;   
    }

    /**
       * get  Discount Product Id
       * @return object
    */
    public function getDiscountSelectedProductId($productIds,$discountId) {
        $discountModel = $this->_modelproductdiscountFactory->create();
        $discountCollection = $discountModel->getCollection()->addFieldToFilter('discount_id', array('eq' => $discountId))->addFieldToFilter('product_id', array('in' => $productIds));
        return $discountCollection;   
    } 

    /**
       * get  Likes By Product Id
       * @return object
    */       
    public function getLikesByProductId($productIds) {
       
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');  
        $connection = $resource->getConnection();
        //$tableName = $resource->getTableName('employee'); //gives table name with prefix  

        //Select Data from table
        $sql = 'SELECT `opce`.* FROM ohfrock_prod.wishlist_item as `opwi` inner join ohfrock_prod.wishlist as `opw` ON `opwi`.`wishlist_id` = `opw`.`wishlist_id` inner join  ohfrock_prod.customer_entity as `opce` ON `opw`.`customer_id` = `opce`.`entity_id` where `opwi`.`product_id` = '.$productIds;
        return $connection->fetchAll($sql); // gives associated array, table fields as key in array.
    }

    /**
     * [getSellerProducts used to get seller products from seller Id]
     * @param  int $sellerId [seller Id]
     * @return object
     */
    public function getSellerProductsId($sellerId) { 
        
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
        
        $response = [];
        //$collectionConfigurable = $collection->getData();
        foreach($collection as $key => $val) {
            $store = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
            $response[$val->getId()]['product_id'] = $val->getId();
            $response[$val->getId()]['title'] = $val->getName();
            $response[$val->getId()]['price'] = $val->getPrice();
            $response[$val->getId()]['description'] = $val->getDescription();
            $response[$val->getId()]['image'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $val->getImage();
        }
        return $response;       
    }

    /**
     * [getSellerProducts used to get seller products from seller Id]
     * @param  int $sellerId [seller Id]
     * @return object
     */
    public function getSellerProductsIdArray($sellerId) { 
        
        $helper = $this->_objectManager->create('Webkul\Agorae\Helper\Data');
        $sellerProductcollection = $helper->getSellerProductDataBySellerId($sellerId);
        $querydata = $sellerProductcollection->addFieldToFilter('status', ['neq' => SellerProduct::STATUS_DISABLED])
        ->addFieldToSelect('mageproduct_id')
        ->setOrder('mageproduct_id');

        $ids = $querydata->getData();
        if(!empty($ids)) {
            return array_column($ids, 'mageproduct_id');
        } else {
            return $ids;
        }
    }

    /**
       * get  Likes By Product Id
       * @return object
    */       
    public function getProductcountByCustomerId($productIds,$customerId) {
     
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');  
        $connection = $resource->getConnection();
        
        $productIds = implode(",",$productIds);
        
        //Select Data from table  wishlist
        $sql = 'SELECT `opwi`.* FROM ohfrock_prod.wishlist as `opw` inner join ohfrock_prod.wishlist_item as `opwi` ON `opw`.`wishlist_id` = `opwi`.`wishlist_id`  where `opwi`.`product_id` IN('.$productIds.') and `opw`.`customer_id` = '.$customerId;
        return $connection->fetchAll($sql); // gives associated array, table fields as key in array.
    }
    /**
       * get Notification Settings By Seller ID
       * @return object
    */       
    public function getNotificationSetting($sellerId) {

        $collection = $this->_objectManager->create('Seasia\Notificationsetting\Model\Notificationsetting');
        $collection = $collection->getCollection()->addFieldToFilter('seller_id',$sellerId);
        $settings = $collection->getFirstItem();
        return $settings;
    }
    
    public function getNotificationDetails($notification){
        $notificationDetails = array();
        $msgStr = "";
        $baseUrl = $this->getBaseUrl();
        $baseReactUrl = $this->getReactUrl();
        $stringId = $this->getStringId($notification->getId());
        switch($notification->getType()){
            case 'frockShop':

            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationFrom());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();
            $name = $notificationDetails['firstname']." ".$notificationDetails['lastname'];
            $msgStr = "<a target='_blank' class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."followers".$stringId."'><span>".$name." started following you."."</span></a>";
            $notificationDetails['notification_url'] = $msgStr;
            return $notificationDetails;
            break;
            case 'product_like': 
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationFrom());
            $productData = $this->getProductById($notification->getNotificationItemId());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();
            $notificationDetails['productname'] = $productData->getName();     
            $name = $notificationDetails['firstname']." ".$notificationDetails['lastname'];
            $msgStr = "<a target='_blank' class='wk-mp-notification-entry-description-start' href='".$baseUrl."readnotification/Index/Index?id=".$notification->getId()."&item_id=".$notification->getNotificationItemId()."&actual_item_id=".$productData->getId()."'><span>".$name." likes product ".$notificationDetails['productname']."</span></a>";

            $notificationDetails['notification_url'] = $msgStr;
            return $notificationDetails;
            break;  
            case 'buyer_order':
            $notificationDetails = $notification->getData();
            $orderData = $this->getOrderById($notification->getNotificationItemId());
            $notificationDetails['orderIncrementId'] = $orderData->getIncrementId();
            $notificationDetails['state'] = $orderData->getState();
            
            $msgStr = "<a target='_blank'  class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."order/".$notification->getNotificationItemId()."/buyer".$stringId."'><span>"."Your purchase is complete (#".$notificationDetails['orderIncrementId'].")</span></a>";


            $notificationDetails['notification_url'] = $msgStr;
            return $notificationDetails;
            break;
            case 'seller_order':
            $notificationDetails = $notification->getData();
            $orderData = $this->getOrderById($notification->getNotificationItemId());

            $customerData = $this->getCustomerById($notification->getNotificationTo());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();
            $orderData = $this->getOrderById($notification->getNotificationItemId());
            $notificationDetails['orderIncrementId'] = $orderData->getIncrementId();
            $notificationDetails['state'] = $orderData->getState();

            $notificationDetails['productname'] = $this->getAllProductNameByOrderId($notification->getNotificationItemId());


            
            $msgStr = "<a target='_blank' class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."order/".$notification->getNotificationItemId()."/seller".$stringId."'><span>"."You Made A New Sale (#".$notificationDetails['orderIncrementId'].")</span></a>";

            
            $notificationDetails['notification_url'] = $msgStr;


            return $notificationDetails;
            break;
            case 'offer_received':
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationTo());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();

            $offerData = $this->getOfferById($notification->getNotificationItemId());
            $notificationDetails['productname'] = $offerData->getName();
            $notificationDetails['productprice'] = $offerData->getPrice();

            $msgStr = "<a target='_blank'  class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."offer/".$offerData->getId()."/recieved".$stringId."'><span>"."Someone wants to make a deal on ".$notificationDetails['productname']."</span></a>";


            $notificationDetails['notification_url'] = $msgStr;
            return $notificationDetails;
            break;
            case 'offer_accepted':
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationTo());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();

            $offerData = $this->getOfferById($notification->getNotificationItemId());
            $notificationDetails['productname'] = $offerData->getName();
            $notificationDetails['productprice'] = $offerData->getPrice();
            
            $msgStr = "<a target='_blank'  class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."offer/".$notification->getNotificationItemId()."/made".$stringId."'><span>"."Your offer was accepted on ".$notificationDetails['productname']."</span></a>";
            $notificationDetails['notification_url'] = $msgStr;

            return $notificationDetails;  
            break; 
            case 'offer_rejected':
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationTo());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();

            $offerData = $this->getOfferById($notification->getNotificationItemId());
            $notificationDetails['productname'] = $offerData->getName();
            $notificationDetails['productprice'] = $offerData->getPrice();
            
            $msgStr = "<a target='_blank'  class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."offer/".$notification->getNotificationItemId()."/made".$stringId."'><span>"."Your offer was rejected on ".$notificationDetails['productname']."</span></a>";
            $notificationDetails['notification_url'] = $msgStr;

            return $notificationDetails;
            break;

            case 'counter_offer':
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationTo());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();

            $offerData = $this->getOfferById($notification->getNotificationItemId());

            $offerModel = $this->_offerFactory->create();
            $offerCollection = $offerModel->getCollection()
            ->addFieldToFilter('counter_offer_id', array('eq' => $offerData->getId()))
            ->getFirstItem()
            ;

            $sellerData = $this->getCustomerById($notification->getNotificationFrom());
            $sellerName = $sellerData->getFirstname()." ".$sellerData->getLastname();
            
            $offerModel = $this->_offerFactory->create();
            $offerCollection = $offerModel->getCollection()
            ->addFieldToFilter('counter_offer_id', array('eq' => $notification->getNotificationItemId()))
            ->getFirstItem()
            ;
            $msgStr =  $offerCollection->getSelect();
            
             $notificationDetails['productname'] = $offerData->getName();
             //$notificationDetails['productprice'] = $offerData->getPrice();
            
             $msgStr = "<a target='_blank'  class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."offer/".$offerCollection->getId()."/made".$stringId."'><span>".$sellerName." has countered your offer on ".$notificationDetails['productname']."</span></a>";


             $notificationDetails['notification_url'] = $msgStr;
            return $notificationDetails;
            break;

            case 'question':
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationFrom());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();
            $questionDetail = $this->getQuestionById($notification->getNotificationItemId());
            $notificationDetails['questiondetail'] = $questionDetail->getData();   

            $productData = $this->getProductById($questionDetail->getProductId());   
            $notificationDetails['productname'] = $productData->getName();
            $name = $notificationDetails['firstname']." ".$notificationDetails['lastname'];
            

            $msgStr = "<a target='_blank' class='wk-mp-notification-entry-description-start' href='".$baseUrl."/readnotification/Index/Index?id=".$notification->getId()."&item_id=".$notification->getNotificationItemId()."&actual_item_id=".$productData->getId()."/#q-".$notification->getNotificationItemId()."'><span>".$name." has a question about ".$notificationDetails['productname']."</span></a>";  

            $notificationDetails['notification_url'] = $msgStr;
            return $notificationDetails;
            break;
            case 'answer':
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationTo());
            $notificationDetails['sellerfirstname'] = $customerData->getFirstname();
            $notificationDetails['sellerlastname'] = $customerData->getLastname();
            $customerData1 = $this->getCustomerById($notification->getNotificationFrom());
            $notificationDetails['buyerfirstname'] = $customerData1->getFirstname();
            $notificationDetails['buyerlastname'] = $customerData1->getLastname();

            $answerDetail = $this->getAnswerById($notification->getNotificationItemId());
            $notificationDetails['answerdetail'] = $answerDetail->getData();

            $questionDetail = $this->getQuestionById($answerDetail->getQuestionId());
            $notificationDetails['questiondetail'] = $questionDetail->getData();   

            $productData = $this->getProductById($questionDetail->getProductId());   
            $notificationDetails['productname'] = $productData->getName();
            $name = $notificationDetails['buyerfirstname']." ".$notificationDetails['buyerlastname'];
            
            $msgStr = "<a target='_blank' class='wk-mp-notification-entry-description-start' href='".$baseUrl."/readnotification/Index/Index?id=".$notification->getId()."&item_id=".$notification->getNotificationItemId()."&actual_item_id=".$productData->getId()."/#q-".$notification->getNotificationItemId()."'><span>".$name." responded to your question about ".$notificationDetails['productname']."</span></a>";
            $notificationDetails['notification_url'] = $msgStr;

            return $notificationDetails;
            break;
            case 'payment':
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationTo());
            $orderData = $this->getOrderById($notification->getNotificationItemId());
            $notificationDetails['orderIncrementId'] = $orderData->getIncrementId();
            $notificationDetails['state'] = $orderData->getState();
            $msgStr = "<a class='wk-mp-notification-entry-description-start' href='javascript:void(0)'><span>"." Payment received for Order #".$notificationDetails['orderIncrementId']."</span></a>";


            $notificationDetails['notification_url'] = $msgStr;

            return $notificationDetails;
            break;
            case 'seller_review':
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationFrom());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();
            $reviewFactory = $this->_objectManager->create('Webkul\Marketplace\Model\ResourceModel\Feedback\CollectionFactory');
            $collection = $reviewFactory->create()
            ->addFieldToFilter(
                'entity_id',
                $notification->getNotificationItemId()
            );
            $notificationDetails['review'] = $collection->getData();
            $name = $notificationDetails['firstname']." ".$notificationDetails['lastname'];

            
            
            $msgStr = "<a target='_blank' class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."ratings".$stringId."'><span>You have a new review from ".$name."</span></a>";


            $notificationDetails['notification_url'] = $msgStr;

            return $notificationDetails;
            break;
            case 'return_made':
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationFrom());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();
            $getsellerReturnItem = $this->getsellerReturnItem($notification->getNotificationItemId()); 
            
            $orderData = $this->getOrderById($getsellerReturnItem->getOrderId());
            $notificationDetails['orderIncrementId'] = $orderData->getIncrementId();
            $notificationDetails['state'] = $orderData->getState();
            $productData = $this->getProductById($getsellerReturnItem->getProductId());
            $notificationDetails['productname'] = $productData->getName();

            $senderName = $notificationDetails['firstname']." ".$notificationDetails['lastname'];
            
            // $msgStr = "<a target='_blank'  class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."order/".$orderData->getId()."/seller".$stringId."'><span>".$senderName." would like to return their purchase</span></a>";

            $msgStr = "<a target='_blank'  class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."return-detail/".$notification->getNotificationTo()."/".$notification->getNotificationItemId().$stringId."'><span>".$senderName." would like to return their purchase</span></a>";


            $notificationDetails['notification_url'] = $msgStr;
            
            return $notificationDetails;
            break;
            case 'return_offer':
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationFrom());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();
            $senderName = $notificationDetails['firstname']." ".$notificationDetails['lastname'];

            $getsellerReturnItem = $this->getsellerReturnItem($notification->getNotificationItemId()); 
            
            $orderData = $this->getOrderById($getsellerReturnItem->getOrderId());
            $notificationDetails['orderIncrementId'] = $orderData->getIncrementId();
            $notificationDetails['state'] = $orderData->getState();

            $productData = $this->getProductById($getsellerReturnItem->getProductId());
            $notificationDetails['productname'] = $productData->getName();
            
            $msgStr = "<a target='_blank'  class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."order/".$orderData->getId()."/buyer".$stringId."'><span>".$senderName." has made an offer on your requested return</span></a>";

            $notificationDetails['notification_url'] = $msgStr;

            return $notificationDetails;
            break;
            case 'return_offer_accepted':
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationFrom());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();
            $getsellerReturnItem = $this->getsellerReturnItem($notification->getNotificationItemId()); 
            $senderName = $notificationDetails['firstname']." ".$notificationDetails['lastname'];
            $orderData = $this->getOrderById($getsellerReturnItem->getOrderId());
            $notificationDetails['orderIncrementId'] = $orderData->getIncrementId();

            $notificationDetails['state'] = $orderData->getState();
            $productData = $this->getProductById($getsellerReturnItem->getProductId());
            $notificationDetails['productname'] = $productData->getName();
            $notificationDetails['notification_url'] = "<a href='#'>Notification Url</a>";
            //"Username" has accepted your offer
            $msgStr = "<a target='_blank' class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."order/".$orderData->getId()."/seller".$stringId."'><span>".$senderName." has accepted your offer</span></a>";

            $notificationDetails['notification_url'] = $msgStr;

            return $notificationDetails;
            break;
            case 'return_approved':
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationFrom());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();
            $getsellerReturnItem = $this->getsellerReturnItem($notification->getNotificationItemId()); 
            
            $orderData = $this->getOrderById($getsellerReturnItem->getOrderId());
            $notificationDetails['orderIncrementId'] = $orderData->getIncrementId();
            $notificationDetails['state'] = $orderData->getState();
            $productData = $this->getProductById($getsellerReturnItem->getProductId());
            $notificationDetails['productname'] = $productData->getName();
            //Your return on order #orderId has been approved
            $msgStr = "<a target='_blank' class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."order/".$orderData->getId()."/buyer".$stringId."'><span>"." Your return on order #".$notificationDetails['orderIncrementId']." has been approved</span></a>";

            $notificationDetails['notification_url'] = $msgStr;

            return $notificationDetails;
            break;
            case 'return_funded':
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationFrom());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();
            $getsellerReturnItem = $this->getsellerReturnItem($notification->getNotificationItemId()); 
            
            $orderData = $this->getOrderById($getsellerReturnItem->getOrderId());
            $notificationDetails['orderIncrementId'] = $orderData->getIncrementId();
            $notificationDetails['state'] = $orderData->getState();

            $productData = $this->getProductById($getsellerReturnItem->getProductId());
            $notificationDetails['productname'] = $productData->getName();
            $msgStr = "<a target='_blank' class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."order/".$orderData->getId()."/buyer".$stringId."'><span>"." Your return on order #".$notificationDetails['orderIncrementId']." has been processed</span></a>";

            $notificationDetails['notification_url'] = $msgStr;
            
            return $notificationDetails;
            break;
            case 'return_offer_rejected':
            $notificationDetails = $notification->getData();
            $customerData = $this->getCustomerById($notification->getNotificationFrom());
            $notificationDetails['firstname'] = $customerData->getFirstname();
            $notificationDetails['lastname'] = $customerData->getLastname();
            $getsellerReturnItem = $this->getsellerReturnItem($notification->getNotificationItemId()); 
            
            $orderData = $this->getOrderById($getsellerReturnItem->getOrderId());
            $notificationDetails['orderIncrementId'] = $orderData->getIncrementId();
               // $notificationDetails['state'] = $orderData->getState();.

            
            $productData = $this->getProductById($getsellerReturnItem->getProductId());
            $notificationDetails['productname'] = $productData->getName();
            $notificationDetails['notification_url'] = "<a href='#'>Notification Url</a>";

            $msgStr = "<a target='_blank' class='wk-mp-notification-entry-description-start' href='".$baseReactUrl."order/".$orderData->getId()."/buyer".$stringId."'><span>"." Your return on order #".$notificationDetails['orderIncrementId']." has been rejected</span></a>";
            
            $notificationDetails['notification_url'] = $msgStr;
                //echo '<pre>';print_r($notificationDetails);die;
            return $notificationDetails;
            break;
            default:
               // echo '<pre>';print_r($notificationDetails);die;
            return $notificationDetails;
            break;
        }
    }

    public function getCustomerById($customerId){
        $customer = $this->_customerRepositoryInterface->getById($customerId);
        return $customer;
    }
    public function getProductById($productId){
        $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($productId);
        return $product;
    }
    public function getOrderById($orderId){
        return $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);
    }
    public function getOfferById($offerId){
        $offer = $this->_offerFactory->create();
        $offer = $offer->load($offerId); 
        $product  = $this->getProductById($offer->getProductId());
        return $product;
    }

    public function getOfferDetails($offerId){
        $offer = $this->_offerFactory->create();
        $offer = $offer->load($offerId); 
        return $offer;
    }

    public function getAllProductNameByOrderId($orderId) {
        $orderDataModel = $this->_objectManager->get('Webkul\Marketplace\Model\Orders')->getCollection()->addFieldToFilter('order_id',array('eq' => $orderId));   

        $orderDatamodel = $orderDataModel->getFirstItem();

        $name = [];
        $productids = $orderDatamodel->getProductIds();
        $productid = explode(",", $productids);
        foreach($productid as $key => $value) {
            $productDetail = $this->getProductById($value);
            $name[] = $productDetail->getName();
        }
        return implode(",", $name);
    }

    public function getQuestionById($questionId) {
        //$notification->getNotificationItemId()
        $_questionFactory = $this->_objectManager->create('Webkul\Mpqa\Model\QuestionFactory');
        $collection = $_questionFactory
        ->create()->getCollection()
        ->addFieldToFilter('entity_id', $questionId);
        $collection = $collection->getFirstItem();
        return $collection;
    }   

    public function getAnswerById($answerId) {
        //echo $answerId;die;   
        $_answer = $this->_objectManager->create('Webkul\Mpqa\Model\MpqaanswerFactory');
        $collection = $_answer->create()->getCollection()->addFieldToFilter('answer_id', $answerId);

        return $collection->getFirstItem();
    }

    public function getsellerReturnItem($sellerReturnItemID) {
        $sellerReturnItem = $this->_objectManager->create('Seasia\Returnitem\Model\ReturnitemFactory');

        $collection = $sellerReturnItem->create()->getCollection()->addFieldToFilter('id', $sellerReturnItemID);
        return $collection->getFirstItem();
    }


    public function getBaseUrl() {
        $storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        return $storeManager->getStore()->getBaseUrl();
    }  
    public function getReactUrl() {
        
        $scopeConfig = $this->_objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');

        $configPath = 'customapi/general/appurl';
        $value =  $scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $value;
    } 
    public function getStringId($notification_id) {        
        if(!empty($notification_id)) {
            return '?n='.$notification_id;
        } 
        return;
    } 

    public function validateAddress($addressData){


        $region = $this->_objectManager->create('Magento\Directory\Model\ResourceModel\Region\Collection')
        ->addFieldToFilter('main_table.region_id', ['eq' => $addressData['state']])
        ->getFirstItem();
        
        $code = "";

        if($region->getCode()){
            $code = $region->getCode();
        }
        
        $input_xml = "<VERIFYADDRESS><COMMAND>ZIP1</COMMAND><SERIALNO>1255826</SERIALNO><PASSWORD>3wWwzq</PASSWORD><USER>1255826</USER><ADDRESS0 /><ADDRESS1>".$addressData['addLine1']."</ADDRESS1><ADDRESS2>".$addressData['addLine2']."</ADDRESS2><ADDRESS3>".$addressData['city'].", ".$code." ". $addressData['zip']."</ADDRESS3></VERIFYADDRESS>";

        $url = "http://www.dial-a-zip.com/XML-Dial-A-ZIP/DAZService.asmx/MethodZIPValidate";
        $ch = curl_init();
        $headers = [ 
            "Content-type: text/xml;charset=\"utf-8\"", 
            "Accept: text/xml", 
            "Content-length: ".strlen($input_xml)
        ]; 

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_URL, $url."?input=" . urlencode($input_xml));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if (curl_error($ch)) {
            echo 'error:' . curl_error($ch);
        }
        $data = curl_exec($ch);
        curl_close($ch);


        $array_data = json_decode(json_encode(simplexml_load_string($data)), true);
        return $array_data;

    }

    public function getSellerShopById($sellerId){
        $marketplaceHelper = $this->_objectManager->create('Webkul\Marketplace\Helper\Data');
        $partner = $marketplaceHelper->getSellerDataBySellerId($sellerId)->getFirstItem();

        $response = array();

        $response['username'] = $partner->getShopUrl();
        $response['shopUrl'] = $marketplaceHelper ->getRewriteUrl(
            'marketplace/seller/collection/shop/'.
            $partner->getShopUrl()
        );
        return $response;
    }
}