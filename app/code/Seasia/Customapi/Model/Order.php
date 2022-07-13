<?php
namespace Seasia\Customapi\Model;
use Seasia\Customapi\Api\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory as SaleslistColl;
use Seasia\Returnitem\Model\ReturnitemFactory;
use Webkul\MpStripe\Model\PaymentMethod;
class Order implements OrderInterface
{
    /**
     * Return data.
     *
     * @api
     */
    protected $dataFactory;

    protected $_vacationFactory;
    protected $_objectManager;
    protected $_orderRepository;
    /**
     * @var SaleslistColl
     */
    protected $saleslistColl;



    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    protected $_storemanager;
    protected $_base_url;
    protected $returnFactory;
    protected $PaymentMethod;

    public function __construct(
        \Webkul\MpSellerVacation\Model\ResourceModel\Vacation\CollectionFactory $vacationFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Seasia\Customapi\Api\Data\ProductdataInterfaceFactory $dataFactory,OrderRepositoryInterface $orderRepository,SaleslistColl $saleslistColl,StoreManager $storemanager,ReturnitemFactory $returnFactory,PaymentMethod $PaymentMethod
    ) {
        $this->timezone = $timezoneInterface;
        $this->_vacationFactory = $vacationFactory;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->dataFactory = $dataFactory;
        $this->_orderRepository = $orderRepository;
        $this->saleslistColl = $saleslistColl;
        $this->_storemanager = $storemanager;
        $this->_base_url = $this->_storemanager->getStore()->getBaseUrl();
        $this->returnFactory = $returnFactory;
        $this->PaymentMethod = $PaymentMethod;
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

    //Save Seller Rating For Order
    public function giveRating($sellerId,$buyerId,$buyerEmail,$status,$feedPrice,$feedValue,$feedQuality,$feedNickname,$feedSummary,$feedReview, $orderId){
        $response = array();
        try{
            $data = [];
            $data['created_at'] = $this->_objectManager->create("Magento\Framework\Stdlib\DateTime\DateTime")->gmtDate();
            $data['feed_price'] = $feedPrice;
            $data['feed_value'] = $feedValue;
            $data['feed_quality'] = $feedQuality;
            $data['seller_id'] = $sellerId;
            $data['feed_nickname'] = $feedNickname;
            $data['feed_summary'] = $feedSummary;
            $data['feed_review'] = $feedReview;
            $data['buyer_id'] = $buyerId;
            $data['buyer_email'] = $buyerEmail;
            $data['order_id'] = $orderId;
            $customerId = $buyerId;

            $feedbackcount = 0;
            $collectionfeed = $this->_objectManager->create("\Webkul\Marketplace\Model\FeedbackcountFactory")
            ->create()
            ->getCollection()
            ->addFieldToFilter('buyer_id', $customerId)
            ->addFieldToFilter('seller_id', $sellerId);
            foreach ($collectionfeed as $value) {
                $feedcountid = $value->getEntityId();
                $ordercount = $value->getOrderCount();
                $feedbackcount = $value->getFeedbackCount();
                $value->setFeedbackCount($feedbackcount + 1);
                $value->save();
            }
            $notificationRes = $this->_objectManager->create("\Webkul\Marketplace\Model\FeedbackFactory")->create()->setData($data)->save();

            $notification_helper = $this->_objectManager->create(
                'Seasia\Customnotifications\Helper\Data'
            );
            $itemId = $notificationRes->getId();
            $type = "seller_review";
            $sent_for = $customerId;
            $sent_to =  $sellerId;
            $message = 'Test';

            $notification_helper->setNotification($itemId,$type,$sent_to,$sent_for,$message);

            $response['response'] = "Success";
            $response['message'] = "Feedback Saved Successfully";

        }catch (\Exception $e) {
            $response['response'] = "Error";
            $response['message'] = $e->getMessage();
        }
        return $this->getResponseFormat($response);
    }

    //Edit Rating For Order
    public function editRating($sellerId,$buyerId,$orderId,$feedPrice,$feedValue,$feedQuality,$feedNickname,$feedSummary,$feedReview){

        $response = array();
        try{

            $feedbackModel = $this->getOrderFeedbacks($sellerId,$buyerId,$orderId);

            if($feedbackModel->getSize() > 0){
                foreach ($feedbackModel as $eachFeedback) {
                    $eachFeedback->setFeedPrice($feedPrice);
                    $eachFeedback->setFeedValue($feedValue);
                    $eachFeedback->setFeedQuality($feedQuality);
                    $eachFeedback->setFeedNickname($feedNickname);
                    $eachFeedback->setFeedSummary($feedSummary);
                    $eachFeedback->setFeedReview($feedReview);
                    $eachFeedback->save();
                }
                $response['response'] = "Success";
                $response['message'] = "Feedback Updated Successfully";
            }else{
                $response['response'] = "Error";
                $response['message'] = "Feedback not found";
            }
        }catch (\Exception $e) {
            $response['response'] = "Error";
            $response['message'] = $e->getMessage();
        }
        return $this->getResponseFormat($response);


    }

    protected function getOrderFeedbacks($sellerId,$buyerId,$orderId){
        $feedbackModel = $this->_objectManager
        ->create("\Webkul\Marketplace\Model\FeedbackFactory")
        ->create()
        ->getCollection()
        ->addFieldToSelect( '*' )
        ->addFieldToFilter( 'order_id',$orderId )
        ->addFieldToFilter( 'seller_id',$sellerId )
        ->addFieldToFilter( 'buyer_id',$buyerId )
        ;

        return $feedbackModel;
    }
    //Delete Rating For Order
    public function deleteRating($sellerId,$buyerId,$orderId){
        $response = array();
        try{

            $feedbackModel = $this->getOrderFeedbacks($sellerId,$buyerId,$orderId);

            if($feedbackModel->getSize() > 0){
                foreach ($feedbackModel as $eachFeedback) {
                    $eachFeedback->delete();
                }
                $response['response'] = "Success";
                $response['message'] = "Feedback Deleted Successfully";
            }else{
                $response['response'] = "Error";
                $response['message'] = "Feedback not found";
            }
        }catch (\Exception $e) {
            $response['response'] = "Error";
            $response['message'] = $e->getMessage();
        }
        return $this->getResponseFormat($response);
    }

    // get Admin commission
    public function commission(){
        $responseArray = array();
        try{
            $percent = $this->_objectManager->create(
                'Webkul\Marketplace\Helper\Data'
            )->getConfigCommissionRate();

            $responseArray['response'] = "Success";
            $responseArray['value'] = $percent;

        }catch (\Exception $e) {
            $responseArray['response'] = "Error";
            $responseArray['message'] = "Something Went Wrong.";
        }
        return $this->getResponseFormat($responseArray);
    }


    public function stripeTransaction(){
        //$order = $this->_orderRepository->get($lastOrderId);


        $request = new \Magento\Framework\DataObject();
        $request->setOrderId(704);
        $request->setSellerId(46);
        $request->setReturnType("TRUE");
        $endiciaObj  = $this->_objectManager->create('Webkul\MarketplaceUspsEndicia\Model\EndiciaManagement');

        $result = $endiciaObj->generateReturnLabel($request);


        echo "<pre>";print_r($result);

        die("SSSSSWWWWWWWWWWW");

    }

    public function getConfig(){
        try{
            $response = array();

            $storeManager = $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface');
            $currency = $this->_objectManager->create('\Magento\Directory\Model\Currency');

            $response['currencyCode'] = $storeManager->getStore()->getCurrentCurrency()->getCode();
            $response['currencySymbol'] =$currency->getCurrencySymbol();
            return $this->getResponseFormat($response);
        }catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    protected function getSellerOrderCollectionFromWebkul($orderId, $sellerId){
        $orderModel = $this->_objectManager->get('Webkul\Marketplace\Model\Orders')
        ->getCollection()
        ->addFieldToSelect( '*' )
        ->addFieldToFilter( 'order_id',$orderId )
        ->addFieldToFilter( 'seller_id',$sellerId );
        return $orderModel;
    }
    public function confirmOrder($orderId, $sellerId,$orderSellerId){
        try{
            $responseArray = array();



            $orderModel = $this->getSellerOrderCollectionFromWebkul($orderId, $orderSellerId);

            //$orderDeliverymodel = $orderModel->addFieldToFilter( 'delivery_confirm', 'confirmed');

            $orderDatamodel = $orderModel->addFieldToFilter( 'seller_id',$orderSellerId );

            $originalOrder = $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);
            if($originalOrder->getCustomerId() == $sellerId){


                foreach($orderDatamodel as $order){
                    if($order){


                        if( $this->_objectManager->create('Webkul\FirstCustomization\Helper\Data')->processSellerOrderbysellerid($originalOrder,$order->getSellerId(),$order )){
                            $order->setDeliveryDate(date('Y:m:d H:i:s'));
                            $order->setEndiciaDeliverStatus("Delivered");
                            $order->setDeliveryConfirm('confirmed');
                            $order->save();
                            $responseArray['status'] = "Success";
                            $responseArray['message'] = "Order Delivery Confirmed";
                        }else{
                            $responseArray['status'] = "Error";
                            $responseArray['message'] = "Invalid Seller Id or Order Id";
                            return $this->getResponseFormat($responseArray);
                        }

                    }else{
                        $responseArray['status'] = "Error";
                        $responseArray['message'] = "Invalid Order Id";
                        //return $this->getResponseFormat($responseArray);
                    }
                }
                $orderDeliverymodel = $this->getSellerOrderCollectionFromWebkul($orderId, $orderSellerId);
                $orderDeliverymodel = $orderDeliverymodel->addFieldToFilter( 'delivery_confirm', 'confirmed');
                if($orderDeliverymodel->getSize() == $orderDatamodel->getSize()) {
                    $originalOrder->setDeliveryDate(date('Y:m:d H:i:s'));
                    $originalOrder->setEndiciaDeliverStatus("Delivered");
                    $originalOrder->setDeliveryConfirm('confirmed');
                    $originalOrder->save();
                }

                // $responseArray['status'] = "Success";
                // $responseArray['message'] = "Order Delivery Confirmed";
                return $this->getResponseFormat($responseArray);

            }else{
                $responseArray['status'] = "Error";
                $responseArray['message'] = "Invalid Seller Id or Order Id";
                return $this->getResponseFormat($responseArray);
            }

        } catch (\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    protected function getShipment($orderId, $shipmentId){
        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);
        $shipmentCollection = $order->getShipmentsCollection();

        if($shipmentCollection->count()){
            foreach ($shipmentCollection as $_shipment) {
                if($shipmentId == $_shipment->getId()){
                    $shipment = $_shipment;
                }

            }
        }
        if($shipment->getId()){
            return $shipment;
        }else{
            return 0;
        }
    }

    // Add Shipment Tracking
    public function addTracking($carrier, $number, $title, $orderId, $shipmentId){
        try{
            $response = array();

            $shipment = $this->getShipment($orderId, $shipmentId);
            if($shipment->getId()){
                $track = $this->_objectManager->create(\Magento\Sales\Model\Order\Shipment\Track::class)->setNumber($number)->setCarrierCode($carrier)->setTitle($title);
                $shipment->addTrack($track)->save();
                $trackId  = $track->getId();
                $response['status'] = "Success";
                $response['message'] = "Tracking Added Successfully";
                $response['trackId'] = $trackId;

            }else{
                $response['status'] = "Success";
                $response['message'] = "Invalid Shipment";
            }
            return $this->getResponseFormat($response);
        } catch (\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    public function deleteTracking($trackingId, $shipmentId, $orderId){
        try{
            $response = array();

            $shipment = $this->getShipment($orderId, $shipmentId);

            if($shipment->getId()){
                $track = $this->_objectManager->create(\Magento\Sales\Model\Order\Shipment\Track::class)
                ->load($trackingId);
                if ($track->getId()) {
                    $track->delete();
                    $response['status'] = "Success";
                    $response['message'] = "Tracking deleted successfully.";
                }else{
                    $response['status'] = "Success";
                    $response['message'] = "Invalid trackingid";
                }
            }else{
                $response['status'] = "Success";
                $response['message'] = "Invalid Shipment";
            }
            return $this->getResponseFormat($response);
        } catch (\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    public function getCarriers($orderId, $shipmentId)
    {
        try{
            $shipment = $this->getShipment($orderId, $shipmentId);

            if($shipment->getId()){
                $response = [];
                $carrierInstances = $this->_getCarriersInstances($shipment);

                $carriers['code'] = 'custom';
                $carriers['title'] = __('Custom Value');
                array_push($response, $carriers);
                foreach ($carrierInstances as $code => $carrier) {
                    $carrierInfo = array();
                    if ($carrier->isTrackingAvailable()) {
                        $carrierInfo['code'] = $code;
                        $carrierInfo['title'] = $carrier->getConfigData('title');
                        array_push($response, $carrierInfo);
                    }
                }
            }

            return $this->getResponseFormat($response);
        } catch (\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    /**
     * @return array
     */
    protected function _getCarriersInstances($shipment)
    {
        $shippingConfig = $this->_objectManager->create('Magento\Shipping\Model\Config');
        return $shippingConfig->getAllCarriers($shipment->getStoreId());
    }

    public function shipOrder($orderId, $sellerId,  $trackingid, $carrier, $shipmentId, $send_email, $shippingLabel){
        try{
            $returnArray = [];
            $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);
            $marketplaceOrder = $this->_objectManager->create('Webkul\Marketplace\Helper\Orders')
            ->getOrderinfo($orderId);
            if (!empty($trackingid)) {

                $trackingData[1]['number'] = $trackingid;
                $trackingData[1]['carrier_code'] = 'custom';
            }
            if (!empty($carrier)) {
                $trackingData[1]['title'] = $carrier;
            }


            $trackingData = [];
            $response = array();

            if ($order->canUnhold()) {
                $response['status'] = "Error";
                $response['message'] = __('Can not create shipment as order is in HOLD state');

            } else {
                $items = [];

                $collection = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Saleslist'
                )
                ->getCollection()
                ->addFieldToFilter(
                    'order_id',
                    $orderId
                )
                ->addFieldToFilter(
                    'seller_id',
                    $sellerId
                );
                foreach ($collection as $saleproduct) {
                    array_push($items, $saleproduct['order_item_id']);
                }
                $itemsarray = $this->_getShippingItemQtys($order, $items);

                if (count($itemsarray) > 0) {


                    $shipment = false;
                    $shipmentId = 0;
                    if (!empty($shipmentId)) {
                        $shipmentId = $shipmentId;
                    }
                    if ($shipmentId) {
                        $shipment = $this->_objectManager->create(
                            'Magento\Sales\Model\Order\Shipment'
                        )->load($shipmentId);
                    } elseif ($orderId) {
                        if ($order->getForcedDoShipmentWithInvoice()) {
                            $response['status'] = "Error";
                            $response['message'] = __('Cannot do shipment for the order separately from invoice.');

                        }
                        if (!$order->canShip()) {
                            $response['status'] = "Error";
                            $response['message'] = __('Cannot do shipment for the order.');

                        }

                        $shipment = $this->_prepareShipment(
                            $order,
                            $itemsarray['data'],
                            $trackingData
                        );
                        if ($shippingLabel!='') {
                            $shipment->setShippingLabel($shippingLabel);
                        }
                    }
                    if ($shipment) {
                        $comment = '';
                        $shipment->getOrder()->setCustomerNoteNotify(
                            !empty($send_email)
                        );
                        $isNeedCreateLabel=!empty($shippingLabel) && $shippingLabel;
                        $shipment->getOrder()->setIsInProcess(true);

                        $transactionSave = $this->_objectManager->create('Magento\Framework\DB\Transaction')
                        ->addObject($shipment)
                        ->addObject($shipment->getOrder());
                        $transactionSave->save();

                        $shipmentId = $shipment->getId();

                        $sellerCollection = $this->_objectManager->create(
                            'Webkul\Marketplace\Model\Orders'
                        )
                        ->getCollection()
                        ->addFieldToFilter(
                            'order_id',
                            ['eq' => $orderId]
                        )
                        ->addFieldToFilter(
                            'seller_id',
                            ['eq' => $sellerId]
                        );
                        foreach ($sellerCollection as $row) {
                            if ($shipment->getId() != '') {
                                $row->setShipmentId($shipment->getId());
                                $row->setTrackingNumber($trackingid);
                                $row->setCarrierName($carrier);
                                if ($row->getInvoiceId()) {
                                    $row->setOrderStatus('complete');
                                } else {
                                    $row->setOrderStatus('processing');
                                }
                                $row->save();
                            }
                        }
                        $shipSender = $this->_objectManager->create('Magento\Sales\Model\Order\Email\Sender\ShipmentSender');

                        $shipSender->send($shipment);
                        $response['status'] = "Success";
                        $response['message'] = __('The shipment has been created.');


                    }
                }else{
                    $response['status'] = "Error";
                    $response['message'] = __('No Items to ship.');
                }

            }

            return $this->getResponseFormat($response);

        }catch (\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    // Create Invoice by Sellere
    public function invoiceOrder($orderId, $sellerId){
        try {
            $helper = $this->_objectManager->create(
                'Webkul\Marketplace\Helper\Data'
            );
            /*$orderHelper = $this->_objectManager->create(
                '\Webkul\Marketplace\Helper\Orders'
            );*/
            $response = [];
            $isPartner = $helper->isSeller();

            $order = $this->_orderRepository->get($orderId);

            if($this->doInvoiceExecution($order, $sellerId)){
                $response['status'] = "Success";
                $response['message'] = "Invoice created";
            }else{
                $response['status'] = "Error";
                $response['message'] = "Invoice not created";
            }

                //$this->doAdminShippingInvoiceExecution($order, $sellerId)
            return $this->getResponseFormat($response);
        } catch (\Exception $e) {
            return $this->errorMessage($e);
        }
    }

    protected function doInvoiceExecution($order, $sellerId) {
        try {
            $helper = $this->_objectManager->create('Webkul\Marketplace\Helper\Data');
            $orderId = $order->getId();

            if ($order->canUnhold()) {
                return false;
            }else{
                $data = [];
                $data['send_email'] = 1;

                $data = [];
                $model = $this->_objectManager->create('Webkul\Marketplace\Model\Orders')->getCollection()
                ->addFieldToFilter(
                    'seller_id',
                    $sellerId
                )
                ->addFieldToFilter(
                    'order_id',
                    $orderId
                );

                $salesOrder = $this->_objectManager->create('Webkul\Marketplace\Model\ResourceModel\Orders\Collection')->getTable('sales_order');

                $model->getSelect()->join(
                    $salesOrder.' as so',
                    'main_table.order_id = so.entity_id',["order_approval_status" => "order_approval_status"])->where("so.order_approval_status=1");
                foreach ($model as $tracking) {
                    $data = $tracking;
                }
                $invoiceId = 0;
                $invoiceId = $data->getInvoiceId();

                if (!$invoiceId) {
                    $items = [];
                    $itemsarray = [];
                    $shippingAmount = 0;
                    $couponAmount = 0;
                    $codcharges = 0;
                    $paymentCode = '';
                    $paymentMethod = '';
                    if($order->getPayment()) {
                        $paymentCode = $order->getPayment()->getMethod();
                    }
                    $trackingsdata = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Orders'
                    )
                    ->getCollection()
                    ->addFieldToFilter(
                        'order_id',
                        $orderId
                    )
                    ->addFieldToFilter(
                        'seller_id',
                        $sellerId
                    );

                    foreach ($trackingsdata as $tracking) {
                        $shippingAmount = $tracking->getShippingCharges();
                        $couponAmount = $tracking->getCouponAmount();
                        if ($paymentCode == 'mpcashondelivery') {
                            $codcharges = $tracking->getCodCharges();
                        }
                    }
                    $codCharges = 0;
                    $tax = 0;
                    $currencyRate = 1;
                    $collection = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Saleslist'
                    )
                    ->getCollection()
                    ->addFieldToFilter(
                        'order_id',
                        ['eq' => $orderId]
                    )
                    ->addFieldToFilter(
                        'seller_id',
                        ['eq' => $sellerId]
                    );

                    foreach ($collection as $saleproduct) {
                        $currencyRate = $saleproduct->getCurrencyRate();
                        if ($paymentCode == 'mpcashondelivery') {
                            $codCharges = $codCharges + $saleproduct->getCodCharges();
                        }
                        $tax = $tax + $saleproduct->getTotalTax();
                        array_push($items, $saleproduct['order_item_id']);
                    }

                    $itemsarray = $this->_getItemQtys($order, $items);

                    if (count($itemsarray) > 0 && $order->canInvoice()) {
                        $invoice = $this->_objectManager->create(
                            'Magento\Sales\Model\Service\InvoiceService'
                        )->prepareInvoice($order, $itemsarray['data']);
                        if (!$invoice) {
                            return false;
                        }
                        if (!$invoice->getTotalQty()) {
                            return false;
                        }

                        if (!empty($data['capture_case'])) {
                            $invoice->setRequestedCaptureCase(
                                $data['capture_case']
                            );
                        }

                        if (!empty($data['comment_text'])) {
                            $invoice->addComment(
                                $data['comment_text'],
                                isset($data['comment_customer_notify']),
                                isset($data['is_visible_on_front'])
                            );

                            $invoice->setCustomerNote($data['comment_text']);
                            $invoice->setCustomerNoteNotify(
                                isset($data['comment_customer_notify'])
                            );
                        }

                        $currentCouponAmount = $currencyRate * $couponAmount;
                        $currentShippingAmount = $currencyRate * $shippingAmount;
                        $currentTaxAmount = $currencyRate * $tax;
                        $currentCodcharges = $currencyRate * $codcharges;
                        $invoice->setBaseDiscountAmount($couponAmount);
                        $invoice->setDiscountAmount($currentCouponAmount);
                        $invoice->setShippingAmount($currentShippingAmount);
                        $invoice->setBaseShippingInclTax($shippingAmount);
                        $invoice->setBaseShippingAmount($shippingAmount);
                        $invoice->setSubtotal($itemsarray['subtotal']);
                        $invoice->setBaseSubtotal($itemsarray['baseSubtotal']);
                        if ($paymentCode == 'mpcashondelivery') {
                            $invoice->setMpcashondelivery($currentCodcharges);
                            $invoice->setBaseMpcashondelivery($codCharges);
                        }
                        $invoice->setGrandTotal(
                            $itemsarray['subtotal'] +
                            $currentShippingAmount +
                            $currentCodcharges +
                            $currentTaxAmount -
                            $currentCouponAmount
                        );
                        $invoice->setBaseGrandTotal(
                            $itemsarray['baseSubtotal'] +
                            $shippingAmount +
                            $codcharges +
                            $tax -
                            $couponAmount
                        );
                        $invoice->register();

                        $invoice->getOrder()->setCustomerNoteNotify(
                            !empty($data['send_email'])
                        );
                        $invoice->getOrder()->setIsInProcess(true);

                        $transactionSave = $this->_objectManager->create(
                            'Magento\Framework\DB\Transaction'
                        )->addObject(
                            $invoice
                        )->addObject(
                            $invoice->getOrder()
                        );
                        $transactionSave->save();

                        $invoiceId = $invoice->getId();

                    }
                    if ($invoiceId != '') {
                        if ($paymentCode == 'mpcashondelivery') {
                            $saleslistColl = $this->_objectManager->create(
                                'Webkul\Marketplace\Model\Saleslist'
                            )
                            ->getCollection()
                            ->addFieldToFilter(
                                'order_id',
                                $orderId
                            )
                            ->addFieldToFilter(
                                'seller_id',
                                $sellerId
                            );
                            foreach ($saleslistColl as $saleslist) {
                                $saleslist->setCollectCodStatus(1);
                                $saleslist->save();
                            }
                        }

                        $trackingcol1 = $this->_objectManager->create(
                            'Webkul\Marketplace\Model\Orders'
                        )
                        ->getCollection()
                        ->addFieldToFilter(
                            'order_id',
                            $orderId
                        )
                        ->addFieldToFilter(
                            'seller_id',
                            $sellerId
                        );
                        foreach ($trackingcol1 as $row) {
                            $row->setInvoiceId($invoiceId);
                            if ($row->getShipmentId()) {
                                $row->setOrderStatus('complete');
                            } else {
                                $row->setOrderStatus('processing');
                            }
                            $row->save();
                        }
                        return true;
                    }
                }
            }
        }  catch (\Exception $e) {
            return false;
        }
    }

    protected function doAdminShippingInvoiceExecution($order, $sellerId) {
        try {
            $paymentCode = '';
            $paymentMethod = '';
            if ($order->getPayment()) {
                $paymentCode = $order->getPayment()->getMethod();
            }



           // echo $order->getGrandTotal() > $order->getTotalPaid() ? "Yes":"no";

            // /&& ($order->getGrandTotal() > $order->getTotalPaid())
            //die("DDDDDDDDD");
            if (!$order->canUnhold()) {


                $isAllItemInvoiced = $this->isAllItemInvoiced($order);

                if ($isAllItemInvoiced && $order->getShippingAmount()) {


                    $invoice = $this->_objectManager->create(
                        'Magento\Sales\Model\Service\InvoiceService'
                    )->prepareInvoice(
                        $order,
                        []
                    );
                    if (!$invoice) {
                       return false;
                   }

                   $baseSubtotal = $order->getBaseShippingAmount();
                   $subtotal = $order->getShippingAmount();

                   if (!empty($data['capture_case'])) {
                    $invoice->setRequestedCaptureCase(
                        $data['capture_case']
                    );
                }

                if (!empty($data['comment_text'])) {
                    $invoice->addComment(
                        $data['comment_text'],
                        isset($data['comment_customer_notify']),
                        isset($data['is_visible_on_front'])
                    );

                    $invoice->setCustomerNote($data['comment_text']);
                    $invoice->setCustomerNoteNotify(
                        isset($data['comment_customer_notify'])
                    );
                }
                $invoice->setShippingAmount($subtotal);
                $invoice->setBaseShippingInclTax($baseSubtotal);
                $invoice->setBaseShippingAmount($baseSubtotal);
                $invoice->setSubtotal($subtotal);
                $invoice->setBaseSubtotal($baseSubtotal);
                $invoice->setGrandTotal($subtotal);
                $invoice->setBaseGrandTotal($baseSubtotal);
                $invoice->register();

                $invoice->getOrder()->setCustomerNoteNotify(
                    !empty($data['send_email'])
                );
                $invoice->getOrder()->setIsInProcess(true);

                $transactionSave = $this->_objectManager->create('Magento\Framework\DB\Transaction')->addObject($invoice)->addObject($invoice->getOrder());

                $transactionSave->save();
                $_invoiceSender = $this->_objectManager->create('Magento\Sales\Model\Order\Email\Sender\InvoiceSender');
                $_invoiceSender->send($invoice);

                die("helkllllllllllllllllll");
                return true;
            }
        }
    } catch (\Exception $e) {
        return false;
    }
}

protected function isAllItemInvoiced($order)
{
    $flag = 1;
    foreach ($order->getAllItems() as $item) {
        if ($item->getParentItem()) {
            continue;
        } else if ($item->getProductType() == 'bundle') {
        // for bundle product
            $bundleitems = array_merge([$item], $item->getChildrenItems());
            foreach ($bundleitems as $bundleitem) {
                if ($bundleitem->getParentItem()) {
                    if (intval($bundleitem->getQtyOrdered() - $item->getQtyInvoiced())) {
                        $flag = 0;
                    }
                }
            }
        } else {
            if (intval($item->getQtyOrdered() - $item->getQtyInvoiced())) {
                $flag = 0;
            }
        }
    }

    return $flag;
}

protected function _getItemQtys($order, $items)
{
    $data = [];
    $subtotal = 0;
    $baseSubtotal = 0;
    foreach ($order->getAllItems() as $item) {
        if (in_array($item->getItemId(), $items)) {
            $data[$item->getItemId()] = intval($item->getQtyOrdered() - $item->getQtyInvoiced());

            $_item = $item;

        // for bundle product
            $bundleitems = array_merge([$_item], $_item->getChildrenItems());

            if ($_item->getParentItem()) {
                continue;
            }

            if ($_item->getProductType() == 'bundle') {
                foreach ($bundleitems as $_bundleitem) {
                    if ($_bundleitem->getParentItem()) {
                        $data[$_bundleitem->getItemId()] = intval(
                            $_bundleitem->getQtyOrdered() - $item->getQtyInvoiced()
                        );
                    }
                }
            }
            $subtotal += $_item->getRowTotal();
            $baseSubtotal += $_item->getBaseRowTotal();
        } else {
            if (!$item->getParentItemId()) {
                $data[$item->getItemId()] = 0;
            }
        }
    }

    return ['data' => $data,'subtotal' => $subtotal,'baseSubtotal' => $baseSubtotal];
}

protected function _prepareShipment($order, $items, $trackingData)
{
    $shipmentFactory = $this->_objectManager->create('Magento\Sales\Model\Order\ShipmentFactory');
    $shipment = $shipmentFactory->create(
        $order,
        $items,
        $trackingData
    );

    if (!$shipment->getTotalQty()) {
        return false;
    }

    return $shipment->register();
}

protected function _getShippingItemQtys($order, $items)
{
    $data = [];
    $subtotal = 0;
    $baseSubtotal = 0;
    foreach ($order->getAllItems() as $item) {
        if (in_array($item->getItemId(), $items)) {
            $data[$item->getItemId()] = intval($item->getQtyOrdered() - $item->getQtyShipped());

            $_item = $item;

        // for bundle product
            $bundleitems = array_merge([$_item], $_item->getChildrenItems());

            if ($_item->getParentItem()) {
                continue;
            }

            if ($_item->getProductType() == 'bundle') {
                foreach ($bundleitems as $_bundleitem) {
                    if ($_bundleitem->getParentItem()) {
                        $data[$_bundleitem->getItemId()] = intval(
                            $_bundleitem->getQtyOrdered() - $item->getQtyShipped()
                        );
                    }
                }
            }
            $subtotal += $_item->getRowTotal();
            $baseSubtotal += $_item->getBaseRowTotal();
        } else {
            if (!$item->getParentItemId()) {
                $data[$item->getItemId()] = 0;
            }
        }
    }

    return ['data' => $data,'subtotal' => $subtotal,'baseSubtotal' => $baseSubtotal];
}

    // get Seller Invoice Detail
public function getSellerInvoiceDetails($orderId,$sellerId,$invoiceId) {

    try {
        $helper = $this->_objectManager->create('Webkul\Marketplace\Helper\Data');
        $orderId = $orderId;
        $sellerId = $sellerId;
        $invoiceId = $invoiceId;

        $model = $this->_objectManager->create('Magento\Sales\Model\Order');
        $order = $model->load($orderId);

        $invoice_model = $this->_objectManager->create('\Magento\Sales\Model\Order\Invoice');
        $invoice = $invoice_model->load($invoiceId);

        $response = [];
        $invoiceStatus = "";


        if ($invoice->getState()==1) {
            $invoiceStatus = __('Pending');
        } else if ($invoice->getState()==2) {
            $invoiceStatus = __('Paid');
        } else if ($invoice->getState()==3) {
            $invoiceStatus = __('Canceled');
        }
        $response['invoice']['status'] = $invoiceStatus;
        $response['invoice']['invoice_date'] = $invoice->getCreatedAt();
        $response['invoice']['order_status'] = $order->getStatus();

        $paymentMethod = '';
        if ($order->getPayment()) {
            $paymentMethod = $order->getPayment()->getMethodInstance()->getTitle();
        }
        if ($order->getShippingDescription()) {
            $shippingDescription =  $order->getShippingDescription();
        } else {
            $shippingDescription = 'No shipping information available';
        }

        $response['invoice']['print_invoice'] =$this->_base_url.'marketplace/order_invoice/printpdf/order_id/'.$orderId.'/invoice_id/'.$invoiceId;
        $response['invoice']['invoice_id'] = $invoiceId;
        $response['invoice']['invoice_increment_id'] = $invoice->getIncrementId();
        $response['invoice']['payment_method'] = $paymentMethod;
        $response['invoice']['shipping_description'] = $shippingDescription;
        $response['invoice']['order_date'] = $order->getCreatedAt();

        $response['billingAddress'] = $order->getBillingAddress()->getData();
        $response['shippingAddress'] = $order->getShippingAddress()->getData();
        $customerArray['customer_name'] = $order->getCustomerName();
        $customerArray['customer_email'] = $order->getCustomerEmail();
        $response['customer_details'] = $customerArray;
        $incr = 0;
        $order_sub_total = 0;
        $order_admin_commission = 0;
        $order_total_amount = 0;
        $order_vendor_amount = 0;
        $items = $order->getItems();
        $currencyRate = $this->getCurrencyRate($orderId,$sellerId);
        foreach ($items as $item) {

            $rowTotal = $item->getPrice() * $item->getQtyOrdered();

            $sellerItemCost= $this->actualSellerAmount($orderId,$sellerId);

            $sellerItemCommission = $this->getTotalCommission($sellerId);

            if (!$currencyRate) {
                $currencyRate = 1;
            }

            $response['invoice']['item'][$incr]['product_name'] = $item->getName();
            $response['invoice']['item'][$incr]['product_price'] = $item->getPrice();
            $response['invoice']['item'][$incr]['order_qty'] =$item->getQtyOrdered()*1;
            $response['invoice']['item'][$incr]['invoice_qty'] = $item->getQtyInvoiced()*1;
            $response['invoice']['item'][$incr]['shipped_qty'] = $item->getQtyShipped()*1;
            $response['invoice']['item'][$incr]['cancel_qty'] = $item->getQtyCanceled()*1;
            $response['invoice']['item'][$incr]['refunded_qty'] = $item->getQtyRefunded()*1;



            $total_price =  $rowTotal*$currencyRate;

            $response['invoice']['item'][$incr]['total_price'] = $total_price;

            $admin_commission = $sellerItemCommission * $currencyRate;
            $response['invoice']['item'][$incr]['admin_commission'] = $admin_commission;

            $vendor_total =  $sellerItemCost*$currencyRate;
            $response['invoice']['item'][$incr]['vendor_total'] = $vendor_total;
            $response['invoice']['item'][$incr]['sub_total'] = $total_price;
            $order_sub_total = $order_sub_total+$total_price;
            $order_admin_commission = $order_admin_commission + $admin_commission;
            $order_total_amount = $order_total_amount+$total_price;
            $order_vendor_amount = $order_vendor_amount+$vendor_total;
            $incr++;
        }
        $response['invoice']['order_sub_total']        = $order_sub_total;
        $response['invoice']['order_admin_commission'] = $order_admin_commission;
        $response['invoice']['order_total_amount']   = $order_total_amount;
        $response['invoice']['order_vendor_amount']  = $order_vendor_amount;

        $response['status'] = 'success';
            //echo '<pre>';print_r($response);die;
        return $this->getResponseFormat($response);
    } catch (Exception $e) {
     return $this->errorMessage($e);
 }
}

protected function getCurrencyRate($orderId,$sellerId) {
    $collection = $this->_objectManager->create(
        'Webkul\Marketplace\Model\Saleslist'
    )
    ->getCollection()
    ->addFieldToFilter(
        'order_id',
        ['eq' => $orderId]
    )
    ->addFieldToFilter(
        'seller_id',
        ['eq' => $sellerId]
    );
    $currencyRate = 1;
    foreach ($collection as $val) {
        $currencyRate = $val->getCurrencyRate();
    }
    if(!$currencyRate) { $currencyRate = 1;}
    return $currencyRate;
}
    // get actual seller amount
protected function actualSellerAmount($orderId,$sellerId)
{
    $collection = $this->_objectManager->create(
        'Webkul\Marketplace\Model\Saleslist'
    )->getCollection()
    ->addFieldToFilter(
        'main_table.seller_id',
        $sellerId
    )->addFieldToFilter(
        'main_table.order_id',
        $orderId
    )->getPricebyorderData();
    $name = '';
    $actualSellerAmount = 0;
    foreach ($collection as $coll) {
            // calculate order actual_seller_amount in base currency
        $appliedCouponAmount = $coll['applied_coupon_amount']*1;
        $shippingAmount = $coll['shipping_charges']*1;
        $refundedShippingAmount = $coll['refunded_shipping_charges']*1;
        $totalshipping = $shippingAmount - $refundedShippingAmount;
        $vendorTaxAmount = $coll['total_tax']*1;
        if ($coll['actual_seller_amount'] * 1) {
            $taxShippingTotal = $vendorTaxAmount + $totalshipping - $appliedCouponAmount;
            $actualSellerAmount += $coll['actual_seller_amount'] + $taxShippingTotal;
        } else {
            if ($totalshipping * 1) {
                $actualSellerAmount += $totalshipping - $appliedCouponAmount;
            }
        }
    }
    return $actualSellerAmount;
}

    /**
     * @return int|float
     */
    protected function getTotalCommission($sellerId)
    {
        $totalCommission = 0;

        $collection = $this->saleslistColl->create()->addFieldToFilter(
            'main_table.seller_id',
            $sellerId
        )
        ->addFieldToFilter(
            'cpprostatus',
            1
        );

        $totalAmountArr = $collection->getTotalSellerAmount();
        if (!empty($totalAmountArr[0]['total_commission'])) {
            $totalCommission = $totalAmountArr[0]['total_commission'];
        }
        return $totalCommission;
    }

     // get Seller Invoice Detail
    public function getBuyerInvoiceDetails($orderId) {

        try {
            $helper = $this->_objectManager->create('Webkul\Marketplace\Helper\Data');
            $rmaHelper = $this->_objectManager->create('Webkul\MpRmaSystem\Helper\Data');
            $orderId = $orderId;
            $model = $this->_objectManager->create('Magento\Sales\Model\Order');
            $order = $model->load($orderId);
            $order_data = $order->getData();
            //$ordeData = $order->getInvoiceCollection()->getData();
            $response = [];

            if ($order->getShippingDescription()) {
                $shippingDescription =  $order->getShippingDescription();
            } else {
                $shippingDescription = 'No shipping information available';
            }
            $response['invoice']['shipping_description'] = $shippingDescription;

            $response['invoice']['order_status'] = $order->getStatus();
            $response['invoice']['order_date'] = $order->getCreatedAt();

            $customerArray['customer_name'] = $order->getCustomerName();
            $customerArray['customer_email'] = $order->getCustomerEmail();
            $response['customer_details'] = $customerArray;



            $incr = 0;
            $paymentMethod = '';
            if ($order->getPayment()) {
                $paymentMethod = $order->getPayment()->getMethodInstance()->getTitle();
            }
            $response['invoice']['payment_method'] = $paymentMethod;
            $response['billingAddress'] = $order->getBillingAddress()->getData();
            $response['shippingAddress'] = $order->getShippingAddress()->getData();
            $customerArray['customer_name'] = $order->getCustomerName();
            $customerArray['customer_email'] = $order->getCustomerEmail();

            $response['invoice']['print_all_invoice'] = $this->_base_url.'sales/order/printInvoice/order_id/'.$orderId;
            //echo $this->_base_url;die;

            foreach ($order->getInvoiceCollection() as $_invoice) {

                $response['invoice']['item'][$incr]['invoice_incremented_id'] =$_invoice->getIncrementId();
                $response['invoice']['item'][$incr]['print_invoice'] =$this->_base_url.'sales/order/printInvoice/invoice_id/'.$_invoice->getId();
                $invoiceStatus1 = "";
                if ($_invoice->getState()==1) {
                    $invoiceStatus1 = __('Pending');
                } else if ($_invoice->getState()==2) {
                    $invoiceStatus1 = __('Paid');
                } else if ($_invoice->getState()==3) {
                    $invoiceStatus1 = __('Canceled');
                }
                $response['invoice']['item'][$incr]['invoice_status'] = $invoiceStatus1;
                $response['invoice']['item'][$incr]['invoice_date'] =$_invoice->getCreatedAt();

                $_items = $_invoice->getAllItems();

                $response['invoice']['item'][$incr]['item_detail'] = [];
                $response['invoice']['item'][$incr]['invoice_sub_total'] = '';
                $response['invoice']['item'][$incr]['invoice_grand_total'] = '';
                $i = 0;
                $incr = 0;
                foreach ($_items as $_item) {

                    $response['invoice']['item'][$incr]['item_detail'][$i]['product_name'] = $_item->getName();

                    $response['invoice']['item'][$incr]['item_detail'][$i]['sku'] = $_item->getSku();
                    $response['invoice']['item'][$incr]['item_detail'][$i]['product_price'] = $_item->getPrice();
                    $response['invoice']['item'][$incr]['item_detail'][$i]['invoice_qty'] = $_item->getQty()*1;

                    $response['invoice']['item'][$incr]['invoice_sub_total'] = $_invoice->getSubTotal();
                    $response['invoice']['item'][$incr]['invoice_grand_total'] = $_invoice->getGrandTotal();
                    $incr++; $i++;
                }
            }
            $response['status'] = 'success';
            return $this->getResponseFormat($response);
        } catch (Exception $e) {
            return $this->errorMessage($e);
        }
    }

    // Get Sold Items of Seller
    public function getList($id, $length, $pageNum, $orderBy, $orderDir, $searchOrderId, $orderStatus) {
        $response = array();

        if (isset($searchOrderId)) {
            $filterOrderid = $searchOrderId != '' ? $searchOrderId : '';
        }
        if (isset($orderStatus)) {
            $filterOrderstatus = $orderStatus != '' ? $orderStatus : '';
        }

        $historyBlock = $this->_objectManager->get('Webkul\Marketplace\Block\Order\History');

        $orderids = $this->getOrderIdsArray($id, $filterOrderstatus);
        $ids = $historyBlock->getEntityIdsArray($orderids);

        $webkulOrderCollectionFactory = $this->_objectManager->create('Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory');
        $collection = $webkulOrderCollectionFactory->create()->addFieldToSelect(
            '*'
        )
        ->addFieldToFilter(
            'entity_id',
            ['in' => $ids]
        );

        if ($filterOrderid) {
            $collection->addFieldToFilter(
                'magerealorder_id',
                ['eq' => $filterOrderid]
            );
        }
        $collection->getSellerOrderCollection();

        $collection->setPageSize($length)->setCurPage($pageNum);
        $collection->setOrder($orderBy, $orderDir);
        $totalCount = $collection->getSize();
        $content = array();
        $i = 0;
        foreach($collection as $eachOrder){

            $data = array();
            $data = $eachOrder->getData();

            $orderRes = [];
            $orderCollection = $this->_objectManager->create(
                'Magento\Sales\Model\Order'
            )->getCollection()
            ->addFieldToFilter(
                'entity_id',
                ['eq' => $eachOrder->getOrderId()]
            );
            foreach ($orderCollection as $res) {
                $shipmentData = $res->getShippingAddress()->getData();
                $orderRes = $res;
                break;
            }

            $collection = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Saleslist'
            )->getCollection()
            ->addFieldToFilter(
                'seller_id',
                ['eq' => $id]
            )
            ->addFieldToFilter(
                'order_id',
                ['eq' => $eachOrder->getOrderId()]
            );
            $name = '';
            $nameId = array();
            foreach ($collection as $res) {
                $data['productname'] = $res['magepro_name'];

            }
            $content[$i]['order_id'] = $res['order_id'];
            $content[$i]['order_incrementId'] = $res['magerealorder_id'];
            $content[$i]['product_name'] = $data['productname'];
            $content[$i]['shipment_firstname'] = $shipmentData['firstname'];
            $content[$i]['shipment_middlename'] = $shipmentData['middlename'];
            $content[$i]['shipment_lastname'] = $shipmentData['lastname'];
            $content[$i]['created_at'] = $eachOrder['created_at'];
            $content[$i]['status']=$orderRes->getStatus();
            $content[$i]['subtotal'] = $orderRes->getSubTotal();
            $content[$i]['shipping_amount'] = $orderRes->getShippingAmount();
            $content[$i]['grand_total'] = $orderRes->getGrandTotal();
            $i++;
        }

        $response['orders'] = $content;
        $response['totalCount'] = $totalCount;
        return $this->getResponseFormat($response);
    }

    // GEt Order By Order Id

    public function getOrderDetails($orderId, $sellerId){



        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);

        if($order->getId()){
            $orderDatamodel = $this->_objectManager->get('Webkul\Marketplace\Model\Orders')->getCollection()
            ->addFieldToSelect( '*' )
            ->addFieldToFilter( 'seller_id',$sellerId )
            ->addFieldToFilter( 'order_id',$orderId )
            ->getFirstItem()
            ;
            $soldItemsArray = explode(",",$orderDatamodel->getProductIds());
            $soldInvoiceId = $orderDatamodel->getInvoiceId();
            $soldShipmentId = $orderDatamodel->getShipmentId();

            $store = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
            $shipmentCollection = $order->getShipmentsCollection();
            $customerRepo = $this->_objectManager->create('Magento\Customer\Api\CustomerRepositoryInterface');
            $customer = $customerRepo->getById($order->getCustomerId());

            $marketplaceHelper = $this->_objectManager->create('Webkul\Marketplace\Helper\Data');
            $apiHelper = $this->_objectManager->create('Seasia\Customapi\Helper\Data');
            $partner = $marketplaceHelper->getSellerDataBySellerId($order->getCustomerId())->getFirstItem();
            if ($partner->getLogoPic()) {
                $logoPic = $marketplaceHelper->getMediaUrl().'avatar/'.$partner->getLogoPic();
            } else {
                $logoPic = $marketplaceHelper->getMediaUrl().'avatar/noimage.png';
            }

            $shipmentId = 0;
            $invoiceId = 0;
            if($shipmentCollection->count()){
                foreach ($shipmentCollection as $shipment) {
                    if($shipment->getId() == $soldShipmentId){
                        $shipmentId = $shipment->getId();
                    }

                }
            }
            if($order->getInvoiceCollection()->count()){
                foreach ($order->getInvoiceCollection() as $invoice)
                {
                    if($invoice->getId() == $soldInvoiceId){
                        $invoiceId = $invoice->getId();
                    }
                }
            }
            $response = array();

            $orderArray =array();
            $orderArray['entity_id'] = $order->getId();
            $orderArray['increment_id'] = $order->getIncrementId();
            $orderArray['created_at'] = $order->getCreatedAt();
            $orderArray['sub_total'] = $order->getSubTotal();
            $orderArray['shipping_amount'] = $order->getShippingAmount();
            $orderArray['grand_total'] = $order->getGrandTotal();
            $orderArray['status'] = $order->getStatus();
            $orderArray['deliveryDate'] = $order->getDeliveryDate();
            $orderArray['endiciaDeliverStatus'] = $order->getEndiciaDeliverStatus();
            $orderArray['deliveryConfirm'] = $order->getDeliveryConfirm();
            $orderArray['shipmentId'] = $shipmentId;

            $orderArray['invoiceId'] = $invoiceId;

            $response['order'] = $orderArray;

            $itemsArray = array();
            $customerArray = array();
            $customerArray['first_name'] = $customer->getFirstname();
            $customerArray['last_name'] = $customer->getLastname();
            $customerArray['entity_id'] = $customer->getId();
            $customerArray['email'] = $customer->getEmail();
            $customerArray['buyer_logo'] = $logoPic;
            $customerArray['buyerInfo'] = $apiHelper->getSellerShopById($order->getCustomerId());

            $returnData = $this->returnFactory->create();

            foreach ($order->getAllItems() as $item) {

                if(in_array($item->getProductId(), $soldItemsArray)){
                    $itemData = $item->getData();
                    $_product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());
                    $itemData['description'] = $_product->getDescription();

                    $itemData['imageUrl'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getImage();

                    $itemData['imageUrl'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getImage();

                    $itemData['productUrl'] = $_product->getProductUrl();

                    $returnCollection = $returnData->getCollection()
                    ->addFieldToFilter('product_id', array('eq' => $item->getProductId()))
                    ->addFieldToFilter('order_id', array('eq' => $order->getId()))
                    ->getFirstItem();

                    $itemData['returnData'] = array();
                    if($returnCollection->getId()){
                        $itemData['returnData'] = $returnCollection->getData();
                    }
                    array_push($itemsArray, $itemData);
                }

            }
            $response['items'] = $itemsArray;

            $response['billingAddress'] = $order->getBillingAddress()->getData();
            $response['shippingAddress'] = $order->getShippingAddress()->getData();
            $customerArray['city'] = $order->getShippingAddress()->getCity();
            $response['customer'] = $customerArray;
            $response['status'] = "Success";
        }else{
            $response['status'] = "Error";
            $response['message'] = "Order doesnot exist";
        }
        return $this->getResponseFormat($response);

    }

    protected function getTrackingInfo($sellerId, $orderId){
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Orders'
        )
        ->getCollection()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->addFieldToFilter(
            'order_id',
            $orderId
        );

        $salesOrder = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Orders\Collection'
        )->getTable('sales_order');

        $model->getSelect()->join(
            $salesOrder.' as so',
            'main_table.order_id = so.entity_id',
            ["order_approval_status" => "order_approval_status"]
        )->where("so.order_approval_status=1");
        foreach ($model as $tracking) {
            $data = $tracking;
        }

        return $data;
    }

    protected function getSellerOrderedItems($sellerId, $orderId){
        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Saleslist'
        )->getCollection()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'order_id',
            ['eq' => $orderId]
        );
        $name = '';
        $nameId = array();
        $collection = $collection->getData();
        $itenData = [];
        $i = 0;
        foreach ($collection as $res) {
            $_product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($res['mageproduct_id']);
            $itenData[$i] = $res;
            $itenData[$i]['product_sku'] = $_product->getSku();
            $i++;

        }

        return $itenData;
// return $collection;
    }


    public function _isEndiciaShipment($orderId, $customerId)
    {

        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);
        $shippingmethod = $order->getShippingMethod();

        if (strpos($shippingmethod, 'mpendicia') !== false) {
            $sellerOrders = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Orders'
            )->getCollection()
            ->addFieldToFilter('seller_id', ['eq' => $customerId])
            ->addFieldToFilter('order_id', ['eq' => $orderId]);

            $labelContent = '';
            foreach ($sellerOrders as $row) {
                $labelContent = $row->getShipmentLabel();
            }

            if ($labelContent != '') {
                return true;
            }
        } elseif (strpos($shippingmethod, 'mp_multishipping') !== false) {
            $sellerOrders = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Orders'
            )->getCollection()
            ->addFieldToFilter('seller_id', ['eq' => $customerId])
            ->addFieldToFilter('order_id', ['eq' => $orderId]);

