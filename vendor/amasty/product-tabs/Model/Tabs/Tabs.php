<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Tabs;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

class Tabs extends AbstractModel implements TabsInterface, IdentityInterface
{
    public const CACHE_TAG = 'amasty_custom_tab';

    /**
     * @var \Amasty\Base\Model\Serializer|null
     */
    private $serializer = null;

    /**
     * @var \Amasty\CustomTabs\Model\Tabs\RuleFactory|null
     */
    private $ruleFactory = null;

    /**
     * @var \Amasty\CustomTabs\Model\Tabs\Indexer\TabProcessor|null
     */
    private $tabProcessor = null;

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\CustomTabs\Model\Tabs\ResourceModel\Tabs::class);
        $this->setIdFieldName(TabsInterface::TAB_ID);
        if ($this->getData('amasty_serializer')) {
            $this->ruleFactory = $this->getData('rule_factory');
        }
        if ($this->getData('amasty_serializer')) {
            $this->serializer = $this->getData('amasty_serializer');
        }
        if ($this->getData('tab_processor')) {
            $this->tabProcessor = $this->getData('tab_processor');
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        if ($this->getConditions()
            && $this->serializer !== null
            && $this->ruleFactory !== null
        ) {
            $rule = $this->ruleFactory->create()
                ->loadPost(['conditions' => $this->getConditions()]);
            $this->setConditionsSerialized($this->serializer->serialize($rule->getConditions()->asArray()));
        }

        return parent::beforeSave();
    }

    /**
     * @inheritdoc
     */
    public function afterSave()
    {
        $this->getResource()->addCommitCallback([$this, 'reindex']);

        return parent::afterSave();
    }

    public function reindex()
    {
        if ($this->tabProcessor !== null) {
            $this->tabProcessor->reindexRow($this->getTabId());
        }
    }

    /**
     * @inheritdoc
     */
    public function getTabId()
    {
        return $this->_getData(TabsInterface::TAB_ID);
    }

    /**
     * @inheritdoc
     */
    public function setTabId($tabId)
    {
        $this->setData(TabsInterface::TAB_ID, $tabId);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder()
    {
        return $this->_getData(TabsInterface::SORT_ORDER);
    }

    /**
     * @inheritdoc
     */
    public function setSortOrder($sortOrder)
    {
        $this->setData(TabsInterface::SORT_ORDER, $sortOrder);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->_getData(TabsInterface::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($createdAt)
    {
        $this->setData(TabsInterface::CREATED_AT, $createdAt);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTabAnchor(): ?string
    {
        return $this->_getData(TabsInterface::TAB_ANCHOR);
    }

    /**
     * @param string $anchor
     * @return $this|TabsInterface
     */
    public function setTabAnchor(string $anchor): TabsInterface
    {
        $this->setData(TabsInterface::TAB_ANCHOR, $anchor);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        return $this->_getData(TabsInterface::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->setData(TabsInterface::UPDATED_AT, $updatedAt);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTabName()
    {
        return $this->_getData(TabsInterface::TAB_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setTabName($tabName)
    {
        $this->setData(TabsInterface::TAB_NAME, $tabName);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return $this->_getData(TabsInterface::TAB_TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setTabTitle($tabTitle)
    {
        $this->setData(TabsInterface::TAB_TITLE, $tabTitle);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->_getData(TabsInterface::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        $this->setData(TabsInterface::STATUS, $status);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getIsActive()
    {
        return $this->_getData(TabsInterface::IS_ACTIVE);
    }

    /**
     * @inheritdoc
     */
    public function setIsActive($isActive)
    {
        $this->setData(TabsInterface::IS_ACTIVE, $isActive);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCustomerGroups()
    {
        return $this->_getData(TabsInterface::CUSTOMER_GROUPS);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerGroups($customerGroups)
    {
        $this->setData(TabsInterface::CUSTOMER_GROUPS, $customerGroups);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getContent()
    {
        return $this->_getData(TabsInterface::CONTENT);
    }

    /**
     * @inheritdoc
     */
    public function setContent($content)
    {
        $this->setData(TabsInterface::CONTENT, $content);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRelatedEnabled()
    {
        return $this->_getData(TabsInterface::RELATED_ENABLED);
    }

    /**
     * @inheritdoc
     */
    public function setRelatedEnabled($relatedEnabled)
    {
        $this->setData(TabsInterface::RELATED_ENABLED, $relatedEnabled);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUpsellEnabled()
    {
        return $this->_getData(TabsInterface::UPSELL_ENABLED);
    }

    /**
     * @inheritdoc
     */
    public function setUpsellEnabled($upsellEnabled)
    {
        $this->setData(TabsInterface::UPSELL_ENABLED, $upsellEnabled);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCrosssellEnabled()
    {
        return $this->_getData(TabsInterface::CROSSSELL_ENABLED);
    }

    /**
     * @inheritdoc
     */
    public function setCrosssellEnabled($crosssellEnabled)
    {
        $this->setData(TabsInterface::CROSSSELL_ENABLED, $crosssellEnabled);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getConditionsSerialized()
    {
        return $this->_getData(TabsInterface::CONDITIONS_SERIALIZED);
    }

    /**
     * @inheritdoc
     */
    public function setConditionsSerialized($conditionsSerialized)
    {
        $this->setData(TabsInterface::CONDITIONS_SERIALIZED, $conditionsSerialized);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getModuleName()
    {
        return $this->_getData(TabsInterface::MODULE_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setModuleName($moduleName)
    {
        $this->setData(TabsInterface::MODULE_NAME, $moduleName);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addStores(array $storeIds)
    {
        $this->setStores($storeIds);

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->_getData(TabsInterface::TAB_TYPE);
    }

    /**
     * @param string $type
     *
     * @return \Amasty\CustomTabs\Api\Data\TabsInterface
     */
    public function setType($type)
    {
        $this->setData(TabsInterface::TAB_TYPE, $type);

        return $this;
    }

    /**
     * @return string
     */
    public function getNameInLayout()
    {
        return $this->_getData(TabsInterface::NAME_IN_LAYOUT);
    }

    /**
     * @param string $nameInLayout
     *
     * @return \Amasty\CustomTabs\Api\Data\TabsInterface
     */
    public function setNameInLayout($nameInLayout)
    {
        $this->setData(TabsInterface::NAME_IN_LAYOUT, $nameInLayout);

        return $this;
    }

    /**
     * @return array
     */
    public function getStores()
    {
        $stores = $this->_getData('stores') !== null
            ? $this->_getData('stores')
            : [];
        if (!is_array($stores)) {
            $stores = explode(',', $stores);
            $this->setData('stores', $stores);
        }

        return $stores;
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        $cacheTag = self::CACHE_TAG . '_' . $this->getTabId();
        $product = $this->_registry->registry('current_product');
        if (!empty($product)) {
            $cacheTag .= '_' . $product->getData('entity_id');
        }

        return [$cacheTag];
    }

    public function getStoreId(): ?int
    {
        return $this->_getData(TabsInterface::STORE_ID);
    }

    public function setStoreId($storeId): TabsInterface
    {
        $this->setData(TabsInterface::STORE_ID, $storeId);

        return $this;
    }
}
