<?php

namespace Seasia\Customapi\Model;

use Seasia\Customapi\Api\ProductInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Webkul\Marketplace\Helper\Data as MarketplaceHelperData;
use Webkul\Marketplace\Model\Product as SellerProduct;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Seasia\Sellerdiscount\Model\ProductdiscountFactory;
use Seasia\Sellerdiscount\Model\DiscountFactory;


/**
 * Defines the implementaiton class of the calculator service contract.
 */
class Product implements ProductInterface
{
    /**
     * Return data.
     *
     * @api
     */
    protected $dataFactory;
    protected $_objectManager;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;



    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $_productResourceModel;

    /**
     * @var \Magento\Framework\App\Request\DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var MarketplaceHelperData
     */
    protected $_marketplaceHelperData;

    /**
     * @var eventManager
     */
    protected $_eventManager;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var CategoryLinkManagementInterface
     */
    protected $categoryLinkManagement;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date
     */
    protected $_dateFilter;

     /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
     protected $_date;

     /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
     protected $_mediaDirectory;

    /**
     * File Uploader factory.
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_fileUploaderFactory;

    protected $_modeldiscountFactory;

    protected $_discountFactory;

    protected $initialProductInfo = array();

    protected $ignoreAttributesArray = array('hidden_size_type', 'defaultImage', 'sold', 'allimagePath','discount_id');



    /**
     * @param Context          $context
     * @param FormKeyValidator $formKeyValidator
     * @param MarketplaceHelperData $marketplaceHelperData
     * @param Webkul\Marketplace\Controller\Product\Builder $productBuilder
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(\Magento\Framework\Event\Manager $eventManager,
      \Magento\Framework\Stdlib\DateTime\DateTime $date,
      \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
      \Magento\Catalog\Model\Product\TypeTransitionManager $catalogProductTypeManager,
      Context $context,
      Filesystem $filesystem,
      \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
      FormKeyValidator $formKeyValidator,
      \Magento\Catalog\Model\ResourceModel\Product $productResourceModel,
      \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
      MarketplaceHelperData $marketplaceHelperData,
      \Magento\Catalog\Model\ProductFactory $productFactory,
      \Magento\Framework\Registry $registry,
      ProductdiscountFactory $modelProductdiscountFactory,
      DiscountFactory $modelDiscountFactory,
      \Seasia\Customapi\Api\Data\ProductdataInterfaceFactory $dataFactory,
      \Webkul\Marketplace\Controller\Product\Builder $productBuilder
    )
    {

      $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
      $this->_eventManager = $eventManager;
      $this->_catalogProductTypeManager = $catalogProductTypeManager;
      $this->_date = $date;
      $this->_dateFilter = $dateFilter;
      $this->_formKeyValidator = $formKeyValidator;
      $this->_productResourceModel = $productResourceModel;
      $this->_productRepositoryInterface = $productRepositoryInterface;
      $this->_marketplaceHelperData = $marketplaceHelperData;
      $this->_productFactory = $productFactory;
      $this->_registry = $registry;
      $this->_modeldiscountFactory = $modelProductdiscountFactory;
      $this->_discountFactory = $modelDiscountFactory;
      $this->_mediaDirectory = $filesystem->getDirectoryWrite(
        DirectoryList::MEDIA
      );
      $this->_fileUploaderFactory = $fileUploaderFactory;
      $this->dataFactory = $dataFactory;
      $this->productBuilder = $productBuilder;
    }
    public function getAttributeArray(){
      $attributeArray = array();

      $attributeArray['Womens'] = array(
        'Accessories'=> 'accessory_type',
        'Bottoms' => 'bottom_type',
        'Costumes' => 'costume_type',
        'Dresses' => 'dress_type',
        'Leotard' => 'leotard_type',
        'Outfit' => 'outfit_type',
        'Outerwear' => 'outerwear',
        'Pajamas' => 'pajamas',
        'Tops' => 'top_type');
      $attributeArray['Mens'] = array(
        'Accessories'=> 'accessory_type',
        'Bottoms' => 'bottom_type',
        'Costumes' => 'costume_type',
        'Outfit' => 'outfit_type',
        'Outerwear' => 'outerwear',
        'Pajamas' => 'pajamas',
        'Tops' => 'top_type'
      );
      $attributeArray['Kids'] = array(
        'Accessories'=> 'accessory_type',
        'Bottoms' => 'bottom_type',
        'Costumes' => 'costume_type',
        'Dresses' => 'dress_type',
        'Leotard' => 'leotard_type',
        'Outfit' => 'outfit_type',
        'Outerwear' => 'outerwear',
        'Pajamas' => 'pajamas',
        'Tops' => 'top_type'
      );
      $attributeArray['Unisex'] = array(
        'Accessories'=> 'accessory_type',
        'Bottoms' => 'bottom_type',
        'Costumes' => 'costume_type',
        'Outfit' => 'outfit_type',
        'Outerwear' => 'outerwear',
        'Pajamas' => 'pajamas',
        'Tops' => 'top_type'
      );
      $attributeArray['Equiptment'] = array('Equipment' => 'accessory_type');

      return $attributeArray;
    }
    // Get Attribute Sets and its attribute type
    public function getAttributeSetDetails() {


      $genders = array('Womens','Mens','Kids','Unisex','Equiptment');

      $attributeArray = $this->getAttributeArray();

      $finalArray = array();


      foreach($genders as $gender){
        $finalArray[$gender] = array();
        foreach($attributeArray[$gender] as $attributeset => $type){
          $resArray = array();
          $collection = $this->_objectManager->create('Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection')->addFieldToFilter(
            'attribute_set_name',
            $attributeset
          )->getFirstItem();
          if($collection->getAttributeSetId()){
            $resArray['attributeSetId'] = $collection->getAttributeSetId();
            $resArray['attributeSetName'] = $attributeset;
            $resArray['type'] = $type;
          }
          array_push($finalArray[$gender], $resArray);


        }

      }

      return $this->getResponseFormat($finalArray);

    }



    // Get Size Attribute according to Gender
    public function getSizeDetails() {

      $attributeArray = array();
      //,'Unisex'
      $genders = array('Womens','Mens','Kids','Equiptment');
      foreach($genders as $eachGender){
        $attributeArray[$eachGender] = array();
      }
      //,'Age Worn'=> 'age'
      $attributeArray['Womens'] = array('Ladies Size' => 'ladies_size');
      $attributeArray['Mens'] = array('Mens Size'=> 'mens_size');
      $attributeArray['Kids'] = array('Girls Size' => 'girls_size','Boys Size' => 'boys_size');
      //$attributeArray['Unisex'] = array();
      $attributeArray['Equiptment'] = array('Kids Shoe Size'=> 'kids_show_size', 'Mens Shoe Size'=> 'mens_show_size', 'Womens Shoe Size'=> 'womens_show_size');

      $finalArray = array();
      $entityType = 'catalog_product';

      foreach($genders as $gender){
        $finalArray[$gender] = array();
        foreach($attributeArray[$gender] as $attributeset => $type){

          $attributeInfo = $this->_objectManager->get(\Magento\Eav\Model\Entity\Attribute::class)->loadByCode($entityType, $type);
          $resArray = array();
          $resArray['label'] = $attributeInfo->getFrontendLabel();
          $resArray['type'] = $type;
          array_push($finalArray[$gender], $resArray);
        }

      }


      return $this->getResponseFormat($finalArray);

    }


    // Upload Product Image

    public function uploadImage($content, $imageName){
      $helper = $this->_objectManager->create(
        'Webkul\Marketplace\Helper\Data'
      );

      try {
        $target = $this->_mediaDirectory->getAbsolutePath(
          $this->_objectManager->get(
            'Magento\Catalog\Model\Product\Media\Config'
          )->getBaseTmpMediaPath()
        )."/";
        $content = preg_replace('#data:image/[^;]+;base64,#', '', $content);
            //$content = str_replace('data:image/png;base64,', '', $content);
        $content = str_replace(' ', '+', $content);
        $data = base64_decode($content);
        $fileName = $imageName;
        $file = $target . $fileName;



        $success = file_put_contents($file, $data);


        $responseArray['status'] =  $success ? "Success" : 'Error';
        if($success){
          $path = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')
          ->getStore()
          ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
          $responseArray['imagePath'] = $file;
          $responseArray['imageUrl'] = $path."tmp/catalog/product/".$fileName;
        }else{
          $responseArray['imagePath'] = '';
          $responseArray['imageUrl'] = '';
        }




      } catch (\Exception $e) {
        $responseArray['status'] =   'Error';
        $responseArray['imagePath'] = '';
        $responseArray['imageUrl'] = '';
      }
      return $this->getResponseFormat($responseArray);

    }

    // Get Product

    public function getProduct($id){
      $msg = "";
      $result = "";
      $productId = $id;
      $productData = array();
      $responseArray = array();



      $responseArray = $this->getProductData($productId);


      return $this->getResponseFormat($responseArray);

    }

    protected function getProductData($productId){
      $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($productId);



      if ($productId && !$product->getId()) {
        $responseArray['response'] = "Error";
        $responseArray['message'] = "This product no longer exists.";

      }else{
        if ($productId) {
          $collectionFactory = $this->_objectManager->get('Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory'
          );

          $collection = $collectionFactory->create()->addFieldToFilter('mageproduct_id',$productId)
          ->addFieldToFilter(
            'seller_pending_notification',
            1
          );
          if ($collection->getSize()) {
            $type = \Webkul\Marketplace\Model\Notification::TYPE_PRODUCT;
            $this->_objectManager->get('Webkul\Marketplace\Helper\Notification')
            ->updateNotificationCollection(
              $collection,
              $type
            );
          }
        }

        $category_ids = "";
        $categories = $product->getCategoryIds();
        $categoryArray = array();
        if(count($categories) > 0){
          foreach($categories as $eachCategory){
            $catArray = array();
            $category = $this->_objectManager->create('Magento\Catalog\Model\Category')
            ->load($eachCategory);
            $catArray['category_id'] = $eachCategory;
            $category->getParentCategory()->getId();
            $catArray['category_name'] = $category->getName();
            $catArray['parent_cat_id'] = $category->getParentCategory()->getId();
            array_push($categoryArray, $catArray);
          }

        }


        $genders = array('Womens','Mens','Kids','Unisex','Equiptment');

        $attributeArray = $this->getAttributeArray();


        $sellerProductColls = $this->_objectManager->create(
          'Webkul\Marketplace\Model\Product'
        )
        ->getCollection()
        ->addFieldToFilter(
          'mageproduct_id',
          $productId
        )->getFirstItem();

        $stockItem = $product->getExtensionAttributes()->getStockItem();


        $store = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
        $responseArray['type'] = "simple";
        $responseArray['set'] = $product->getAttributeSetId();
        $responseArray['sellerId'] = $sellerProductColls->getSellerId();
        $productData['id'] = $product->getId();
        $productData['gender'] = $product->getGender();
        $productData['name'] = $product->getName();
        $productData['description'] = $product->getDescription();
        $productData['category_ids'] = $categoryArray;
        $productData['price'] = $product->getPriceInfo()->getPrice('regular_price')->getValue();
        $productData['cost'] = $product->getCost();
        $productData['tax_class_id'] = $product->getTaxClassId();
        $productData['product_has_weight'] = $product->getProductHasWeight();
        $productData['weight'] = $product->getWeight();
        $productData['color'] = $product->getColor();
        $productData['all_colors'] = $product->getAllColors();
        $productData['material'] = $product->getMaterial();
        $productData['age'] = $product->getAge();
        $productData['ladies_size'] = $product->getLadiesSize();
        $productData['girls_size'] = $product->getGirlsSize();
        $productData['mens_size'] = $product->getMensSize();
        $productData['boys_size'] = $product->getBoysSize();
        $productData['condition'] = $product->getCondition();
        $productData['waist'] = $product->getWaist();
        $productData['bust'] = $product->getBust();
        $productData['hips'] = $product->getHips();
        $productData['girth'] = $product->getGirth();
        $productData['skirt_length'] = $product->getSkirtLength();
        $productData['sports_brand'] = $product->getSportsBrand();
        $productData['frock_gender'] = $product->getFrockGender();
        $productData['frock_size_type'] = $product->getFrockSizeType();
        $productData['hidden_size_type'] = $product->getHiddenSizeType();
        $productData['discount'] = $product->getProductDiscount();
        $productData['draft'] = $product->getDraft();
        $productData['has_size'] = $product->getHasSize();
        $productData['defaultImage'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
        $productData['girls_size'] = $product->getGirlsSize();
        $productData['color_type'] = $product->getColorType();
        $productData['color_type_subcolor'] = $product->getColorTypeSubcolor();
        $productData['has_color'] = empty($productData['color_type_subcolor'])?true:false;
        $productData['custom_size'] = $product->getCustomSize();



        $productData['sold'] = $stockItem->getQty() > 0 ? "0":"1";



        foreach($genders as $gender){
          foreach($attributeArray[$gender] as $attributeset => $type){
            $productData[$type] = $product->getData($type);
          }

        }
        $stockItem = $product->getExtensionAttributes()->getStockItem();
        $productData['qty'] = $stockItem->getQty();
        $productData['status'] = $product->getStatus();

        $discountCollection = $this->getDiscountCollection($productId);
        $productData['discount_id'] = "";
        if(count($discountCollection) > 0){
          $discount = $discountCollection->getFirstItem();
          $productData['discount_id'] = $discount->getDiscountId();
        }


        $target = $this->_mediaDirectory->getAbsolutePath(
          $this->_objectManager->get(
            'Magento\Catalog\Model\Product\Media\Config'
          )->getBaseMediaPath()
        )."/";
        $target = rtrim($target,'/');

        $path = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')
        ->getStore()
        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        $path = $path."catalog/product";
        $mediaFiles = array();
        $mediaGallery = $product->getMediaGalleryEntries();
        $i = 0;
        if(count($mediaGallery) > 0){
          foreach($mediaGallery as $image){

            $mediaFiles[$i]['imagePath'] = $target.$image->getFile();
            $mediaFiles[$i]['imageUrl'] = $path.$image->getFile();
            $mediaFiles[$i]['imageLabel'] = $image->getLabel();
            $i++;
          }
        }



        $productData['ship_immediately'] = $product->getShipImmediately();
        $productData['ship_after_date'] = $product->getShipAfterDate();
        $productData['available_for_sell'] = $stockItem->getIsInStock()?"1":"0";
        $productData['product_tags'] = $product->getProductTags();
        $productData['created_at'] = $product->getCreatedAt();
        $productData['updated_at'] = $product->getUpdatedAt();
        $productData['allimagePath'] = $mediaFiles;
        $responseArray['product'] = $productData;

        return $responseArray;
      }
    }

    // Delete Product

    public function deleteProduct($id, $sellerId){
      $helper = $this->_objectManager->create(
        'Webkul\Marketplace\Helper\Data'
      );
      try {
        $wholedata = array();
        $wholedata['id'] = $id;
        $this->_eventManager->dispatch(
          'mp_delete_product',
          [$wholedata]
        );

        $deleteFlag = 0;

        $sellerCollectionFactory = $this->_objectManager->get(
          'Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory'
        );

        $sellerProducts = $sellerCollectionFactory->create()->addFieldToFilter(
          'mageproduct_id',
          $wholedata['id']
        )->addFieldToFilter(
          'seller_id',
          $sellerId
        );
        foreach ($sellerProducts as $sellerProduct) {
          $deletedProductId = $sellerProduct['mageproduct_id'];
          $sellerProduct->delete();
        }

        $collectionFactory = $this->_objectManager->get(
          'Magento\Catalog\Model\ResourceModel\Product\CollectionFactory'
        );

        $mageProducts = $collectionFactory->create()
        ->addFieldToFilter(
          'entity_id',
          $deletedProductId
        );
        foreach ($mageProducts as $mageProduct) {
          $mageProduct->delete();
          $deleteFlag = 1;
        }

        if ($deleteFlag) {
          $responseArray['status'] = "Success";
          $responseArray['message'] = __('Product has been successfully deleted from your account.');

        } else {
          $responseArray['status'] = "Error";
          $responseArray['message'] = __('You are not authorize to delete this product.');

        }


      }catch(\Exception $e){
        $responseArray['status'] = "Error";
        $responseArray['message'] = $e->getMessage();
      }

      return $this->getResponseFormat($responseArray);

    }

    // Create Product

    public function createProduct($sellerId, $type, $set, $product){


      $helper = $this->_objectManager->create('Webkul\Marketplace\Helper\Data');      

      $wholedata = array();
      $wholedata['type'] = $type;
      $wholedata['set'] = $set;
      $wholedata['product'] = $product;

      $skuType = $helper->getSkuType();
      $skuPrefix = $helper->getSkuPrefix();
      if ($skuType == 'dynamic') {
        $sku = $skuPrefix.$wholedata['product']['name'];
        $wholedata['product']['sku'] = $this->checkSkuExist($sku);
      }



      $this->initialProductInfo = $this->getProductData($product['id']);
      $_loadProduct = $this->initialProductInfo['product'];
      $categoriesArray = array();
      foreach($_loadProduct['category_ids'] as $eachCategory){
        array_push($categoriesArray, $eachCategory['category_id']);
      }
      unset($_loadProduct['category_ids']);
      $_loadProduct['category_ids'] = implode(",", $categoriesArray);

      $imagesArray = array();
      foreach($_loadProduct['allimagePath'] as $eachImage){
        array_push($imagesArray, $eachImage['imagePath']);
      }
      unset($_loadProduct['allimagePath']);




      $_loadProduct['allimagePath'] = implode(",", $imagesArray);
      foreach ($this->ignoreAttributesArray as $value) {
        unset($_loadProduct[$value]);

      }
            //echo "<pre>"; print_r($_loadProduct);
            //echo "<pre>"; print_r(array_diff_assoc($_loadProduct, $product));
            //die("SSSSSSS");


      $wholedata['product']['category_ids'] = isset($wholedata['product']['category_ids'])?explode(",",$wholedata['product']['category_ids']):"";
      $wholedata['product']['all_colors'] = isset($wholedata['product']['all_colors'])?explode(",",$wholedata['product']['all_colors']):"";
      $wholedata['product']['material'] = isset($wholedata['product']['material'])?explode(",",$wholedata['product']['material']):"";
      $wholedata['product']['ladies_size'] = isset($wholedata['product']['ladies_size'])?explode(",",$wholedata['product']['ladies_size']):"";
      $wholedata['product']['girls_size'] = isset($wholedata['product']['girls_size'])?explode(",",$wholedata['product']['girls_size']):"";
      $wholedata['product']['mens_size'] = isset($wholedata['product']['mens_size'])?explode(",",$wholedata['product']['mens_size']):"";
      

      $wholedata['product']['boys_size'] = isset($wholedata['product']['boys_size'])?explode(",",$wholedata['product']['boys_size']):"";
      $wholedata['product']['color_type'] = isset($wholedata['product']['color_type'])?explode(",",$wholedata['product']['color_type']):"";

      $wholedata['product']['color_type_subcolor'] = isset($wholedata['product']['color_type_subcolor'])?explode(",",$wholedata['product']['color_type_subcolor']):"";

        // Set Manage Stock
      $wholedata['product']['stock_data'] = array();
      $wholedata['product']['stock_data']['manage_stock'] = 1;
      $wholedata['product']['stock_data']['use_config_manage_stock'] = 1;

      $wholedata['product']['quantity_and_stock_status'] = array();
      $wholedata['product']['quantity_and_stock_status']['qty'] = $wholedata['product']['qty'];

      $wholedata['product']['quantity_and_stock_status']['is_in_stock'] = $wholedata['product']['available_for_sell'] == "1" ? 1:0;
      $wholedata['product']['available_for_sell'] == "1" && $wholedata['product']['draft'] == "0" ? 1:0;

      $wholedata['product']['meta_title'] = $wholedata['product']['name']?$wholedata['product']['name']:"";

      $wholedata['product']['custom_size'] = $wholedata['product']['custom_size']?$wholedata['product']['custom_size']:"";

      $defaultImageUpdate = $wholedata['product']['defaultImage']?$wholedata['product']['defaultImage']:"";

      $wholedata['product']['age'] = isset($wholedata['product']['age'])?$wholedata['product']['age']:"";
      
      $returnArr = $this->saveProductData(
        $sellerId,
        $wholedata
      );
      $productId = $returnArr['product_id'];
      $responseMessage = "";
      $responseArray = array();
      if ($productId != '') {

                //$helper->clearCache();
        if (empty($errors)) {
          if(isset($wholedata['product']['id'])){
            $responseArray['message'] = 'Your product has been successfully updated.';
          }else{
            $responseArray['message'] = 'Your product has been successfully saved.';
          }
          $responseArray['status'] = "Success";
          $responseArray['product_id'] = $productId;

          $this->getDataPersistor()->clear('seller_catalog_product');
        }
        //Go back to the frocking list when we are done saving
      } else {

        if (isset($returnArr['error']) && isset($returnArr['message'])) {
          if ($returnArr['error'] && $returnArr['message'] != '') {
            $responseArray['status'] = "Error";
            $responseArray['message'] = $returnArr['message'];
          }
        }
        $this->getDataPersistor()->set('seller_catalog_product', $wholedata);

      }

      $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($productId);

        $images = $product->getMediaGalleryImages();
        $image = $images->getItems();
        $imgArr = explode('/',$defaultImageUpdate);
        $defautIMG = end($imgArr);
    
        foreach ($image as $key => $value) {
       
          $arrImg = explode('/',$value->toArray()['file']);
          $defaultArrImg = end($arrImg);
          if($defautIMG == $defaultArrImg) {           
            $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $tableName = $resource->getTableName('catalog_product_entity_varchar');
            $sql = "UPDATE ".$tableName." SET value = '".$value->toArray()['file']."' WHERE attribute_id IN(84,85,86) AND entity_id = " . $productId;
            $connection->query($sql);
            break;
          }           
       }
       
      return $this->getResponseFormat($responseArray);


    }


    private function checkSkuExist($sku)
    {
      $sku = str_replace(" ", "-", mb_strtolower(substr($sku, 0, 50)))."-".time();
      try {
        $id = $this->_productResourceModel->getIdBySku($sku);
        if ($id) {
          $avialability = 0;
        } else {
          $avialability = 1;
        }
      } catch (\Exception $e) {
        $avialability = 0;
      }
      if ($avialability == 0) {
        $sku = substr($sku, 0, 45)."-".rand(10000,99999);
        $sku = $this->checkSkuExist($sku);
      }
      return $sku;
    }

    /**
     * Retrieve data persistor
     *
     * @return \Magento\Framework\App\Request\DataPersistorInterface|mixed
     */
    protected function getDataPersistor()
    {
      if (null === $this->dataPersistor) {
        $this->dataPersistor = $this->_objectManager->get(
          \Magento\Framework\App\Request\DataPersistorInterface::class
        );
      }

      return $this->dataPersistor;
    }

