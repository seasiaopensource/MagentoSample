<?php
use SimpleExcel\SimpleExcel;
require_once('src/SimpleExcel/SimpleExcel.php');

/*
This Class fetches all Customers 
from Magento API and export them in Excel Sheet
*/

class customerDataToExcel {

    protected $header = [];
    protected $gender = ["0"=>"","1"=>"Male","2"=>"Female"];
    protected $dataArray = [];
    protected $apiData;
    protected $addressArray = [];
    protected $customerAddress;
    protected $accessToken = '2uhhjg3zd1cksu6bx7vnwo9j99vbnkcx';
    protected $apiAccessUrl = 'http://test.app-room.ch:8080/magento/index.php/rest/V1/';

    public function __construct() {
        $this->getApiData();
    }

        // Set Header Element
    protected function setHeaderElement() {
        $this->dataArray[0] = array('ID',  'Firstname',   'Lastname', 'Email',  'Date Of Birth',   'Website Id' , 'Store Id', 'Created In','Group Id', 'Gender','Address');
    }

        // Set Curl Header Element
    protected function setCurlHeaderElement() {
            // set header for cur
        $this->header[] = 'Content-type: application/json';
        $this->header[] = 'Authorization: Bearer '.$this->accessToken;
    }

        // get api data
    protected function getApiData() {

            $this->setCurlHeaderElement(); // set header element

            $curl_handle = curl_init();
            
            /*  
            API URL for Getting all Customers 
            order by Entity Id in Ascending Order
            */
            $customerSearchUrl = $this->apiAccessUrl."customers/search?searchCriteria[sortOrders][0][field]=entity_id&searchCriteria[sortOrders][0][direction]=asc";

            curl_setopt($curl_handle,CURLOPT_URL,$customerSearchUrl);
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER,$this->header);
            curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($curl_handle, CURLOPT_POST,false);
            
            $response = curl_exec($curl_handle);
            curl_close($curl_handle);
            
            if (empty($response)){
                print_r('Curl error: ' . curl_error($response));
            } else {
                $this->apiData = json_decode($response);
            }
        }  

        /*
        Get Address String from Address Array of Customer
        */
        protected function getAddress($data = null) {
            if(!empty($data)){  
                foreach($data as $key => $value) {
                    $this->addressArray[$key] = $value->firstname.' '.$value->lastname.', '.implode (",",$value->street).', '.$value->city.', '.$value->region->region.' '.$value->postcode;
                    
                }
                return  count($this->addressArray) > 1 ? implode('\r', $this->addressArray) : $this->addressArray[0];
            } else {
                return '';
            } 
        }

        /*
        Get Customer Data from Customer API Response
        */
        protected function extractApiData() {

            foreach($this->apiData->items as $key => $value) { 
                $key = $key + 1;
                $this->dataArray[$key]['id']          =   $value->id;
                $this->dataArray[$key]['firstname']   =   $value->firstname;
                $this->dataArray[$key]['lastname']    =   $value->lastname;
                $this->dataArray[$key]['email']       =   $value->email;
                $this->dataArray[$key]['dob']         =   isset($value->dob) ? $value->dob : '';
                $this->dataArray[$key]['website_id']  =   $value->website_id;
                $this->dataArray[$key]['store_id']    =   $value->store_id;
                $this->dataArray[$key]['created_in']  =   $value->created_in;  
                $this->dataArray[$key]['group_id']    =   $value->group_id;  
                $this->dataArray[$key]['gender']      =   $this->gender[$value->gender];
                $this->dataArray[$key]['address']     =   $this->getAddress($value->addresses);
            }
        }

        public function createExcel() {
            // set header element
            $this->setHeaderElement(); 
            // extract api data
            $this->extractApiData();

            /*
            instantiate new object (will automatically construct the parser & writer type as CSV)
            */
            $excel = new SimpleExcel('csv'); 
            /*
            add some data to the writer
            */
            $excel->writer->setData($this->dataArray); 

            /*
            (optional) if delimiter not set, by default comma (",") will be used instead
            */  
            /*
            $excel->writer->setDelimiter(";");                
            */  
            $excel->writer->saveFile('customer');  
        }
    }
    
    $obj = new customerDataToExcel();
    $obj->createExcel();

    ?>
