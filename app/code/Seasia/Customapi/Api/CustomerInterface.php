<?php

namespace Seasia\Customapi\Api;

use Seasia\Customapi\Api\Data\ProductdataInterface;

interface CustomerInterface
{


    /**
     * Returns Stripe orders
     *
     * @api
     * @param string $sellerId Sellers id
     * @return array
     */
    public function stripeFields($sellerId);

    /**
     * Returns seller orders
     *
     * @api
     * @param string $id Sellers id
     * @param string $email Seller Email
     * @return array
     */
    public function stripeConnectedUrl($id, $email);

    /**
     * Returns seller Ratings
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
    public function myRatings($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr);

    /**
     * Returns Ratings given by Seller
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
    public function givenRatings($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr);


    /**
     * Add Customer Address
     *
     * @api
     * @param string $id
     * @param string $email
     * @param string $addLine1
     * @param string $addLine2
     * @param string $city
     * @param string $state
     * @param string $country
     * @param string $zip
     * @param string $telephone
     * @param string $defaultShipping
     * @param string $defaultBilling
     * @param string $addressId
     * @return array
     */
    
    public function customeraddress($sellerId,$home_address,$addLine1, $addLine2, $city, $state, $country, $zip, $telephone, $defaultShipping, $defaultBilling, $addressId);
    


    /**
     * Returns Seller All Addresses
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
    public function getAddresses($sellerId);


    /**
     * Returns Address By Id
     *
     * @api
     * @param string $sellerId Seller id
     * @param string $addressId Address id
     * @return array
     */
    public function addressById($sellerId,$addressId);

    /**
     * Delete Address By Id
     *
     * @api
     * @param string $addressId Address id
     * @return array
     */
    public function deleteAddress($addressId);

    /**
     * Get Seller Profile
     *
     * @api
     * @param string $sellerId Seller id
     * @param string $email Seller Email
     * @return array
     */
    public function profile($sellerId, $email);

    /**
     * Edit Seller Profile
     *
     * @api
     * @param string $sellerId Seller id
     * @param string $firstname Seller First anme
     * @param string $lastname Seller Last name
     * @param string $email Seller Email
     * @param string $contact Seller Contact Number
     * @param string $companyLogo Seller Company Logo
     * @param string $smokefree Smoke Free Home
     * @param string $petfree Pet Free Home
     * @param string $pettype Pet Type
     * @param string $gender Gender
     * @param string $dob DOB
     * @return array
     */
    public function editProfile($sellerId, $firstname, $lastname,  $email, $contact, $smokefree, $petfree,$pettype,$gender,$dob, $companyLogo);

    /**
     * Upload Product image
     *
     * @api
     * @param string $content  Base 64 of image.
     * @param string $sellerId  Seller Id
     * @return array
     */

    public function editProfilePic($content, $sellerId);


    /**
     * Get Seller Profile Step1
     *
     * @api
     * @param string $sellerId Seller id
     * @param string $email Seller Email
     * @return array
     */
    public function getProfileStep1($sellerId);


    /**
     * Update Seller Profile Step1
     *
     * @api
     * @param string $sellerId Seller id
     * @param string $petfree
     * @param string $pettype
     * @param string $smokefree
     * @param string $dob
     * @param string $gender
     * @param string $companyLogo
     * @param string $imagename
     * @return array
     */
    public function updateProfileStep1($sellerId, $smokefree, $petfree,$pettype,$gender,$dob, $companyLogo,$imagename);


    /**
     * Check Customer Password
     *
     * @api
     * @param string $sellerId Seller id
     * @param string $password Seller Password
     * @return array
     */
    public function checkPassword($sellerId, $password);

    /**
     * Check Customer Password
     *
     * @api
     * @param string $sellerId Seller id
     * @param string $currentPassword Seller Current Password
     * @param string $password Seller Password
     * @param string $confirmPassword Seller Confirm Password
     * @return array
     */
    public function resetPassword($sellerId, $currentPassword, $password, $confirmPassword);

    

    /**
     * Get Rating by Id
     *
     * @api
     * @param string $ratingId Rating id
     * @return array
     */
    public function getRating($ratingId);

    /**
     * Get Rating by Id
     *
     * @api
     * @param string $sellerId Seller id
     * @param string $pageNum Page Number
     * @param string $length Length
     * @param string $orderBy Order By
     * @param string $orderDir Order Direction
     * @return array
     */
    public function allQuestions($sellerId, $pageNum, $length, $orderBy, $orderDir);

    /**
     * Get Question by Id
     *
     * @api
     * @param string $questionId QuestionId
     * @return array
     */
    public function questionById($questionId);

    /**
     * Get Question by Id
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $questionId QuestionId
     * @param string $answer QuestionId
     * @return array
     */
    public function answerQuestion($sellerId, $questionId, $answer);