    protected function saveProductData($sellerId, $wholedata){
      $returnArr = [];
      $returnArr['error'] = 0;
      $returnArr['product_id'] = '';
      $returnArr['message'] = '';
      $wholedata['new-variations-attribute-set-id'] = $wholedata['set'];
      $wholedata['product']['attribute_set_id'] = $wholedata['set'];

      $helper = $this->_marketplaceHelperData;

      $this->_registry->register('mp_flat_catalog_flag', 1);

      if (!empty($wholedata['product']['id'])) {
        $mageProductId = $wholedata['product']['id'];
        $editFlag = 1;
        $storeId = $helper->getCurrentStoreId();
        if (!$helper->getCustomerSharePerWebsite()) {
          $savedWebsiteIds = $this->_productRepositoryInterface
          ->getById(
            $mageProductId
          )->getWebsiteIds();
          foreach ($wholedata['product']['website_ids'] as $websiteId) {
            if (in_array($websiteId, $savedWebsiteIds)) {
              $wholedata['product']['website_ids'] = $savedWebsiteIds;
            } else {
              array_push($savedWebsiteIds, $websiteId);
            }
          }
          $wholedata['product']['website_ids'] = $savedWebsiteIds;
        }
        $this->_eventManager->dispatch(
          'mp_customattribute_deletetierpricedata',
          [$wholedata]
        );



      }else {
        $mageProductId = '';
        $editFlag = 0;
        $storeId = 0;
        $wholedata['product']['website_ids'][] = $helper->getWebsiteId();
        $wholedata['product']['url_key'] = $wholedata['product']['sku'];

      }
      if(!isset($wholedata['product']['website_ids']))
        $wholedata['product']['website_ids'][] = $helper->getWebsiteId();

      if ($mageProductId) {
        $status1 = $helper->getIsProductEditApproval() ?
        SellerProduct::STATUS_DISABLED : SellerProduct::STATUS_ENABLED;
        if ($helper->getIsProductApproval() && !$helper->getIsProductEditApproval()) {
          $sellerProductColls = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Product'
          )
          ->getCollection()
          ->addFieldToFilter(
            'mageproduct_id',
            $mageProductId
          )->addFieldToFilter(
            'seller_id',
            $sellerId
          );
          foreach ($sellerProductColls as $sellerProductColl) {
            $status1 = !$sellerProductColl->getIsApproved() ?
            SellerProduct::STATUS_DISABLED : SellerProduct::STATUS_ENABLED;
          }
        }
      } else {
        $status1 = $helper->getIsProductApproval() ?
        SellerProduct::STATUS_DISABLED : SellerProduct::STATUS_ENABLED;
      }

