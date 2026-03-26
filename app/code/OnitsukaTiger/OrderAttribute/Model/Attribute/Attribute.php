<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Attribute;

use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity;
use OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputTypeProvider;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;

/**
 * Checkout attribute model
 *
 * @method \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Attribute getResource()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 */
class Attribute extends \Magento\Eav\Model\Attribute implements CheckoutAttributeInterface
{
    /**
     * Name of the module
     */
    public const MODULE_NAME = 'OnitsukaTiger_OrderAttribute';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'onitsukatiger_orderattribute_entity_attribute';

    /**
     * Prefix of model events object
     *
     * @var string
     */
    protected $_eventObject = 'attribute';

    /**
     * @var InputTypeProvider
     */
    private $inputTypeProvider;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    private $indexerRegistry;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Eav\Model\Entity\TypeFactory $eavTypeFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Eav\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $optionDataFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Catalog\Model\Product\ReservedAttributeList $reservedAttributeList,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        DateTimeFormatterInterface $dateTimeFormatter,
        InputTypeProvider $inputTypeProvider,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $eavConfig,
            $eavTypeFactory,
            $storeManager,
            $resourceHelper,
            $universalFactory,
            $optionDataFactory,
            $dataObjectProcessor,
            $dataObjectHelper,
            $localeDate,
            $reservedAttributeList,
            $localeResolver,
            $dateTimeFormatter,
            $resource,
            $resourceCollection,
            $data
        );
        $this->inputTypeProvider = $inputTypeProvider;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Initialize resource model
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_init(\OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Attribute::class);
        $this->setEntityType(Entity::ENTITY_TYPE_CODE);
    }

    /**
     * Detect backend storage type using frontend input type
     *
     * @param string $type frontend_input field value
     *
     * @return string backend_type field value
     */
    public function getBackendTypeByInput($type)
    {
        return $this->inputTypeProvider->getAttributeInputType($type)->getBackendType();
    }

    /**
     * Detect default value using frontend input type
     *
     * @return string default_value field value
     */
    public function getDefaultValueByInput($type)
    {
        $key = $this->inputTypeProvider->getAttributeInputType($type)->getDefaultValue();
        if ($key === false) {
            return '';
        }
        if (is_string($key)) {
            return 'default_value_' . $key;
        }

        return $key;
    }

    /**
     * @return \OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\InputType
     */
    public function getInputTypeConfiguration($inputType = null)
    {
        if (!$inputType) {
            $inputType = $this->getFrontendInput();
        }
        return $this->inputTypeProvider->getAttributeInputType($inputType);
    }

    /**
     * Used in Magento core EAV
     *
     * @see \Magento\Eav\Model\AttributeDataFactory::create
     *
     * @return string
     */
    public function getDataModel()
    {
        return $this->getInputTypeConfiguration()->getDataModel();
    }

    /**
     * Used in Magento core EAV
     * @see \Magento\Eav\Model\Attribute\Data\AbstractData::_getFormFilter
     *
     * @return string|bool
     */
    public function getInputFilter()
    {
        switch ($this->getFrontendInput()) {
            case 'date':
            case 'datetime':
                return 'date';
        }

        return $this->_getData(self::INPUT_FILTER);
    }

    /**
     * @return bool
     */
    public function getIsRequired()
    {
        try {
            return (parent::getIsRequired()
                || ($this->_appState->getAreaCode() == 'frontend' && $this->getRequiredOnFrontOnly())
            );
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return parent::getIsRequired();
        }
    }

    /**
     * @return bool
     */
    public function getIsVisible()
    {
        try {
            if ($this->_appState->getAreaCode() == 'adminhtml') {
                return (bool)$this->getIsVisibleOnBack();
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return (bool)$this->getIsVisibleOnBack();
        }

        return (bool)$this->getIsVisibleOnFront();
    }

    /**
     * Processing object after save data
     *
     * @return $this
     */
    public function afterSave()
    {
        if (($this->isObjectNew() && $this->isShowOnGrid())
            || (!$this->isObjectNew() && $this->dataHasChangedFor(CheckoutAttributeInterface::SHOW_ON_GRIDS))
        ) {
            $this->_getResource()->addCommitCallback([$this, 'invalidate']);
        }

        return parent::afterSave();
    }

    /**
     * Init indexing process after customer delete
     *
     * @return \Magento\Framework\Model\AbstractModel
     */
    public function afterDeleteCommit()
    {
        if ($this->getData(CheckoutAttributeInterface::SHOW_ON_GRIDS) == true) {
            $this->invalidate();
        }
        return parent::afterDeleteCommit();
    }

    /**
     * Init indexing process after customer save
     *
     * @return void
     */
    public function invalidate()
    {
        /** @var \Magento\Framework\Indexer\IndexerInterface $indexer */
        $indexer = $this->indexerRegistry->get(Entity::GRID_INDEXER_ID);
        $indexer->invalidate();
    }

    /**
     * Check whether attribute is searchable in admin grid and it is allowed
     *
     * @return bool
     */
    public function canBeSearchableInGrid()
    {
        return false;
    }

    /**
     * Check whether attribute is filterable in admin grid and it is allowed
     *
     * @return bool
     */
    public function canBeFilterableInGrid()
    {
        return $this->isShowOnGrid() && $this->getInputTypeConfiguration()->isFilterableInGrid();
    }

    /**
     * Prepare data for save
     *
     * @return $this
     */
    public function beforeSave()
    {
        $defaultValue = $this->getDefaultValue();
        $hasDefaultValue = (string)$defaultValue != '';
        if ($this->getInputTypeConfiguration()->getDefaultValue() == 'datetime' && $hasDefaultValue) {
            $format = $this->_localeDate->getDateTimeFormat(
                \IntlDateFormatter::SHORT
            );
            try {
                $defaultValueDateTime = $this->dateTimeFormatter->formatObject(new \DateTime($defaultValue), $format);
                $this->setDefaultValue($defaultValueDateTime);
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid default date'));
            }
        }

        //parent will save default date without time
        parent::beforeSave();

        if (isset($defaultValueDateTime)) {
            $this->setDefaultValue($defaultValueDateTime);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getAvailableInStores()
    {
        if (!$this->_getData('store_ids')) {
            $this->setData(
                'store_ids',
                $this->getResource()->getAvailableInStoresByAttributeId($this->getAttributeId())
            );
        }

        return $this->_getData('store_ids');
    }

    /**
     * @return array
     */
    public function getCustomerGroups()
    {
        if (!$this->_getData('customer_groups')) {
            $this->setData(
                'customer_groups',
                $this->getResource()->getCustomerGroupsByAttributeId($this->getAttributeId())
            );
        }

        return $this->_getData('customer_groups');
    }

    /**
     * @return array
     */
    public function getShippingMethods()
    {
        if (!$this->_getData('shipping_methods')) {
            $this->setData(
                'shipping_methods',
                $this->getResource()->getShippingMethodsByAttributeId($this->getAttributeId())
            );
        }

        return $this->_getData('shipping_methods');
    }

    /**
     * @return array
     */
    public function getStoreTooltips()
    {
        if (!$this->_getData('store_tooltips')) {
            $this->setData(
                'store_tooltips',
                $this->getResource()->getTooltipsByAttributeId($this->getAttributeId())
            );
        }

        return $this->_getData('store_tooltips');
    }

    /**
     * Get the forms this attribute is used in.
     *
     * @return array|null
     */
    public function getUsedInForms()
    {
        return $this->getData('used_in_forms');
    }

    /**
     * Set the forms this attribute is used in.
     *
     * @param array $usedInForms
     * @return $this
     */
    public function setUsedInForms(array $usedInForms)
    {
        return $this->setData('used_in_forms', $usedInForms);
    }


    /**
     * @param string|null $inputFilter
     *
     * @return string|bool
     */
    public function setInputFilter($inputFilter)
    {
        $this->setData(self::INPUT_FILTER, $inputFilter);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getIsVisibleOnFront()
    {
        return $this->_getData(CheckoutAttributeInterface::IS_VISIBLE_ON_FRONT);
    }

    /**
     * @inheritdoc
     */
    public function setIsVisibleOnFront($isVisibleOnFront)
    {
        $this->setData(CheckoutAttributeInterface::IS_VISIBLE_ON_FRONT, $isVisibleOnFront);

        return $this;
    }

    public function setIsHiddenFromCustomer(?bool $isHidden)
    {
        return $this->setData(self::IS_HIDDEN_FROM_CUSTOMER, $isHidden);
    }

    public function getIsHiddenFromCustomer(): bool
    {
        return (bool) $this->_getData(self::IS_HIDDEN_FROM_CUSTOMER);
    }

    /**
     * @inheritdoc
     */
    public function getIsVisibleOnBack()
    {
        return $this->_getData(CheckoutAttributeInterface::IS_VISIBLE_ON_BACK);
    }

    /**
     * @inheritdoc
     */
    public function setIsVisibleOnBack($isVisibleOnBack)
    {
        $this->setData(CheckoutAttributeInterface::IS_VISIBLE_ON_BACK, $isVisibleOnBack);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMultiselectSize()
    {
        return $this->_getData(CheckoutAttributeInterface::MULTISELECT_SIZE);
    }

    /**
     * @inheritdoc
     */
    public function setMultiselectSize($multiselectSize)
    {
        $this->setData(CheckoutAttributeInterface::MULTISELECT_SIZE, $multiselectSize);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSortingOrder()
    {
        return $this->_getData(CheckoutAttributeInterface::SORTING_ORDER);
    }

    /**
     * @inheritdoc
     */
    public function setSortingOrder($sortingOrder)
    {
        $this->setData(CheckoutAttributeInterface::SORTING_ORDER, $sortingOrder);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCheckoutStep()
    {
        return $this->_getData(CheckoutAttributeInterface::CHECKOUT_STEP);
    }

    /**
     * @inheritdoc
     */
    public function setCheckoutStep($checkoutStep)
    {
        $this->setData(CheckoutAttributeInterface::CHECKOUT_STEP, $checkoutStep);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isShowOnGrid()
    {
        return $this->_getData(CheckoutAttributeInterface::SHOW_ON_GRIDS);
    }

    /**
     * @inheritdoc
     */
    public function setShowOnGrids($showOnGrids)
    {
        $this->setData(CheckoutAttributeInterface::SHOW_ON_GRIDS, $showOnGrids);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getIncludeInPdf()
    {
        return $this->_getData(CheckoutAttributeInterface::INCLUDE_IN_PDF);
    }

    /**
     * @inheritdoc
     */
    public function setIncludeInPdf($includeInPdf)
    {
        $this->setData(CheckoutAttributeInterface::INCLUDE_IN_PDF, $includeInPdf);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getIncludeInHtmlPrintOrder()
    {
        return $this->_getData(CheckoutAttributeInterface::INCLUDE_IN_HTML_PRINT_ORDER);
    }

    /**
     * @inheritdoc
     */
    public function setIncludeInHtmlPrintOrder($includeInHtmlPrintOrder)
    {
        $this->setData(CheckoutAttributeInterface::INCLUDE_IN_HTML_PRINT_ORDER, $includeInHtmlPrintOrder);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isSaveToFutureCheckout()
    {
        return $this->_getData(CheckoutAttributeInterface::SAVE_TO_FUTURE_CHECKOUT);
    }

    /**
     * @inheritdoc
     */
    public function setSaveToFutureCheckout($saveToFutureCheckout)
    {
        $this->setData(CheckoutAttributeInterface::SAVE_TO_FUTURE_CHECKOUT, $saveToFutureCheckout);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getApplyDefaultValue()
    {
        return $this->_getData(CheckoutAttributeInterface::APPLY_DEFAULT_VALUE);
    }

    /**
     * @inheritdoc
     */
    public function setApplyDefaultValue($applyDefaultValue)
    {
        $this->setData(CheckoutAttributeInterface::APPLY_DEFAULT_VALUE, $applyDefaultValue);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isIncludeInEmail()
    {
        return (bool) $this->_getData(CheckoutAttributeInterface::INCLUDE_IN_EMAIL);
    }

    /**
     * @inheritdoc
     */
    public function setIsIncludeInEmail($includeInEmail)
    {
        $this->setData(CheckoutAttributeInterface::INCLUDE_IN_EMAIL, (bool)$includeInEmail);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRequiredOnFrontOnly()
    {
        return $this->_getData(CheckoutAttributeInterface::REQUIRED_ON_FRONT_ONLY);
    }

    /**
     * @inheritdoc
     */
    public function setRequiredOnFrontOnly($requiredOnFrontOnly)
    {
        $this->setData(CheckoutAttributeInterface::REQUIRED_ON_FRONT_ONLY, $requiredOnFrontOnly);

        return $this;
    }

    public function getIsFrontRequired()
    {
        return ($this->getIsRequired() || $this->getRequiredOnFrontOnly());
    }

    public function isAllowedCustomerGroup($currentGroup)
    {
        $isAllowed = true;

        if ($attributeGroups = $this->getCustomerGroups()) {
            $isAllowed = in_array($currentGroup, $attributeGroups);
        }

        return (bool)$isAllowed;
    }

    public function getDefaultOrLastValue()
    {
        /** TODO load saved value too */
        $value = $this->getDefaultValue();

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function __sleep()
    {
        $this->unsetData('entity_type');

        return array_diff(
            parent::__sleep(),
            ['inputTypeProvider', 'indexerRegistry']
        );
    }

    /**
     * @inheritdoc
     */
    public function __wakeup()
    {
        parent::__wakeup();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->inputTypeProvider = $objectManager->get(InputTypeProvider::class);
        $this->indexerRegistry = $objectManager->get(\Magento\Framework\Indexer\IndexerRegistry::class);
    }

    /**
     * @return bool
     */
    public function isScopeGlobal()
    {
        return true;
    }
}
