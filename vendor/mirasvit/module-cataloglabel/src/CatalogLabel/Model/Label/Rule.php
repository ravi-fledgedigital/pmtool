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


namespace Mirasvit\CatalogLabel\Model\Label;


use Mirasvit\CatalogLabel\Api\Data\IndexInterface;
use Mirasvit\CatalogLabel\Api\Data\LabelInterface;
use Magento\Rule\Model\AbstractModel;
use Mirasvit\CatalogLabel\Model\Label\DisplayFactory;
use Mirasvit\CatalogLabel\Model\Label\Rule\Condition\CombineFactory;
use Mirasvit\CatalogLabel\Model\Label\Rule\Action\CollectionFactory as LabelRuleCollectionFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Model\ResourceModel\Iterator;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Mirasvit\CatalogLabel\Repository\LabelRepository;
use Mirasvit\CatalogLabel\Service\ProductCollectionService;


/**
 * @SuppressWarnings(PHPMD)
 */
class Rule extends AbstractModel
{
    const CACHE_TAG = 'cataloglabel_label_rule';

    /**
     * @var array
     */
    protected $_productIds;
    /**
     * @var string
     */
    protected $_cacheTag = 'cataloglabel_label_rule';
    /**
     * @var string
     */
    protected $_eventPrefix = 'cataloglabel_label_rule';

    /**
     * Get identities.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }

    protected $labelDisplayFactory;

    protected $labelRepository;

    protected $labelRuleConditionCombineFactory;

    protected $labelRuleActionCollectionFactory;

    protected $productFactory;

    protected $productCollectionService;

    protected $resourceIterator;

    protected $resource;

    protected $storeManager;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        DisplayFactory $labelDisplayFactory,
        LabelRepository $labelRepository,
        CombineFactory $labelRuleConditionCombineFactory,
        LabelRuleCollectionFactory $labelRuleActionCollectionFactory,
        ProductFactory $productFactory,
        ProductCollectionService $productCollectionService,
        Iterator $resourceIterator,
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        TimezoneInterface $localeDate,
        StoreManagerInterface $storeManager,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->labelDisplayFactory              = $labelDisplayFactory;
        $this->labelRepository                  = $labelRepository;
        $this->labelRuleConditionCombineFactory = $labelRuleConditionCombineFactory;
        $this->labelRuleActionCollectionFactory = $labelRuleActionCollectionFactory;
        $this->productFactory                   = $productFactory;
        $this->productCollectionService         = $productCollectionService;
        $this->resourceIterator                 = $resourceIterator;
        $this->resource                         = $resource;
        $this->storeManager                     = $storeManager;

        parent::__construct($context, $registry, $formFactory, $localeDate, $resource, $resourceCollection, $data);
    }

    public function getDisplay(): Display
    {
        $display = $this->labelDisplayFactory->create();

        if ($this->getDisplayId()) {
            $display->load($this->getDisplayId());
        }

        return $display;
    }

    public function getLabel(): LabelInterface
    {
        $label = $this->labelRepository->create();

        if ($id = $this->getLabelId()) {
            $label = $this->labelRepository->get((int)$id);
        }

        return $label;
    }

    public function getConditionsInstance(): Rule\Condition\Combine
    {
        return $this->labelRuleConditionCombineFactory->create();
    }

    public function getActionsInstance(): Rule\Action\Collection
    {
        return $this->labelRuleActionCollectionFactory->create();
    }

    public function getMatchingProductIds(?array $productIds = null): array
    {
        $this->_productIds = [];

        foreach ($this->storeManager->getStores() as $store) {
            if (
                !in_array($store->getId(), $this->getLabel()->getStoreIds())
                && !in_array(0, $this->getLabel()->getStoreIds())
            ) {
                continue;
            }

            $this->setCollectedAttributes([]);

            $productCollection = $this->productCollectionService->getCollection((int)$store->getId(), $productIds);

            $this->getConditions()->collectValidatedAttributes($productCollection);

            $this->resourceIterator->walk(
                $productCollection->getSelect(),
                [[$this, 'callbackValidateProduct']],
                [
                    'attributes' => $this->getCollectedAttributes(),
                    'product'    => $this->productFactory->create(),
                    'storeId'    => $store->getId(),
                ]
            );
        }

        return $this->_productIds;
    }

    public function callbackValidateProduct(array $args): void
    {
        $product = clone $args['product'];
        $product->setData($args['row']);
        $product->setStoreId($args['storeId']);

        if ($this->getConditions()->validate($product)) {
            $this->_productIds[] = ['id' => $product->getId(), 'store_id' => $args['storeId']];
        }
    }

    public function getProductIds(): array
    {
        if (!$this->resource) {
            $this->resource = $this->getLabel()->getResource();
        }

        $read = $this->resource->getConnection();
        $select = $read->select()->from($this->resource->getTable(IndexInterface::TABLE_NAME), 'product_id')
            ->where('label_id=?', $this->getLabel()->getId());

        return $read->fetchCol($select);
    }

    public function getConditions(): \Magento\Rule\Model\Condition\Combine
    {
        if (empty($this->_conditions)) {
            $this->_resetConditions();
        }

        // Load rule conditions if it is applicable
        if ($this->hasConditionsSerialized()) {
            $conditions = $this->getConditionsSerialized();

            if (!empty($conditions)) {
                $decode = json_decode($conditions);

                if ($decode) { //M2.2 compatibility
                    $conditions = $this->serializer->unserialize($conditions);
                } else {
                    $conditions = \Magento\Framework\Serialize\Serializer\Json::unserialize($conditions);
                }

                if (is_array($conditions) && !empty($conditions)) {
                    $this->_conditions->loadArray($conditions);
                }
            }

            $this->unsConditionsSerialized();
        }

        return $this->_conditions;
    }
}