            $labelContent = '';
            $method = '';
            foreach ($sellerOrders as $row) {
                $labelContent = $row->getShipmentLabel();
                $method = $row->getMultishipMethod();
            }

            if ($labelContent != '' && strpos($method, 'mpendicia') !== false) {
                return true;
            }
        }
        return false;
    }

    public function getSoldOrderShipment($sellerId,$orderId, $shipmentId){
        try{
            $response = array();
            $helper = $this->_objectManager->create('Webkul\Marketplace\Helper\Data');
            $orderHelper = $this->_objectManager->create('Webkul\Marketplace\Helper\Orders');
            $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);
            $customerId = $order->getCustomerId();
            $customerRepo = $this->_objectManager->create('Magento\Customer\Api\CustomerRepositoryInterface');
            $customer = $customerRepo->getById($customerId);
            $tracking=$this->getTrackingInfo($sellerId,$orderId);
            foreach ($order->getShipmentsCollection() as $_shipment){

                if($_shipment->getId() == $shipmentId){

                    $shipment = $_shipment;
                }
            }
            if ($order->getPayment()) {
                $response['paymentMethod'] = $order->getPayment()->getMethodInstance()->getTitle();
            }
            $response['shipment_autoid'] = $shipment->getIncrementId();
            $response['created_at'] = $shipment->getCreatedAt();

            $collection = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Orders'
            )->getCollection()
            ->addFieldToFilter(
                'order_id',
                ['eq' => $orderId]
            )
            ->addFieldToFilter(
                'seller_id',
                ['eq' => $sellerId]
            );
            $trackingUrl = "";

            if($tracking->getTrackingNumber()){
                $trackingUrl = "https://tools.usps.com/go/TrackConfirmAction?tLabels=".$tracking->getTrackingNumber();
            }

            $_tracks = $shipment->getAllTracks();
            $trackData = array();
            foreach ($_tracks as $_track){
                array_push($trackData, $_track->getData());
            }
            $orderData['incrementId'] = $order->getIncrementId();
            $orderData['shipping_description'] = $order->getShippingDescription();
            $orderData['created_at'] = $order->getCreatedAt();
            $orderData['status'] = $order->getStatus();
            $response['trackigUrl'] = $trackingUrl;
            $response['orderData'] = $orderData;

            $response['trackData'] = $trackData;
            $response['billingAddress'] = $order->getBillingAddress()->getData();
            $response['shippingAddress'] = $order->getShippingAddress()->getData();

            $response['customer'] = array();
            $response['customer']['firstname'] = $customer->getFirstname();
            $response['customer']['lastname'] = $customer->getLastname();
            $response['customer']['email'] = $customer->getEmail();

            $store = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
            $baseUrl = $store->getBaseUrl();
            $response['endeciaLabelUrl'] =  "";
            if($this->_isEndiciaShipment($orderId, $sellerId)){
                $response['endeciaLabelUrl'] = $baseUrl."endicia/shipment/printpdf/order_id/".$orderId."/shipment_id/".$shipmentId;
            }

            $response['printShipmentUrl'] =  $baseUrl."marketplace/order_shipment/printpdf/order_id/".$orderId."/shipment_id/".$shipmentId;

            $orderItems = $this->getSellerOrderedItems($sellerId, $orderId);
            $response['items'] = $orderItems;//->getData();
            return $this->getResponseFormat($response);
        }catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }


    public function getShipmentDetails($customerId,$orderId){
        try{
            $_order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);

            $shipments = array();
            $helper = $this->_objectManager->create('Magento\Shipping\Helper\Data');
            $response = array();

            $response['billingAddress'] = $_order->getBillingAddress()->getData();
            $response['shippingAddress'] = $_order->getShippingAddress()->getData();
            if ($_order->getPayment()) {
                $response['paymentMethod'] = $_order->getPayment()->getMethodInstance()->getTitle();
            }
            $response['order_increment_id'] = $_order->getIncrementId();
            $itemsArray = array();

            $store = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();

            foreach ($_order->getAllItems() as $item) {
                $itemData = array();
                $itemData = $item->getData();
                $_product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());
                $itemData['description'] = $_product->getDescription();

                $itemData['imageUrl'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getImage();
                array_push($itemsArray, $itemData);
            }
            $response['items'] = $itemsArray;
            $response['shippingmethod'] = $_order->getShippingDescription();
            foreach ($_order->getShipmentsCollection() as $_shipment){
                $shipmentArray = array();

                $shipmentArray['shipmentNumber'] = $_shipment->getIncrementId();
                $shipmentArray['trackingPopupUrl'] = $helper->getTrackingPopupUrlBySalesModel($_shipment);
                $tracks = $_shipment->getTracksCollection();
                if ($tracks->count()){
                    foreach ($tracks as $track){
                        $trackArray = array();
                        $trackArray = $track->getData();
                        if($track->isCustom()){
                            $trackArray['trackUrl'] = "";
                        }else{
                            $trackArray['trackUrl'] = $helper->getTrackingPopupUrlBySalesModel($track);
                        }



                        $shipmentArray['tracking'] = $trackArray;

                        $orderDatamodel = $this->_objectManager->get('Webkul\Marketplace\Model\Orders')->getCollection()->addFieldToSelect( '*' )->addFieldToFilter( 'tracking_number',$track->getNumber() )->getFirstItem();


                        $shipmentArray['confirmStatus'] = $orderDatamodel->getDeliveryConfirm() == "confirmed" ? "confirmed":"confirm";

                    }

                }
                $response['shipmentdetail'] = $shipmentArray;
                //array_push($response, $shipmentArray);

            }
            return $this->getResponseFormat($response);
        }catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }
    public function getBuyerOrderDetails($orderId){
        try{
            $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);
            if($order->getId()){

                $store = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
                $marketplaceHelper = $this->_objectManager->create('Webkul\Marketplace\Helper\Data');
                $customerRepo = $this->_objectManager->create('Magento\Customer\Api\CustomerRepositoryInterface');
                $returnData = $this->returnFactory->create();
                $shipmentCollection = $order->getShipmentsCollection();
                $shipmentId = 0;
                $invoiceId = 0;
                $invoiceCollection = $order->getInvoiceCollection();

                foreach ($shipmentCollection as $shipment) {
                    $shipmentId = $shipment->getId();
                }

                if(count($invoiceCollection) > 0 && !empty($invoiceCollection)){
                    foreach ($order->getInvoiceCollection() as $invoice)
                    {
                        $invoiceId = $invoice->getId();
                    }
                }

                $response = array();

                $orderArray =array();
                $orderArray['entity_id'] = $order->getId();
                $orderArray['increment_id'] = $order->getIncrementId();
                $orderArray['created_at'] = $order->getCreatedAt();
                $orderArray['sub_total'] = $order->getSubTotal();
                $orderArray['shipping_amount'] = $order->getShippingAmount();
                $orderArray['grand_total'] = $order->getGrandTotal();
                $orderArray['status'] = $order->getStatus();
                $orderArray['deliveryDate'] = $order->getDeliveryDate();
                $orderArray['endiciaDeliverStatus'] = $order->getEndiciaDeliverStatus();
                $orderArray['deliveryConfirm'] = $order->getDeliveryConfirm();

                $orderArray['shipmentId'] = $shipmentId;

                $orderArray['invoiceId'] = $invoiceId;

                $response['order'] = $orderArray;

                $itemsArray = array();

                $sellerIds = array();

                $rmaHelper = $this->_objectManager->create('Webkul\MpRmaSystem\Helper\Data');
                foreach ($order->getAllItems() as $item) {
                    $sellerId = $rmaHelper->getSellerIdByproductid($item->getProductId());
                    if(!in_array($sellerId, $sellerIds)){
                        array_push($sellerIds, $sellerId);
                        $orderDatamodel = $this->_objectManager->get('Webkul\Marketplace\Model\Orders')->getCollection()
                        ->addFieldToSelect( '*' )
                        ->addFieldToFilter( 'seller_id',$sellerId )
                        ->addFieldToFilter( 'order_id',$order->getId() )
                        ->getFirstItem();

                        $partner = $marketplaceHelper->getSellerDataBySellerId($sellerId)->getFirstItem();
                        if($partner->getId()){
                            $customer = $customerRepo->getById($sellerId);
                            $itemsArray[$sellerId]['info'] = array();
                            $itemsArray[$sellerId]['info']['seller_id'] = $partner->getId();
                            $itemsArray[$sellerId]['info']['username'] = $partner->getShopUrl();
                            $itemsArray[$sellerId]['info']['shopUrl'] = $marketplaceHelper ->getRewriteUrl(
                                'marketplace/seller/collection/shop/'.
                                $partner->getShopUrl()
                            );
                            $itemsArray[$sellerId]['info']['first_name'] = $customer->getFirstname();
                            $itemsArray[$sellerId]['info']['last_name'] = $customer->getLastname();
                            $itemsArray[$sellerId]['info']['rating'] = $marketplaceHelper->getSelleRating($sellerId);
                            $itemsArray[$sellerId]['info']['deliver_status'] = $orderDatamodel->getEndiciaDeliverStatus();
                            $itemsArray[$sellerId]['info']['review'] = $orderDatamodel->getInReview();

                            $feedbackModel = $this->getOrderFeedbacks($sellerId,$order->getCustomerId(),$orderId);
                            $feedback = $feedbackModel->getFirstItem();
                            $itemsArray[$sellerId]['info']['order_rating'] = $feedback->getData();

                            if ($partner->getLogoPic()) {
                                $logoPic = $marketplaceHelper->getMediaUrl().'avatar/'.$partner->getLogoPic();
                            } else {
                                $logoPic = $marketplaceHelper->getMediaUrl().'avatar/noimage.png';
                            }
                            $itemsArray[$sellerId]['info']['seller_logo'] = $logoPic;


                        }else{
                            $itemsArray[0]['info'] = array();
                            $itemsArray[$sellerId]['info']['username'] = "";
                            $itemsArray[$sellerId]['info']['first_name'] = "";
                            $itemsArray[$sellerId]['info']['last_name'] = "";
                            $itemsArray[$sellerId]['info']['rating'] = 0;
                            $itemsArray[$sellerId]['info']['deliver_status'] = $orderDatamodel->getEndiciaDeliverStatus();
                            $itemsArray[$sellerId]['info']['review'] = $orderDatamodel->getInReview();
                        }
                        $itemsArray[$sellerId]['items'] = array();
                        $itemsArray[$sellerId]['return'] = array();
                        $itemsArray[$sellerId]['returnCount'] = count(explode(",", $orderDatamodel->getProductInReview()));
                    }

                    $itemData = array();
                    $itemData = $item->getData();
                    $_product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());
                    $itemData['description'] = $_product->getDescription();

                    $itemData['imageUrl'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getImage();
                    $itemData['productUrl'] = $_product->getProductUrl();


                    $itemData['return'] = array();
                    $returnCollection = $returnData->getCollection()
                    ->addFieldToFilter('product_id', array('eq' => $item->getProductId()))
                    ->addFieldToFilter('order_id', array('eq' => $order->getId()))
                    ->getFirstItem();

                    if($returnCollection->getId()){
                        $itemData['return'] = $returnCollection->getData();


                        $itemData['return']['returnPrintUrl'] = "";
                        if($orderDatamodel->getReturnLabel()){
                            $itemData['return']['returnPrintUrl'] = $store->getBaseUrl()."endicia/shipment/printreturnpdf/order_id/".$orderDatamodel->getOrderId()."/shipment_id/".$orderDatamodel->getShipmentId()."/seller_id/".$orderDatamodel->getSellerId();
                        }


                    }
                    array_push($itemsArray[$sellerId]['items'], $itemData);
                }
                $billingAddress = [];
                $shippingAddress = [];
                if(!empty($order->getBillingAddress())){
                    $billingAddress = $order->getBillingAddress()->getData();
                }
                if(!empty($order->getShippingAddress())){
                    $shippingAddress = $order->getShippingAddress()->getData();
                }
                $response['items'] = $itemsArray;
                $response['billingAddress'] = $billingAddress;
                $response['shippingAddress'] = $shippingAddress;
                $response['status'] = "Success";
            }else{
                $response['status'] = "Error";
                $response['message'] = "Order doesnot exist";
            }

            return $this->getResponseFormat($response);

        }catch(\Exception $e) {
            return $this->errorMessage($e);
        }
    }


    // Get Seller Order Ids as Array
    public function getOrderIdsArray($customerId = '', $filterOrderstatus = '')
    {
        $orderids = [];

        $collectionOrders = $this->getSellerOrders($customerId);

        // if ($buyerId = $customerId) {
        //     $buyerIds = $collectionOrders->getAllBuyerIds();
        //     if (in_array($buyerId, $buyerIds)) {
        //         $collectionOrders->addFieldToFilter('magebuyer_id', $buyerId);
        //     }
        // }

        foreach ($collectionOrders as $collectionOrder) {
            $tracking = $this->getOrderinfo($collectionOrder->getOrderId(), $customerId);
            $orderRepository = $this->_objectManager->create(
                '\Magento\Sales\Model\OrderRepository'
            );

            if ($tracking) {
                if ($filterOrderstatus != "") {

                    if ($tracking->getIsCanceled()) {
                        if ($filterOrderstatus == 'canceled') {
                            array_push($orderids, $collectionOrder->getOrderId());
                        }
                    } else {
                        $tracking = $orderRepository->get($collectionOrder->getOrderId());

                        if ($tracking->getStatus() == $filterOrderstatus && $tracking->getStatus() != "offer_made") {
                            array_push($orderids, $collectionOrder->getOrderId());
                        }
                    }
                } else {

                    $tracking = $orderRepository->get($collectionOrder->getOrderId());
                    if ($tracking->getStatus() != "offer_made" ) {
                        array_push($orderids, $collectionOrder->getOrderId());
                    }

                }
            }
        }

        return $orderids;
    }

    public function getSellerOrders($sellerId)
    {
        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Orders'
        )->getCollection()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ;
        return $collection;
    }

    //Get Order Info by Order Id and customer Id
    public function getOrderinfo($orderId = '', $customerId)
    {
        $data = [];
        $model = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Orders'
        )
        ->getCollection()
        ->addFieldToFilter(
            'seller_id',
            $customerId
        )
        ->addFieldToFilter(
            'order_id',
            $orderId
        );

        $salesOrder = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Orders\Collection'
        )->getTable('sales_order');

        $model->getSelect()->join(
            $salesOrder.' as so',
            'main_table.order_id = so.entity_id',
            ["order_approval_status" => "order_approval_status"]
        )->where("so.order_approval_status=1");
        foreach ($model as $tracking) {
            $data = $tracking;
        }

        return $data;
    }

    // Get All Order Status
    public function orderAllStatus() {
        $options = array();
        $response['content'] = array();
        $statusFactory = $this->_objectManager->create('\Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory');
        $options = $statusFactory->create()->toOptionArray();
        $i = 0;
        foreach($options as $eachOption){
            if($eachOption['value'] == "offer_made"){
                unset($options[$i]);
            }
            $i++;
        }
        return $this->getResponseFormat($options);
    }

    // Get Seller Frocks
    public function getFrocks($id, $length, $pageNum, $orderBy, $orderDir, $searchStr,  $sold, $draft) {
        $response = array();

        $storeId = 1;
        $customerId = $id;
        $websiteId = $this->_objectManager->create(
            'Webkul\Marketplace\Helper\Data'
        )->getWebsiteId();


        $filter = '';
        //$filterStatus = '';
        $filterDateFrom = '';
        $filterDateTo = '';
        $from = null;
        $to = null;

        $soldStatus = '';

        if (isset($searchStr)) {
            $filter = $searchStr != '' ? $searchStr : '';
        }
        if (isset($sold) && $sold != "") {
            $soldStatus = $sold == '0' ? ">0" : '=0';
        }
        $eavAttribute = $this->_objectManager->get(
            'Magento\Eav\Model\ResourceModel\Entity\Attribute'
        );
        $proAttId = $eavAttribute->getIdByCode('catalog_product', 'name');
        $proStatusAttId = $eavAttribute->getIdByCode('catalog_product', 'status');

        $proDraftAttId = $eavAttribute->getIdByCode('catalog_product', 'draft');


        $catalogProductEntity = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Product\Collection'
        )->getTable('catalog_product_entity');

        $catalogProductEntityVarchar = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Product\Collection'
        )->getTable('catalog_product_entity_varchar');

        $catalogProductEntityInt = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Product\Collection'
        )->getTable('catalog_product_entity_int');
        $catalogProductEntityStock = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Product\Collection'
        )->getTable('cataloginventory_stock_item');
        $storeCollection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )
        ->getCollection()
        ->setOrder("entity_id", "desc")
        ->addFieldToFilter(
            'seller_id',
            $customerId
        )
        ->addFieldToSelect(
            ['mageproduct_id']
        );

        $storeCollection->getSelect()->join(
            $catalogProductEntityVarchar.' as cpev',
            'main_table.mageproduct_id = cpev.entity_id'
        )->where(
            ' cpev.value like "%'.$filter.'%" AND
            cpev.attribute_id = '.$proAttId
        );

        $storeCollection->getSelect()->join(
            $catalogProductEntityInt.' as cpei',
            'main_table.mageproduct_id = cpei.entity_id'
        )->where(
            'cpei.attribute_id = '.$proStatusAttId
        );



        $storeCollection->getSelect()->join(
            $catalogProductEntityStock.' as cpes',
            'main_table.mageproduct_id = cpes.product_id'
        );

        // $storeCollection->getSelect()->join(
        //     $catalogProductEntityInt.' as cpeid',
        //     'main_table.mageproduct_id = cpeid.entity_id'
        // );

        if($soldStatus != ""){
            $storeCollection->getSelect()->where(
                'cpes.qty  '.$soldStatus
            );
        }

        if($draft != ""){
            $storeCollection->getSelect()->join(
                $catalogProductEntityInt.' as cpeid',
                'main_table.mageproduct_id = cpeid.entity_id'
            )->where(
                'cpeid.value = "'.$draft.'" AND cpeid.attribute_id = '.$proDraftAttId
            );
        }


        $storeCollection->getSelect()->join(
            $catalogProductEntity.' as cpe',
            'main_table.mageproduct_id = cpe.entity_id'
        );

        $storeCollection->getSelect()->group('mageproduct_id');

       // echo $storeCollection->getSelect();

       // die("SSSSSSSSSSS");


        $storeProductIDs = $storeCollection->getAllIds();


        /* Get Seller Product Collection for 0 Store Id */

        $adminStoreCollection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )
        ->getCollection();

        $adminStoreCollection->addFieldToFilter(
            'seller_id',
            $customerId
        )->addFieldToSelect(
            ['mageproduct_id']
        );

        $adminStoreCollection->getSelect()->join(
            $catalogProductEntityVarchar.' as cpev',
            'main_table.mageproduct_id = cpev.entity_id'
        )->where(
            'cpev.store_id = 0 AND
            cpev.value like "%'.$filter.'%" AND
            cpev.attribute_id = '.$proAttId
        );

        $adminStoreCollection->getSelect()->join(
            $catalogProductEntityInt.' as cpei',
            'main_table.mageproduct_id = cpei.entity_id'
        )->where(
            'cpei.store_id = 0 AND
            cpei.attribute_id = '.$proStatusAttId
        );

        if($draft != ""){
            $adminStoreCollection->getSelect()->join(
                $catalogProductEntityInt.' as cpeid',
                'main_table.mageproduct_id = cpeid.entity_id'
            )->where(
                'cpeid.store_id = 0 AND
                cpeid.value = "'.$draft.'" AND
                cpev.attribute_id = '.$proDraftAttId
            );
        }

        // $adminStoreCollection->getSelect()->join(
        //     $catalogProductEntityInt.' as cpeid',
        //     'main_table.mageproduct_id = cpeid.entity_id'
        // )->where(
        //     'cpeid.store_id = 0 AND
        //     cpeid.attribute_id = '.$proDraftAttId
        // );

        // $adminStoreCollection->getSelect()->join(
        //     $catalogProductEntityInt.' as cpeid',
        //     'main_table.mageproduct_id = cpeid.entity_id'
        // )->where(
        //     'cpeid.store_id = 0 AND
        //     cpeid.attribute_id = '.$proDraftAttId
        // );

        // if ($filterStatus) {
        //     $adminStoreCollection->getSelect()->where(
        //         'cpei.value = '.$filterStatus
        //         );
        // }

        $adminStoreCollection->getSelect()->join(
            $catalogProductEntity.' as cpe',
            'main_table.mageproduct_id = cpe.entity_id'
        );

        $adminStoreCollection->getSelect()->join(
            $catalogProductEntityStock.' as cpes',
            'main_table.mageproduct_id = cpes.product_id'
        );


        if($soldStatus != ""){
            $adminStoreCollection->getSelect()->where(
                'cpes.qty  '.$soldStatus
            );
        }

        // if($draft != ""){
        //     $adminStoreCollection->getSelect()->where(
        //         'cpeid.value =   '.$draft
        //     );
        // }

        //echo $adminStoreCollection->getSelect();

       // die("DDDDDDDDDD");

        $adminStoreCollection->getSelect()->group('mageproduct_id');

        $adminProductIDs = $adminStoreCollection->getAllIds();

        $productIDs = array_merge($storeProductIDs, $adminProductIDs);

        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
        )
        ->getCollection()
        ->setOrder("entity_id", "desc")
        ->addFieldToFilter(
            'seller_id',
            $customerId
        )
        ->addFieldToFilter(
            'mageproduct_id',
            ['in' => $productIDs]
        );





        $collection->setPageSize($length)->setCurPage($pageNum);
        $collection->setOrder($orderBy, $orderDir);

        $totalCount = $collection->getSize();

        $i = 0;
        $content = [];
        $store = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();

        foreach($collection as $eachProduct){
            $likesByProductId = $this->_objectManager->create('Seasia\Customapi\Helper\Data')->getLikesByProductId($eachProduct->getMageproductId());

            $content[$i]['likes_user'] =  $likesByProductId;

            $content[$i]['likes_count'] =  count($likesByProductId);

            $product = $this->getProductData($eachProduct->getMageproductId());
            $stockItem = $product->getExtensionAttributes()->getStockItem();
            $content[$i]['product_id'] = $product->getId();
            $content[$i]['product_name'] = $product->getName();
            $content[$i]['price'] = $product->getPriceInfo()->getPrice('final_price')->getValue();
            $content[$i]['status'] = $product->getResource()->getAttribute('status')->getFrontend()->getValue($product);
            $content[$i]['image'] =
            $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
            $content[$i]['stock_status'] = $stockItem->getQty() > 0 ? ($stockItem->getIsInStock() ? "Available": "Unlisted") : "Sold";
            $content[$i]['draft'] = $product->getDraft();
            $content[$i]['qty'] = $stockItem->getQty();
  					$content[$i]['available'] = $stockItem->getIsInStock()?"1":"0";
            $i++;
        }

        $response['frocks'] = $content;
        $response['totalCount'] = $totalCount;
        return $this->getResponseFormat($response);
    }


    // Get Product Data by Product Id
    public function getProductData($id = '')
    {
        return $this->_objectManager->create(
            'Magento\Catalog\Model\Product'
        )->load($id);
    }
    // Get Seller vacations
    public function getVacation($id){
        $response = array();
        $res =  array();
        $vacationsdata = $this->_vacationFactory->create()->addFieldToFilter('seller_id', ['eq' => $id]);
        $currentDate = $this->timezone->date()->format('Y-m-d h:i:s');
        if($vacationsdata){


            foreach ($vacationsdata as $data) {
                if($data->getDateTo() != ""){

                    if(strtotime($currentDate) > strtotime($data->getDateTo())){

                        $res['id'] = $data->getId();
                        $res['website_id'] = "";
                        $res['store_id'] = "";
                        $res['created_at'] = "";
                        $res['updated_at'] = "";
                        $res['vacation_status'] = "";
                        $res['product_status'] = "";
                        $res['seller_id'] = "";
                        $res['vacation_msg'] = "";
                        $res['product_disable_type'] = "";
                        $res['date_from'] = "";
                        $res['date_to'] = "";
                    }else{
                        $res =  $data->getData();
                    }
                }else{
                    $res =  $data->getData();
                }

                break;
            }
        }else{
            $res = [];
        }
        $response = $res;
        return $this->getResponseFormat($response);
    }
    // Set Seller Vacation
    public function setVacation($id, $vacation_id, $vacation_status, $date_from, $date_to, $vacation_msg){

        $scopeConfig = $this->_objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface');
        $response = array();
        $response['content'] = array();
        $data = array();
        $data['seller_id'] = $id;
        $data['id'] = $vacation_id;
        $data['vacation_status'] = $vacation_status;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;
        $data['vacation_msg'] = $vacation_msg;

        $data['product_disable_type'] = $scopeConfig
        ->getValue(
            'mpsellervacation/vacation_settings/vacation_mode',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($vacation_id) {
            try {
                $data['date_from'] = $this->converToTz(
                    $data['date_from'],
                    $this->timezone->getConfigTimezone(),
                    $this->timezone->getDefaultTimezone()
                );
                $data['date_to']=$this->converToTz(
                    $data['date_to'],
                    $this->timezone->getConfigTimezone(),
                    $this->timezone->getDefaultTimezone()
                );
                $vacation = $this->_objectManager
                ->create('Webkul\MpSellerVacation\Model\Vacation')->load($data['id']);
                if ($vacation->getId()) {
                    $data['updated_at'] = $this->timezone->date()->format('Y-m-d h:i:s');
                    $data['updated_at'] = $this->converToTz(
                        $data['updated_at'],
                        $this->timezone->getConfigTimezone(),
                        $this->timezone->getDefaultTimezone()
                    );
                    $vacation->setData($data)->save();
                    $response = [
                        'success' => 'Vacation setting successfully updated',
                        'id' => $vacation->getId(),
                    ];
                    return $this->getResponseFormat($response);

                } else {
                    return [
                        'error' => 'Vacation setting is not saved yet',
                    ];
                }
            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }
        } else {
            unset($data['id']);
            try {
                $data['created_at'] = $this->timezone->date()->format('Y-m-d h:i:s');
                $data['created_at']=$this->converToTz(
                    $data['created_at'],
                    $this->timezone->getConfigTimezone(),
                    $this->timezone->getDefaultTimezone()
                );
                $data['date_from']=$this->converToTz(
                    $data['date_from'],
                    $this->timezone->getConfigTimezone(),
                    $this->timezone->getDefaultTimezone()
                );
                $data['date_to']=$this->converToTz(
                    $data['date_to'],
                    $this->timezone->getConfigTimezone(),
                    $this->timezone->getDefaultTimezone()
                );
                $vacation = $this->_objectManager
                ->create('Webkul\MpSellerVacation\Model\Vacation')
                ->setData($data)->save();

                $response =  [
                    'success' => 'Vacationnn setting successfully saved',
                    'id' => $vacation->getId(),
                ];
                return $this->getResponseFormat($response);

            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }
    }
    protected function _getVacationHelper() {
        return $this->_objectManager->create('Webkul\MpSellerVacation\Helper\Data');
    }

    /**
      * this is used to convert into ConfigTimezone form DefaultTimezone.
      *
      * @param string dateTime - time tobe converted.
      *
      * @param string timeZone inwhich you want to convert.
      *
      * @param string timeZone fromwhich.
      *
      * @return string datetime according to 2'nd Param.
      */
    protected function converToTz($dateTime = "", $toTz = '', $fromTz = '')
    {

        $date = new \DateTime($dateTime, new \DateTimeZone($fromTz));
        $date->setTimezone(new \DateTimeZone($toTz));
        $dateTime = $date->format('Y-m-d h:i:s');
        return $dateTime;
    }
}
