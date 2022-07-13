<?php
namespace Seasia\Customapi\Model;
use Seasia\Customapi\Api\CustomerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class Customer implements CustomerInterface
{

	/**
	 * Return data.
	 *
	 * @api
	 */
	protected $dataFactory;
	protected $_objectManager;
	protected $_saveSearchFactory;
	protected $_stripeHelper;
	protected $_customerObj;
	protected $_marketplacehelper;
	protected $_storemanager;
	protected $_stripeFactory;
	protected $_reviewFactory;
	protected $_addressRepo;
	protected $_customApiHelper;
	protected $_customerRepo;
	protected $_agoraehelper;
	protected $_fileSystem;
	protected $_sellerModel;
	protected $_dateTime;
	protected $_productModel;
	protected $_authModel;
	protected $_questionFactory;
	protected $_answer;

	public function __construct(\Seasia\Customapi\Api\Data\ProductdataInterfaceFactory $dataFactory) {
		$this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$this->_saveSearchFactory = $this->_objectManager->create('Seasia\Savesearch\Model\SavesearchFactory');

		$this->_stripeHelper = $this->_objectManager->create('Webkul\MpStripe\Helper\Data');
		$this->_customerObj = $this->_objectManager->create('Magento\Customer\Model\Customer');
		$this->_marketplacehelper = $this->_objectManager->create('Webkul\Marketplace\Helper\Data');
		$this->_storemanager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
		$this->_stripeFactory = $this->_objectManager->create('Webkul\MpStripe\Model\ResourceModel\StripeSeller\CollectionFactory');

		$this->_reviewFactory = $this->_objectManager->create('Webkul\Marketplace\Model\ResourceModel\Feedback\CollectionFactory');

		$this->_addressRepo = $this->_objectManager->create('Magento\Customer\Api\AddressRepositoryInterface');


		$this->_customApiHelper = $this->_objectManager->create('Seasia\Customapi\Helper\Data');

		$this->_customerRepo = $this->_objectManager->create('Magento\Customer\Api\CustomerRepositoryInterface');
		$this->_agoraehelper = $this->_objectManager->create('Webkul\Agorae\Helper\Data');

		$this->_fileSystem = $this->_objectManager->create('Magento\Framework\Filesystem');
		$this->_sellerModel = $this->_objectManager->create('Webkul\Marketplace\Model\Seller');
		$this->_dateTime = $this->_objectManager->create('\Magento\Framework\Stdlib\DateTime\DateTime');
		$this->_productModel = $this->_objectManager->create('Magento\Catalog\Model\Product');
		$this->_authModel = $this->_objectManager->create('Magento\Customer\Model\Authentication');
		$this->_questionFactory = $this->_objectManager->create('Webkul\Mpqa\Model\QuestionFactory');
		$this->dataFactory = $dataFactory;
		$this->_answer = $this->_objectManager->create('Webkul\Mpqa\Model\MpqaanswerFactory');
	}
	// Return Response
	public function getResponseFormat($content){
		$page_object = $this->dataFactory->create();
		$page_object->setName($content);
		return $page_object;
	}

	// Get Stripe Hidden Fields For Connect To Stripe
	public function stripeFields($sellerId) {
		try{
			$helper = $this->_stripeHelper;
			$customerObj = $this->_customerObj->load($sellerId);
			$marketplaceHelper = $this->_marketplacehelper;
			$partner = $marketplaceHelper->getSellerDataBySellerId($sellerId)->getFirstItem();

			$partnerData = $partner->getData();

			$storemanager = $this->_storemanager;
			$response = array();
			$stripe_user = array();
			$response['client_id'] = $helper->getConfigValue("client_secret");
			$response['response_type'] = "code";
			$response['stripe_landing'] = "register";
			$response['scope'] = "read_write";
			$stripe_user['url'] = $storemanager->getStore()->getBaseUrl()."marketplace/seller/profile/".$partner->getShopUrl();

			$stripe_user['email'] = $customerObj->getEmail();
			$stripe_user['country'] = "US";
			$stripe_user['currency'] = $storemanager->getStore()->getCurrentCurrency()->getCode();
			$stripe_user['first_name'] = $customerObj->getFirstname();
			$stripe_user['last_name'] = $customerObj->getLastname();
			$stripe_user['phone_number'] = '';
			$stripe_user['business_name'] = $partner->getShopTitle()?$partner->getShopTitle():"";
			$stripe_user['redirect_uri'] = $storemanager->getStore()->getBaseUrl()."mpstripe/seller/connect";


			$response['stripe_user'] = $stripe_user;


			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}



	// Get Stripe Hidden Fields For Connect To Stripe
	public function stripeConnectedUrl($id, $email) {
		try {
			$response = array();

			$response = $this->getSellerStripe($id, $email);

			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}




	protected function getSellerStripe($id, $email){
		$response = array();
		$helper = $this->_stripeHelper;
		$stripeClientSecret = $helper->getConfigValue('api_key');
		$accessToken = '';
		$dataArray = array();
		$stripeFactory = $this->_stripeFactory;

		$sellerCollection = $stripeFactory->create()->addFieldToFilter(
			'seller_id',
			['eq' => $id]
		)
		->addFieldToFilter(
			'email',
			['eq' => $email]
		);


		foreach ($sellerCollection as $value) {
			$accessToken = $value['access_token'];
			$connectAccountId = $value['stripe_user_id'];
		}
		$stripeArray = array();
		if($accessToken != ""){
			$response['stripeArray'] = $sellerCollection->getFirstItem()->getData();
			$response['stripeArray']['link'] = $this->getStripeLink($accessToken, $connectAccountId);
		}

		return $response;
	}

	protected function getStripeLink($stripeApiKey, $connectAccountId){
		try {
			\Stripe\Stripe::setApiKey($stripeApiKey);
			$account = \Stripe\Account::retrieve($connectAccountId);
			return $account->login_links->create()->url;
			$return = json_decode($account->login_links->create(), true);
			return $return['url'];
		}
		catch (\Magento\Framework\Exception\LocalizedException $e) {
			return "";
		}
	}


	// Return Ratings given by users to seller
	public function myRatings($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr){

		try{
			$response = array();
			$reviewFactory = $this->_reviewFactory;

			$collection = $reviewFactory->create()
			->addFieldToFilter(
				'seller_id',
				$sellerId
			);
			if($searchStr != ""){
				$collection->getSelect()->where(
					'feed_nickname like "%'.$searchStr.'%" OR feed_summary like "%'.$searchStr.'%" OR feed_review like "%'.$searchStr.'%"'
				);
			}

			$apiHelper = $this->_customApiHelper;


			$collection->setPageSize($length)->setCurPage($pageNum);
			$collection->setOrder($orderBy, $orderDir);
			$totalCount = $collection->getSize();
			$allRatings = [];
			foreach ($collection as $eachRating) {
				$ratingArray = $eachRating->getData();
				$ratingArray['buyerInfo'] = $apiHelper->getSellerShopById($eachRating->getBuyerId());
				array_push($allRatings, $ratingArray);

			}

			$response['ratings'] = $allRatings;
			$response['totalCount'] = $totalCount;
			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}

	// Return Ratings given by Seller To others
	public function givenRatings($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr){
		try{
			$response = array();
			$reviewFactory = $this->_reviewFactory;

			$marketplaceHelper = $this->_marketplacehelper;

			$websiteId = $marketplaceHelper->getWebsiteId();

			$collection = $reviewFactory->create()
			->addFieldToFilter(
				'buyer_id',
				$sellerId
			);

			$joinTable = $this->_objectManager->create(
				'Webkul\Marketplace\Model\ResourceModel\Seller\Collection'
			)->getTable('customer_entity');

			$collection->getSelect()->join(
				$joinTable.' as cgf',
				'main_table.buyer_id = cgf.entity_id AND website_id= '.$websiteId,
				array('firstname','lastname')
			);



			if($searchStr != ""){
				$collection->getSelect()->where(
					'firstname like "%'.$searchStr.'%" OR lastname like "%'.$searchStr.'%" OR feed_summary like "%'.$searchStr.'%" OR feed_review like "%'.$searchStr.'%"'
				);
			}


			$collection->setPageSize($length)->setCurPage($pageNum);
			$collection->setOrder($orderBy, $orderDir);
			$totalCount = $collection->getSize();

			$allRatings = [];

			$apiHelper = $this->_customApiHelper;

			foreach ($collection as $eachRating) {
				$ratingArray = $eachRating->getData();
				$ratingArray['buyerInfo'] = $apiHelper->getSellerShopById($eachRating->getBuyerId());
				array_push($allRatings, $ratingArray);

			}

			$response['items'] = $allRatings;
			$response['total_count'] = $totalCount;
			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}



		// Add Customer Address
	public function customeraddress($sellerId,$home_address,$addLine1, $addLine2, $city, $state, $country, $zip, $telephone, $defaultShipping, $defaultBilling, $addressId){
		$responseArray = array();
		try{
			$addressData =array();
			$addressData['addLine1'] = $addLine1;
			$addressData['addLine2'] = $addLine2;
			$addressData['city'] = $city;
			$addressData['state'] = $state;
			$addressData['country'] = $country;
			$addressData['zip'] = $zip;



			$array_data = $this->_customApiHelper->validateAddress($addressData);

			if($array_data['AddrExists'] == "TRUE"){
				$addressRepository = $this->_addressRepo;
				$addressDataFactory = $this->_objectManager->create('\Magento\Customer\Api\Data\AddressInterface');
				$customerObj = $this->_customerObj->load($sellerId);
				$streetArray = array();
				array_push($streetArray, $addLine1);
				array_push($streetArray, $addLine2);

				if($addressId != ""){
					$address = $addressRepository->getById($addressId);

					$address
					->setCustomerId($sellerId)
					->setFirstname($customerObj->getFirstname())
					->setLastname($customerObj->getLastname())
					->setCountryId($country)
					->setPostcode($zip)
					->setCity($city)
					->setRegionId($state)
					->setTelephone($telephone)
					->setCompany('')
					->setStreet($streetArray)
					->setCustomAttribute('home_address',$home_address)
					->setIsDefaultBilling($defaultShipping)
					->setIsDefaultShipping($defaultBilling);

					$addressRepository->save($address);

				}else{
					$address = $addressDataFactory
					->setCustomerId($sellerId)
					->setFirstname($customerObj->getFirstname())
					->setLastname($customerObj->getLastname())
					->setCountryId($country)
					->setPostcode($zip)
					->setCity($city)
					->setRegionId($state)
					->setTelephone($telephone)
					->setCompany('')
					->setStreet($streetArray)
					->setCustomAttribute('home_address',$home_address)
					->setIsDefaultBilling($defaultShipping)
					->setIsDefaultShipping($defaultBilling);

					$addressRepository->save($address);

					$addressId = $address->getId();

				}






				$responseArray['status'] = "Success";
				$responseArray['message'] = __('Address Saved Successfully.');
				$responseArray['addressId'] = $addressId;
			}else{
				$responseArray['status'] = "Error";
				$responseArray['message'] = __('Invalid Address');
			}



		}catch (\Exception $e){
			$responseArray['status'] = "Error";
			//$responseArray['message'] = __('Something went wrong.');
			$responseArray['message'] = $e->getMessage();
		}

		return $this->getResponseFormat($responseArray);

	}

	public function getAddresses($sellerId){
		try{
			$customerObj = $this->_customerObj->load($sellerId);
			$response = array();
			$defaultBilling = $customerObj->getDefaultBillingAddress();
			$defaultShipping = $customerObj->getDefaultShippingAddress();
			foreach ($customerObj->getAddresses() as $address)
			{
				$responseArray = $address->toArray();
				if ($defaultBilling) {
					if($defaultBilling->getId() == $address['entity_id']){
						$responseArray['defaultBilling'] = 1;
					}else{
						$responseArray['defaultBilling'] = 0;
					}
				}
				if ($defaultShipping) {
					if($defaultShipping->getId() == $address['entity_id']){
						$responseArray['defaultShipping'] = 1;
					}else{
						$responseArray['defaultShipping'] = 0;
					}
				}
				array_push($response, $responseArray);
			}

			return $this->getResponseFormat($response);
		}catch (\Exception $e){
			return $this->errorMessage($e);
		}

	}

	// GEt Customer Address By Address Id
	public function addressById($sellerId, $addressId){
		try{


			$addressRepository = $this->_addressRepo;
			$customerRepository= $this->_customerRepo;
			$customer = $customerRepository->getById($sellerId);
			$billingAddressId = $customer->getDefaultBilling();
			$shippingAddressId = $customer->getDefaultShipping();

			$addressObject = $addressRepository->getById($addressId);
			$response = (array)$addressObject;
			//$res = $response['data'];
			$responseArray = array();
			$homeObj = $addressObject->getCustomAttribute('home_address');
			if($homeObj){
				$home_address = $addressObject->getCustomAttribute('home_address')->getValue();
			}else{
				$home_address = 1;
			}
			$responseArray['home_address'] = $home_address;
			$street = $addressObject->getStreet();
			$responseArray['addLine1'] = $street[0];
			$responseArray['addLine2'] = (count($street) > 1) ? $street[1]: "";
			$responseArray['city'] = $addressObject->getCity();
			$responseArray['state'] = $addressObject->getRegionId();
			$responseArray['country'] = $addressObject->getCountryId();
			$responseArray['zip'] = $addressObject->getPostcode();
			$responseArray['telephone'] = $addressObject->getTelephone();
			$responseArray['defaultShipping'] = $addressObject->getId() == $shippingAddressId ? 1:0;
			$responseArray['defaultBilling'] = $addressObject->getId() == $billingAddressId ? 1:0;
			$responseArray['addressId'] = $addressObject->getId();


			return $this->getResponseFormat($responseArray);

		}catch(\Exception $e){
			return $this->errorMessage($e);
		}

	}

	// Delete Customer Address By Address Id
	public function deleteAddress($addressId){
		try{
			$addressRepository = $this->_addressRepo;

			$addressRepository->deleteById($addressId);
			$responseArray = array();
			$responseArray['status'] = "Success";
			$responseArray['message'] = __('Address Deleted Successfully.');

			return $this->getResponseFormat($responseArray);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}

	// Get Seller Profile

	public function profile($sellerId, $email){
		try{
			$helper = $this->_agoraehelper;
			$model = $this->_objectManager->create(
				'Webkul\Marketplace\Model\Product'
			)->getCollection()
			->addFieldToFilter(
				'seller_id',
				$sellerId
			);

			$model->getSelect()->join(
				["k" => "cataloginventory_stock_item"], 'main_table.mageproduct_id = k.product_id',
				array('qty')
			)->where("qty > 0");

			
			$marketplaceHelper = $this->_marketplacehelper;
			$regionModel = $this->_objectManager->create('Magento\Directory\Model\Region');
			$countryFactory = $this->_objectManager->create('Magento\Directory\Model\CountryFactory');
			$billingArray = array();
			$shippingArray = array();


			$orderModel = $this->getSalesTotalByOrderStatus($sellerId,"complete");



			$salesAmount = 0;

			if($orderModel->getSize() > 0){
				$salesObj = $orderModel->getFirstItem();
				$salesAmount = $salesObj->getSalesAmount() - $salesObj->getTotalCommission();
			}


			$pendingPayout = $this->getSalesTotalByOrderStatus($sellerId,"processing");

			$pendingAmount = 0;

			// if($pendingPayout->getSize() > 0){
			// 	$pendingObj = $pendingPayout->getFirstItem();
			// 	$pendingAmount = ($pendingObj->getSalesAmount() - $pendingObj->getTotalCommission() );
			// }

			$pendingAmount = $this->getInventorySales($sellerId);

			$customerRepo = $this->_customerRepo;
			$customer = $customerRepo->getById($sellerId);
			$billingAddressId = $customer->getDefaultBilling();
			$shippingAddressId = $customer->getDefaultShipping();

			$addressRepo = $this->_addressRepo;
			if($billingAddressId){
				$billingAddress = $addressRepo->getById($billingAddressId);
				if($billingAddress->getId()){
					$countryCode = $billingAddress->getCountryId();
					$country = $countryFactory->create()->loadByCode($countryCode);
					$billingArray['firstname'] = $billingAddress->getFirstname();
					$billingArray['lastname'] = $billingAddress->getLastname();
					$billingArray['city'] = $billingAddress->getCity();
					$billingArray['country'] = $country->getName();

					$billingRegion = $regionModel->load($billingAddress->getRegionId());
					$billingArray['state'] = $billingRegion->getName();
				}
			}
			if($shippingAddressId){
				$shippingAddress = $addressRepo->getById($shippingAddressId);
				if($shippingAddress->getId()){
					$countryCode = $shippingAddress->getCountryId();
					$country = $countryFactory->create()->loadByCode($countryCode);
					$shippingArray['firstname'] = $shippingAddress->getFirstname();
					$shippingArray['lastname'] = $shippingAddress->getLastname();
					$shippingArray['city'] = $shippingAddress->getCity();
					$shippingArray['country'] = $country->getName();

					$shippingRegion = $regionModel->load($shippingAddress->getRegionId());
					$shippingArray['state'] = $shippingRegion->getName();

				}
			}
			$responseArray = array();
			$partner = $marketplaceHelper->getSellerDataBySellerId($sellerId)->getFirstItem();

			if ($partner->getLogoPic()) {
				$logoPic = $marketplaceHelper->getMediaUrl().'avatar/'.$partner->getLogoPic();
			} else {
				$logoPic = $marketplaceHelper->getMediaUrl().'avatar/noimage.png';
			}
			$baseUrl = $this->_storemanager->getStore()->getBaseUrl();
			$seller = $helper->getCustomerData($sellerId);
			$responseArray['firstname'] = $customer->getFirstname();
			$responseArray['lastname'] = $customer->getLastname();
			$responseArray['sellerEmail'] = $seller->getEmail();
			$responseArray['companyLogo'] = $logoPic;
			$responseArray['contact'] = $partner->getContactNumber();
			$responseArray['smokefree'] = $partner->getSmokeFreeHome();
			$responseArray['petfree'] = $partner->getPetFreeHome();
			$responseArray['pettype'] = $partner->getPetType();
			$responseArray['gender'] = $customer->getGender();
			$responseArray['dob'] = $customer->getDob();
			$responseArray['public_profile_link'] = $baseUrl."marketplace/seller/collection/shop/".$partner->getShopUrl();
			$responseArray['productCount'] = count($model);
			$responseArray['followers'] = count($helper->getFollowersCustomer($sellerId));
			$responseArray['following'] = count($helper->getFollowingCustomer($sellerId));
			$responseArray['billingAddress'] = $billingArray;
			$responseArray['shippingAddress'] = $shippingArray;
			$responseArray['stripe'] = $this->getSellerStripe($sellerId, $email);
			$responseArray['salesAmount'] = round($salesAmount);
			$responseArray['pendingAmount'] = round($pendingAmount);
			$responseArray['ratings'] = $marketplaceHelper->getSelleRating($sellerId);

			return $this->getResponseFormat($responseArray);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}


	public function getInventorySales($customerId){
		$eavAttribute = $this->_objectManager->get(
			'Magento\Eav\Model\ResourceModel\Entity\Attribute'
		);
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
		$proStatusAttId = $eavAttribute->getIdByCode('catalog_product', 'status');

		$storeCollection->getSelect()->join(
			$catalogProductEntityStock.' as cpes',
			'main_table.mageproduct_id = cpes.product_id'
		);

		$storeCollection->getSelect()->where(
			'cpes.qty  >0 '
		);

		$storeCollection->getSelect()->group('mageproduct_id');
		$storeProductIDs = $storeCollection->getAllIds();
		$invAmount = 0;

		$resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
		$connection = $resource->getConnection();


		if(count($storeProductIDs) > 0){
			$productIds = implode( "', '", $storeProductIDs );

			$sql = "Select sum(value) as sumAmount FROM  catalog_product_entity_decimal where attribute_id = 74 and entity_id IN('".$productIds."')";
			$result = $connection->fetchRow($sql);	
			$invAmount = $result['sumAmount'];
		}
		return $invAmount;



	}

	public function getSalesTotalByOrderStatus($sellerId, $status){
		$payoutModel = $this->_objectManager->get('Webkul\Marketplace\Model\Orders')
		->getCollection()
		->addFieldToSelect( '*' )
		->addFieldToFilter( 'main_table.seller_id',$sellerId );

		$payoutModel->getSelect()->join(
			["pso" => "sales_order"],
			'main_table.order_id = pso.entity_id',
			array('subtotal')
		);

		$payoutModel->getSelect()->join(
			["psl" => "marketplace_saleslist"],
			'main_table.order_id = psl.order_id AND main_table.seller_id = psl.seller_id',
			array('total_commission')
		);

		$payoutModel->getSelect()
		->columns('sum(subtotal) as sales_amount')
		->columns('sum(total_commission) as total_commission')
		//->columns('sum(IF(`pso`.`subtotal` >  30,(`pso`.`subtotal` * .15),(`pso`.`subtotal` * .20))) as commission_amount')
		->where("subtotal is not null and pso.status = '".$status."'");

		return $payoutModel;
	}

	public function customerUnfollow($sellerId, $customerId){
		try{
			$reviewFactory = $this->_objectManager->create('Webkul\Agorae\Model\ResourceModel\Mpfavouriteseller\CollectionFactory');
			$collection = $reviewFactory->create()
			->addFieldToFilter('customer_id',$customerId)
			->addFieldToFilter('seller_id',$sellerId);


			if ($collection->getSize() > 0) {
				foreach ($collection as $eachFollow) {
					$eachFollow->delete();
				}
			}
			$response['status'] = "Success";
			$response['message'] = "Seller Unfollowed Successfully";
			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}

	// Upload Seller Profile Pic

	public function editProfilePic($content, $sellerId) {
		try {
			$response = array();
			$fileSystem = $this->_fileSystem;
			$mediaDirectory = $fileSystem->getDirectoryWrite(
				DirectoryList::MEDIA
			);

			$marketplaceHelper = $this->_marketplacehelper;

			$seller = $this->_sellerModel->getCollection()
			->addFieldToFilter('seller_id', $sellerId)->getFirstItem();

			if($seller->getId()){

				$target = $mediaDirectory->getAbsolutePath('avatar/');
				$content = preg_replace('#data:image/[^;]+;base64,#', '', $content);
				$content = str_replace(' ', '+', $content);
				$data = base64_decode($content);
				$fileName = uniqid() . '.png';

				//echo $fileName;
				//die("DDDDDDDDDDDDD");

				$file = $target . $fileName;
				$success = file_put_contents($file, $data);
				$seller->setLogoPic($fileName);
				$seller->save();
				$responseArray['status'] =   'Success';
				$responseArray['imagePath'] = $marketplaceHelper->getMediaUrl().'avatar/'.$fileName;
				$responseArray['message'] = 'Profile Pic updated successfully.';
			}else{
				$responseArray['status'] =   'Error';
				$responseArray['imagePath'] = "";
				$responseArray['message'] = 'Invalid Seller.';
			}
			return $this->getResponseFormat($responseArray);

		} catch (\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	public  function editProfile($sellerId, $firstname, $lastname,  $email, $contact, $smokefree, $petfree,$pettype,$gender,$dob, $companyLogo){
		try{
			$response = array();
			$fileSystem = $this->_fileSystem;
			$mediaDirectory = $fileSystem->getDirectoryWrite(
				DirectoryList::MEDIA
			);
			$marketplaceHelper = $this->_marketplacehelper;

			$value = $this->_sellerModel
			->getCollection()
			->addFieldToFilter('seller_id', $sellerId)->getFirstItem();
			$target = $mediaDirectory->getAbsolutePath('avatar/');
			$content = preg_replace('#data:image/[^;]+;base64,#', '', $companyLogo);
			$content = str_replace(' ', '+', $content);
			$data = base64_decode($content);
			$fileName = uniqid() . '.png';
			$file = $target . $fileName;
			$success = file_put_contents($file, $data);
			$customerRepo = $this->_customerRepo;
			$customer = $customerRepo->getById($sellerId);
			$customer->setFirstname($firstname);
			$customer->setLastname($lastname);
			$customer->setGender($gender);
			$customer->setDob($dob);
			//$value->setLogoPic($fileName);
			$value->setContactNumber($contact);
			$value->setSmokeFreeHome($smokefree);
			$value->setPetFreeHome($petfree);
			$value->setPetType($pettype);

			$value->save();
			$customerRepo->save($customer);

			$responseArray['status'] = "Success";
			$responseArray['message'] = __('Profile Updated Successfully.');
			return $this->getResponseFormat($responseArray);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}

	public function checkPassword($sellerId, $password){
		try{
			$response = array();
			$authModel = $this->_authModel;
			if($authModel->authenticate($sellerId, $password)){
				$response['status'] = "Success";
				$response['message'] = __('Valid Password.');
			}
			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}
	public function resetPassword($sellerId, $currentPassword, $password, $confirmPassword){
		try{
			$response = array();
			$authModel = $this->_authModel;
			if($authModel->authenticate($sellerId, $currentPassword)){

				if($password != $confirmPassword){
					$response['status'] = "Fail";
					$response['message'] = __('Password does not match with confirm password.');
				}else{
					$customerRepository = $this->_customerRepo;
					$encryptor = $this->_objectManager->create('Magento\Framework\Encryption\Encryptor');
					$customerRegistry = $this->_objectManager->create('Magento\Customer\Model\CustomerRegistry');
					$customer = $customerRepository->getById($sellerId);
					$customerRepository->save($customer, $encryptor->getHash($password, true));
					$response['status'] = "Success";
					$response['message'] = __('Password changed successfully.');
				}
			}
			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}

	public function getRating($ratingId){
		try{
			$response = array();
			$reviewFactory = $this->_reviewFactory;
			$collection = $reviewFactory->create()
			->addFieldToFilter(
				'entity_id',
				$ratingId
			);;
			return $this->getResponseFormat($collection->getData());
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}



	}


	public function allQuestions($sellerId, $pageNum, $length, $orderBy, $orderDir){
		try{
			$_questionFactory = $this->_questionFactory;
			$collection = $_questionFactory
			->create()->getCollection()
			->addFieldToFilter('seller_id', $sellerId)
			->addFieldToFilter('status', 1);
			$store = $this->_storemanager->getStore();
			$collection->setPageSize($length)->setCurPage($pageNum);
			$collection->setOrder($orderBy, $orderDir);
			$totalCount = $collection->getSize();
			$questionArray = array();
			foreach($collection as $question){
				$itemData = $question->getData();
				$_product = $this->_productModel
				->load($question->getProductId());
				$itemData['name'] = $_product->getName();

				$itemData['imageUrl'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getImage();
				array_push($questionArray, $itemData);
			}
			$response['questions'] = $questionArray;
			$response['totalCount'] = $totalCount;

			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}

	public function questionById($questionId){
		try{
			$_questionFactory = $this->_questionFactory;
			$question = $_questionFactory->create()->load($questionId);
			$apiHelper = $this->_customApiHelper;
			$response = array();
			$store = $this->_storemanager->getStore();
			$product = $this->_productModel->load($question->getProductId());
			$productArray = array();
			$productArray['name'] = $product->getName();
			$productArray['description'] = $product->getDescription();
			$productArray['imageUrl'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
			$response['question'] = $question->getData();
			$response['product'] = $productArray;
			$response['buyerInfo'] = $apiHelper->getSellerShopById($question->getBuyerId());
			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}

	public function answerQuestion($sellerId, $questionId, $answer){
		try{
			$response = array();
			$datetime = $this->_dateTime;
			$_questionFactory = $this->_questionFactory;
			$_answer = $this->_answer;
			$time = $datetime->date();

			$respond_type = 'Seller';

			$data_time = $datetime->date('Y-m-d H:i:s', $time);
			$answer = strip_tags($answer);
			$model=$_answer->create();
			$model->setQuestionId($questionId)
			->setRespondFrom($sellerId)
			->setRespondType($respond_type)
			->setRespondNickname("Seller")
			->setContent($answer)
			->setStatus(1)
			->setCreatedAt($data_time);
			$id=$model->save()->getId();

			if (isset($id)) {   //send response mail
				$_mphelper = $this->_marketplacehelper;
				$customerRepository = $this->_objectManager->create('\Magento\Customer\Api\CustomerRepositoryInterface\Proxy');
				$_helper = $this->_objectManager->create('Webkul\Mpqa\Helper\Data');

				$response['status'] = "Success";
				$response['message'] = "Response saved successfully.";
				//$this->messageManager->addSuccess(__("Response saved successfully."));
				$question = $_questionFactory->create()->load($questionId);
				$customer_id=$question->getBuyerId();
				$seller_id = $question->getSellerId();
				$p_id=$question->getProductId();
				// Set Notification Buyer


				$notification_helper = $this->_objectManager->create(
					'Seasia\Customnotifications\Helper\Data'
				);
				$itemId = $questionId;
				$type = "answer";
				$sent_for = $seller_id;
				$sent_to =  $customer_id;
				$message = 'Test';

				$notification_helper->setNotification($itemId,$type,$sent_to,$sent_for,$message);


				$adminStoremail = $_mphelper->getAdminEmailId();
				$adminEmail=$adminStoremail? $adminStoremail:$_mphelper->getDefaultTransEmailId();
				$adminUsername = 'Admin';
				$customers=$customerRepository->getById($customer_id);
				$customer_name=$customers->getFirstName()." ".$customers->getLastName();
				$product = $this->_productModel->load($p_id);
				$scopeConfig = $this->_objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface');
				$product_name=$product->getName();
				$url = $product->getProductUrl();
				$msg = __('You have got a new response on your Question.');
				$templateVars = [
					'store' => $this->_storemanager->getStore(),
					'customer_name' => $customer_name,
					'link'          =>  $url,
					'product_name'  => $product_name,
					'message'   => $msg
				];
				$to = [$customers->getEmail()];
				$from = ['email' => $adminEmail, 'name' => 'Admin'];
				$_helper->sendResponseMail($templateVars, $from, $to);
			// mail to admin
				$admin_email = $scopeConfig->getValue(
					'mpqa/general_settings/responseto_admin',
					\Magento\Store\Model\ScopeInterface::SCOPE_STORE
				);
				if ($admin_email) {
					$msg = __('Seller replied to a query on his product.');
					$customers=$customerRepository->getById($seller_id);
					$customer_name=$customers->getFirstName()." ".$customers->getLastName();
					$templateVars = [
						'store' => $this->_storemanager->getStore(),
						'customer_name' => __('Admin'),
						'link'          =>  $url,
						'product_name'  => $product_name,
						'message'   => $msg
					];
					$to = [$adminEmail];
					$from = ['email' => $customers->getEmail(), 'name' => $customer_name];
					$_helper->sendResponseMail($templateVars, $from, $to);
				}
			} else {
				$response['status'] = "Error";
				$response['message'] = "Error while saving response.";
				//$this->messageManager->addError(__("Error while saving response."));
			}




			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}

	// Get All Answers of Question

	public function answersByQuestion($sellerId, $questionId){
		try{
			$_answer = $this->_answer;
			$_review = $this->_objectManager->create('Webkul\Mpqa\Model\ReviewFactory');
			$model=$_answer->create()->getCollection();
			$model->addFieldToFilter('question_id', $questionId);
			$response = array();
			foreach ($model as $ans) {
				$answerArray = $ans->getData();

				$reviews = $_review->create()->getCollection()->addFieldToFilter('answer_id', $ans->getAnswerId());
				$likes = 0;
				$dislikes = 0;
				foreach ($reviews as $key) {
					if ($key->getLikeDislike()==1) {
						$likes++;
					} else {
						$dislikes++;
					}
				}

				$answerArray['likesCount'] = $likes;
				$answerArray['dislikesCount'] = $dislikes;
				array_push($response, $answerArray);
			}
			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}


	// Get Seller Saved Cards

	public function savedCards($sellerId){
		try{
			$response = array();
			$collection = $this->_objectManager
			->create('Magento\Vault\Model\PaymentToken')
			->getCollection()
			->addFieldToFilter('customer_id', ['eq' => $sellerId]);
			if ($collection->getSize() > 0) {
				$response = $collection->getData();
			}
			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}

	public function deleteCards($sellerId, $cardIds){
		try{
			$response = array();
			$cardIdArray = explode(",",$cardIds);
			if ($sellerId && count($cardIdArray) > 0) {
				foreach($cardIdArray as $card){
					$collection = $this->_objectManager
					->create('Magento\Vault\Model\PaymentToken')
					->getCollection()
					->addFieldToFilter('customer_id', ['eq' => $sellerId])
					->addFieldToFilter('entity_id', ['eq' => $card]);
					if ($collection->getSize() > 0) {
						foreach ($collection as $eachcard) {
							$eachcard->delete();
						}
					}
				}
			}
			$response['status'] = "Success";
			$response['message'] = __('Cards Deleted Successfully.');
			return $this->getResponseFormat($response);

		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}



	// GEt Saved Searches

	public function savedSearches($sellerId, $pageNum, $length, $orderBy, $orderDir){
		try{
			$saveModel = $this->_saveSearchFactory->create();
			$collection = $saveModel->getCollection()->addFieldToFilter('customer_id', array('eq' => $sellerId));

			$collection->setPageSize($length)->setCurPage($pageNum);
			$collection->setOrder($orderBy, $orderDir);
			$totalCount = $collection->getSize();

			$searchArray = array();
			foreach($collection as $eachSearch){
				$eachArray = $eachSearch->getData();
				$searchcollection = $this->getSavedSearchPendingCollection($eachSearch->getId(), $sellerId);
				$eachArray['notificationCount'] = $searchcollection->getSize();
				array_push($searchArray, $eachArray);

			}

			$response = array();
			$response['savedSearches'] = $searchArray;
			$response['totalCount'] = $totalCount;
			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}


	protected function getSavedSearchPendingCollection($searchId,$sellerId){
		$searchcollection = $this->_objectManager->create('Seasia\Savesearchnotification\Model\ResourceModel\Savesearchnotification\Collection')
		->addFieldToFilter('save_search_id', array('eq' => $searchId))
		->addFieldToFilter('notification_for', array('eq' => $sellerId))
		->addFieldToFilter('notification_status', array('eq' => "pending"));
		return $searchcollection;
	}

	// GEt Saved Search by Id

	public function searchById($searchId){
		try{
			$saveModel = $this->_saveSearchFactory->create();
			$searches = $saveModel->load($searchId);
			$response = array();
			$response = $searches->getData();
			$searchcollection = $this->getSavedSearchPendingCollection($searchId, $searches->getCustomerId());
			$products = array();
			$store = $this->_storemanager->getStore();
			foreach($searchcollection as $collection){
				//$collection->setNotificationStatus("seen")->save();
				$eachProduct = array();
				$product = $this->_productModel->load($collection->getProductId());
				$eachProduct['product_id'] = $product->getId();
				$eachProduct['product_name'] = $product->getName();
				$eachProduct['price'] = $product->getPriceInfo()->getPrice('final_price')->getValue();
				$eachProduct['original_price'] = $product->getCost();
				$eachProduct['description'] = $product->getDescription();
				$eachProduct['imageUrl'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
				$eachProduct['productUrl'] = $product->getProductUrl();
				array_push($products, $eachProduct);
			}
			$response['products'] = $products;

			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}

	}

	// Edit Saved Search

	public function editSearch($sellerId, $searchTerm, $searchName){
		try{
			$saveModel = $this->_saveSearchFactory->create();

			$response = array();
			$saveModel->setSearchTerm($searchTerm);
			$saveModel->setSearchName($searchName);
			$saveModel->setCustomerId($sellerId);
			$saveModel->save();
			$response['message'] = __('Your Search Term Saved Successfully!!');
			$response['status'] = "Success";
			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}

	// Delete Saved Searches

	public function deleteSearch($searchIds){
		try{
			$saveModel = $this->_saveSearchFactory->create();
			$searchArray = explode(",", $searchIds);
			if(count($searchArray) > 0){
				foreach($searchArray as $search){
					$searches = $saveModel->load($search);
					$searches->delete();
				}
			}
			$response['message'] = __('Your Search Term Deleted Successfully!!');
			$response['status'] = "Success";
			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}

	// Get Customer Following Data

	public function customerFollowing($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr){
		try {
			$helper = $this->_agoraehelper;
			$collection = $helper->getFollowingCustomer($sellerId);

			$joinTable = $this->_objectManager->create(
				'Webkul\Agorae\Model\ResourceModel\Mpfavouriteseller\Collection'
			)->getTable('customer_entity');

			$collection->getSelect()->join(
				$joinTable.' as cgf',
				'main_table.seller_id = cgf.entity_id',
				array('firstname','lastname')
			);

			if($searchStr != ""){
				$collection->getSelect()->where(
					'firstname like "%'.$searchStr.'%" OR lastname like "%'.$searchStr.'%"'
				);
			}

			$collection->setPageSize($length)->setCurPage($pageNum);
			$collection->setOrder($orderBy, $orderDir);
			$totalCount = $collection->getSize();
			$allFollowing = array();
			$i = 0;
			$marketplaceHelper = $this->_marketplacehelper;
			foreach($collection as $value) {

				$partner = $marketplaceHelper->getSellerDataBySellerId($value->getSellerId())->getFirstItem();

				if ($partner->getLogoPic()) {
					$logoPic = $marketplaceHelper->getMediaUrl().'avatar/'.$partner->getLogoPic();
				} else {
					$logoPic = $marketplaceHelper->getMediaUrl().'avatar/noimage.png';
				}
				$eachFollowing = $value->getData();
				$eachFollowing['username'] = $partner->getShopUrl();
				$eachFollowing['shopUrl'] = $marketplaceHelper ->getRewriteUrl(
					'marketplace/seller/collection/shop/'.
					$partner->getShopUrl()
				);
				$eachFollowing['companyLogo'] = $logoPic;
				$eachFollowing['ratings'] = $marketplaceHelper->getSelleRating($value['seller_id']);

				array_push($allFollowing, $eachFollowing);
			}

			$response['following'] = $allFollowing;
			$response['totalCount'] = $totalCount;
			return $this->getResponseFormat($response);

		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	// Get Customer Followers Data

	public function customerFollowers($sellerId, $pageNum, $length, $orderBy, $orderDir, $searchStr){
		try {
			$helper = $this->_agoraehelper;
			$collection = $helper->getFollowersCustomer($sellerId);
			$marketplaceHelper = $this->_marketplacehelper;
			$joinTable = $this->_objectManager->create(
				'Webkul\Agorae\Model\ResourceModel\Mpfavouriteseller\Collection'
			)->getTable('customer_entity');

			$collection->getSelect()->join(
				$joinTable.' as cgf',
				'main_table.customer_id = cgf.entity_id',
				array('firstname','lastname')
			);

			if($searchStr != ""){
				$collection->getSelect()->where(
					'firstname like "%'.$searchStr.'%" OR lastname like "%'.$searchStr.'%"'
				);
			}

			$collection->setPageSize($length)->setCurPage($pageNum);
			$collection->setOrder($orderBy, $orderDir);
			$totalCount = $collection->getSize();

			$collection_data = $collection->getData();

			$res_array = [];
			$_helper = $this->_customApiHelper;
			foreach($collection_data as $key => $value) {
				$partner = $marketplaceHelper->getSellerDataBySellerId($value['customer_id'])->getFirstItem();

				if ($partner->getLogoPic()) {
					$logoPic = $marketplaceHelper->getMediaUrl().'avatar/'.$partner->getLogoPic();
				} else {
					$logoPic = $marketplaceHelper->getMediaUrl().'avatar/noimage.png';
				}
				$productids = $_helper->getSellerProductsIdArray($value['seller_id']);

				$product_count = $_helper->getProductcountByCustomerId($productids,$value['customer_id']);

				$like_count = 0;
				if(count($product_count) > 0) {
					$like_count = count($product_count);
				}
				$value['companyLogo'] = $logoPic;

				$value['ratings'] = $marketplaceHelper->getSelleRating($value['customer_id']);

				$value['like_count'] = $like_count;

				$value['buyerInfo'] = $this->_customApiHelper->getSellerShopById($value['customer_id']);
				$res_array[$key] = $value;
			}

			$response['followers'] = $res_array;
			$response['totalCount'] = $totalCount;

			return $this->getResponseFormat($response);

		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	// get product detail of customer likes using seller id and customer id
	public function getProductDetailsBySellerCustomerId($sellerId,$customerId){

		try {
			$_helper = $this->_customApiHelper;
			$productids = $_helper->getSellerProductsIdArray($sellerId);
			$productIdArray = $_helper->getProductcountByCustomerId($productids,$customerId);
			$productDetailArray = [];
			if(count($productIdArray) > 0) {
				foreach($productIdArray as $key => $value) {
					$product = $this->_productModel->load($value['product_id']);
					$stockItem = $product->getExtensionAttributes()->getStockItem();

					$productDetailArray[$key]['product_id'] = $product->getId();
					$productDetailArray[$key]['product_seller_id'] = $sellerId;
					$productDetailArray[$key]['product_customer_id'] = $customerId;
					$productDetailArray[$key]['product_name'] = $product->getName();
					$productDetailArray[$key]['product_desc'] = $product->getDescription();
					$productDetailArray[$key]['product_sku'] = $product->getSku();

					$productDetailArray[$key]['status'] = $product->getResource()->getAttribute('status')->getFrontend()->getValue($product);

					$productDetailArray[$key]['stock_status'] = $stockItem->getQty() > 0 ? "Available":"Sold";
				}
			}
			return $this->getResponseFormat($productDetailArray);
		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	public function addReview($sellerId, $customerId, $customerEmail, $feed_nickname, $feed_summary, $feed_review, $feed_price, $feed_value, $feed_quality){
		try{
			$wholedata = array();
			$buyerId = $customerId;
			$buyerEmail = $customerEmail;
			$wholedata['seller_id'] = $sellerId;
			$wholedata['feed_nickname'] = $feed_nickname;
			$wholedata['feed_summary'] = $feed_summary;
			$wholedata['feed_review'] = $feed_review;
			$wholedata['feed_price'] = $feed_price;
			$wholedata['feed_value'] = $feed_value;
			$wholedata['feed_quality'] = $feed_quality;
			$wholedata['buyer_email'] = $buyerEmail;
			$datetime = $this->_dateTime;

			$time = $datetime->date();
			$data_time = $datetime->date('Y-m-d H:i:s', $time);
			$wholedata['created_at'] = $data_time;
			$feedbackcount = 0;
			$collectionfeed = $this->_objectManager->create('Webkul\Marketplace\Model\Feedbackcount')->getCollection()->addFieldToFilter('seller_id',$sellerId)->addFieldToFilter('buyer_id',[$buyerId]);


			foreach ($collectionfeed as $value) {
				$feedbackcount = $value->getFeedbackCount();
				$value->setFeedbackCount($feedbackcount + 1);
				$value->save();
			}


			$collection = $this->_objectManager->create(
				'Webkul\Marketplace\Model\Feedback'
			);
			$collection->setData($wholedata);
			$collection->save();

			// Seller Review Notification

			$notification_helper = $this->_objectManager->create('Seasia\Customnotifications\Helper\Data');
				//$lastOrderId $sellerid

			$itemId = $collection->getId();
			$type = "seller_review";
			$message = 'Test';

			$notification_helper->setNotification($itemId,$type,$sellerId,$customerId,$message);

			$response['status'] = "Success";
			$response['message'] = __('Your Review was successfully saved');
			return $this->getResponseFormat($response);
		}catch(\Exception $e) {
			return $this->errorMessage($e);
		}

	}

	public function getCustomerLikedProducts($sellerId, $length, $pageNum, $orderBy, $orderDir){
		try{
			$response = array();
			$wishlist = $this->_objectManager->create('\Magento\Wishlist\Model\Wishlist');
			$collection = $wishlist->loadByCustomerId($sellerId, true)->getItemCollection();
			$collection->setPageSize($length)->setCurPage($pageNum);
			$collection->setOrder($orderBy, $orderDir);
			$products = array();
			$store = $this->_storemanager->getStore();
			foreach ($collection as  $_product) {
				$eachProduct = array();
				$product = $this->_productModel->load($_product->getProductId());
				$eachProduct['product_id'] = $product->getId();
				$eachProduct['product_name'] = $product->getName();
				$eachProduct['price'] = $product->getPriceInfo()->getPrice('final_price')->getValue();
				$eachProduct['original_price'] = $product->getCost();
				$eachProduct['description'] = $product->getDescription();
				$eachProduct['imageUrl'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
				$eachProduct['productUrl'] = $product->getProductUrl();
				array_push($products, $eachProduct);
			}
			$response['likedProducts'] = $products;
			$response['productTotalCount'] = $collection->getSize();
			return $this->getResponseFormat($response);
		}catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}


	public function notificationsettings($sellerId, $settings, $notificationId){
		try{
			$response = array();
			$collection = $this->_objectManager->create('Seasia\Notificationsetting\Model\Notificationsetting');
			if(!empty($notificationId)) {

				$collection = $collection->getCollection()->addFieldToFilter('seller_id',$sellerId);

				$savedSettings = $collection->getFirstItem();
				$savedSettings->setNotificationSerialized(serialize($settings));
				$savedSettings->save();
			}else{


				$datetime = $this->_dateTime;

				$time = $datetime->date();
				$data_time = $datetime->date('Y-m-d H:i:s', $time);

				$collection->setSellerId($sellerId);
				$collection->setNotificationSerialized(serialize($settings));
				$collection->setCreatedAt($data_time);
				$collection->save();
			}
			$response['status'] = "Success";
			$response['message'] = "Notification Settings Saved Successfully.";
			return $this->getResponseFormat($response);
		}
		catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	public function getnotificationsettings($sellerId){
		try{
			$response = array();
			$_helper = $this->_customApiHelper;
			$notificationSettings = $_helper->getNotificationSetting($sellerId);
			if($notificationSettings->getId()){
				$savedSettings = unserialize($notificationSettings->getNotificationSerialized());
				$response['notificationId'] = $notificationSettings->getId();
				$response['settings'] = $savedSettings;
			}else{
				$collection = $this->_objectManager->create('Seasia\Notificationsetting\Model\Notificationsetting');
				$datetime = $this->_dateTime;

				$time = $datetime->date();
				$data_time = $datetime->date('Y-m-d H:i:s', $time);
				$settings = array(
					"answer" => "1",
					"buyer_order" => "1",
					"frockShop" => "1",
					"offer_accepted" => "1",
					"offer_received" => "1",
					"offer_rejected" => "1",
					"payment" => "1",
					"product_like" => "1",
					"question" => "1",
					"returns" => "1",
					"saveSearchMatches" => "1",
					"seller_order" => "1",
					"seller_review" => "1",
					"trackingShipmentReceived" => "1",
					"trackingshipment" => "1",
					"counter_offer" => "1",
					"return_approved" => "1",
					"return_funded" => "1",
					"return_made" => "1",
					"return_offer" => "1",
					"return_offer_accepted" => "1",
					"return_offer_rejected" => "1"
				);
				$collection->setSellerId($sellerId);
				$collection->setNotificationSerialized(serialize($settings));
				$collection->setCreatedAt($data_time);
				$collection->save();

				$response['sellerId'] = $sellerId;
				$response['notificationId'] = $collection->getId();
				$response['settings'] = $settings;

			}

			return $this->getResponseFormat($response);
		}
		catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	public function getAllNotification($sellerId,$length, $pageNum, $orderBy, $orderDir) {
		try {
			$response = [];
			$type_string = [];
			$_helper = $this->_customApiHelper;
			$notificationSettings = $_helper->getNotificationSetting($sellerId);

			$collection = $this->_objectManager->create('Seasia\Customnotifications\Model\Notifications');
			$collection = $collection->getCollection()->addFieldToFilter('notification_to',$sellerId)->addFieldToFilter('status','unread');



			if($notificationSettings->getId()) {
				$savedSettings = unserialize($notificationSettings->getNotificationSerialized());
				// echo "<pre>"; print_r($savedSettings);

				// die();
				foreach ($savedSettings as $key => $value) {
					if($value != '0') {
						$type_string[] = $key;
					}
				}
				$string = implode(',', $type_string);
				$collection = $collection->addFieldToFilter('type',['in' => $string]);
			}

			//echo $collection->getSelect();

			//$data = $collection->getData();
			$collection->setPageSize($length)->setCurPage($pageNum);
			$collection->setOrder($orderBy, $orderDir);
			$response['total_count'] = $collection->getSize();

			$notificationArray = array();
			foreach($collection as $eachNotification){
				$eachNotificationArray = $_helper->getNotificationDetails($eachNotification);
				if(!empty($eachNotificationArray))
					array_push($notificationArray, $eachNotificationArray);
			}

			$response['notifications'] = $notificationArray;

			return $this->getResponseFormat($response);
		}  catch(\Exception $e) {
			return '';//$this->errorMessage($e);
		}
	}
	public function getNotification($sellerId,$length, $pageNum, $orderBy, $orderDir) {

		try {
			$response = [];
			$type_string = [];
			$_helper = $this->_customApiHelper;
			$notificationSettings = $_helper->getNotificationSetting($sellerId);

			$collection = $this->_objectManager->create('Seasia\Customnotifications\Model\Notifications');
			$collection = $collection->getCollection()->addFieldToFilter('notification_to',$sellerId)->addFieldToFilter('status','unread');

			if($notificationSettings->getId()) {
				$savedSettings = unserialize($notificationSettings->getNotificationSerialized());
				foreach ($savedSettings as $key => $value) {
					if($value != '0') {
						$type_string[] = $key;
					}
				}
				$string = implode(',', $type_string);
				$collection = $collection->addFieldToFilter('type',['in' => $string]);
			}

			$collection->setPageSize($length)->setCurPage($pageNum);
			$collection->setOrder($orderBy, $orderDir);
			$data = $collection->getData();
			$notificationArray = array();
			foreach($collection as $eachNotification){
				$eachNotificationArray = $_helper->getNotificationDetails($eachNotification);
				if(!empty($eachNotificationArray))
					array_push($notificationArray, $eachNotificationArray);
			}


			$response['notifications'] = $notificationArray;
			$response['total_count'] = $collection->getSize();

			return $this->getResponseFormat($response);
		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	public function readNotification($notificationId) {

		try {
			$response = [];
			$collection = $this->_objectManager->create('Seasia\Customnotifications\Model\Notifications');
			$collection = $collection->getCollection()->addFieldToFilter('entity_id',$notificationId);

			$collection = $collection->getFirstItem();
			$collection->setStatus('read');
			$collection->save();
			$response['status']= 'Success';
			$response['message']= 'Notification has been read successfully.';
			return $this->getResponseFormat($response);
		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	public function saveStripeSeller($account_code, $sellerId) {

		try {

			$ch = curl_init();
			$data = array();
			$helper = $this->_stripeHelper;
			$stripeClientSecret = $helper->getConfigValue('api_key');
			$data = array(
				"client_secret" => $stripeClientSecret,
				"code" => $account_code,
				"grant_type" => "authorization_code"
			);

			$ch = curl_init("https://connect.stripe.com/oauth/token");
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);

			$value = curl_exec($ch);

			$revert = json_decode($value);
			$revert = (array) $revert;

			if(isset($revert['livemode']) && $revert['livemode'] != ""){
				$verified = '1';
			}else{
				$verified = '0';
			}
			$post = array(
				'user_id' => $sellerId,
				'key' => $revert['stripe_publishable_key'],
				'isverified' => $verified,
				'user_type' => "seller",
				'access_token' => $revert['access_token'],
				'refresh_token' => $revert['refresh_token'],
				'stripe_user_id' => $revert['stripe_user_id'],
			);
			$response = [];
			$collection = $this->_stripeHelper->saveStripeSeller($post);
			$response['status']= 'Success';
			$response['message']= 'You are successfully connected to stripe.';
			return $this->getResponseFormat($response);
		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	public function getBadges() {

		try {
			$response = [];
			$_badge = $this->_objectManager->create('Webkul\MpSellerBadge\Model\Badge');
			$collection = $_badge->getCollection();

			$response['badges'] = $collection->getData();

			return $this->getResponseFormat($response);
		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	public function getSellerBadges($sellerId) {
		try {
			$response = $this->getSellerBadgeDetails($sellerId);
			return $this->getResponseFormat($response);
		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	public function getSellerBadgeDetails($sellerId){
		$response = [];
		if($this->updateReliableBadge($sellerId)  && $this->updateFastShipperBadge($sellerId) && $this->updatePhoneBadge($sellerId) && $this->updateResponderBadge($sellerId)){

			$seller_badge = $this->_objectManager->create('Webkul\MpSellerBadge\Model\Sellerbadge');

			$collection = $seller_badge->getCollection();
			$collection->addFieldToFilter('main_table.seller_id',array('eq' => $sellerId));
			$collection->getSelect()->join(
				["sb" => "mpbadges"],
				'main_table.badge_id = sb.entity_id',
				array('badge_name', 'font_awesome_icon')
			);;

			$response['badges'] = $collection->getData();

			$customer = $this->_customerObj->load($sellerId);
			$response['since'] = date("Y",strtotime($customer->getCreatedAt()));
		}
		return $response;
	}

	public function updateResponderBadge($sellerId){
		$badge = $this->getBadgeByName("Fast Responder");
		$badgeId = $badge->getId();

		$seller_badge = $this->_objectManager->create('Webkul\MpSellerBadge\Model\Sellerbadge');

		$collection = $seller_badge->getCollection();
		$collection->addFieldToFilter('badge_id',array('eq' => $badgeId));
		$collection->addFieldToFilter('seller_id',array('eq' => $sellerId));

		$questionModel = $this->_objectManager->get('Webkul\Mpqa\Model\Question')
		->getCollection()
		->addFieldToFilter( 'main_table.seller_id',$sellerId );

		$questionModel ->getSelect()
		->columns('AVG(respond_time) as respond_avg')
		->where("respond_time is not null");



		$avgValue = $questionModel->getFirstItem();
		if($avgValue->getId()){
			if($avgValue->getRespondAvg() > 12){
				foreach ($collection as $respondBadge) {
					$respondBadge->delete();
				}
			}else{
				if($collection->getSize() == 0){
					$this->saveSellerBadge($badgeId, $sellerId, $seller_badge);
				}

			}
		}
		return true;
	}

	public function updatePhoneBadge($sellerId){
		$badge = $this->getBadgeByName("Phone Verified");
		$badgeId = $badge->getId();

		$seller_badge = $this->_objectManager->create('Webkul\MpSellerBadge\Model\Sellerbadge');

		$collection = $seller_badge->getCollection();
		$collection->addFieldToFilter('badge_id',array('eq' => $badgeId));
		$collection->addFieldToFilter('seller_id',array('eq' => $sellerId));


		$stripeFactory = $this->_stripeFactory;

		$sellerCollection = $stripeFactory->create()->addFieldToFilter(
			'seller_id',
			['eq' => $sellerId]
		)
		;

		if($sellerCollection->getSize() > 0){
			if($collection->getSize() == 0){
				$this->saveSellerBadge($badgeId, $sellerId, $seller_badge);
			}
			return true;
		}else{
			foreach ($collection as $phoneBadge) {
				$phoneBadge->delete();
			}
			return true;
		}
		return false;
	}

	public function updateReliableBadge($sellerId){
		$badge = $this->getBadgeByName("Reliable");
		$badgeId = $badge->getId();
		$seller_badge = $this->_objectManager->create('Webkul\MpSellerBadge\Model\Sellerbadge');

		$collection = $seller_badge->getCollection();
		$collection->addFieldToFilter('badge_id',array('eq' => $badgeId));
		$collection->addFieldToFilter('seller_id',array('eq' => $sellerId));

		$orderModel = $this->_objectManager->get('Webkul\Marketplace\Model\Orders')
		->getCollection()
		->addFieldToSelect( '*' )
		->addFieldToFilter( 'main_table.seller_id',$sellerId )
		->addFieldToFilter('created_at', array('gteq' => date('Y-m-d H:i:s', strtotime('-90 day'))));


		$orderModel->getSelect()->join(
			["so" => "sales_order"],
			'main_table.order_id = so.entity_id and status = "canceled"',
			array('status')
		);

		$orderModel->getSelect()->joinLeft(
			["sof" => "selleroffers"],
			'main_table.order_id = sof.order_id',
			array('status')
		);

		$orderModel->getSelect()->where('sof.order_id is null');

		$returnModel = $this->_objectManager->get('Seasia\Returnitem\Model\Returnitem')
		->getCollection()
		->addFieldToSelect( '*' )
		->addFieldToFilter( 'seller_id',$sellerId );



		if($orderModel->getSize() > 0  || $returnModel->getSize() > 0){
			foreach ($collection as $reliableBadge) {
				$reliableBadge->delete();
			}
		}else{
			if($collection->getSize() == 0){
				$this->saveSellerBadge($badgeId, $sellerId, $seller_badge);
			}
		}

		return true;

	}

	public function updateFastShipperBadge($sellerId){
		$badge = $this->getBadgeByName("Fast Shipper");
		$badgeId = $badge->getId();
		$seller_badge = $this->_objectManager->create('Webkul\MpSellerBadge\Model\Sellerbadge');

		$collection = $seller_badge->getCollection();
		$collection->addFieldToFilter('badge_id',array('eq' => $badgeId));
		$collection->addFieldToFilter('seller_id',array('eq' => $sellerId));

		$orderModel = $this->_objectManager->get('Webkul\Marketplace\Model\Orders')
		->getCollection()
		->addFieldToFilter( 'main_table.seller_id',$sellerId );

		$orderModel ->getSelect()
		->columns('AVG(shipping_hour) as shipping_avg')
		->where("tracking_number is not null");

		$avgValue = $orderModel->getFirstItem();
		if($avgValue->getId()){
			if($avgValue->getShippingAvg() > 24){
				foreach ($collection as $shippingBadge) {
					$shippingBadge->delete();
				}
			}else{
				if($collection->getSize() == 0){
					$this->saveSellerBadge($badgeId, $sellerId, $seller_badge);
				}

			}
		}



		return true;

	}

	public function getEmailForEmailVerificationBadge($sellerId) {
		try {
			$response = [];

			$customer = $this->_customerObj->load($sellerId);
			$customerid = $customer->getId();
			$helper = $this->_marketplacehelper;

			$adminStoremail = $helper->getAdminEmailId();
			$adminEmail = $adminStoremail ? $adminStoremail : $helper->getDefaultTransEmailId();
			$adminUsername = 'Admin';
			$senderInfo = [
				'name' => $customer->getFirstName().' '.$customer->getLastName(),
				'email' => $customer->getEmail(),
			];
			$receiverInfo = [
				'name' => $adminUsername,
				'email' => $adminEmail,
			];

			$emailTemplateVariables['myvar3'] = $customer->getFirstName().' '.
			$customer->getLastName();
			$emailTemplateVariables['myvar2'] = $this->_objectManager->get('Magento\Backend\Model\Url')
			->getUrl(
				'customer/index/edit',
				['id' => $customer->getId()]
			);
			$emailTemplateVariables['myvar3'] = 'Admin';

			$custom_helper = $this->_customApiHelper;
			$emailTemplateVariables['myvar4'] = $custom_helper->getReactUrl()."?emailbadge=verify";
			$this->_objectManager->create(
				'Webkul\Marketplace\Helper\Email'
			)->sendNewSellerVerify(
				$emailTemplateVariables,
				$senderInfo,
				$receiverInfo
			);



			$response['status'] = "success";
			$response['message'] = "Badge Email Sent Successfully.";

			return $this->getResponseFormat($response);
		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}


	public function verifyEmailBadge($sellerId){
		try{
			$response = [];

			$badge = $this->getBadgeByName("Email Verified");
			if($badge->getId()){
				$seller_badge = $this->_objectManager->create('Webkul\MpSellerBadge\Model\Sellerbadge');

				$collection = $seller_badge->getCollection();
				$collection->addFieldToFilter('badge_id',array('eq' => $badge->getId()));
				$collection->addFieldToFilter('seller_id',array('eq' => $sellerId));

				if($collection->getSize() > 0){
					$response['status'] = "success";
					$response['message'] = "Email Badge Already Verified";
				}else{

					$this->saveSellerBadge($badge->getId(), $sellerId, $seller_badge);

					$response['status'] = "success";
					$response['message'] = "Email Badge Verified";



				}
			}else{
				$response['status'] = "error";
				$response['message'] = "Email Badge doesnot exist";
			}

			return $this->getResponseFormat($response);
		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}

	}


	public function getProfileStep1($sellerId){
		try{
			$response = [];
			$customerObj = $this->_customerObj->load($sellerId);
			$marketplaceHelper = $this->_marketplacehelper;
			$partner = $marketplaceHelper->getSellerDataBySellerId($sellerId)->getFirstItem();

			$partnerData = $partner->getData();
			if ($partner->getLogoPic()) {
				$logoPic = $marketplaceHelper->getMediaUrl().'avatar/'.$partner->getLogoPic();
			} else {
				$logoPic = $marketplaceHelper->getMediaUrl().'avatar/noimage.png';
			}
			$response['pet_free_home'] = $partnerData['pet_free_home'];
			$response['pet_type'] = $partnerData['pet_type'];
			$response['smoke_free_home'] = $partnerData['smoke_free_home'];
			$response['profilePic'] = $logoPic;
			$response['dob'] = $customerObj->getDob();
			$response['gender'] = $customerObj->getGender();

			return $this->getResponseFormat($response);
		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	public function updateProfileStep1($sellerId, $smokefree, $petfree,$pettype,$gender,$dob, $companyLogo,$imagename){
		try{
			$response = array();
			$fileSystem = $this->_fileSystem;
			$mediaDirectory = $fileSystem->getDirectoryWrite(
				DirectoryList::MEDIA
			);
			$marketplaceHelper = $this->_marketplacehelper;

			$value = $this->_sellerModel
			->getCollection()
			->addFieldToFilter('seller_id', $sellerId)->getFirstItem();
			$target = $mediaDirectory->getAbsolutePath('avatar/');

			$customerRepo = $this->_customerRepo;
			$customer = $customerRepo->getById($sellerId);

			if($companyLogo != ""){
				$fileName = $imagename;
				$file = $target . $fileName;
				$content = preg_replace('#data:image/[^;]+;base64,#', '', $companyLogo);
				$content = str_replace(' ', '+', $content);
				$data = base64_decode($content);

				$success = file_put_contents($file, $data);

				$value->setLogoPic($fileName);
			}

			$customer->setGender($gender);
			$customer->setDob($dob);


			$value->setSmokeFreeHome($smokefree);
			$value->setPetFreeHome($petfree);
			$value->setPetType($pettype);

			$value->save();
			$customerRepo->save($customer);

			$responseArray['status'] = "Success";
			$responseArray['message'] = __('Profile Step 1 Updated Successfully.');
			return $this->getResponseFormat($responseArray);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}

	/*Get Profile Step Status*/
	public function profileStatus($sellerId){
		try{
			$response = array(
				"step1" => 0,
				"step2" => 0,
				"step3" => 0,
				"step4" => 0,
			);
			$marketplaceHelper = $this->_marketplacehelper;

			$partner = $this->_sellerModel
			->getCollection()
			->addFieldToFilter('seller_id', $sellerId)
			->getFirstItem();
			$step1Status = 0;

			$customer = $this->_customerRepo->getById($sellerId);

			$billingAddressId = $customer->getDefaultBilling();
			$shippingAddressId = $customer->getDefaultShipping();
			$sellerCollection = $this->_stripeFactory->create()->addFieldToFilter(
				'seller_id',
				['eq' => $sellerId]
			)
			->addFieldToFilter(
				'email',
				['eq' => $customer->getEmail()]
			);
			if($partner->getId()){
				//$partner->getLogoPic() &&
				 //echo $customer->getGender();
				//die("hellddddo");

				if(!is_null($partner->getSmokeFreeHome())
					&& !is_null($partner->getPetFreeHome())
					&& $customer->getGender()
					&& $customer->getDob()
				)
				{
					$response['step1'] = 1;
					// if($partner->getPetFreeHome() && $partner->getPetType() != ""){
					// 	$response['step1'] = 1;
					// }elseif (!$partner->getPetFreeHome()) {
					// 	$response['step1'] = 1;
					// }else{
					// 	$response['step1'] = 0;
					// }

				}

			}
			if($sellerCollection->getSize() > 0){
				$response['step3'] = 1;
			}
			if($billingAddressId && $shippingAddressId){
				$response['step2'] = 1;
			}
			$response['step4'] = (int)$partner->getAcceptTerms();

			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}


	}

	/*Accept Profile Terms*/
	public function profileTerms($sellerId){
		$response = array();
		try{
			$partner = $this->_sellerModel
			->getCollection()
			->addFieldToFilter('seller_id', $sellerId)
			->getFirstItem();

			if($partner->getId()){
				$partner->setAcceptTerms(1);
				$partner->save();
				$response['status'] = "success";
				$response['message'] = "Terms and Conditions accepted successfully.";
			}else{
				$response['status'] = "error";
				$response['message'] = "Invalid Seller Id.";
			}
			return $this->getResponseFormat($response);
		}catch(\Exception $e){
			return $this->errorMessage($e);
		}
	}

	protected function saveSellerBadge($badgeId, $sellerId, $seller_badge){
		$datetime = $this->_dateTime;
		$time = $datetime->date();
		$data_time = $datetime->date('Y-m-d H:i:s', $time);
		$seller_badge->setBadgeId($badgeId);
		$seller_badge->setSellerId($sellerId);
		$seller_badge->setCreatedAt($data_time);
		$seller_badge->save();
	}

	protected function getBadgeByName($name){
		$_badge = $this->_objectManager->create('Webkul\MpSellerBadge\Model\Badge');
		$collection = $_badge->getCollection();
		$collection->addFieldToFilter('badge_name',array('eq' => $name));
		return $collection->getFirstItem();
	}


	public function errorMessage($e){
		$responseArray = array();
		$responseArray['status'] = __('Error');
		$responseArray['message'] = $e->getMessage();
		return $this->getResponseFormat($responseArray);

	}
}
