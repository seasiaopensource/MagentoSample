<?php

namespace Seasia\Customapi\Api;

use Seasia\Customapi\Api\Data\ProductdataInterface;

interface OrderInterface
{
    /**
     * Returns seller orders
     *
     * @api
     * @param string $id Sellers id.
     * @param string $length Collection Length.
     * @param string $pageNum Collection Page.
     * @param string $orderBy Collection Order By.
     * @param string $orderDir Collection Order Direction.
     * @param string $searchOrderId Collection by Order Increment Id.
     * @param string $orderStatus Collection by Order Status.
     * @return array
     */
    
    public function getList($id, $length, $pageNum, $orderBy, $orderDir, $searchOrderId, $orderStatus);

    /**
     * Returns Order Details
     *
     * @api
     * @param string $orderId Order id.
     * @param string $sellerId Seller id.
     * @return array
     */
    
    public function getOrderDetails($orderId, $sellerId);

    /**
     * Returns Order Details
     *
     * @api
     * @param string $customerId Order id.
     * @param string $orderId Order id.
     * @return array
     */
    public function getShipmentDetails($customerId,$orderId);

    /**
     * Returns Order Details
     *
     * @api
     * @param string $sellerId Order id.
     * @param string $orderId Order id.
     * @param string $shipmentId shipmentId id.
     * @return array
     */
    public function getSoldOrderShipment($sellerId,$orderId, $shipmentId);


    


    /**
     * Returns Buyer Order Details
     *
     * @api
     * @param string $orderId Order id.
     * @return array
     */
    
    public function getBuyerOrderDetails($orderId);

    


    /**
     * Returns seller frocks
     *
     * @api
     * @param string $id Sellers id.
     * @param string $length Collection Length.
     * @param string $pageNum Collection Page.
     * @param string $orderBy Collection Order By.
     * @param string $orderDir Collection Order Direction.
     * @param string $searchStr Search by String.
     * @param string $status Search by Status.
     * @param string $sold Search by Sold.
     * @param string $draft Search by Draft.
     * @return array
     */
    
    public function getFrocks($id, $length, $pageNum, $orderBy, $orderDir, $searchStr, $sold, $draft);

    /**
     * Returns seller Vacation Status
     *
     * @api
     * @param string $id Sellers id.
     * @param string $vacation_id Vacation id.
     * @param string $vacation_status Vacation Status
     * @param string $date_from Vacation From Date.
     * @param string $date_to Vacation To Date.
     * @param string $vacation_msg Vacation Text.
     * @return array
     */
    
    public function setVacation($id, $vacation_id, $vacation_status, $date_from, $date_to, $vacation_msg);

    /**
     * Returns seller Vacation Status
     *
     * @api
     * @param string $id Sellers id.
     * @return array
     */
    
    public function getVacation($id);


    /**
     * Returns All Order Status
     *
     * @api
     * @return array
     */
    
    public function orderAllStatus();

    /**
     * Returns Seller Commission
     *
     * @api
     * @return array
     */
    
    public function commission();

    /**
     * Returns Seller Commission
     *
     * @api
     * @return array
     */

    public function getConfig();

    

    /**
     * Add Shipment Tracking
     *
     * @api
     * @param string $carrier  Carrier.
     * @param string $number  Track Number
     * @param string $title  Title
     * @param string $orderId  Order Id
     * @param string $shipmentId  Shipment Id
     * @return array
     */

    public function addTracking($carrier, $number, $title, $orderId, $shipmentId);

    /**
     * Delete Tracking
     *
     * @api
     * @param string $trackingId  Tracking Id.
     * @param string $shipmentId  Shipment Id
     * @param string $orderId  Order Id
     * @return array
     */

    public function deleteTracking($trackingId, $shipmentId, $orderId);

    /**
     * Delete Tracking
     * @api
     * @param string $orderId  Order Id.
     * @param string $shipmentId  Shipment Id
     * @return array
     */
    public function getCarriers($orderId, $shipmentId);

    /**
     * Delete Tracking
     * @api
     * @param string $orderId  Order Id.
     * @param string $sellerId  Seller Id
     * @param string $trackingid
     * @param string $carrier
     * @param string $shipmentId  shipment Id
     * @param string $send_email  Customer Email
     * @param string $shippingLabel  Shipping Label
     * @return array
     */
    public function shipOrder($orderId, $sellerId,  $trackingid, $carrier, $shipmentId, $send_email, $shippingLabel);


    /**
     * Confirm Order Delivery
     * @api
     * @param string $orderId  Order Id.
     * @param string $sellerId  Seller Id
     * @param string $orderSellerId  Order Seller Id
     * @return array
     */
    public function confirmOrder($orderId, $sellerId,$orderSellerId);

    /**
     * 
     * @api
     * @param string $orderId  Order Id.
     * @param string $sellerId  Seller Id
     * @return array
     */
    public function invoiceOrder($orderId, $sellerId);

    /**
     * 
     * @api
     * @param string $orderId  Order Id.
     * @param string $sellerId  Seller Id
     * @param string $invoiceId  Invoice Id
     * @return array
     */
    public function getSellerInvoiceDetails($orderId,$sellerId,$invoiceId);

    /**
     * 
     * @api
     * @param string $orderId  Order Id.
     * @return array
     */
    public function getBuyerInvoiceDetails($orderId);

    /**
     * 
     * @api
     * @return array
     */
    public function stripeTransaction();

    /**
     * 
     * @api
     * @param string $sellerId
     * @param string $buyerId
     * @param string $buyerEmail
     * @param string $status
     * @param string $feedPrice
     * @param string $feedValue
     * @param string $feedQuality
     * @param string $feedNickname
     * @param string $feedSummary
     * @param string $feedReview
     * @param string $orderId
     * @return array
     */
    public function giveRating($sellerId,$buyerId,$buyerEmail,$status,$feedPrice,$feedValue,$feedQuality,$feedNickname,$feedSummary,$feedReview, $orderId);

    /**
     * 
     * @api
     * @param string $sellerId
     * @param string $buyerId
     * @param string $orderId
     * @param string $feedPrice
     * @param string $feedValue
     * @param string $feedQuality
     * @param string $feedNickname
     * @param string $feedSummary
     * @param string $feedReview
     * @return array
     */
    public function editRating($sellerId,$buyerId,$orderId,$feedPrice,$feedValue,$feedQuality,$feedNickname,$feedSummary,$feedReview);

    /**
     * 
     * @api
     * @param string $sellerId
     * @param string $buyerId
     * @param string $orderId
     * @return array
     */
    public function deleteRating($sellerId,$buyerId,$orderId);

    
}