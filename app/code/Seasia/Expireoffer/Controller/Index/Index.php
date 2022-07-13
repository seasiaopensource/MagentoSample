<?php

namespace Seasia\Expireoffer\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
  public function execute()
  {
    $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
    $datetime = $objectManager->create('\Magento\Framework\Stdlib\DateTime\DateTime');
    $time = $datetime->date();
    $data_time = $datetime->date('Y-m-d H:i:s', $time);
    $offerCollection = $objectManager->create('Seasia\Selleroffer\Model\ResourceModel\Offer\Collection')
    ->addFieldToFilter('offer_used', array('eq' => '0'))
    ->addFieldToFilter('created_at', array('lt' => date('Y-m-d H:i:s', strtotime('-2 day'))))
    ->addFieldToFilter('status', array('eq' => 'pending'));
    foreach($offerCollection as $eachCollection){
      $eachCollection->setStatus("expired");
      $eachCollection->save();
    }
    
    //echo "HELllllllllllllllllllllllllllllllllll";

  }
}
