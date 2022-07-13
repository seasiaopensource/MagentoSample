<?php
namespace Seasia\Customapi\Model;
use Seasia\Customapi\Api\FamilyInterface;


class Families implements FamilyInterface
{

	/**   
     * Return data.
     * @api
    */
	protected $dataFactory;
	protected $_objectManager; 
	
	public function __construct(\Seasia\Customapi\Api\Data\ProductdataInterfaceFactory $dataFactory) {

		$this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$this->dataFactory = $dataFactory;

	}     
	
	/* Add Edit Family Size */
	public function addeditsize($id,$sellerId,$first_name,$last_name, $relation,$gender,$age,$size_name,$size_value) {
		
		try {  
			$collection = $this->_objectManager->create('Seasia\Familysize\Model\Family');
			if(!empty($id)) {
				$collection = $collection->getCollection()->addFieldToFilter('id', array('eq' => $id));  
				if($collection->getSize() > 0) {
					$familySizeData = $collection->getFirstItem();    			
					$familySizeData->setSellerId($sellerId);
					$familySizeData->setFirstName($first_name);
					$familySizeData->setLastName($last_name);
					$familySizeData->setRelation($relation);
					$familySizeData->setGender($gender);
					$familySizeData->setAge($age);
					$familySizeData->setSizeName($size_name);
					$familySizeData->setSizeValue($size_value);
					$familySizeData->setUpdatedAt(date("Y-m-d H:i:s"));
					if($familySizeData->save()) {
						$response['status'] = "Success";
						$response['message'] = __('Family Size Updated Successfully.');  
					} else {
						$response['status'] = "Error";
						$response['message'] = "Error while updating response.";
					}
				} else {
					$response['status'] = "Error";
					$response['message'] = "Error while updating response.";
				}
			} else {     
				$collection->setFirstName($first_name);
				$collection->setLastName($last_name);
				$collection->setSellerId($sellerId);
				$collection->setRelation($relation);
				$collection->setGender($gender);
				$collection->setAge($age);
				$collection->setSizeName($size_name);
				$collection->setSizeValue($size_value);
				$collection->setCreatedAt(date("Y-m-d H:i:s"));
				if($collection->save()) {
					$response['status'] = "Success";
					$response['message'] = __('Family Size Saved Successfully.');
				} else {
					$response['status'] = "Error";
					$response['message'] = "Error while saving response.";
				}
			}   
			return $this->getResponseFormat($response);
		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}  
	
	/* Add Delete Family Size */
	public function deletesize($id,$sellerId) {      
		try {    
			$collection = $this->_objectManager->create('Seasia\Familysize\Model\Family');
			if(!empty($id)) {
				$collection = $collection->getCollection()->addFieldToFilter('id', array('eq' => $id))->addFieldToFilter('seller_id', array('eq' => $sellerId)); 
				if($collection->getSize() > 0) {
					$familySizeData = $collection->getFirstItem();
					if($familySizeData->delete()) {
						$response['status'] = "Success";
						$response['message'] = __('Address Deleted Successfully.');
					} else {
						$response['status'] = "Error";
						$response['message'] = "Error while deleting response.";
					}
				} else {
					$response['status'] = "Error";
					$response['message'] = "Error while deleting response.";
				}
			} else {
				$response['status'] = "Error";
				$response['message'] = "Error while deleting response.";
			}
			return $this->getResponseFormat($response);
		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	/* Get Seller Single Family Size by id */
	
	public function familysizebyid($id) {
		try {    
			$collection = $this->_objectManager->create('Seasia\Familysize\Model\Family');
			
			$collection = $collection->getCollection()->addFieldToFilter('id', array('eq' => $id)); 
			$familySizeData = array();
			
			if($collection->getSize() > 0){
				$familySize = $collection->getFirstItem();
				$familySizeData = $familySize->getData();
			}
			$response['status'] = "Success";
			$response['familysizedata'] = $familySizeData;
			
			return $this->getResponseFormat($response);
		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	/* Get Seller All Family Size by sellerId */
	
	public function familysize($sellerId,$pageNum, $length, $orderBy, $orderDir,$searchStr) {       
		try {       
			$collection = $this->_objectManager->create('Seasia\Familysize\Model\Family');
			
			$collection = $collection->getCollection()->addFieldToFilter('seller_id', array('eq' => $sellerId)); 
			
			if($searchStr != ""){
				$collection->getSelect()->where(
					'first_name like "%'.$searchStr.'%" OR last_name like "%'.$searchStr.'%"'
					);
			}

			$collection->setPageSize($length)->setCurPage($pageNum);
			$collection->setOrder($orderBy, $orderDir);
			$totalCount = $collection->getSize();
			$response['familysizedata'] = $collection->getData();
			$response['totalCount'] = $totalCount; 
			$response['status'] = "Success";
			
			return $this->getResponseFormat($response);
		} catch(\Exception $e) {
			return $this->errorMessage($e);
		}
	}

	public function sizebygender(){
		try{
			$response = array();
			$response['male'] = array('boys_size' => 'Boys Size','mens_size' => 'Mens Size');
			$response['female'] = array('girls_size' => 'Girls Size','ladies_size' => 'Ladies Size');
			return $this->getResponseFormat($response);
		} catch(\Exception $e) {
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