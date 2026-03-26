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


namespace Mirasvit\CatalogLabel\Service;


use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\CatalogLabel\Api\Data\DisplayInterface;
use Mirasvit\CatalogLabel\Api\Data\IndexInterface;
use Mirasvit\CatalogLabel\Api\Data\LabelInterface;
use Mirasvit\CatalogLabel\Model\ResourceModel\Label\Collection as LabelCollection;
use Mirasvit\CatalogLabel\Model\ResourceModel\Label\CollectionFactory as LabelCollectionFactory;
use Mirasvit\CatalogLabel\Repository\DisplayRepository;

class ReindexService
{
    private $storeManager;

    private $resource;

    private $connection;

    private $productCollectionService;

    private $labelCollectionFactory;

    private $timezone;

    private $displayRepository;

    /** @var string */
    private $indexTable;

    /** @var array */
    private $rows = [];

    /** @var StoreInterface[] */
    private $stores;

    public function __construct(
        StoreManagerInterface $storeManager,
        ResourceConnection $resource,
        LabelCollectionFactory $labelCollectionFactory,
        TimezoneInterface $timezone,
        DisplayRepository $displayRepository,
        ProductCollectionService $productCollectionService
    ) {
        $this->storeManager             = $storeManager;
        $this->resource                 = $resource;
        $this->connection               = $this->resource->getConnection();
        $this->labelCollectionFactory   = $labelCollectionFactory;
        $this->timezone                 = $timezone;
        $this->displayRepository        = $displayRepository;
        $this->productCollectionService = $productCollectionService;
    }

    public function execute(?int $labelId = null, ?array $productIds = [])
    {
        $this->indexTable = $this->resource->getTableName(IndexInterface::TABLE_NAME);

        $this->cleanup($labelId, $productIds);

        $this->stores = $this->storeManager->getStores();

        /** @var LabelInterface $label */
        foreach ($this->getLabelsCollection($labelId) as $label) {
            if ($label->getType() == LabelInterface::TYPE_RULE) {
                $this->reindexRuleLabel($label, $productIds);

                continue;
            }

            $this->reindexAttributeLabel($label, $productIds);
        }

        $this->write(true);
    }

    private function reindexRuleLabel(LabelInterface $label, ?array $productIds = [])
    {
        foreach ($label->getRule()->getMatchingProductIds($productIds) as $storeProductData) {
            $displayIds = [];

            foreach ($this->displayRepository->getByData([DisplayInterface::LABEL_ID => $label->getId()]) as $display) {
                $displayIds[] = $display->getId();
            }

            $this->rows[] = [
                'product_id'      => $storeProductData['id'],
                'label_id'        => $label->getId(),
                'store_id'        => $storeProductData['store_id'],
                'display_ids'     => implode(',', $displayIds),
                'sort_order'      => $label->getSortOrder(),
                'customer_groups' => implode(',', $label->getCustomerGroupIds()),
            ];

            $this->write();
        }
    }

    private function reindexAttributeLabel(LabelInterface $label, ?array $productIds = [])
    {
        $attributeCodeSelect = $this->connection->select()->from(
            $this->resource->getTableName('eav_attribute'),
            ['attribute_code']
        )->where('attribute_id = ' . $label->getAttributeId());

        $attributeCode = $this->connection->fetchOne($attributeCodeSelect);

        foreach ($this->stores as $store) {
            if (
                !in_array($store->getId(), $label->getStoreIds())
                && !in_array(0, $label->getStoreIds())
            ) {
                continue;
            }

            $productCollection = $this->productCollectionService->getCollection(
                (int)$store->getId(),
                $productIds,
                $attributeCode
            );

            $productCollection->setPageSize(1000);

            $page     = 1;
            $lastPage = $productCollection->getLastPageNumber();

            while ($page <= $lastPage) {
                $productCollection->setCurPage($page);

                foreach ($productCollection as $product) {
                    $labelData = $this->reindexProductLabelData($product, $label);

                    if (empty($labelData)) {
                        continue;
                    }

                    $this->rows[] = $labelData;

                    $this->write();
                }

                $productCollection->clear();
                $page++;
            }
        }
    }

    private function write(bool $isFinal = false)
    {
        if (!count($this->rows) || (!$isFinal && count($this->rows) < 1000)) {
            return;
        }

        $this->connection->insertOnDuplicate($this->indexTable, $this->rows, ['product_id', 'label_id', 'store_id']);
        $this->rows = [];
    }

    private function cleanup(?int $labelId = null, ?array $productIds = [])
    {
        if (empty($productIds) && $labelId === null) {
            $this->connection->truncateTable($this->indexTable);

            return;
        }

        $where = [];

        if (!empty($productIds)) {
            $where[] = 'product_id in (' . implode(',', $productIds) . ')';
        }

        if ($labelId) {
            $where[] = 'label_id = ' . $labelId;
        }

        if (count($where)) {
            $this->connection->delete($this->indexTable, implode(' AND ', $where));
        }
    }

    private function getLabelsCollection(?int $labelId = null, ?int $storeId = null): LabelCollection
    {
        $currentDate     = $this->timezone->date()->format('Y-m-d H:i:s');
        $labelCollection = $this->labelCollectionFactory->create()
            ->addFieldToFilter(LabelInterface::IS_ACTIVE, ['eq' => 1])
            ->addFieldToFilter(LabelInterface::ACTIVE_FROM, [['null' => true], ['lteq' => $currentDate]])
            ->addFieldToFilter(LabelInterface::ACTIVE_TO, [['null' => true], ['gteq' => $currentDate]]);

        if ($labelId !== null) {
            $labelCollection->addFieldTofilter('main_table.label_id', ['eq' => $labelId]);
        }

        if ($storeId !== null) {
            $labelCollection->addStoreFilter((int)$storeId);
        }

        return $labelCollection;
    }

    private function reindexProductLabelData(ProductInterface $product, LabelInterface $label): iterable
    {
        $displayIds = $label->getDisplayIds($product, null, true);

        if (!count($displayIds)) {
            return [];
        }

        return [
            'product_id'      => $product->getId(),
            'label_id'        => $label->getId(),
            'store_id'        => $product->getStoreId(),
            'display_ids'     => implode(',', $displayIds),
            'sort_order'      => $label->getSortOrder(),
            'customer_groups' => implode(',', $label->getCustomerGroupIds()),
        ];
    }
}
