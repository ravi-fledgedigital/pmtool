<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Model\System\Config\Backend\Catalog\Product\Price;

use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Config\ScopeInterface;

/**
 * Catalog Default Product Price Backend Model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class DefaultPrice extends \Magento\Framework\App\Config\Value
{
    /**
     * Price permissions data
     *
     * @var \Magento\PricePermissions\Helper\Data
     */
    protected $_pricePermData = null;

    /**
     * @var ScopeInterface
     */
    private $scope;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\PricePermissions\Helper\Data $pricePermData
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param \Magento\Framework\Config\ScopeInterface|null $scope
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\PricePermissions\Helper\Data $pricePermData,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        ScopeInterface $scope = null,
        array $data = []
    ) {
        $this->_pricePermData = $pricePermData;
        $this->scope = $scope ?? ObjectManager::getInstance()->get(ScopeInterface::class);
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Check permission to edit product prices before the value is saved
     *
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();
        $value = $this->scope->getCurrentScope();
        $defaultProductPriceValue = floatval($this->getValue());
        if ($value === Area::AREA_ADMINHTML
            && !$this->_pricePermData->getCanAdminEditProductPrice() || $defaultProductPriceValue < 0) {
            $defaultProductPriceValue = floatval($this->getOldValue());
        }
        $this->setValue((string)$defaultProductPriceValue);
        return $this;
    }

    /**
     * Check permission to read product prices before the value is shown to user
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        if (!$this->_pricePermData->getCanAdminReadProductPrice()) {
            $this->setValue(null);
        }
        return $this;
    }
}
