<?php
namespace Seasia\Customapi\Model;
use Seasia\Customapi\Api\ReturnInterface;
use Seasia\Returnitem\Model\ReturnitemFactory;
use Seasia\Returncomment\Model\ReturncommentFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Webkul\MpStripe\Model\PaymentMethod;
class ReturnItem implements ReturnInterface
{
        /**
         * Return data.
         *
         * @api
         */
        protected $dataFactory;
        protected $_objectManager;
        protected $returnFactory;
        protected $returnCommentFactory;
        protected $mhelper;
        protected $PaymentMethod;
        protected $productModel;
        protected $_storeManager;
        protected $_transportBuilder;
        protected $_inlineTranslation;
        protected $scopeConfig;
        protected $stripeHelper;

        public function __construct(
            \Seasia\Customapi\Api\Data\ProductdataInterfaceFactory $dataFactory,
            ReturnitemFactory $returnFactory,
            ReturncommentFactory $returncommentfactory,
            \Webkul\Marketplace\Helper\Data $mhelper,
            PaymentMethod $PaymentMethod,
            \Magento\Catalog\Model\Product $productModel,
            \Magento\Store\Model\StoreManagerInterface $storemanager,
            \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
            \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Webkul\MpStripe\Helper\Data $stripehelper
            ) {
            $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $this->dataFactory = $dataFactory;
            $this->returnFactory = $returnFactory;
            $this->returnCommentFactory = $returncommentfactory;
            $this->mhelper = $mhelper;
            $this->PaymentMethod = $PaymentMethod;
            $this->productModel = $productModel;
            $this->_storeManager = $storemanager;
            $this->_transportBuilder  = $transportBuilder;
            $this->_inlineTranslation = $inlineTranslation;
            $this->scopeConfig = $scopeConfig;
            $this->stripeHelper = $stripehelper;
        }
        
        // Upload Return Item File
        public function uploadReturnImage($sellerId, $imageName, $content) {
            try {
                $response = array();
                $fileSystem = $this->_objectManager->create('Magento\Framework\Filesystem');
                $mediaDirectory = $fileSystem->getDirectoryWrite(
                    DirectoryList::MEDIA
                    );


                $target = $mediaDirectory->getAbsolutePath('returnimages/');
                $content = preg_replace('#data:image/[^;]+;base64,#', '', $content);
                $content = str_replace(' ', '+', $content);
                $data = base64_decode($content);
                $fileName = $imageName;
                $file = $target . $fileName;
                $success = file_put_contents($file, $data);
                $responseArray['status'] =   'Success';
                $responseArray['imagePath'] = $this->mhelper->getMediaUrl().'returnimages/'.$fileName;
                $responseArray['message'] = 'Return Image Uploaded successfully.';

                
                return $this->getResponseFormat($responseArray);
                
            } catch (\Exception $e) {
                return $this->errorMessage($e);
            }
        }

