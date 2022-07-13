<?php

namespace Seasia\Expireoffer\Cron;

class Expireoffer
{
	protected $logger;

	public function __construct(
		\Psr\Log\LoggerInterface $loggerInterface
 ) {
		$this->logger = $loggerInterface;
  }

  public function execute() {

    $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
    $datetime = $objectManager->create('\Magento\Framework\Stdlib\DateTime\DateTime');
    $time = $datetime->date();
    $data_time = $datetime->date('Y-m-d H:i:s', $time);
    $offerCollection = $objectManager->create('Seasia\Selleroffer\Model\ResourceModel\Offer\Collection')
    //->addFieldToFilter('offer_used', array('eq' => '0'))
    ->addFieldToFilter('created_at', array('lt' => date('Y-m-d H:i:s', strtotime('-2 day'))))
    ->addFieldToFilter('status', array('eq' => 'pending'));

    $offerHelper = $objectManager->create(
      'Seasia\Selleroffer\Helper\Data'
    );
    
    foreach($offerCollection as $eachCollection){

      if($offerHelper->rejectOfferByOfferId($eachCollection->getSellerId(), $eachCollection->getId(), "expired")){
        $eachCollection->setStatus("expired");
        $eachCollection->setExpired(1);
        $eachCollection->save();
      }
      

    }
  }
}
