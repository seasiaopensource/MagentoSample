<?php

namespace Seasia\Customapi\Api;

use Seasia\Customapi\Api\Data\ProductdataInterface;

interface OfferInterface
{


    /**
     * Get Customer Offer Made Or Received
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $pageNum Page Number
     * @param string $length Length
     * @param string $orderBy Order By
     * @param string $orderDir Order Direction
     * @param string $searchStr Search String
     * @param string $getData Type of Collection
     * @return array
     */
    public function offers($sellerId, $pageNum, $length, $orderBy, $orderDir,$searchStr, $getData);


    /**
     * Get Customer Offer Made Or Received
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $pageNum Page Number
     * @param string $length Length
     * @param string $orderBy Order By
     * @param string $orderDir Order Direction
     * @param string $searchStr Search String
     * @return array
     */
    public function offersReceived($sellerId, $pageNum, $length, $orderBy, $orderDir,$searchStr);

    /**
     * Get Customer Offer Made by Id
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $offerId Offer Id
     * @return array
     */
    public function offermade($sellerId, $offerId);

    /**
     * Get Customer Offer Received by Id
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $offerId Offer Id
     * @return array
     */
    public function offerreceivedbyid($sellerId, $offerId);


     /**
     * Accept Offer By Offer Id
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $offerId Offer Id
     * @return array
     */
     public function acceptOfferById($sellerId, $offerId);

     /**
     * Reject Offer By Offer Id
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $offerId Offer Id
     * @return array
     */
     public function rejectOfferById($sellerId, $offerId);

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
    public function setSellerOfferDiscountByProductId($sellerId,$productId,$offerAmount, $offer_comment);


    /**
     * Counter Offer By Offer Id
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $offerId Offer Id
     * @param string $offerAmount Offer Amount
     * @param string $comment Comment
     * @return array
     */
    public function counterOfferById($sellerId, $offerId, $offerAmount, $comment);

     /**
     * ShippingRates By Address Id
     *
     * @api
     * @param string $customerEmail Customer Email
     * @param string $addressId Address Id
     * @param string $productId Product Id
     * @param string $offerAmount Offer Amount
     * @return array
     */
     public function shippingRates($customerEmail,$addressId,$productId, $offerAmount);

     /**
     * Offer payment
     *
     * @api
     * @param string $customerId
     * @param string $customerEmail
     * @param string[] $post
     * @return array
     */
     public function offerPayment($customerId,$customerEmail,$post);


     /**
     * Offer History
     *
     * @api
     * @param string $sellerId
     * @param string $offerId
     * @return array
     */
     public function offerHistory($sellerId,$offerId);

     /**
     * Offer History
     *
     * @api
     * @return array
     */
     public function checkOrderInvoice();

     


 }