        public function getReturnComments($returnId){
            try{
                $returnComments = $this->returnCommentFactory->create();
                $returnCollection = $returnComments->getCollection()
                ->addFieldToFilter('return_id', array('eq' => $returnId));

                $joinTable = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\ResourceModel\Seller\Collection'
                    )->getTable('customer_entity');

                $joinPartnerTable = $this->_objectManager->create(
                    'Webkul\Agorae\Model\ResourceModel\Mpfavouriteseller\Collection'
                    )->getTable('marketplace_userdata');
                $websiteId = $this->mhelper->getWebsiteId();
                $returnCollection->getSelect()->joinLeft(
                    $joinTable.' as cgf',
                    'main_table.user_id = cgf.entity_id AND website_id= '.$websiteId,
                    array('firstname','lastname')
                    );
                $returnCollection->getSelect()->joinLeft(
                    $joinPartnerTable.' as cpf',
                    'main_table.user_id = cpf.seller_id',
                    array('shop_url as username')
                    );

                //echo $returnCollection->getSelect();

                //die("DDDDDDDd");
                $marketplaceHelper = $this->_objectManager->create('Webkul\Marketplace\Helper\Data');
                $response = array();
                foreach ($returnCollection as $collection) {
                    $commentArray = array();
                    $commentArray = $collection->getData();
                    $commentArray['shopUrl'] = $marketplaceHelper ->getRewriteUrl(
                    'marketplace/seller/collection/shop/'.
                    $collection->getShopUrl()
                    );
                    array_push($response, $commentArray);
                }
                 
                return $this->getResponseFormat($response);

            } catch (\Exception $e) {
                return $this->errorMessage($e);
            }
        }        
        
        public function createReturn($sellerId, $buyerId, $orderId, $productId, $reason,$imagesName, $comment){
            try{
                $returnData = $this->returnFactory->create();
                $returnCollection = $returnData->getCollection()
                ->addFieldToFilter('seller_id', array('eq' => $sellerId))
                ->addFieldToFilter('buyer_id', array('eq' => $buyerId))
                ->addFieldToFilter('order_id', array('eq' => $orderId))
                ->addFieldToFilter('product_id', array('eq' => $productId));
                if($returnCollection->getSize() > 0){
                    $returnItem = $returnCollection->getFirstItem();
                    $returnItem->setReason($reason);
                    $returnItem->setImagesPath($imagesName);
                    $res =  $returnItem->save(); 
                }else{
                    $returnData->setSellerId($sellerId);
                    $returnData->setBuyerId($buyerId);
                    $returnData->setOrderId($orderId);
                    $returnData->setProductId($productId);
                    $returnData->setReason($reason);
                    $returnData->setImagesPath($imagesName);
                    $returnData->setReturnStatus("waiting");
                    $returnData->setCreatedAt(date("Y-m-d H:i:s"));
                    $res =  $returnData->save();
                }
                $return_id = $res->getId();
                if($comment != ""){
                    $this->addReturnComment($buyerId, $comment, $return_id, $orderId, $productId);
                }

                $orderDatamodel = $this->_objectManager->get('Webkul\Marketplace\Model\Orders')->getCollection()->addFieldToSelect( '*' )
                ->addFieldToFilter( 'seller_id',$sellerId )
                ->addFieldToFilter( 'order_id',$orderId )->getFirstItem();
                
                if($orderDatamodel->getId()){
                    $reportedProducts = array();
                    $reviewProducts = explode(",", $orderDatamodel->getProductInReview());
                    if(count($reviewProducts) > 0){
                        if(!in_array($productId, $reviewProducts)){
                            array_push($reportedProducts, $productId);
                        }
                    }
                    $orderDatamodel->setInReview('1');
                    $orderDatamodel->setProductInReview(implode(',',$reportedProducts));
                    $orderDatamodel->save();

                    $return = $returnData->load($return_id);

                    if($this->sendReturnMail($return,"seller", "return_made")){
                        // Send Notification To Buyer
                        $returnId = $return->getId();
                        $type = "return_made";
                        $buyerId = $return->getBuyerId();
                        $sellerId = $return->getSellerId();
                        $this->sendReturnNotification($returnId, $type,$sellerId,$buyerId);

                        $response['status'] = "Success";
                        $response['message'] = "Return Saved successfully.";
                    }else{
                        $response['status'] = "Error";
                        $response['message'] = "Email not sent.";
                    }
                }else{
                    $response['status'] = "Error";
                    $response['message'] = "Invalid orderid.";
                }

                return $this->getResponseFormat($response);

            } catch (\Exception $e) {
                return $this->errorMessage($e);
            }
        }

        protected function sendReturnMail($return,$mailTo, $returnType){
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

                $adminStoremail = $this->mhelper->getAdminEmailId();
                $adminEmail=$adminStoremail? $adminStoremail:$this->mhelper->getDefaultTransEmailId();

                $adminUsername = 'Admin';
                if($mailTo == "seller"){
                    $customerObj = $this->_objectManager->create('Magento\Customer\Model\Customer')
                    ->load($return->getBuyerId());
                    $customer_name = $customerObj->getFirstname()." ".$customerObj->getLastname();
                    $customer_email = $customerObj->getEmail();

                    $seller = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($return->getSellerId());

                    $seller_name=$seller->getFirstname()." ".$seller->getLastname();
                    $seller_email=$seller->getEmail();
                }else{
                    $customerObj = $this->_objectManager->create('Magento\Customer\Model\Customer')
                    ->load($return->getSellerId());
                    $customer_name = $customerObj->getFirstname()." ".$customerObj->getLastname();
                    $customer_email = $customerObj->getEmail();

                    $seller = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($return->getBuyerId());

                    $seller_name=$seller->getFirstname()." ".$seller->getLastname();
                    $seller_email=$seller->getEmail();
                }
                $customerObj = $this->_objectManager->create('Magento\Customer\Model\Customer')
                ->load($return->getBuyerId());
                $customer_name = $customerObj->getFirstname()." ".$customerObj->getLastname();
                $customer_email = $customerObj->getEmail();

                

                $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($return->getProductId());
                $product_name = $product->getName();

                $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($return->getOrderId());

                switch ($returnType) {
                    case 'return_made':
                    $msg= "Buyer ".$customer_name." has created a return request for Product ".$product->getName()." from Order Id ".$order->getIncrementId();
                    break;

                    case 'return_offer':
                    $msg= "Seller ".$customer_name." has offered you on Return for Product ".$product->getName()." from Order Id ".$order->getIncrementId();
                    break;

                    case 'return_offer_accepted':
                    $msg= "Buyer ".$customer_name." has accepted your offer of ".$return->getOfferAmount()." on Return for Product ".$product->getName()." from Order Id ".$order->getIncrementId();
                    break;

                    case 'return_approved':
                    $msg= "Seller ".$customer_name." has approved your return  for Product ".$product->getName()." from Order Id ".$order->getIncrementId();
                    break;

                    case 'return_funded':
                    $msg= "Seller ".$customer_name." has refunded your amount of ".$return->getRefundedAmount()." for Product ".$product->getName()." from Order Id ".$order->getIncrementId();
                    break;
                    case 'return_offer_rejected':
                    $msg= "Seller ".$customer_name." has rejected your offer on return for Product ".$product->getName()." from Order Id ".$order->getIncrementId();
                    break;

                    case 'return_rejected':
                    $msg= "Seller ".$customer_name." has rejected your return for Product ".$product->getName()." from Order Id ".$order->getIncrementId();
                    break;

                    
                    default:
                    $msg= "";
                    break;
                }

                
                

                $templateVars = [
                'store' => $this->_storeManager->getStore(),
                'customer_name' => $customer_name,
                'seller_name'   => $seller_name,
                'link'          =>  $product->getProductUrl(),
                'product_name'  => $product_name,
                'message'   => $msg
                ];

                $to = [$seller_email];
                $from = ['email' => $adminEmail, 'name' => 'Admin'];
                $templateOptions = ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->_storeManager->getStore()->getId()];

                $this->_inlineTranslation->suspend();



                $transport = $this->_transportBuilder->setTemplateIdentifier('seller_return_item_request')
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFrom($from)
                ->addTo($to)
                ->getTransport();
                $transport->sendMessage();
                $this->_inlineTranslation->resume();


                if ($mail_to_admin) {
                    $templateVars['seller_name']='Admin';
                    $templateVars['link']='Please login to view and give response';
                    $templateVars['message']="I would like to inform that Buyer ".$customer_name." has created a return request for Seller ".$seller_name." for Product ".$product->getName()." from Order Id ".$order->getIncrementId();
                    
                    $this->sendAdminMail($templateVars, $adminEmail, $adminEmail);
                }
                return true;  
            } catch (\Exception $e){
                $this->_inlineTranslation->resume();   
                //echo $e->getMessage();die('===========');
                return false;
            }
        }

        public function sendAdminMail($templateVars, $adminEmail, $to)
        {
            try{
                $from = ['email' => $adminEmail, 'name' => 'Admin'];
                $templateOptions = ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->_storeManager->getStore()->getId()];
                $this->_inlineTranslation->suspend();

                $transport = $this->_transportBuilder->setTemplateIdentifier('admin_return_request')
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFrom($from)
                ->addTo($to)
                ->getTransport();
                $transport->sendMessage();
                $this->_inlineTranslation->resume();
            } catch (\Exception $e) {
                $this->_inlineTranslation->resume();
            }
        }

        protected function addReturnComment($userId, $comment, $return_id, $orderId, $productId){

            if($comment != ""){
                $returnCommentData = $this->returnCommentFactory->create();
                $returnCommentData->setUserId($userId);
                $returnCommentData->setReturnId($return_id);
                $returnCommentData->setOrderId($orderId);
                $returnCommentData->setComment($comment);
                $returnCommentData->setProductId($productId);
                $returnCommentData->setCreatedAt(date("Y-m-d H:i:s"));
                $returnCommentData->save();

            }
        }

        protected function getItemDetailsFromOrder($orderId, $productId){
            $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);
            $orderItems = $order->getAllItems();
            foreach($orderItems as $item){
                if($item->getProductId() == $productId){
                    return $item->getData();
                }
            }
            return [];
        }

        //GEt Return For Seller Order
        public function getReturn($returnId){
            try{
                $returnData = $this->returnFactory->create();
                $returnCollection = $returnData->getCollection()
                ->addFieldToFilter('id', array('eq' => $returnId));
                $salesOrder = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\ResourceModel\Orders\Collection'
                    )->getTable('sales_order');

                $marketplaceOrder = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\ResourceModel\Orders\Collection'
                    )->getTable('marketplace_orders');

                $returnCollection->getSelect()->joinLeft(
                    $salesOrder.' as so',
                    'main_table.order_id = so.entity_id',
                    array('increment_id as order_increment_id')
                    );

                $apiHelper = $this->_objectManager->create('Seasia\Customapi\Helper\Data');

                

                if($returnCollection->getSize() > 0){
                    $returnItem = $returnCollection->getFirstItem();

                    $response = $returnItem->getData();
                    $images = explode(",",$response['images_path']);
                    $imagesArray = array();
                    foreach ($images as $key => $value) {
                        array_push($imagesArray, $this->mhelper->getMediaUrl().'returnimages/'.$value);
                    }
                    $response['images_path'] = implode(",", $imagesArray);
                    $_product =$this->productModel->load($returnItem->getProductId());
                    $store = $this->_storeManager->getStore();

                    $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($returnItem->getOrderId());

                    $response['productname'] = $_product->getName();
                    $response['description'] = $_product->getDescription();
                    $response['price'] = $_product->getPriceInfo()->getPrice('final_price')->getValue();
                    $response['imageUrl'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getImage();
                    $response['productUrl'] = $_product->getProductUrl();

                    $itemData = $this->getItemDetailsFromOrder($returnItem->getOrderId(), $returnItem->getProductId());
                    $response['sold_price'] = $itemData['price'];
                    $response['order_increment_id'] = $returnItem->getOrderIncrement_id();
                    $response['returnPrintUrl'] = "";
                    $response['order_status'] = $order->getStatus();
                    $response['userinfo'] = $apiHelper->getSellerShopById($returnItem->getBuyerId());

                    $orderDatamodel = $this->_objectManager->get('Webkul\Marketplace\Model\Orders')->getCollection()
                    ->addFieldToSelect( '*' )
                    ->addFieldToFilter( 'seller_id',$returnItem->getSellerId() )
                    ->addFieldToFilter( 'order_id',$returnItem->getOrderId() )
                    ->getFirstItem();
                    if($orderDatamodel->getId()){
                        $response['return_tracking_number'] = $orderDatamodel->getReturnTrackingNumber();
                        $store = $this->_storeManager->getStore();
                        if($orderDatamodel->getReturnLabel()){

                            $response['returnPrintUrl'] = $store->getBaseUrl()."endicia/shipment/printreturnpdf/order_id/".$orderDatamodel->getOrderId()."/shipment_id/".$orderDatamodel->getShipmentId()."/seller_id/".$orderDatamodel->getSellerId();
                        }
                    }


                    $response['status'] =   'Success';
                } else {
                    $response['status'] =   'Error';
                    $response['message'] = 'Return Not Found.';
                }
                return $this->getResponseFormat($response);

            } catch (\Exception $e) {
                return $this->errorMessage($e);
            }
        }

        public function offerReturn($sellerId,$returnId, $offerAmount, $comment){
            try{
                $returnData = $this->returnFactory->create();
                $returnCollection = $returnData->getCollection()
                ->addFieldToFilter('id', array('eq' => $returnId))
                ->addFieldToFilter('seller_id', array('eq' => $sellerId))
                ->addFieldToFilter('return_status', array('neq' => "offer_received"));
                if($returnCollection->getSize() > 0){
                    $returnItem = $returnCollection->getFirstItem();
                    $returnItem->setOfferAmount($offerAmount);
                    $returnItem->setReturnStatus("offer_received");
                    $returnItem->save();
                    if($comment != ""){
                        $this->addReturnComment($sellerId, $comment, $returnId, $returnItem->getOrderId(), $returnItem->getProductId());
                    }

                    if($this->sendReturnMail($returnItem,"buyer", "return_offer")){
                        $type = "return_offer";
                        $buyerId = $returnItem->getBuyerId();
                        $sellerId = $returnItem->getSellerId();
                        $this->sendReturnNotification($returnId, $type,$buyerId,$sellerId);

                        $response['status'] =   'Success';
                        $response['message'] =   'Return Offered Successfully.';
                    }else{
                        $response['status'] = "Error";
                        $response['message'] = "Email not sent.";
                    }

                }else{
                    $response['status'] =   'Error';
                    $response['message'] = 'Return Not Found.';
                }
                return $this->getResponseFormat($response);

            } catch (\Exception $e) {
                return $this->errorMessage($e);
            }
        }

        protected function sendReturnNotification($returnId, $type,$buyerId,$sellerId){
            $notification_helper = $this->_objectManager->create(
                'Seasia\Customnotifications\Helper\Data'
                );
            $message = 'Test';

            $notification_helper->setNotification($returnId,$type,$buyerId,$sellerId,$message);
        }

        public function acceptReturnOffer($sellerId,$returnId, $comment){
            try{

                $returnData = $this->returnFactory->create();
                $returnCollection = $returnData->getCollection()
                ->addFieldToFilter('id', array('eq' => $returnId))
                ->addFieldToFilter('buyer_id', array('eq' => $sellerId))
                ->addFieldToFilter('return_status', array('neq' => "return_approved"));
                if($returnCollection->getSize() > 0){
                    $returnItem = $returnCollection->getFirstItem();
                    if($returnItem->getId()){

                        $itemData = $this->getItemDetailsFromOrder($returnItem->getOrderId(), $returnItem->getProductId());
                        $itemPrice = $itemData['price'];
                        $offerAmount = $returnItem->getOfferAmount();

                        $refundAmount = ($itemPrice - $offerAmount);

                        $orderDatamodel = $this->_objectManager->get('Webkul\Marketplace\Model\Orders')->getCollection()
                        ->addFieldToSelect( '*' )
                        ->addFieldToFilter( 'seller_id',$returnItem->getSellerId() )
                        ->addFieldToFilter( 'order_id',$returnItem->getOrderId() )
                        ->getFirstItem();
                        if($orderDatamodel->getId()){
                            $chargeId = $orderDatamodel->getSellerStripeTransId();
                            $stripeKey = $this->stripeHelper->getConfigValue('api_key');
                            \Stripe\Stripe::setApiKey($stripeKey);

                            $refund = \Stripe\Refund::create([
                                'charge' => $chargeId,
                                'amount' => $refundAmount*100,
                                ]);
                            $returnItem->setRefundedAmount($refundAmount);
                            $returnItem->setReturnStatus("offer_accepted");
                            $returnItem->save();
                            $returnId = $returnItem->getId();
                            $this->resetOrderItemPrice($returnItem->getOrderId(),$returnItem->getProductId(),$refundAmount);
                            if($comment != ""){
                                $this->addReturnComment($returnItem->getBuyerId(), $comment, $returnId, $returnItem->getOrderId(), $returnItem->getProductId());
                            }
                            if($this->sendReturnMail($returnItem,"seller", "return_offer_accepted")){
                            // Send Notification To Seller

                                $type = "return_offer_accepted";
                                $buyerId = $returnItem->getSellerId();
                                $sellerId = $returnItem->getBuyerId();
                                $this->sendReturnNotification($returnId, $type,$sellerId,$buyerId);

                                $response['status'] = "Success";
                                $response['message'] = "Return Offer accepted Successfully.";
                            }else{
                                $response['status'] = "Error";
                                $response['message'] = "Email not sent.";
                            }
                        // Code for Update Main Order Item Price TBD




                        }else{
                            $response['status'] =   'Error';
                            $response['message'] =   'Order does not exist.';
                        }
                    }else{
                        $response['status'] =   'Error';
                        $response['message'] = 'Return Not Found.';
                    }
                }else{
                    $response['status'] =   'Error';
                    $response['message'] = 'Return Not Found or already settled.';
                }
                return $this->getResponseFormat($response);

            } catch (\Exception $e) {
                return $this->errorMessage($e);
            }
        }

        protected function generateReturnLabel($sellerId, $orderId){
            $request = new \Magento\Framework\DataObject();
            $request->setOrderId($orderId);
            $request->setSellerId($sellerId);
            $request->setReturnType("TRUE");
            $endiciaObj  = $this->_objectManager->create('Webkul\MarketplaceUspsEndicia\Model\EndiciaManagement');

            $result = $endiciaObj->generateReturnLabel($request);
        }

        public function rejectReturnOffer($sellerId, $returnId, $comment){
            try{
                $returnData = $this->returnFactory->create();
                $returnCollection = $returnData->getCollection()
                ->addFieldToFilter('id', array('eq' => $returnId))
                ->addFieldToFilter('buyer_id', array('eq' => $sellerId));

                if($returnCollection->getSize() > 0){
                    $returnItem = $returnCollection->getFirstItem();
                    $returnItem->setReturnStatus('return_offer_rejected');
                    $returnItem->save();
                    $returnId = $returnItem->getId();

                    $this->generateReturnLabel($returnItem->getSellerId(), $returnItem->getOrderId());

                    if($this->sendReturnMail($returnItem,"seller", "return_offer_rejected")){
                            // Send Notification To Seller

                        $type = "return_offer_rejected";
                        $buyerId = $returnItem->getSellerId();
                        $sellerId = $returnItem->getBuyerId();
                        $this->sendReturnNotification($returnId, $type,$sellerId,$buyerId);

                        $response['status'] = "Success";
                        $response['message'] = "Return Offer rejected Successfully.";
                    }else{
                        $response['status'] = "Error";
                        $response['message'] = "Email not sent.";
                    }
                } else {
                    $response['status'] =   'Error';
                    $response['message'] = 'Return Not Found.';
                }

                return $this->getResponseFormat($response);
            }catch (\Exception $e) {
                return $this->errorMessage($e);
            }
        }

        public function returnshipped($sellerId, $returnId){
            try{
                $returnData = $this->returnFactory->create();
                $returnCollection = $returnData->getCollection()
                ->addFieldToFilter('id', array('eq' => $returnId))
                ->addFieldToFilter('buyer_id', array('eq' => $sellerId));
                if($returnCollection->getSize() > 0){
                    $returnItem = $returnCollection->getFirstItem();
                    $returnItem->setReturnStatus("return_shipped");
                    $returnItem->save();
                }
                $response['status'] = "Success";
                $response['message'] = "Return shipped Successfully.";
                return $this->getResponseFormat($response);
            }catch (\Exception $e) {
                return $this->errorMessage($e);
            }
        }

        public function approveReturn($sellerId, $returnId){
            try{
                $returnData = $this->returnFactory->create();
                $returnCollection = $returnData->getCollection()
                ->addFieldToFilter('id', array('eq' => $returnId))
                ->addFieldToFilter('seller_id', array('eq' => $sellerId));
                if($returnCollection->getSize() > 0){
                    $returnItem = $returnCollection->getFirstItem();
                    $returnItem->setReturnStatus("return_approved");
                    $returnItem->save();
                    $returnId = $returnItem->getId();
                    $this->generateReturnLabel($returnItem->getSellerId(), $returnItem->getOrderId());
                    if($this->sendReturnMail($returnItem,"buyer", "return_approved")){
                    // Send Notification To Seller

                        $type = "return_approved";
                        $sellerId = $returnItem->getSellerId();
                        $buyerId = $returnItem->getBuyerId();
                        $this->sendReturnNotification($returnId, $type,$buyerId,$sellerId);

                        $response['status'] = "Success";
                        $response['message'] = "Return  approved Successfully.";
                    }else{
                        $response['status'] = "Error";
                        $response['message'] = "Email not sent.";
                    }
                } else {
                    $response['status'] =   'Error';
                    $response['message'] = 'Return Not Found.';
                }
                return $this->getResponseFormat($response);
            }catch (\Exception $e) {
                return $this->errorMessage($e);
            }
        }

        public function rejectReturn($sellerId, $returnId){
            try{
                $returnData = $this->returnFactory->create();
                $returnCollection = $returnData->getCollection()
                ->addFieldToFilter('id', array('eq' => $returnId))
                ->addFieldToFilter('seller_id', array('eq' => $sellerId));
                if($returnCollection->getSize() > 0){
                    $returnItem = $returnCollection->getFirstItem();
                    $returnItem->setReturnStatus("return_rejected");
                    $returnItem->save();
                    $returnId = $returnItem->getId();
                    /*$this->generateReturnLabel($returnItem->getSellerId(), $returnItem->getOrderId());*/
                    if($this->sendReturnMail($returnItem,"seller", "return_rejected")){
                    // Send Notification To Seller

                        $type = "return_rejected";
                        $sellerId = $returnItem->getSellerId();
                        $buyerId = $returnItem->getBuyerId();
                        $this->sendReturnNotification($returnId, $type,$buyerId,$sellerId);

                        $response['status'] = "Success";
                        $response['message'] = "Return  rejected Successfully.";
                    }else{
                        $response['status'] = "Error";
                        $response['message'] = "Email not sent.";
                    }
                } else {
                    $response['status'] =   'Error';
                    $response['message'] = 'Return Not Found.';
                }
                return $this->getResponseFormat($response);
            }catch (\Exception $e) {
                return $this->errorMessage($e);
            }
        }

        public function processReturn($sellerId, $returnId, $comment){
            try{

                $returnData = $this->returnFactory->create();

                $returnCollection = $returnData->getCollection()
                ->addFieldToFilter('id', array('eq' => $returnId))
                ->addFieldToFilter('buyer_id', array('eq' => $sellerId));
                if($returnCollection->getSize() > 0){
                    $returnItem = $returnCollection->getFirstItem();
                    if($returnItem->getId()){
                        $marketplaceOrder = $this->_objectManager->get('Webkul\Marketplace\Model\Orders')->getCollection()->addFieldToSelect( '*' )
                        ->addFieldToFilter( 'seller_id',$returnItem->getSellerId() )
                        ->addFieldToFilter( 'order_id',$returnItem->getOrderId() )->getFirstItem();

                        if($marketplaceOrder->getId()){
                            $productsInOrder = explode(",",$marketplaceOrder->getProductIds());
                            $chargeId = $marketplaceOrder->getSellerStripeTransId();
                            $stripeKey = $this->stripeHelper->getConfigValue('api_key');
                            \Stripe\Stripe::setApiKey($stripeKey);
                            $refundPrice = 0;
                            if(count($productsInOrder) > 1){
                                $itemData = $this->getItemDetailsFromOrder($returnItem->getOrderId(), $returnItem->getProductId());
                                $refundPrice = $itemData['price'];

                                $refund = \Stripe\Refund::create([
                                    'charge' => $chargeId,
                                    'amount' => $refundPrice*100,
                                    ]);


                            }else{
                                $charge = \Stripe\Charge::retrieve($chargeId);
                                $originalChargeArray = (array) $charge;

                                foreach ($originalChargeArray as $key => $value) {
                                    if (strpos($key, 'values') !== false) {
                                        $originalChargeArray = $value;
                                    }
                                }

                                $refundPrice = $originalChargeArray['amount'];


                                $refund = \Stripe\Refund::create([
                                    'charge' => $chargeId,
                                    ]);
                            }
                            $returnItem->setRefundedAmount($refundPrice);
                            $returnItem->setReturnStatus("return_funded");
                            $returnItem->save();
                            $this->resetOrderItemPrice($returnItem->getOrderId(),$returnItem->getProductId(),$refundPrice);
                            $returnId = $returnItem->getId();
                            if($this->sendReturnMail($returnItem,"seller", "return_funded")){
                            // Send Notification To Seller

                                $type = "return_funded";
                                $buyerId = $returnItem->getSellerId();
                                $sellerId = $returnItem->getBuyerId();
                                $this->sendReturnNotification($returnId, $type,$buyerId,$sellerId);

                                $response['status'] = "Success";
                                $response['message'] = "Return refunded Successfully.";
                            }else{
                                $response['status'] = "Error";
                                $response['message'] = "Email not sent.";
                            }

                        }
                    }else{
                        $response['status'] =   'Error';
                        $response['message'] = 'Return Not Found.';
                    }
                } else {
                    $response['status'] =   'Error';
                    $response['message'] = 'Return Not Found or already settled.';
                }
                return $this->getResponseFormat($response);
            } catch (\Exception $e) {
                return $this->errorMessage($e);
            }
        }

        protected function resetOrderItemPrice($orderId, $productId, $discount){
            try{

                $quoteToOrder = $this->_objectManager->create(
                    'Magento\Quote\Model\Quote\Item\ToOrderItem'
                    );

                $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);

                $quote = $this->_objectManager->create('\Magento\Quote\Model\Quote')->load($order->getQuoteId());
                $items = $order->getAllItems();
                foreach ($items as $item) {

                    if($item->getProductId() == $productId){
                        $itemPrice = $item->getPrice();
                        $price = $itemPrice - $discount;
                        $item->setPrice($price);
                        $item->setCustomPrice($price);
                        $item->setCustomOriginalPrice($price);
                        $item->setRowTotal($price);
                        $item->save();
                    }
                    $grandTotal = $order->getGrandTotal();
                    $subtotal = $order->getSubTotal();
                    $totalPaid = $order->getTotalPaid();  
                    $order->setSubTotal($subtotal - $discount);
                    $order->setGrandTotal($grandTotal - $discount);
                    $order->setTotalPaid($totalPaid - $discount);  
                    $order->save();

                }


            } catch (\Exception $e) {
                echo $e->getMessage();
                die("errorr");
            }
        }

        public function returnProduct($returnId){
            $returnData = $this->returnFactory->create();
            $returnData->load($returnId);
            if($returnData->getId()){
                if($returnData->getOfferAmount() == ""){
                    $orderId = $returnData->getOrderId();
                    $sellerId = $returnData->getSellerId();
                    $orderDatamodel = $this->_objectManager->get('Webkul\Marketplace\Model\Orders')->getCollection()->addFieldToSelect( '*' )
                    ->addFieldToFilter( 'seller_id',$sellerId )
                    ->addFieldToFilter( 'order_id',$orderId )->getFirstItem();

                    if($orderDatamodel->getId()){
                        $orderedProducts = explode(",",$orderDatamodel->getProductIds());
                        if(count($orderedProducts) == 1 && $orderDatamodel->getProductIds() == $returnData->getProductId()){
                        // Refund Full Amount of Seller Charge to Customer
                        }else{
                            $itemData = $this->getItemDetailsFromOrder($orderId, $returnData->getProductId());
                            $refundAmount = $itemData['price'];

                        }

                    }
                }
            }
        }

        protected function processReturnAmount($returnData, $status){
            $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($returnData->getOrderId());
            $cartItems = $order->getAllVisibleItems();
            $stripeHelper = $this->_objectManager->create(
                'Webkul\MpStripe\Helper\Data'
                );
            foreach($cartItems as $item){
                if($item->getId() == $returnData->getProductId()){
                    $sellerStripe = $this->getUserStripe($returnData->getSellerId());
                    if($sellerStripe->getSize() > 0){
                        $itemPrice = $item->getPrice();
                        $offerAmount = $returnData->getOfferAmount();
                        echo $refundAmount = ($itemPrice - $offerAmount) * 100;
                        die("hellooooo");
                    // $mpStripe = $sellerStripe->getFirstItem();
                    // $stripeData = $mpStripe->getData();
                    // $buyerStripe = $this->getUserStripe($returnData->getBuyerId());
                    // $mpBuyerStripe = $buyerStripe->getFirstItem();
                    // $buyerStripeData = $mpBuyerStripe->getData();
                    // $amount = $returnData->getOfferAmount() * 100;
                    // try {
                    //     \Stripe\Stripe::setApiKey($stripeData['stripeApiKey']);
                    //     $transfer = \Stripe\Transfer::create(array(
                    //         "amount" => ($refundAmount),
                    //         "currency" => $order->getBaseCurrencyCode(),
                    //         "source_transaction" => $order->getMainChargeid(),
                    //         "destination" => $buyerStripeData['stripe_user_data']

                    //         ));

                    // } catch (\Exception $e) {
                    //     return false;
                    // }
                    }else{
                        return false;
                    }
                }
            //echo "<pre>"; print_r($item->getData());
            }



            $sellerStripe = $this->getUserStripe($returnData->getSellerId());





            if (count($finalCart) > 0) {

            }
            die("QQQQQQQQQQQQQQQQ");
        }

        protected function getUserStripe($userId){
            $sellerStripe = $this->_objectManager
            ->create('Webkul\MpStripe\Model\StripeSeller')
            ->getCollection()
            ->addFieldToFilter('seller_id', ['eq' => $userId]);

            return $sellerStripe;
        }

        public function returnsMade($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr){
            try{
                $returnCollection = $this->getReturnsData($sellerId, "made", $searchStr);

                $returnCollection->setPageSize($length)->setCurPage($pageNum);
                $returnCollection->setOrder($orderBy, $orderDir);
                $returnData = array();
                foreach($returnCollection as $eachReturn){

                    $eachReturnArray = array();
                    $itemData = $this->getItemDetailsFromOrder($eachReturn->getOrderId(), $eachReturn->getProductId());

                    $eachReturnArray = $eachReturn->getData();
                    if(!empty($itemData)){
                        $eachReturnArray['price'] = $itemData['price'];
                    }else{
                        $eachReturnArray['price'] = "";
                    }

                    array_push($returnData, $eachReturnArray);
                }

                $totalCount = $returnCollection->getSize();
                $response['returnsMade'] = $returnData;
                $response['returnMadeCount'] = $totalCount;
                return $this->getResponseFormat($response);

            } catch (\Exception $e) {
                return $this->errorMessage($e);
            }
        }

        protected function getReturnsData($customerId,$type,$searchStr){
            $returnData = $this->returnFactory->create();
            $returnCollection = $returnData->getCollection();
            if($type == "made"){
                $returnCollection->addFieldToFilter('buyer_id', array('eq' => $customerId));
            }else{
                $returnCollection->addFieldToFilter('main_table.seller_id', array('eq' => $customerId));
            }
            ;

            $sellerjoinTable = $this->_objectManager->create(
                'Webkul\Agorae\Model\ResourceModel\Mpfavouriteseller\Collection'
                )->getTable('marketplace_userdata');
            if($type == "made"){
                $returnCollection->getSelect()->joinLeft(
                    $sellerjoinTable.' as cgf',
                    'main_table.buyer_id = cgf.seller_id',
                    array('shop_url as username')
                    );
            }
            if($type == "received"){
                $returnCollection->getSelect()->joinLeft(
                    $sellerjoinTable.' as cgf',
                    'main_table.seller_id = cgf.seller_id',
                    array('shop_url as username')
                    );
            }

            $salesOrder = $this->_objectManager->create(
                'Webkul\Marketplace\Model\ResourceModel\Orders\Collection'
                )->getTable('sales_order');

            $returnCollection->getSelect()->joinLeft(
                $salesOrder.' as so',
                'main_table.order_id = so.entity_id',
                array('increment_id as order_increment_id')
                );

            $eavAttribute = $this->_objectManager->get(
                'Magento\Eav\Model\ResourceModel\Entity\Attribute'
                );
            $proAttId = $eavAttribute->getIdByCode('catalog_product', 'name');
            $catalogProductEntityVarchar = $this->_objectManager->create(
                'Webkul\Marketplace\Model\ResourceModel\Product\Collection'
                )->getTable('catalog_product_entity_varchar');


            $returnCollection->getSelect()->joinLeft($catalogProductEntityVarchar.' as cpev','main_table.product_id = cpev.entity_id', array('value as productName'))
            ->where('cpev.attribute_id = '.$proAttId);

            if($searchStr != ""){
                $returnCollection->getSelect()->where(
                    'shop_url like "%'.$searchStr.'%"  OR value like "%'.$searchStr.'%" OR increment_id like "%'.$searchStr.'%"'
                    );
            }
            return $returnCollection;
        }

        public function returnsReceived($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr){
            try{
                $returnCollection = $this->getReturnsData($sellerId, "received", $searchStr);

                $returnCollection->setPageSize($length)->setCurPage($pageNum);
                $returnCollection->setOrder($orderBy, $orderDir);

                $returnData = array();
                foreach($returnCollection as $eachReturn){
                    $eachReturnArray = array();
                    $itemData = $this->getItemDetailsFromOrder($eachReturn->getOrderId(), $eachReturn->getProductId());

                    $eachReturnArray = $eachReturn->getData();
                    if(!empty($itemData)){
                        $eachReturnArray['price'] = $itemData['price'];
                    }else{
                        $eachReturnArray['price'] = "";
                    }

                    array_push($returnData, $eachReturnArray);
                }


                $totalCount = $returnCollection->getSize();

                $response['returnReceived'] = $returnData;
                $response['returnReceivedCount'] = $totalCount;
                return $this->getResponseFormat($response);

            } catch (\Exception $e) {
                return $this->errorMessage($e);
            }
        }

        // Return React App Version
        public function getReactAppVersion() {
            try {
               $scopeConfig = $this->_objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
                $configPath = 'customapi/general/appversion';
                $value =  $scopeConfig->getValue(
                    $configPath,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $response['appversion'] = $value;
                return $this->getResponseFormat($response);
            } catch (\Exception $e) {
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
    ?>