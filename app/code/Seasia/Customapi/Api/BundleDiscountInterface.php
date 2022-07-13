<?php

namespace Seasia\Customapi\Api;

use Seasia\Customapi\Api\Data\ProductdataInterface;

interface BundleDiscountInterface
{   
    /**   
     * Get Customer Followers by Id
     *
     * @api
     * @param string $id Bundle Discount Id
     * @param string $sellerId Sellers id
     * @param string $condition Condition
     * @param string $type type
     * @param string $value value
     * @param string $discount_type Discount Type
     * @param string $discount_name Discount Name
     * @param string $discount_value Discount Value
     * @param string $combined_amount Combined Amount
     * @return array
    */
    public function addEditBundleDiscount($id,$sellerId,$condition,$type, $value,$discount_type,$discount_name,$discount_value,$combined_amount);

    /**   
     * Delete Bundle Discount by Id
     *
     * @api
     * @param string $id Bundle Discount Id
     * @param string $sellerId Sellers id
     * @return array
    */
    public function deleteBundleDiscount($id,$sellerId);

     /**   
     * Get Bundle Discount by Id
     *
     * @api
     * @param string $id Bundle Discount Id
     * @param string $sellerId Sellers id
     * @return array
    */
    public function bundleDiscountbyid($id,$sellerId);

    /**   
     * Get Bundle Discounts by Seller Id
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
    public function bundleDiscounts($sellerId,  $length, $pageNum, $orderBy, $orderDir, $searchStr);
}