    /**
     * Get Answers by querstionId
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $questionId QuestionId
     * @return array
     */
    public function answersByQuestion($sellerId, $questionId);

    /**
     * Get Seller Saved Cards
     *
     * @api
     * @param string $sellerId Seller Id
     * @return array
     */
    public function savedCards($sellerId);

    /**
     * Get Seller Saved Cards
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $cardIds Card Id
     * @return array
     */
    public function deleteCards($sellerId, $cardIds);


    /**
     * Get Seller Saved Searches
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $pageNum Page Number
     * @param string $length Length
     * @param string $orderBy Order By
     * @param string $orderDir Order Direction
     * @return array
     */
    public function savedSearches($sellerId, $pageNum, $length, $orderBy, $orderDir);

    /**
     * Get Seller Saved Search By Id
     *
     * @api
     * @param string $searchId Search Id
     * @return array
     */
    public function searchById($searchId);


    /**
     * Get Seller Saved Search By Id
     *
     * @api
     * @param string $sellerId Search Id
     * @param string $searchTerm Search Term
     * @param string $searchName Search Name
     * @return array
     */
    public function editSearch($sellerId, $searchTerm, $searchName);


    /**
     * Delete Seller Saved Search By Id
     *
     * @api
     * @param string $searchId Search Id
     * @return array
     */
    public function deleteSearch($searchIds);


    /**
     * Get Customer Offer Made by Id
     *
     * @api
     * @param string $sellerId Seller Id
     * @return array
     */

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
     * Get Product Details By Seller Customer Id 
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $customerId Customer Id
     * @return array
     */
    public function getProductDetailsBySellerCustomerId($sellerId, $customerId);


    /**
     * Save Review for Seller
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $customerId Customer Id
     * @param string $customerEmail Customer Id
     * @param string $feed_nickname Customer Id
     * @param string $feed_summary Customer Id
     * @param string $feed_review Customer Id
     * @param string $feed_price Customer Id
     * @param string $feed_value Customer Id
     * @param string $feed_quality Customer Id
     * @return array
     */
    public function addReview($sellerId, $customerId, $customerEmail, $feed_nickname, $feed_summary, $feed_review, $feed_price, $feed_value, $feed_quality);

    /**
     * Save Review for Seller
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $customerId Customer Id
     * @return array
     */
    public function customerUnfollow($sellerId, $customerId);

    /**
     * Get Seller Liked Products
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $length Length
     * @param string $pageNum Page Number
     * @param string $orderBy Order By
     * @param string $orderDir Order Direction
     * @return array
     */
    public function getCustomerLikedProducts($sellerId, $length, $pageNum, $orderBy, $orderDir);


    /**
     * Save Seller Notification
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string[] $settings
     * @param string $notificationId NotificationId
     * @return array
     */
    public function notificationsettings($sellerId, $settings, $notificationId);

    /**
     * Get Seller Notification Settings
     *
     * @api
     * @param string $sellerId Seller Id
     * @return array
     */
    public function getnotificationsettings($sellerId);

    /**
     * Get Notifications
     *
     * @api
     * @param string $sellerId Seller Id
     * @param string $length Length
     * @param string $pageNum Page Number
     * @param string $orderBy Order By
     * @param string $orderDir Order Direction
     * @return array
     */
    public function getNotification($sellerId,$length, $pageNum, $orderBy, $orderDir);
   
    /**
     * Get Notifications
     *
     * @api
     * @param string $notificationId Notification Id
     * @return array
     */
    public function readNotification($notificationId);

    /**
     * Save Stripe Seller
     *
     * @api
     * @param string $account_code
     * @param string $sellerId
     * @return array
     */
    public function saveStripeSeller($account_code, $sellerId);

    /**
     * Get Badges
     *
     * @api
     * @return array
     */
    public function getBadges();

    /**
     * Get Badges
     *
     * @api
     * @param string $sellerId Seller Id
     * @return array
    */
    public function getSellerBadges($sellerId);

    /**
     * Get Email FOr Email Badge Verification
     *
     * @api
     * @param string $sellerId Seller Id
     * @return array
    */
    public function getEmailForEmailVerificationBadge($sellerId);

    /**
     * Verify Email Badge
     *
     * @api
     * @param string $sellerId Seller Id
     * @return array
    */
    public function verifyEmailBadge($sellerId);

    /**
     * Check Profile Status
     *
     * @api
     * @param string $sellerId Seller Id
     * @return array
    */
    public function profileStatus($sellerId);

    /**
     * Accept Profile Terms
     *
     * @api
     * @param string $sellerId Seller Id
     * @return array
    */
    public function profileTerms($sellerId);

}