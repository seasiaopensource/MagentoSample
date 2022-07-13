<?php
namespace Seasia\Customapi\Model;
use Seasia\Customapi\Api\OfferInterface;
use Seasia\Selleroffer\Model\OfferFactory;

class Offer implements OfferInterface
{

    /**
     * Return data.
     *
     * @api
     */
    protected $dataFactory;
    protected $_objectManager;
    protected $_offerFactory;
    protected $_storemanager;
    protected $_productModel;
    protected $_marketplacehelper;
    protected $_orderModel;
    protected $_pricehelper;
    protected $_transportBuilder;
    protected $_inlineTranslation;
    protected $scopeConfig;
    protected $offerhelper;
    protected $_customApiHelper;
    public function __construct(
        \Seasia\Customapi\Api\Data\ProductdataInterfaceFactory $dataFactory,
        OfferFactory $offerFactory,
        \Magento\Store\Model\StoreManagerInterface $storemanager,
        \Magento\Catalog\Model\Product $productmodel,
        \Webkul\Marketplace\Helper\Data $marketplacehelper,
        \Magento\Sales\Model\Order $ordermodel,
        \Magento\Framework\Pricing\Helper\Data $pricehelper,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Seasia\Selleroffer\Helper\Data $offerhelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->dataFactory = $dataFactory;
        $this->_offerFactory = $offerFactory;
        $this->_storemanager = $storemanager;
        $this->_productModel = $productmodel;
        $this->_marketplacehelper = $marketplacehelper;
        $this->_orderModel = $ordermodel;
        $this->_pricehelper = $pricehelper;
        $this->_transportBuilder  = $transportBuilder;
        $this->_inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;

        $this->offerhelper = $offerhelper;
        $this->_customApiHelper = $this->_objectManager->create('Seasia\Customapi\Helper\Data');
        $this->_countryFactory = $this->_objectManager->get('\Magento\Directory\Model\CountryFactory');
        $this->_marketplaceHelper = $this->_objectManager->get('Webkul\Marketplace\Helper\Data');

        $this->_storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');




    }



    // Get Customer Offer Made Data

