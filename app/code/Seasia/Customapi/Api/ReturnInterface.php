<?php

namespace Seasia\Customapi\Api;

use Seasia\Customapi\Api\Data\ProductdataInterface;

interface ReturnInterface
{


    /**
     * Upload Return Image
     *
     * @api
     * @param string $sellerId Sellers id
     * @param string $imageName Image Name
     * @param string $content Content
     * @return array
     */
    public function uploadReturnImage($sellerId, $imageName, $content);

    /**
     * Create Return
     *
     * @api  
     * @param string $sellerId Sellers id
     * @param string $buyerId Buyer Id
     * @param string $orderId Order Id
      * @param string $productId Product Id
     * @param string $reason Reason for Return
     * @param string $imagesName images for Return
     * @param string $comment comment for Return
     * @return array
     */
    public function createReturn($sellerId, $buyerId, $orderId, $productId, $reason,$imagesName, $comment);

    /**
     * Get Return Details
     *
     * @api
     * @param string $returnId Return Id
     * @return array
     */
    public function getReturn($returnId);
    

    /**
     * Get Return Details
     *
     * @api
     * @param string $returnId Return id
     * @return array
     */
    public function getReturnComments($returnId);


    /**
     * Offer On Return
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $returnId Order Id
     * @param string $offerAmount Offer Amount for Return
     * @param string $comment Comment on Return
     * @return array
     */

    public function offerReturn($sellerId,$returnId, $offerAmount, $comment);

    /**
     * Offer On Return
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $returnId Order Id
     * @param string $comment Return comment
     * @return array
     */

    public function acceptReturnOffer($sellerId, $returnId, $comment);

    /**
     * Reject Offer On Return
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $returnId Order Id
     * @param string $comment Return comment
     * @return array
     */

    public function rejectReturnOffer($sellerId, $returnId, $comment);


    /**
     * Return Shipped
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $returnId Order Id
     * @return array
     */

    public function returnshipped($sellerId, $returnId);

    /**
     * Offer On Return
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $returnId Order Id
     * @param string $comment Return comment
     * @return array
     */

    public function processReturn($sellerId, $returnId, $comment);


    /**
     * Approve Return
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $returnId Order Id
     * @return array
     */

    public function approveReturn($sellerId, $returnId);
    

    /**
     * Reject Return
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $returnId Order Id
     * @return array
     */

    public function rejectReturn($sellerId, $returnId);

    

    /**
     * Return Product
     *
     * @api
     * @param string $returnId Return Id
     * @return array
     */

    public function returnProduct($returnId);


    /**
     * Returns Made by Seller
     *
     * @api
     * @param string $sellerId Sellers id
     * @param string $pageNum Page Number
     * @param string $length Length
     * @param string $orderBy Order By
     * @param string $orderDir Order Direction
     * @param string $searchStr Search Strings
     * @return array
     */
    public function returnsMade($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr);

    /**
     * Returns Received By Seller
     *
     * @api
     * @param string $sellerId Sellers id
     * @param string $pageNum Page Number
     * @param string $length Length
     * @param string $orderBy Order By
     * @param string $orderDir Order Direction
     * @param string $searchStr Search String
     * @return array
     */
    public function returnsReceived($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr);

    /**
     * Return React App Version
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException If ID is not found
     * @return array
     */
    public function getReactAppVersion();

}