      $status = isset($wholedata['product']['status'])?$wholedata['product']['status']:$status1;

      $this->_eventManager->dispatch(
        'mp_product_save_before',
        [$wholedata]
      );

      $catalogProductTypeId = $wholedata['type'];




            /*
        * Product Initialize method to set product data
        */
            $catalogProduct = $this->productInitialize(
              $this->build($wholedata, $storeId, $sellerId),
              $wholedata
            );
            $this->_catalogProductTypeManager->processProduct($catalogProduct);

            $set = $catalogProduct->getAttributeSetId();

            $type = $catalogProduct->getTypeId();


            if (isset($set) && isset($type)) {
              $allowedsets = explode(',', $helper->getAllowedAttributesetIds());
              $allowedtypes = explode(',', $helper->getAllowedProductType());
              if (!in_array($type, $allowedtypes) || !in_array($set, $allowedsets)) {
                $returnArr['error'] = 1;
                $returnArr['message'] = __('Product Type Invalid Or Not Allowed');

                return $returnArr;
              }

            } else {
              $returnArr['error'] = 1;
              $returnArr['message'] = __('Product Type Invalid Or Not Allowed');

              return $returnArr;
            }

            if ($catalogProduct->getSpecialPrice() == '') {
              $catalogProduct->setSpecialPrice(null);
              $catalogProduct->getResource()->saveAttribute($catalogProduct, 'special_price');
            }

