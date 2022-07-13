<?php

namespace Seasia\Customapi\Api;

use Seasia\Customapi\Api\Data\ProductdataInterface;

interface CustomerNewInterface
{
    

    /** Code By Villiam */
    /**
     * Get Customer Followering by Id
     *
     * @api
     * @param string $sellerId Sellers id
     * @param string $pageNum Page Number
     * @param string $length Length
     * @param string $orderBy Order By
     * @param string $orderDir Order Direction
     * @param string $searchStr Search Rating
     * @return array
     */
    public function customerFollowing($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr);

    /**
     * Get Customer Followers by Id
     *
     * @api
     * @param string $sellerId Sellers id
     * @param string $pageNum Page Number
     * @param string $length Length
     * @param string $orderBy Order By
     * @param string $orderDir Order Direction
     * @param string $searchStr Search Rating
     * @return array
     */
    public function customerFollowers($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr);

    /**
     * Get Customer Offer Made by Id
     *
     * @api
     * @param string $sellerId Seller Id
     * @return array
     */
    public function offerMade($sellerId);
 
    /**
     * Get Customer Offer Received by Id
     *
     * @api
     * @param string $sellerId Seller Id
     * @return array
     */
    public function offerReceived($sellerId);

    /**
     * Get All Seller Product by Seller Id
     *
     * @api
     * @param string $sellerId Sellers id
     * @param string $pageNum Page Number
     * @param string $length Length
     * @param string $orderBy Order By
     * @param string $orderDir Order Direction
     * @return array
     */
    public function getSellerProducts($sellerId, $pageNum, $length, $orderBy, $orderDir);

    /**
     * Get All Seller Product by Seller Id
     *
     * @api
     * @param string $sellerId Sellers id
     * @param string $discountId Discount Id
     * @param string $title Title
     * @param string $percent Percent
     * @param string $status Status
     * @param string $productids Productids
     *  @return array
     */
    public function addEditDiscount($sellerId,$discountId,$title,$percent,$status,$productids);
}