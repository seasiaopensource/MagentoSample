<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Seasia\Customapi\Model;;


/**
 * Data object converter for REST
 */
class ServiceOutputProcessor  extends \Magento\Framework\Webapi\ServiceOutputProcessor
{

    
    public function process($data, $serviceClassName, $serviceMethodName)
    {
        /** @var string $dataType */
        $dataType = $this->methodsMapProcessor->getMethodReturnType($serviceClassName, $serviceMethodName);
        if($dataType == 'array'){
            return $data;           
        }else{
            return $this->convertValue($data, $dataType);

        }
    }


}