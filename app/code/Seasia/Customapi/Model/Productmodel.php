<?php

namespace Seasia\Customapi\Model;

class Productmodel extends \Magento\Framework\Model\AbstractModel implements
    \Seasia\Customapi\Api\Data\ProductdataInterface 
{
    const KEY_NAME = 'data';


     public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }


    public function getName()
    {
        return $this->_getData(self::KEY_NAME);
    }


    /**
     * Set name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->setData(self::KEY_NAME, $name);
    }


}