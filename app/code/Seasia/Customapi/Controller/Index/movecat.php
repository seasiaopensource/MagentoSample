<?php

namespace Seasia\Customapi\Controller\Index;

class Movecat extends \Magento\Framework\App\Action\Action
{
	public function execute()
	{
		try {
			
			$objectManager =  \Magento\Framework\App\ObjectManager::getInstance();


			 $model = $objectManager->create('\Webkul\MarketplaceUspsEndicia\Model\EndiciaManagement');
			 $model->getConfigData('requester_id');
			 $result =   $model->_createSoapClientcopy();

			die("helll00000000000");

			// $param = [
			// "COMMAND" => "ZIP1"
			// "SERIALNO" => "2547135",
			// "PASSWORD" => "cwH6PQzh",
			// "USER" => "2547135",
			// "ADDRESS0" => "",
			// "ADDRESS1" => "DYMO ENDICIA",
			// "ADDRESS2" => "385 SHERMAN AVENUE STE 1",
			// "ADDRESS3" => "PALO ALTO, CA 94306"
			// ];

			// $response = $result->StatusRequest($param);

			//echo "<pre>";
			//print_r($response);
		} catch(\Exception $e) {
			echo $e->getMessage();
		}
	}
}