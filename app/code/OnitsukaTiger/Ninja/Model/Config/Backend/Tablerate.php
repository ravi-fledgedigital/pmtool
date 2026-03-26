<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\Ninja\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use OnitsukaTiger\Ninja\Model\ResourceModel\Carrier\TablerateFactory;

/**
 * Backend model for shipping table rates CSV importing
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tablerate extends \Magento\Framework\App\Config\Value
{
    /**
     * @var TablerateFactory
     */
    protected $_tablerateFactory;

    /**
     * Tablerate constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param TablerateFactory $tablerateFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        TablerateFactory $tablerateFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_tablerateFactory = $tablerateFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return \Magento\Framework\App\Config\Value
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterSave()
    {
        /** @var \OnitsukaTiger\Ninja\Model\ResourceModel\Carrier\Tablerate $tableRate */
        $tableRate = $this->_tablerateFactory->create();
        $tableRate->uploadAndImport($this);
        return parent::afterSave();
    }
}