    public function offers($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr, $getData){
        try {
            $response = array();
            $responseArray = array();
            $offerModel = $this->_offerFactory->create();
            $key = "";
            if($getData == "offermade"){
                $key = "buyer_id";
            }else{
                $key = "seller_id";
            }


            $offerCollection = $offerModel->getCollection()
            ->addFieldToFilter($key, array('eq' => $sellerId))
            ;

            $offerCollection->getSelect()->where("(offer_used = '1' OR status = 'counter_offer') AND status != 'counter'");

            // $joinTable = $this->_objectManager->create(
            //     'Webkul\Agorae\Model\ResourceModel\Mpfavouriteseller\Collection'
            //     )->getTable('customer_entity');


            $joinTable = $this->_objectManager->create(
                'Webkul\Agorae\Model\ResourceModel\Mpfavouriteseller\Collection'
            )->getTable('marketplace_userdata');



            if($getData == "offermade"){
                $offerCollection->getSelect()->joinLeft(
                    $joinTable.' as cgf',
                    'main_table.seller_id = cgf.seller_id',
                    array('shop_url as username')
                );
            }else{
                $offerCollection->getSelect()->joinLeft(
                    $joinTable.' as cgf',
                    'main_table.buyer_id = cgf.seller_id',
                    array('shop_url as username')
                );
            }



            $eavAttribute = $this->_objectManager->get(
                'Magento\Eav\Model\ResourceModel\Entity\Attribute'
            );
            $proAttId = $eavAttribute->getIdByCode('catalog_product', 'name');

            $catalogProductEntityVarchar = $this->_objectManager->create(
                'Webkul\Marketplace\Model\ResourceModel\Product\Collection'
            )->getTable('catalog_product_entity_varchar');


            $offerCollection->getSelect()->joinLeft($catalogProductEntityVarchar.' as cpev','main_table.product_id = cpev.entity_id', array('value as productName'))
            ->where('cpev.attribute_id = '.$proAttId);

            if($searchStr != ""){
                $offerCollection->getSelect()->where(
                    'shop_url like "%'.$searchStr.'%"  OR value like "%'.$searchStr.'%" OR offer_amount like "%'.$searchStr.'%" OR status like "%'.$searchStr.'%"'
                );
            }

            $offerCollection->setPageSize($length)->setCurPage($pageNum);
            $offerCollection->setOrder($orderBy, $orderDir);
            $totalCount = $offerCollection->getSize();

            //echo $offerCollection->getSelect();

            //die("DDDDDDDDDDD");

            $productArr = [];
            $i = 0;
            $j = 0;
            foreach($offerCollection as $collection){
                $partner = $this->_marketplacehelper->getSellerDataBySellerId($collection->getBuyerId())->getFirstItem();

                $seller = $this->_marketplacehelper->getSellerDataBySellerId($collection->getSellerId())->getFirstItem();

                $responseArray[$j] = $collection->getData();


                $now = date('Y-m-d H:i:s');
                $expiredDate = $this->offerhelper->getExpiredDate($collection->getCreatedAt());
                $t1 = strtotime( $expiredDate );
                $t2 = strtotime($now);

                $diff = $t1 - $t2;
                $hours = $diff / ( 60 * 60 );

                if($hours > 0){
                    $status = $responseArray[$j]['status'];
                }else{
                    $status = "expired";
                }
                $responseArray[$j]['status'] = $status;

                $productIds = explode(",",$collection->getProductId());
                if(count($productIds) > 0){
                    $productNameArray = array();
                    foreach ($productIds as $productId) {
                        $productArr[$productId][$i] = $collection->getId();
                        $i++;
                        $product = $this->_productModel->load($productId);
                        array_push($productNameArray, $product->getName());
                    }
                    $responseArray[$j]['productName'] = implode(",", $productNameArray);
                    $responseArray[$j]['shop_url'] = $this->_marketplacehelper->getRewriteUrl(
                        'marketplace/seller/collection/shop/'.
                        $seller->getShopUrl()
                    );
                    $responseArray[$j]['expired_at'] = $this->offerhelper->getExpiredDate($collection->getCreatedAt());

                }
                $j++;
            }
            //echo '<pre>'; print_r($responseArray);die;
            $finalArray = [];
            $idArray = [];
            $incr = 0;
            // if(!empty($productArr)) {
            //     foreach($responseArray as $val) {
            //         if(max($productArr[$val['product_id']]) == $val['id'] && !in_array($val['id'],$idArray)) {
            //             $idArray[$incr++] = $val['id'];
            //             array_push($finalArray, $val);
            //         }
            //     }
            // }
            //echo "<pre>"; print_r($responseArray);

            //die();
            $response['offers'] = $responseArray;
            $response['totalCount'] = $incr;
            return $this->getResponseFormat($response);
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }


    public function offersReceived($sellerId, $pageNum, $length, $orderBy, $orderDir,$searchStr){
        try{

            $response = array();
            $responseArray = array();
            $offerModel = $this->_offerFactory->create();

            $offerCollection = $offerModel->getCollection()
            ->addFieldToFilter('main_table.seller_id', array('eq' => $sellerId))
            //->addFieldToFilter('expired', array('eq' => '0'))
            //->addFieldToFilter('offer_used', array('eq' => '1'))
            ;
            $offerCollection->getSelect()->where("(offer_used = '1' OR status = 'counter_offer') AND status != 'counter'");


            $eavAttribute = $this->_objectManager->get(
                'Magento\Eav\Model\ResourceModel\Entity\Attribute'
            );
            $proAttId = $eavAttribute->getIdByCode('catalog_product', 'name');

            $catalogProductEntityVarchar = $this->_objectManager->create(
                'Webkul\Marketplace\Model\ResourceModel\Product\Collection'
            )->getTable('catalog_product_entity_varchar');

            $sellerjoinTable = $this->_objectManager->create(
                'Webkul\Agorae\Model\ResourceModel\Mpfavouriteseller\Collection'
            )->getTable('marketplace_userdata');

            $offerCollection->getSelect()->joinLeft($catalogProductEntityVarchar.' as cpev','main_table.product_id = cpev.entity_id', array('value as productName'))
            ->where('cpev.attribute_id = '.$proAttId);

            $offerCollection->getSelect()->joinLeft(
                $sellerjoinTable.' as sgf',
                'main_table.buyer_id = sgf.seller_id',
                array('shop_url as username')
            );

            if($searchStr != ""){
                $offerCollection->getSelect()->where(
                    ' value like "%'.$searchStr.'%" OR status like "%'.$searchStr.'%" OR shop_url like "%'.$searchStr.'%" '
                );
            }


            $offerCollection ->getSelect()
            ->columns('COUNT(*) AS offerCount')
            ->group('product_id');

            $offerCollection->setPageSize($length)->setCurPage($pageNum);
            $offerCollection->setOrder($orderBy, $orderDir);

            $totalCount = $offerCollection->getSize();
            $allOffers = array();
            $productOfferArray = array();
            $productsArray = array();
            foreach ($offerCollection as $eachOffer) {

                $product = $this->_productModel->load($eachOffer->getProductId());

                $eachOfferCollection = $offerModel->getCollection()
                ->addFieldToFilter('main_table.seller_id', array('eq' => $sellerId))
                ->addFieldToFilter('product_id', array('eq' => $eachOffer->getProductId()))
                ->addFieldToFilter('expired', array('eq' => '0'))
                //->addFieldToFilter('offer_used', array('eq' => '1'))
                ;
                $eachOfferCollection->getSelect()->where("offer_used = '1' OR status = 'counter_offer'");
                $eachOfferCollection ->getSelect()
                ->columns('max(offer_amount) AS offer_amount')

                ->group('product_id');

                $eachOfferExpireCollection = $offerModel->getCollection()
                ->addFieldToFilter('main_table.seller_id', array('eq' => $sellerId))
                ->addFieldToFilter('product_id', array('eq' => $eachOffer->getProductId()))
                ->addFieldToFilter('expired', array('eq' => '0'))
                //->addFieldToFilter('offer_used', array('eq' => '1'))
                ;
                $eachOfferExpireCollection->getSelect()->where("offer_used = '1' OR status = 'counter_offer'");
                $eachOfferExpireCollection ->getSelect()
                ->columns('max(main_table.created_at) AS max_created_at')
                ->group('product_id');

                $now = date('Y-m-d H:i:s');
                $activeCollection = $offerModel->getCollection()
                ->addFieldToFilter('main_table.seller_id', array('eq' => $sellerId))
                ->addFieldToFilter('product_id', array('eq' => $eachOffer->getProductId()))
                ->addFieldToFilter('expired', array('eq' => '0'))
                ->addFieldToFilter('created_at', array('gt' => date('Y-m-d H:i:s', strtotime('-2 day'))))
                ;
                $activeCollection->getSelect()->where("(offer_used = '1' OR status = 'counter_offer') AND status = 'pending' ");

                $eachOfferArray = array();
                $eachOfferArray = $eachOffer->getData();
                $eachOfferArray['shop_url'] = $this->_marketplacehelper->getRewriteUrl(
                    'marketplace/seller/collection/shop/'.
                    $eachOffer->getUsername()
                );
                $offerAmountObj = $eachOfferCollection->getFirstItem();
                $eachOfferArray['offer_amount'] = $offerAmountObj->getOfferAmount();

                $offerCreatedObj = $eachOfferExpireCollection->getFirstItem();
                $eachOfferArray['created_at'] = $offerCreatedObj->getMaxCreatedAt();

                $eachOfferArray['product_price'] = $product->getPriceInfo()->getPrice('final_price')->getValue();

                $expiredDate = $this->offerhelper->getExpiredDate($eachOfferArray['created_at']);

                $t1 = strtotime( $expiredDate );
                $t2 = strtotime($now);
                $diff = $t1 - $t2;
                $hours = $diff / ( 60 * 60 );

                if($hours < 1 && $hours > 0){
                    $hours = $diff / ( 60 );
                    $hours = round($hours). " Mins";
                }elseif($hours > 1 && $hours < 48){
                    $hours = round($hours). " Hours";
                }else{
                    $hours = "Expired";
                }
                $eachOfferArray['expired_at'] = $hours;
                $eachOfferArray['activeCount'] = $activeCollection->getSize();
                array_push($allOffers, $eachOfferArray);
            }





            $response['receivedoffers'] = $allOffers;
            $response['receivedTotalCount'] = $totalCount;

            return $this->getResponseFormat($response);
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    //Get Offer Made Details By Offer Id

    public function offermade($sellerId, $offerId){
        try{
            $response = array();
            $offerModel = $this->_offerFactory->create();
            $store = $this->_storemanager->getStore();
            $offerCollection = $offerModel->getCollection()
            ->addFieldToFilter('buyer_id', array('eq' => $sellerId))
            ->addFieldToFilter('id', array('eq' => $offerId))
            //->addFieldToFilter('expired', array('eq' => '0'))
            //->addFieldToFilter('offer_used', array('eq' => '1'))
            ;
            $offerCollection->getSelect()->where("offer_used = '1' OR status = 'counter_offer'");
            if($offerCollection->getSize() > 0){
                $products = array();
                $offer = $offerCollection->getFirstItem();

                $response = $offer->getData();
                $productIds = explode(",",$offer->getProductId());
                foreach($productIds as $productId){
                    $product = $this->_productModel->load($productId);
                    $eachProduct = array();
                    $eachProduct['productId'] = $product->getId();
                    $eachProduct['name'] = $product->getName();
                    $eachProduct['description'] = $product->getDescription();
                    $eachProduct['price'] = $product->getPriceInfo()->getPrice('regular_price')->getValue();
                    $eachProduct['imageUrl'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();

                    $eachProduct['productUrl'] =  $product->getProductUrl();

                    array_push($products, $eachProduct);
                }

                $partner = $this->_marketplacehelper->getSellerDataBySellerId($offer->getSellerId())->getFirstItem();

                if ($partner->getLogoPic()) {
                    $logoPic = $this->_marketplacehelper->getMediaUrl().'avatar/'.$partner->getLogoPic();
                } else {
                    $logoPic = $this->_marketplacehelper->getMediaUrl().'avatar/noimage.png';
                }
                $shopUrl = $this->_marketplacehelper->getRewriteUrl(
                    'marketplace/seller/collection/shop/'.
                    $partner->getShopUrl()
                );
                $response['products'] = $products;
                $response['seller']['firstname'] = $partner->getFirstname();
                $response['seller']['lastname'] = $partner->getLastname();
                $response['seller']['email'] = $partner->getEmail();
                $response['seller']['logo_image'] = $logoPic;

                $response['seller']['username'] = $partner->getShopUrl();
                $response['seller']['shopUrl'] = $shopUrl;
            }

            return $this->getResponseFormat($response);
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    //Get Offer Received Details By Offer Id

    public function offerreceivedbyid($sellerId, $offerId){
        try{
            $response = array();
            $response['products'] = array();
            $response['buyer'] = array();
            $offerModel = $this->_offerFactory->create();
            $store = $this->_storemanager->getStore();
            $offerCollection = $offerModel->getCollection()
            ->addFieldToFilter('seller_id', array('eq' => $sellerId))
            ->addFieldToFilter('product_id', array('eq' => $offerId))
            //->addFieldToFilter('expired', array('eq' => '0'))
            //->addFieldToFilter('offer_used', array('eq' => '1'))
            ;
            $offerCollection->getSelect()->where("(offer_used = '1' OR status = 'counter_offer') AND status != 'counter' ");


            if($offerCollection->getSize() > 0){
                $productsArray = array();


                $productData = array();
                $product = $this->_productModel->load($offerId);
                $productData['name'] = $product->getName();
                $productData['description'] = $product->getDescription();
                $productData['original_price'] = $product->getCost();
                $productData['price'] = $product->getPriceInfo()->getPrice('regular_price')->getValue();
                $productData['imageUrl'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();

                $productData['productUrl'] = $product->getProductUrl();

                array_push($productsArray, $productData);

                $buyerCollection = $offerModel->getCollection()
                ->addFieldToFilter('main_table.seller_id', array('eq' => $sellerId))
                ->addFieldToFilter('product_id', array('eq' => $offerId))
                //->addFieldToFilter('offer_used', array('eq' => '1'))
                ;
                $buyerCollection->getSelect()->where("(offer_used = '1' OR status = 'counter_offer') AND status != 'counter'");
                $joinTable = $this->_objectManager->create(
                    'Webkul\Agorae\Model\ResourceModel\Mpfavouriteseller\Collection'
                )->getTable('customer_entity');

                $buyerCollection->getSelect()->join(
                    $joinTable.' as cgf',
                    'main_table.buyer_id = cgf.entity_id',
                    array('firstname','lastname')
                );

                $sellerjoinTable = $this->_objectManager->create(
                    'Webkul\Agorae\Model\ResourceModel\Mpfavouriteseller\Collection'
                )->getTable('marketplace_userdata');

                $buyerCollection->getSelect()->joinLeft(
                    $sellerjoinTable.' as sgf',
                    'main_table.buyer_id = sgf.seller_id',
                    array('shop_url as username')
                );
                $buyerCollection->setOrder("offer_amount", "desc");



                $allBuyers = array();
                foreach ($buyerCollection as $eachBuyer) {
                    $partner = $this->_marketplacehelper->getSellerDataBySellerId($eachBuyer->getBuyerId())->getFirstItem();
                    $eachBuyerArray = array();
                    $eachBuyerArray = $eachBuyer->getData();

                    $now = date('Y-m-d H:i:s');
                    $expiredDate = $this->offerhelper->getExpiredDate($eachBuyerArray['created_at']);
                    $t1 = strtotime( $expiredDate );
                    $t2 = strtotime($now);

                    $diff = $t1 - $t2;
                    $hours = $diff / ( 60 * 60 );

                    if($hours > 0){
                        $status = $eachBuyerArray['status'];
                    }else{
                        $status = "expired";
                    }
                    $eachBuyerArray['status'] = $status;
                    $eachBuyerArray['shop_url'] ="";
                    if($partner->getId()){
                        $eachBuyerArray['shop_url'] = $this->_marketplacehelper->getRewriteUrl(
                            'marketplace/seller/collection/shop/'.
                            $partner->getShopUrl()
                        );
                    }

                    $eachBuyerArray['expired_at'] = $this->offerhelper->getExpiredDate($eachBuyer->getCreatedAt());


                    array_push($allBuyers, $eachBuyerArray);
                }


                $response['products'] = $productsArray;
                $response['buyer'] = $allBuyers;



            }
            return $this->getResponseFormat($response);
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }


    // Mark Offfer as Accepted

    public function acceptOfferById($sellerId, $offerId){

        try{
            $response = array();
            $offerHelper = $this->_objectManager->create(
                'Seasia\Selleroffer\Helper\Data'
            );

            if($offerHelper->acceptOfferByOfferId($sellerId, $offerId)){

                $offer = $this->getOfferById($offerId);
                if($this->sendOfferMail("accepted", $offer)){
                    // Send Notification To Buyer
                    $notification_helper = $this->_objectManager->create(
                        'Seasia\Customnotifications\Helper\Data'
                    );
                    $offerId = $offer->getId();
                    $type = "offer_accepted";
                    $buyerId = $offer->getBuyerId();
                    $sellerId = $offer->getSellerId();
                    $message = 'Test';

                    $notification_helper->setNotification($offerId,$type,$buyerId,$sellerId,$message);

                    $response['status'] = "Success";
                    $response['message'] = "Offer accepted successfully.";
                }else{
                    $response['status'] = "Error";
                    $response['message'] = "Email not sent.";
                }

            }else{
                $response['status'] = "Error";
                $response['message'] = "Offer not accepted successfully.";
            }

            return $this->getResponseFormat($response);
        }
        catch(\Exception $e) {
            return $this->errorMessage($e);
        }

    }
    protected function getOfferById($offerId){
        $offerModel = $this->_offerFactory->create();
        $offer = $offerModel->load($offerId);
        return $offer;
    }
    protected function sendOfferMail($acceptReject, $offer){
        try{
            $response = array();



            $mail_to_admin=$this->scopeConfig->getValue(
                'mpqa/general_settings/admin_email',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $mail_to_seller=$this->scopeConfig->getValue(
                'mpqa/general_settings/seller_email',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            $adminStoremail = $this->_marketplacehelper->getAdminEmailId();
            $adminEmail=$adminStoremail? $adminStoremail:$this->_marketplacehelper->getDefaultTransEmailId();

            $adminUsername = 'Oh Frock Support';

            $customerObj = $this->_objectManager->create('Magento\Customer\Model\Customer')
            ->load($offer->getBuyerId());
            //$customer_name = $customerObj->getFirstname()." ".$customerObj->getLastname();
            $customer_email = $customerObj->getEmail();

            $customerPartner = $this->_marketplacehelper->getSellerDataBySellerId($offer->getBuyerId())->getFirstItem();

            $customer_name = $customerPartner->getShopUrl();

            $seller = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($offer->getSellerId());

            //$seller_name=$seller->getFirstname()." ".$seller->getLastname();
            $seller_email=$seller->getEmail();

            $sellerPartner = $this->_marketplacehelper->getSellerDataBySellerId($offer->getSellerId())->getFirstItem();

            $seller_name = $sellerPartner->getShopUrl();



            $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($offer->getProductId());
            $product_name = $product->getName();

            $offerAmount = $this->_pricehelper->currency(number_format($offer->getOfferAmount(),2),true,false);
            $offerLink = "";
            if($acceptReject == "countered"){
                $offerModel = $this->_offerFactory->create();
                $offerCollection = $offerModel->getCollection()
                ->addFieldToFilter('counter_offer_id', array('eq' => $offer->getId()))
                ->getFirstItem()
                ;

                $offerAmount = $this->_pricehelper->currency(number_format($offerCollection->getOfferAmount(),2),true,false);


                $offerHref = $this->_customApiHelper->getReactUrl()."offer/".$offerCollection->getId()."/made";
                $msg= "<a href='https://www.ohfrock.com/marketplace/seller/collection/shop/".$seller_name."'>@".$seller_name."</a> has countered your offer on their ".$product->getName().". Their counter offer is ".$offerAmount;
                $action = "Click <a href='".$offerHref."'>here</a> to accept their offer or counter back.";
            }else{
                $offerHref = $this->_customApiHelper->getReactUrl()."offer/".$offer->getId()."/made";
                $msg= "<a href='https://www.ohfrock.com/marketplace/seller/collection/shop/".$seller_name."'>@".$seller_name."</a> has ".$acceptReject." your ".$offerAmount." offer on their ".$product->getName();
                $action="";
            }

            switch ($acceptReject) {
              case "rejected" :
                $intro="Bad News";
                break;
              case "countered" :
                $intro="Things Just Got Interesting";
                break;
              default :
                $intro="Congratulations!";
                break;
            }
            $productImage = $this->_storemanager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
            $templateVars = [
                'store' => $this->_storemanager->getStore(),
                'customer_name' => $customer_name,
                'seller_name'   => $seller_name,
                'link'          =>  $product->getProductUrl(),
                'product_name'  => $product_name,
                'intro'         => $intro,
                'message'       => $msg,
                'action'        => $action,
                "offer_link"    => $offerHref,
                "product_image" => $productImage
            ];

            $to = [$customer_email];


            $from = ['email' => $adminEmail, 'name' => 'Oh Frock'];
            $templateOptions = ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->_storemanager->getStore()->getId()];

            $this->_inlineTranslation->suspend();



            $transport = $this->_transportBuilder->setTemplateIdentifier('seller_offer_accept_reject')
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($templateVars)
            ->setFrom($from)
            ->addTo($to)
            ->getTransport();
            $transport->sendMessage();
            $this->_inlineTranslation->resume();
            return true;

        } catch (\Exception $e){
            $this->_inlineTranslation->resume();
            return false;
        }
    }

    public function rejectOfferById($sellerId, $offerId){
        try{
            $response = array();
            $offerHelper = $this->_objectManager->create(
                'Seasia\Selleroffer\Helper\Data'
            );

            if($offerHelper->rejectOfferByOfferId($sellerId, $offerId, "rejected")){
                $offer = $this->getOfferById($offerId);
                if($this->sendOfferMail("rejected", $offer)){
                    // Send Notification To Buyer
                    $notification_helper = $this->_objectManager->create(
                        'Seasia\Customnotifications\Helper\Data'
                    );
                    $offerId = $offer->getId();
                    $type = "offer_rejected";
                    $buyerId = $offer->getBuyerId();
                    $sellerId = $offer->getSellerId();
                    $message = 'Test';

                    $notification_helper->setNotification($offerId,$type,$buyerId,$sellerId,$message);

                    $response['status'] = "Success";
                    $response['message'] = "Offer rejected successfully.";
                }else{
                    $response['status'] = "Error";
                    $response['message'] = "Email not sent.";
                }
            }else{
                $response['status'] = "Error";
                $response['message'] = "Offer not rejected successfully.";
            }

            return $this->getResponseFormat($response);
        }
        catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    public function counterOfferById($sellerId, $offerId, $offerAmount, $comment){
        try{
            $response = array();
            $offerHelper = $this->_objectManager->create(
                'Seasia\Selleroffer\Helper\Data'
            );

            if($offerHelper->rejectOfferByOfferId($sellerId, $offerId,"counter")){
                $offer = $this->getOfferById($offerId);


                $offerCollection = $this->_offerFactory->create();
                $offerCollection->setProductId($offer->getProductId());
                $offerCollection->setSellerId($offer->getSellerId());
                $offerCollection->setBuyerId($offer->getBuyerId());
                $offerCollection->setOrderId(null);
                $offerCollection->setOfferAmount($offerAmount);
                $offerCollection->setStatus('counter_offer');
                $offerCollection->setComment($comment);
                $offerCollection->setCreatedAt(date("Y-m-d H:i:s"));
                $offerCollection->setStripeToken(null);
                $offerCollection->setExpired(null);
                $offerCollection->setOfferUsed(null);
                $offerCollection->setOfferType('productOffer');
                $offerCollection->setCounterOfferId($offer->getId());
                $offerCollection->save();
                if($this->sendOfferMail("countered", $offer)){
                    // Send Notification To Buyer
                    $notification_helper = $this->_objectManager->create(
                        'Seasia\Customnotifications\Helper\Data'
                    );
                    $offerId = $offer->getId();
                    $type = "counter_offer";
                    $buyerId = $offer->getBuyerId();
                    $sellerId = $offer->getSellerId();
                    $message = 'Test';

                    $notification_helper->setNotification($offerId,$type,$buyerId,$sellerId,$message);

                    $response['status'] = "Success";
                    $response['message'] = "Counter Offer successfully.";
                }else{
                    $response['status'] = "Error";
                    $response['message'] = "Email not sent.";
                }
            }else{
                $response['status'] = "Error";
                $response['message'] = "Offer not countered successfully.";
            }

            return $this->getResponseFormat($response);
        }
        catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    /**
     * Set Offer Discount By Product Id
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $productId Product Id
     * @param string $offerAmount Offer Amount
     * @param string $offer_comment Comment
     * @return array
    */

    public function setSellerOfferDiscountByProductId($sellerId,$productId,$offerAmount, $offer_comment) {
        try {
            $rmaHelper = $this->_objectManager->create('Webkul\MpRmaSystem\Helper\Data');
            $productSellerId = $rmaHelper->getSellerIdByproductid($productId);
            $response = [];
            $i = 0;
            if($sellerId == $productSellerId) {
                $likesByProductId = $this->_objectManager->create('Seasia\Customapi\Helper\Data')->getLikesByProductId($productId);

                //echo '<pre>'; print_r($likesByProductId);die;
                if(is_array($likesByProductId)) {
                    foreach($likesByProductId as $value) {
                        $offerCollection = $this->_offerFactory->create();
                        $offerCollection->setProductId($productId);
                        $offerCollection->setSellerId($sellerId);
                        $offerCollection->setBuyerId($value['entity_id']);
                        $offerCollection->setOrderId(null);
                        $offerCollection->setOfferAmount($offerAmount);
                        $offerCollection->setStatus('pending');
                        $offerCollection->setComment($offer_comment);
                        $offerCollection->setCreatedAt(date("Y-m-d H:i:s"));
                        $offerCollection->setStripeToken(null);
                        $offerCollection->setExpired(null);
                        $offerCollection->setOfferUsed(null);
                        $offerCollection->setOfferType('productOffer');
                        $offerCollection->save();
                        if($offerCollection->save()) {
                            $offerId = $offerCollection->getId();
                            $offer = $this->getOfferById($offerId);
                            if($this->sendOfferMail("sent", $offer)) {

                                $notification_helper = $this->_objectManager->create(
                                    'Seasia\Customnotifications\Helper\Data'
                                );

                                $offerId = $offer->getId();
                                $type = "offer_received";
                                $buyer = $offer->getBuyerId();
                                $seller = $offer->getSellerId();
                                $message = 'Test';

                                $notification_helper->setNotification($offerId,$type,$buyer,$seller,$message);
                                // Send Notification To Buyer
                                $i++;
                            }
                        }
                    }
                }
                $response['status'] = "Success ";
                $response['message'] = "Discount set to offer successfully";
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


    public function shippingRates($customerEmail,$addressId,$productId, $offerAmount){
        try{
            $store = $this->_storemanager->getStore();
            $customerFactory = $this->_objectManager->get('Magento\Customer\Model\CustomerFactory');
            $_product = $this->_objectManager->create('\Magento\Catalog\Model\ProductFactory');
            $quoteManagement = $this->_objectManager->create('\Magento\Quote\Model\QuoteManagement');
            $orderModel = $this->_objectManager->create('Magento\Sales\Model\Order');
            $quoteObj = $this->_objectManager->create('\Magento\Quote\Model\QuoteFactory');
            $customerRepository = $this->_objectManager->create('\Magento\Customer\Api\CustomerRepositoryInterface');
            $shippingRates = array();

            $websiteId = $this->_storemanager->getStore()->getWebsiteId();
            $customer = $customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($customerEmail);

            if($customer->getEntityId()){
                $addresses = $customer->getAddresses();
                foreach($addresses as $eachAddress){
                    if($eachAddress->getId() == $addressId){

                        $quote=$quoteObj->create();
                        $quote->setStore($store);
                        $customer= $customerRepository->getById($customer->getEntityId());
                        $quote->setCurrency();
                        $quote->assignCustomer($customer);
                        $productIdArray = explode(",",$productId);
                        foreach($productIdArray as $productid){
                            $product = $_product->create()->setStoreId(1)->load($productid);
                            $product->setCustomPrice($offerAmount);
                            $product->setCustomOriginalPrice($offerAmount);
                            $quote->addProduct($product,1);
                        }

                        $quote->getBillingAddress()->addData($eachAddress->getData());
                        $quote->getShippingAddress()->addData($eachAddress->getData());

                        $shippingAddress=$quote->getShippingAddress();
                        $shippingAddress->setCollectShippingRates(true)->collectShippingRates();



                        foreach ($shippingAddress->getAllShippingRates() as $rate) {
                            array_push($shippingRates, $rate->getData());
                        }

                    }
                }

            }
            return $this->getResponseFormat($shippingRates);
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }

    }

    public function offerPayment($customerId,$customerEmail,$post){
        try{
            $post['notification_type'] = "counter_offer_paid";
            $response = $this->offerhelper->makeOfferPayment($customerId,$customerEmail,$post);
            return $this->getResponseFormat($response);
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }


    public function offerHistory($sellerId,$offerId){
        try{
            $statusArray = array(
                "offer_received"=>"offer_made",
                "counter_offer"=>"counter_offer",
                "counter_offer_paid" => "counter_offer_paid",
                "offer_accepted" => "offer_accepted",
                "offer_rejected" => "offer_rejected"
            );
            $collection = $this->_objectManager->create('Seasia\Customnotifications\Model\Notifications');
            $collection = $collection->getCollection()->addFieldToFilter('notification_item_id',array('in' => $offerId));

            $collection->setOrder('entity_id', 'DESC');

            $response = array();

            foreach($collection as $eachMessage){
                $message = array();
                $sender = $this->_marketplacehelper->getSellerDataBySellerId($eachMessage->getNotificationFrom())
                ->getFirstItem();
                $receiver = $this->_marketplacehelper->getSellerDataBySellerId($eachMessage->getNotificationTo())
                ->getFirstItem();
                if(isset($statusArray[$eachMessage->getType()])){
                    $message['senderName'] = $sender->getShopUrl();
                    $message['receiverName'] = $receiver->getShopUrl();
                    $message['type'] = $statusArray[$eachMessage->getType()];
                    $message['created_at'] = $eachMessage->getCreatedAt();



                    if($eachMessage->getType() == "counter_offer"){
                        $originalOffer = $this->getOfferById($eachMessage->getNotificationItemId());

                        $offerModel = $this->_offerFactory->create();
                        $offerCollection = $offerModel->getCollection()
                        ->addFieldToFilter('counter_offer_id', array('eq' => $eachMessage->getNotificationItemId()))
                        ->getFirstItem()
                        ;
                        $offerData = $offerCollection;
                    }else{
                        $offerData = $this->getOfferById($eachMessage->getNotificationItemId());
                    }
                    $message['offerData'] = $offerData->getData();
                    switch ($message['type']) {
                        case 'offer_made':
                        $message['message'] = "Made an offer for $". sprintf("%.2f", $message['offerData']['offer_amount']);
                        break;
                        case 'counter_offer':
                        $message['message'] = "Counter Offer of $". sprintf("%.2f", $message['offerData']['offer_amount']);
                        break;
                        case 'counter_offer_paid':
                        $message['message'] = "Counter Offer Paid for $". sprintf("%.2f", $message['offerData']['offer_amount']);
                        break;
                        case 'offer_accepted':
                        $message['message'] = "Accepted Offer for $". sprintf("%.2f", $message['offerData']['offer_amount']);
                        break;
                        case 'offer_rejected':
                        $message['message'] = "Rejected Offer for $". sprintf("%.2f", $message['offerData']['offer_amount']);
                        break;

                        default:

                        break;
                    }


                    array_push($response, $message);
                }

            }

            return $this->getResponseFormat($response);
        } catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }


    public function checkOrderInvoice(){
        $orderModel = $this->_objectManager->create('Magento\Sales\Model\Order');
        $order = $orderModel->load(1239);


        foreach ($order->getInvoiceCollection() as $invoicedetail)
        {
            $invoice = $invoicedetail;
        }

        if($invoice->getId() && count($order->getInvoiceCollection()) > 0){


            $shippingaddress = $order->getShippingAddress();
            $billingaddress  = $order->getBillingAddress();
            $shippingresult = $shippingaddress->getData();
            $CountryId =  $shippingaddress['country_id'];
            $telephone =  $shippingaddress['telephone'];

            $CountryFactory = $this->_objectManager->get('\Magento\Directory\Model\CountryFactory');

            $country = $CountryFactory->create()->loadByCode($CountryId);

            $shippingname = $shippingresult['firstname'].' '.$shippingresult['lastname'];
            $shippingstreet = $shippingresult['street'];
            $shippingaddress = $shippingresult['city'].', '.$shippingresult['region'].', '.$shippingresult['postcode'];



            $billingresult = $billingaddress->getData();
            $billingaddressCountryId =  $billingaddress['country_id'];
            $billingaddresstelephone =  $billingaddress['telephone'];

            $billingname = $billingresult['firstname'].' '.$billingresult['lastname'];
            $billingstreet = $billingresult['street'];
            $billingaddress = $billingresult['city'].', '.$billingresult['region'].', '.$billingresult['postcode'];


            $adminStoremail = $this->_marketplaceHelper->getAdminEmailId();
            $defaultTransEmailId = $this->_marketplaceHelper->getDefaultTransEmailId();
            $adminEmail = $adminStoremail ? $adminStoremail : $defaultTransEmailId;

            $to = [$adminEmail];

            $templateVars = [
                'store' => $this->_storeManager->getStore(),
                'message'   => "Testing Offer Email",
                'order' => $order,
                'shippingname' => $shippingname,
                'shippingstreet' => $shippingstreet,
                'shippingaddress' => $shippingaddress,
                'shippingcountry' => $country->getName(),
                'shippingtelephone' => $telephone,
                'billingname' => $billingname,
                'billingstreet' => $billingstreet,
                'billingaddress' => $billingaddress,
                'billingcountry' => $country->getName(),
                'billingtelephone' => $billingaddresstelephone,
                'invoice' => $invoice,
                'stripe_card' => 'Stored Cards (Stripe)',
                'card_type' => 'Card Type',
                'card_last' => 'Card Last 4 Digits'

            ];
            $from = ['email' => $adminEmail, 'name' => 'Admin'];
            $templateOptions = ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->_storeManager->getStore()->getId()];

            $this->_inlineTranslation->suspend();

            $transport = $this->_transportBuilder->setTemplateIdentifier('custom_invoice_mail')
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($templateVars)
            ->setFrom($from)
            ->addTo($to)
            ->getTransport()
            ;        $transport->sendMessage();
            $this->_inlineTranslation->resume();

            $invoiceResource = $this->_objectManager->get('Magento\Sales\Model\ResourceModel\Order\Invoice');
            $invoice->setEmailSent(true);
            $invoiceResource->saveAttribute($invoice, ['send_email', 'email_sent']);
        }





/*
        $PaymentMethod = $this->_objectManager->create('Webkul\MpStripe\Model\PaymentMethod');


        $finalCart = $PaymentMethod->getFinalCart($order, true);

        $sellerArray = array();
        $mainArray = array();

        foreach ($finalCart as $key => $value) {
            if(isset($value['seller']) && $value['seller'] != "" && $value['products'] != ""){
                if(!in_array($value['seller'], $sellerArray)){
                    $sellerId = $value['seller'];
                    $mainArray[$sellerId] = array();
                    $mainArray[$sellerId]['percent'] = $value['commissionPercent'];
                    $mainArray[$sellerId]['commission'] = $value['commission'];
                }
            }

        }

        echo "<pre>"; print_r($finalCart);

        die("SSSSSSSSs");*/

    }


}