            $originalSku = $catalogProduct->getSku();
            $catalogProduct->setShipAfterDate($wholedata['product']['ship_after_date']);


            $discountPercent = $wholedata['product']['discount'];
            $specialPrice = $wholedata['product']['price'] - ($wholedata['product']['price'] * $discountPercent/100);
            $catalogProduct->setSpecialPrice($specialPrice);
            $catalogProduct->setProductDiscount($discountPercent);
            $catalogProduct->setStatus($status)->save();
            $mageProductId = $catalogProduct->getId();
            $discountId = $wholedata['product']['discount_id'];
            if($discountId != ""){
                //$this->setProductDiscount($catalogProduct,$mageProductId, $sellerId, $discountId);
            }



            $this->getCategoryLinkManagement()->assignProductToCategories(
              $catalogProduct->getSku(),
              $catalogProduct->getCategoryIds()
            );



            $wholedata['id'] = $mageProductId;
            $this->_eventManager->dispatch(
              'mp_customoption_setdata',
              [$wholedata]
            );

            $associatedProductIds = [];
            $this->saveMaketplaceProductTable(
              $mageProductId,
              $sellerId,
              $status,
              $editFlag,
              $associatedProductIds
            );
            $this->_eventManager->dispatch(
              'mp_customattribute_settierpricedata',
              [$wholedata]
            );

