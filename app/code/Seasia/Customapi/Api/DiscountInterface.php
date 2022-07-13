<?php

namespace Seasia\Customapi\Api;

use Seasia\Customapi\Api\Data\ProductdataInterface;

interface DiscountInterface
{

    /**
     * Get All Seller Product by Seller Id
     *
     * @api
     * @param string $sellerId Sellers id
     * @param string $orderBy Order By
     * @param string $orderDir Order Direction
     * @param string $searchStr Search Product
     * @return array
     */
    public function getSellerProducts($sellerId, $orderBy, $orderDir, $searchStr);

    
    /**
     * Returns Seller Discounts
     *
     * @api
     * @param string $id Sellers id.
     * @param string $status Discount Status.
     * @param string $length Collection Length.
     * @param string $pageNum Collection Page.
     * @param string $orderBy Collection Order By.
     * @param string $orderDir Collection Order Direction.
     * @return array
     */
    
    public function discounts($id, $status, $length, $pageNum, $orderBy, $orderDir);

    /**
     * Get Customer Discount by Id
     *
     * @api
     * @param string $sellerId Sellers id
     * @param string $discountId Discount Id
     * @return array
     */
    public function getDiscount($sellerId, $discountId);

    /**
     * Delete Customer Discount
     *
     * @api
     * @param string $sellerId Sellers id
     * @param string $discountId Discount Id
     * @return array
     */
    public function deleteDiscount($sellerId, $discountId);

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

    /**
     * Apply Discount To Products
     *
     * @api
     * @param string $sellerId Sellers id
     * @param string $discount Discount
     * @param string $productids Product Ids
     *  @return array
     */
    public function applyDiscount($sellerId, $discount, $productids);

    /**
     * Remove Discount From Products
     *
     * @api
     * @param string $sellerId Sellers id
     * @param string $discount Discount
     * @param string $productids Product Ids
     *  @return array
     */
    public function removeDiscount($sellerId, $discount, $productids);


    /**
     * Returns Seller Discounts
     *
     * @api
     * @param string $sellerId Sellers id.
     * @param string $length Collection Length.
     * @param string $pageNum Collection Page.
     * @param string $orderBy Collection Order By.
     * @param string $orderDir Collection Order Direction.
     * @param string $searchStr Collection Search.
     * @return array
     */
    
    public function getDiscountedProducts($sellerId,  $length, $pageNum, $orderBy, $orderDir, $searchStr);

    
}