<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\CatalogLabel\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\ProductFactory;
use Magento\Eav\Model\Entity\AttributeFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\CatalogLabel\Api\Data\LabelInterface;
use Mirasvit\CatalogLabel\Api\Data\DisplayInterface;
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Mirasvit\CatalogLabel\Model;
use Mirasvit\CatalogLabel\Model\ResourceModel\Label\Display\Collection as DisplayCollection;
use Mirasvit\CatalogLabel\Repository\DisplayRepository;
use Mirasvit\CatalogLabel\Repository\PlaceholderRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Label extends AbstractModel implements IdentityInterface, LabelInterface
{
    /**
     * @var string
     */
    protected $_cacheTag = 'cataloglabel_label';
    /**
     * @var string
     */
    protected $_eventPrefix = 'cataloglabel_label';

    /**
     * Get identities.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }

    protected $entityAttributeFactory;

    protected $placeholderRepository;

    protected $productFactory;

    protected $config;

    protected $storeManager;

    protected $context;

    protected $registry;

    protected $resource;

    protected $resourceCollection;

    private $ruleFactory;

    private $rule;

    private $displayRepository;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        DisplayRepository $displayRepository,
        AttributeFactory $entityAttributeFactory,
        PlaceholderRepository $placeholderRepository,
        ProductFactory $productFactory,
        Model\Label\RuleFactory $ruleFactory,
        EavConfig $config,
        StoreManagerInterface $storeManager,
        Context $context,
        Registry $registry,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->displayRepository      = $displayRepository;
        $this->entityAttributeFactory = $entityAttributeFactory;
        $this->placeholderRepository  = $placeholderRepository;
        $this->productFactory         = $productFactory;
        $this->ruleFactory            = $ruleFactory;
        $this->config                 = $config;
        $this->storeManager           = $storeManager;
        $this->context                = $context;
        $this->registry               = $registry;
        $this->resource               = $resource;
        $this->resourceCollection     = $resourceCollection;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mirasvit\CatalogLabel\Model\ResourceModel\Label');
    }

    public function getRule(): Model\Label\Rule
    {
        if (!$this->rule) {
            $this->rule = $this->ruleFactory->create()->setLabelId($this->getId())
                ->setData(self::CONDITIONS_SERIALIZED, $this->getData(self::CONDITIONS_SERIALIZED));
        }

        return $this->rule;
    }

    public function getAttribute(): AbstractAttribute
    {
        $code = $this->entityAttributeFactory->create()->load($this->getAttributeId())->getAttributeCode();
        $attribute = $this->config->getAttribute('catalog_product', $code);

        return $attribute;
    }

    /**
     * @return Model\Label\Display[]
     */
    public function getDisplaysByProduct(ProductInterface $product): array
    {
        $displayIds = $this->getDisplayIds($product);

        if (!count($displayIds)) {
            return [];
        }

        return $displays = $this->displayRepository->getCollection()
            ->addFieldToFilter(
                'display_id',
                ['in' => $displayIds]
            );
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getDisplayIds(ProductInterface $product, $storeId = null): array
    {
        $result = [];

        if (!$storeId) {
            $storeId = $product->getStoreId() ?: $this->storeManager->getStore()->getId();
        }

        if ($this->getType() == self::TYPE_ATTRIBUTE) {
            $option = $this->productFactory->create()->getAttributeRawValue(
                $product->getId(),
                $this->getAttributeId(),
                $storeId
            );

            if (empty($option)) {
                return $result;
            }

            $attribute = $this->getAttribute();
            $options   = [];

            if (!$attribute->usesSource()) {
                //text attribute
//                $labelAttributes = $this->labelAttributeCollectionFactory->create()
//                    ->addFieldToFilter('label_id', $this->getId());
//                foreach ($labelAttributes as $labelAttribute) {
//                    if (strpos($option, $labelAttribute->getOptionText()) !== false) {
//                        $result[] = $labelAttribute->getDisplayId();
//                    }
//                }
            } else {
                // int attribute
                $options = explode(',', $option);
            }

            if ($product->getTypeId() == 'configurable') {
                $productAttributeOptions = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
                foreach ($productAttributeOptions as $productAttribute) {
                    if ($productAttribute['attribute_id'] != $this->getAttributeId()) {
                        continue;
                    }

                    foreach ($productAttribute['values'] as $attribute) {
                        $options[] = $attribute['value_index'];
                    }
                }
            }

            $options = array_unique($options);

            if (count($options)) {
                $displays = $this->displayRepository->getByData([
                    DisplayInterface::LABEL_ID       => $this->getId(),
                    DisplayInterface::ATTR_OPTION_ID => $options
                ]);

                foreach ($displays as $display) {
                    $result[] = $display->getId();
                }
            }
        } elseif ($this->getType() == self::TYPE_RULE) {
            $rule = $this->getRule();

            if ($rule->getConditions()->validate($product)) {
                $displays = $this->displayRepository->getByData([DisplayInterface::LABEL_ID => $this->getId()]);

                foreach ($displays as $display) {
                    $result[] = $display->getId();
                }
            }

        }

        return array_filter($result);
    }

    public function getId(): ?int
    {
        return $this->getData(self::ID) ? (int)$this->getData(self::ID) : null;
    }

    public function getType(): string
    {
        return (string)$this->getData(self::TYPE);
    }

    public function setType(string $type): LabelInterface
    {
        return $this->setData(self::TYPE, $type);
    }

    public function getAttributeId(): int
    {
        return (int)$this->getData(self::ATTRIBUTE_ID);
    }

    public function setAttributeId(int $attrId): LabelInterface
    {
        return $this->setData(self::ATTRIBUTE_ID, $attrId);
    }

    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName(string $name): LabelInterface
    {
        return $this->setData(self::NAME, $name);
    }

    public function getActiveFrom(): ?string
    {
        return $this->getData(self::ACTIVE_FROM) ?: null;
    }

    public function setActiveFrom(string $from): LabelInterface
    {
        return $this->setData(self::ACTIVE_FROM, $from);
    }

    public function getActiveTo(): ?string
    {
        return $this->getData(self::ACTIVE_TO) ?: null;
    }

    public function setActiveTo(string $to): LabelInterface
    {
        return $this->setData(self::ACTIVE_TO, $to);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $isActive): LabelInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    public function getStoreIds(): array
    {
        return $this->getData(self::STORE_IDS)
            ? explode(',', $this->getData(self::STORE_IDS))
            : [0];
    }

    public function setStoreIds(array $storeIds): LabelInterface
    {
        return $this->setData(self::STORE_IDS, implode(',', $storeIds));
    }

    public function getCustomerGroupIds(): array
    {
        $ids = $this->getData(self::CUSTOMER_GROUP_IDS);

        return $ids || $ids == 0
            ? explode(',', $this->getData(self::CUSTOMER_GROUP_IDS))
            : [];
    }

    public function setCustomerGroupIds(array $ids): LabelInterface
    {
        return $this->setData(self::CUSTOMER_GROUP_IDS, implode(',', $ids));
    }

    public function getAppearence(): string
    {
        return $this->getData(self::APPEARENCE) ?: self::APPEARENCE_LIST . ',' . self::APPEARENCE_VIEW;
    }

    public function getDisplaysByType(string $type): ?DisplayCollection
    {
        $displays = $this->getDisplays()
            ->addFieldToFilter(DisplayInterface::TYPE, ['in' => [$type, 'both']]);

        return $displays;
    }

    public function getDisplays(): DisplayCollection
    {
        return $this->displayRepository->getCollection()
            ->addFieldToFilter(DisplayInterface::LABEL_ID, $this->getId());
    }

    // Labels no longer related to placeholders since v 2.0.0
    // Method is left to avoid frontend errors right after the upgrade to v >= 2.0.0
    public function getPlaceholder(): PlaceholderInterface
    {
        $placeholder = $this->placeholderRepository->create();

        return $placeholder;
    }
}
