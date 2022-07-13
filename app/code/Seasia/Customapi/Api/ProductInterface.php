<?php

namespace Seasia\Customapi\Api;

use Seasia\Customapi\Api\Data\ProductdataInterface;

interface ProductInterface
{
    /**
     * Retrieve list of info
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException If ID is not found
     * @return array
     */
    public function getSizeDetails();

    /**
     * Retrieve list of info
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException If ID is not found
     * @return array
     */
    public function getAttributeSetDetails();

    /**
     * Upload Product image
     *
     * @api
     * @param string $content  Base 64 of image.
     * @param string $imageName  Image Name.
     * @return array
     */

    public function uploadImage($content, $imageName);


    /**
     * Get Product
     *
     * @api
     * @param string $id  Product Id.
     * @return array
     */

    public function getProduct($id);

    /**
     * Delete Product
     *
     * @api
     * @param string $id  Product Id.
     * @param string $sellerId  Seller Id.
     * @return array
     */

    public function deleteProduct($id,$sellerId);

    /**
     * Creates Product
     *
     * @api
     * @param string $sellerId  Seller Id.
     * @param string $type Product Type.
     * @param string $set  Attributeset Id.
     * @param string[] $product
     * @return array
     */

    public function createProduct($sellerId, $type, $set, $product);

    /**
     * Get Likes By Product Id
     *
     * @api
     * @param string $sellerId  Seller Id.
     * @param string $productId  Product Id.
     * @return array
     */
    
    public function getLikesByProductId($sellerId,$productId);
}