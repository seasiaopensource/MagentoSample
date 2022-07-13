<?php

namespace Seasia\Customapi\Api;

interface FamilyInterface {
	/**
     * Add/Edit Seller Family Size
     *
     * @api
     * @param string $id Family Size id
     * @param string $sellerId Sellers id
     * @param string $first_name First Name
     * @param string $last_name Last Name
     * @param string $relation Relation
     * @param string $gender Gender
     * @param string $age Age
     * @param string $size_name Size name
     * @param string $size_value Size Value
     * @return array
     */
    public function addeditsize($id,$sellerId,$first_name,$last_name, $relation,$gender,$age,$size_name,$size_value);
   
     /**
     * Delete Seller Family Size
     *
     * @api
     * @param string $id Family Size id
     * @param string $sellerId Sellers id
     * @return array
     */
    public function deletesize($id,$sellerId);
   
    /**
     * Get Seller Family Size by id
     *
     * @api
     * @param string $id Family Size id
     * @return array
     */
    public function familysizebyid($id); 

    /**
     * Get Seller Family Size by id
     *
     * @api
     * @param string $sellerId Family Size id
     * @param string $pageNum Page Number
     * @param string $length Length
     * @param string $orderBy Order By
     * @param string $orderDir Order Direction
     * @param string $searchStr Search String
     * @return array
     */

    public function familysize($sellerId,$pageNum, $length, $orderBy, $orderDir,$searchStr);

    /**
     * Get Size by gender
     *
     * @api
     * @return array
     */

    public function sizebygender();
}