            $this->_eventManager->dispatch(
              'mp_product_save_after',
              [$wholedata]
            );

            //$this->sendProductMail($wholedata, $sellerId, $editFlag);

            $returnArr['product_id'] = $mageProductId;
            return $returnArr;
          }
        /**
     * @param string $sellerId
     * @param string   $productId
     */
        protected function setProductDiscount($product,$productId, $sellerId, $discountId){
          $discountModel = $this->_modeldiscountFactory->create();
          $discountCollection = $this->getDiscountCollection($productId);
          if(count($discountCollection) > 0){
            $discount = $discountCollection->getFirstItem();
            $discount->setDiscountId($discountId);
            $discount->save();
          }else{
            $discountModel->setSellerId($sellerId);
            $discountModel->setProductId($productId);
            $discountModel->setDiscountId($discountId);
            $discountModel->setCreatedAt(date("Y-m-d H:i:s"));
            $discountModel->save();
          }

          $discountColl = $this->_discountFactory->create();

          $discountMainCollection = $discountColl->getCollection()->addFieldToFilter('status', array('eq' => '1'))->addFieldToFilter('id', array('eq' => $discountId));
          if(count($discountMainCollection) > 0){
            $mainDiscount = $discountMainCollection->getFirstItem();
            $price = $product->getPrice();
            $discountPercent = $mainDiscount->getDiscountPercent();
            $specialPrice = $price - ($price * $discountPercent/100);
            $product->setSpecialPrice($specialPrice);
            $product->save();

          }


        }

        protected function getDiscountCollection($productId){
          $discountModel = $this->_modeldiscountFactory->create();

          $discountCollection = $discountModel->getCollection()->addFieldToFilter('product_id', array('eq' => $productId));
          return $discountCollection;
        }

        /**
     * @param array  $data
     * @param string $sellerId
     * @param bool   $editFlag
     */
        private function sendProductMail($data, $sellerId, $editFlag = null)
        {
          $helper = $this->_marketplaceHelperData;

          $customer = $this->_objectManager->get(
            'Magento\Customer\Model\Customer'
          )->load($sellerId);

          $sellerName = $customer->getFirstname().' '.$customer->getLastname();
          $sellerEmail = $customer->getEmail();

          if (isset($data['product']) && !empty($data['product']['category_ids'])) {
            $categoriesy = $this->_objectManager->get(
              'Magento\Catalog\Model\Category'
            )->load(
              $data['product']['category_ids'][0]
            );
            $categoryname = $categoriesy->getName();
          } else {
            $categoryname = '';
          }

          $emailTempVariables = [];
          $adminStoremail = $helper->getAdminEmailId();
          $adminEmail = $adminStoremail ?
          $adminStoremail : $helper->getDefaultTransEmailId();
          $adminUsername = 'Admin';

          $emailTempVariables['myvar1'] = $data['product']['name'];
          $emailTempVariables['myvar2'] = $categoryname;
          $emailTempVariables['myvar3'] = $adminUsername;
          if ($editFlag == null) {
            $emailTempVariables['myvar4'] = __(
              'I would like to inform you that recently I have added a new product in the store.'
            );
          } else {
            $emailTempVariables['myvar4'] = __(
              'I would like to inform you that recently I have updated a  product in the store.'
            );
          }
                //'email' => $sellerEmail,
          $senderInfo = [
            'name' => $sellerName,
            'email' => $sellerEmail,
          ];

                //'email' => $adminEmail,
          $receiverInfo = [
            'name' => $adminUsername,
            'email' => $adminEmail,
          ];
          if (($editFlag == null && $helper->getIsProductApproval() == 1)
            || ($editFlag && $helper->getIsProductEditApproval() == 1)) {
            $this->_objectManager->create(
              'Webkul\Marketplace\Helper\Email'
            )->sendNewProductMail(
              $emailTempVariables,
              $senderInfo,
              $receiverInfo,
              $editFlag
            );
          }
        }

        /**
     * Set Product Records in marketplace_product table.
     *
     * @param int $mageProductId
     * @param int $sellerId
     * @param int $status
     * @param int $editFlag
     * @param array $associatedProductIds
     */
        private function saveMaketplaceProductTable(
          $mageProductId,
          $sellerId,
          $status,
          $editFlag,
          $associatedProductIds
        ) {
          $savedIsApproved = 0;
          $sellerProductId = 0;
          $helper = $this->_marketplaceHelperData;
          if ($mageProductId) {
            $sellerProductColls = $this->_objectManager->create(
              'Webkul\Marketplace\Model\Product'
            )
            ->getCollection()
            ->addFieldToFilter(
              'mageproduct_id',
              $mageProductId
            )->addFieldToFilter(
              'seller_id',
              $sellerId
            );
            foreach ($sellerProductColls as $sellerProductColl) {
              $sellerProductId = $sellerProductColl->getId();
              $savedIsApproved = $sellerProductColl->getIsApproved();
            }
            $collection1 = $this->_objectManager->create(
              'Webkul\Marketplace\Model\Product'
            )->load($sellerProductId);
            $collection1->setMageproductId($mageProductId);
            $collection1->setSellerId($sellerId);
            $collection1->setStatus($status);
            $isApproved = 1;
            if ($helper->getIsProductEditApproval()) {
              $collection1->setAdminPendingNotification(2);
            }
            if (!$editFlag) {
              $collection1->setCreatedAt($this->_date->gmtDate());
              if ($helper->getIsProductApproval()) {
                $isApproved = 0;
                $collection1->setAdminPendingNotification(1);
              }
            } else if (!$helper->getIsProductEditApproval()) {
              $isApproved = $savedIsApproved;
            } else {
              $isApproved = 0;
            }
            $collection1->setIsApproved($isApproved);
            $collection1->setUpdatedAt($this->_date->gmtDate());
            $collection1->save();
          }

          foreach ($associatedProductIds as $associatedProductId) {
            if ($associatedProductId) {
              $sellerAssociatedProductId = 0;
              $sellerProductColls = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Product'
              )
              ->getCollection()
              ->addFieldToFilter(
                'mageproduct_id',
                $associatedProductId
              )
              ->addFieldToFilter(
                'seller_id',
                $sellerId
              );
              foreach ($sellerProductColls as $sellerProductColl) {
                $sellerAssociatedProductId = $sellerProductColl->getId();
              }
              $collection1 = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Product'
              )
              ->load($sellerAssociatedProductId);
              $collection1->setMageproductId($associatedProductId);
              if (!$editFlag) {
                /* If new product is added*/
                $collection1->setStatus(SellerProduct::STATUS_ENABLED);
                $collection1->setCreatedAt($this->_date->gmtDate());
              }
              if ($editFlag) {
                $collection1->setAdminPendingNotification(2);
              }
              $collection1->setUpdatedAt($this->_date->gmtDate());
              $collection1->setSellerId($sellerId);
              $collection1->setIsApproved(1);
              $collection1->save();
            }
          }
        }

        /**
     * @return CategoryLinkManagementInterface
     */
        private function getCategoryLinkManagement()
        {
          if (null === $this->categoryLinkManagement) {
            $this->categoryLinkManagement = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(CategoryLinkManagementInterface::class);
          }
          return $this->categoryLinkManagement;
        }

        /**
     * Build product based on requestData.
     *
     * @param $requestData
     *
     * @return \Magento\Catalog\Model\Product $mageProduct
     */
        public function build($requestData, $store = 0, $sellerId=0)
        {
          if (!empty($requestData['product']['id'])) {
            $mageProductId = (int) $requestData['product']['id'];
          } else {
            $mageProductId = '';
          }
          /** @var $mageProduct \Magento\Catalog\Model\Product */
          $mageProduct = $this->_productFactory->create();
          if (!empty($requestData['set'])) {
            $mageProduct->setAttributeSetId($requestData['set']);
          }
          if (!empty($requestData['type'])) {
            $mageProduct->setTypeId($requestData['type']);
          }
          $mageProduct->setStoreId($store);
          if ($mageProductId) {
            try {
              $mageProduct->load($mageProductId);
            } catch (\Exception $e) {
                    //$this->_logger->critical($e);
            }
          }
          if (!$this->_registry->registry('product')) {
            $this->_registry->register('product', $mageProduct);
          }
          if (!$this->_registry->registry('current_product')) {
            $this->_registry->register('current_product', $mageProduct);
          }
          return $mageProduct;
        }


        /**
     * Product initialize function before saving.
     *
     * @param \Magento\Catalog\Model\Product $catalogProduct
     * @param $requestData
     *
     * @return \Magento\Catalog\Model\Product
     */
        private function productInitialize(\Magento\Catalog\Model\Product $catalogProduct, $requestData)
        {
          $helper = $this->_marketplaceHelperData;
          $requestProductData = $requestData['product'];
          unset($requestProductData['custom_attributes']);
          unset($requestProductData['extension_attributes']);

        /*
        * Manage seller product Stock data
        */
        $requestProductData = $this->manageSellerProductStock($requestProductData);

        $requestProductData = $this->normalizeProductData($requestProductData);

        if (!empty($requestProductData['is_downloadable'])) {
          $requestProductData['product_has_weight'] = 0;
        }

        $requestProductData = $this->manageProductCategoryWebsiteData($requestProductData);

        $wasLockedMedia = false;
        if ($catalogProduct->isLockedAttribute('media')) {
          $catalogProduct->unlockAttribute('media');
          $wasLockedMedia = true;
        }

        $requestProductData = $this->manageProductDateTimeFilter($catalogProduct, $requestProductData);

        if (isset($requestProductData['options'])) {
          $productOptions = $requestProductData['options'];
          unset($requestProductData['options']);
        } else {
          $productOptions = [];
        }

        $catalogProduct->addData($requestProductData);

        $allImages = explode(",",$requestData['product']['allimagePath']);

        $remainingArray = array();

        $target = $this->_mediaDirectory->getAbsolutePath(
          $this->_objectManager->get(
            'Magento\Catalog\Model\Product\Media\Config'
          )->getBaseMediaPath()
        )."/";
        $target = rtrim($target,'/');

        if(isset($requestData['product']['id']) && $requestData['product']['id'] != ""){
          $existingArray = array();
          $existingMediaGalleryEntries = $catalogProduct->getMediaGalleryEntries();

          if(count($existingMediaGalleryEntries) > 0){
            foreach ($existingMediaGalleryEntries as $key => $entry) {
              $imageFilepath = $target.$entry->getFile();
              if (in_array($imageFilepath, $allImages)) {
                array_push($existingArray, $imageFilepath);
              }else{
                unset($existingMediaGalleryEntries[$key]);

              }
            }
            $catalogProduct->setMediaGalleryEntries($existingMediaGalleryEntries);
            $this->_productRepositoryInterface->save($catalogProduct);
          }
          //$existingMediaGalleryEntries = $catalogProduct->getMediaGalleryEntries();
          if($requestData['product']['allimagePath'] != ""){
            foreach($allImages as $eachImage){
              if($eachImage != $requestData['product']['defaultImage'] && !in_array($eachImage, $existingArray)){
                $catalogProduct->addImageToMediaGallery($eachImage, array('image', 'small_image', 'thumbnail'), false, false);
              }
              if($eachImage == $requestData['product']['defaultImage'] && !in_array($eachImage, $existingArray)){
                $catalogProduct->addImageToMediaGallery($requestData['product']['defaultImage'], array('image', 'small_image', 'thumbnail'), false, false);
              }
            }
          }
         // $catalogProduct->setMediaGalleryEntries($existingMediaGalleryEntries);
          
          $catalogProduct->save();
         // $this->_productRepositoryInterface->save($catalogProduct);

          /*$existingMediaGalleryEntries = $catalogProduct->getMediaGalleryEntries();

            $imageLabels = explode(",",$requestData['product']['allImageLabels']);

            //mail("singhharpreet2@seasiainfotech.com","imageLabels", print_r($imageLabels,1));
            $i=0;

            echo '<pre>'; 
           
            print_r($existingMediaGalleryEntries);
            die('------');
            foreach ($existingMediaGalleryEntries as $key => $entry) {
              if(isset($imageLabels[$i]) && $imageLabels[$i] != "undefined"){
                $entry->setLabel($imageLabels[$i]);
              }
              $i++;
            }
            $catalogProduct->setMediaGalleryEntries($existingMediaGalleryEntries);

            $catalogProduct->save();*/

        }else{
          

          if($requestData['product']['allimagePath'] != ""){


            foreach($allImages as $eachImage){
              if($eachImage != $requestData['product']['defaultImage'] && $eachImage != ""){

                $catalogProduct->addImageToMediaGallery($eachImage, array('image', 'small_image', 'thumbnail'), false, false);


              }

            }
            if($requestData['product']['defaultImage']){
              $catalogProduct->addImageToMediaGallery($requestData['product']['defaultImage'], array('image', 'small_image', 'thumbnail'), false, false);
            }

            $catalogProduct->save();

            $existingMediaGalleryEntries = $catalogProduct->getMediaGalleryEntries();

            $imageLabels = explode(",",$requestData['product']['allImageLabels']);

            //mail("singhharpreet2@seasiainfotech.com","imageLabels", print_r($imageLabels,1));
            $i=0;
            foreach ($existingMediaGalleryEntries as $key => $entry) {
              if(isset($imageLabels[$i]) && $imageLabels[$i] != "undefined"){
                $entry->setLabel($imageLabels[$i]);
              }
              $i++;
            }
            $catalogProduct->setMediaGalleryEntries($existingMediaGalleryEntries);
          }
        }




        if ($wasLockedMedia) {
          $catalogProduct->lockAttribute('media');
        }

        if ($helper->getSingleStoreStatus()) {
          $catalogProduct->setWebsiteIds([$helper->getWebsiteId()]);
        }

        /*
         * Check for "Use Default Value" field value
         */
        $catalogProduct = $this->manageProductForDefaultAttribute($catalogProduct, $requestData);

        /*
         * Set Downloadable links if available
         */
        //$catalogProduct = $this->manageProductDownloadableData($catalogProduct, $requestData);

        /*
         * Set Product options to product if exist
         */
        $catalogProduct = $this->manageProductOptionData($catalogProduct, $productOptions);

        /*
         * Set Product Custom options status to product
         */
        if (empty($requestData['affect_product_custom_options'])) {
          $requestData['affect_product_custom_options'] = '';
        }

        $catalogProduct->setCanSaveCustomOptions(
          (bool) $requestData['affect_product_custom_options']
          && !$catalogProduct->getOptionsReadonly()
        );

        return $catalogProduct;
      }

    /**
     * @param Magento/Catalog/Model/Product $catalogProduct
     * @param $productOptions
     *
     * @return Magento/Catalog/Model/Product
     */
    public function manageProductOptionData($catalogProduct, $productOptions)
    {
      if ($productOptions && !$catalogProduct->getOptionsReadonly()) {
            // mark custom options that should to fall back to default value
        $options = $this->mergeProductOptions(
          $productOptions,
          []
        );
        $customOptions = [];
        foreach ($options as $customOptionData) {
          if (empty($customOptionData['is_delete'])) {
            if (empty($customOptionData['option_id'])) {
              $customOptionData['option_id'] = null;
            }
            if (isset($customOptionData['values'])) {
              $customOptionData['values'] = array_filter(
                $customOptionData['values'],
                function ($valueData) {
                  return empty($valueData['is_delete']);
                }
              );
            }
            $customOption = $this->_objectManager->get(
              'Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory'
            )->create(['data' => $customOptionData]);
            $customOption->setProductSku($catalogProduct->getSku());
            $customOptions[] = $customOption;
          }
        }
        $catalogProduct->setOptions($customOptions);
      }
      return $catalogProduct;
    }



    /**
     * @param Magento/Catalog/Model/Product $catalogProduct
     * @param array $requestData
     *
     * @return Magento/Catalog/Model/Product
     */
    private function manageProductForDefaultAttribute($catalogProduct, $requestData)
    {
      if (!empty($requestData['use_default'])) {
        foreach ($requestData['use_default'] as $attributeCode => $useDefaultState) {
          if ($useDefaultState) {
            $catalogProduct->setData($attributeCode, null);
            if ($catalogProduct->hasData('use_config_'.$attributeCode)) {
              $catalogProduct->setData('use_config_'.$attributeCode, false);
            }
          }
        }
      }
      return $catalogProduct;
    }

    /**
     * @param Magento/Catalog/Model/Product $catalogProduct
     * @param array $requestProductData
     *
     * @return array
     */
    private function manageProductDateTimeFilter($catalogProduct, $requestProductData)
    {
      $dateFieldFilters = [];
      $attributes = $catalogProduct->getAttributes();
      foreach ($attributes as $attrKey => $attribute) {
        if ($attribute->getBackend()->getType() == 'datetime') {
          if (array_key_exists($attrKey, $requestProductData) && $requestProductData[$attrKey]!='') {
            $dateFieldFilters[$attrKey] = $this->_dateFilter;
          }
        }
      }
      $inputFilter = new \Zend_Filter_Input(
        $dateFieldFilters,
        [],
        $requestProductData
      );
      $requestProductData = $inputFilter->getUnescaped();
      return $requestProductData;
    }

    /**
     * @param array $requestProductData
     *
     * @return array
     */
    private function manageProductCategoryWebsiteData($requestProductData)
    {
      foreach (['category_ids', 'website_ids'] as $field) {
        if (!isset($requestProductData[$field])) {
          $requestProductData[$field] = [
            0=>$this->_marketplaceHelperData->getRootCategoryIdByStoreId()
          ];
        }
      }
      foreach ($requestProductData['website_ids'] as $websiteId => $checkboxValue) {
        if (!$checkboxValue) {
          unset($requestProductData['website_ids'][$websiteId]);
        }
      }
      return $requestProductData;
    }



    /**
     * Internal normalization
     *
     * @param array $requestProductData
     *
     * @return array
     */
    private function normalizeProductData(array $requestProductData)
    {
      foreach ($requestProductData as $key => $value) {
        if (is_scalar($value)) {
          if ($value === 'true') {
            $requestProductData[$key] = '1';
          } elseif ($value === 'false') {
            $requestProductData[$key] = '0';
          }
        } elseif (is_array($value)) {
          $requestProductData[$key] = $this->normalizeProductData($value);
        }
      }

      return $requestProductData;
    }

    /**
     * @param array $requestProductData
     *
     * @return array
     */
    private function manageSellerProductStock($requestProductData)
    {
      if ($requestProductData) {
        if (isset($requestProductData['quantity_and_stock_status']['qty'])) {
          if ($requestProductData['quantity_and_stock_status']['qty'] < 0) {
            $requestProductData['quantity_and_stock_status']['qty'] = abs(
              $requestProductData['quantity_and_stock_status']['qty']
            );
            $requestProductData['stock_data']['qty'] = $requestProductData['quantity_and_stock_status']['qty'];
          }
        }
        $stockData = isset($requestProductData['stock_data']) ?
        $requestProductData['stock_data'] : [];
        if (isset($stockData['qty']) && (double) $stockData['qty'] > 99999999.9999) {
          $stockData['qty'] = 99999999.9999;
        }
        if (isset($stockData['min_qty']) && (int) $stockData['min_qty'] < 0) {
          $stockData['min_qty'] = 0;
        }
        if (!isset($stockData['use_config_manage_stock'])) {
          $stockData['use_config_manage_stock'] = 0;
        }
        if ($stockData['use_config_manage_stock'] == 1 && !isset($stockData['manage_stock'])) {
                //$stockData['manage_stock'] = $this->stockConfiguration
                //->getManageStock();
        }
        if (!isset($stockData['is_decimal_divided']) || $stockData['is_qty_decimal'] == 0) {
          $stockData['is_decimal_divided'] = 0;
        }
        $requestProductData['stock_data'] = $stockData;
      }
      return $requestProductData;
    }

    // Get Likes By Product Id

    public function getLikesByProductId($sellerId,$productId) {
      try  {
        $rmaHelper = $this->_objectManager->create('Webkul\MpRmaSystem\Helper\Data');
        $productSellerId = $rmaHelper->getSellerIdByproductid($productId);
        if($sellerId == $productSellerId) {
          $likesByProductId = $this->_objectManager->create('Seasia\Customapi\Helper\Data')->getLikesByProductId($productId);
          $response['likes'] = $likesByProductId;
        } else {
          $response['likes'] = "This product id does not related with seller id.";
        }
        return $this->getResponseFormat($response);
      } catch (\Exception $e) {
        return $this->errorMessage($e);
      }
    }



    // Return Response
    public function getResponseFormat($content){
      $page_object = $this->dataFactory->create();
      $page_object->setName($content);
      return $page_object;
    }

    public function errorMessage($e){
      $responseArray = array();
      $responseArray['status'] = __('Error');
      $responseArray['message'] = $e->getMessage();
      return $this->getResponseFormat($responseArray);
    }

  }
