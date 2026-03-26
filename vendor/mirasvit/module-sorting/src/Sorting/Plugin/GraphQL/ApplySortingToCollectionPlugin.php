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
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Sorting\Plugin\GraphQL;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection as FulltextCollection;
use Magento\Framework\DB\Select;
use Magento\Framework\GraphQl\Query\Resolver\ArgumentsCompositeProcessor;
use Mirasvit\Sorting\Repository\CriterionRepository;
use Mirasvit\Sorting\Service\Collection\CollectionService;
use Mirasvit\Sorting\Service\CriteriaApplierService;
use Mirasvit\Sorting\Service\GraphQl\CategoryContextService;
use Zend_Db_Expr;

/**
 * @see ProductCollection::addAttributeToSort()
 * @see ProductCollection::setOrder()
 * @see FulltextCollection::addAttributeToSort()
 * @see FulltextCollection::addAttributeToSort()
 * @see \Mirasvit\LayeredNavigation\Model\ResourceModel\Fulltext\Collection::addAttributeToSort()
 * @see \Mirasvit\LayeredNavigation\Model\ResourceModel\Fulltext\Collection::setOrder()
 * @see \Mirasvit\LayeredNavigation\Model\ResourceModel\Fulltext\SearchCollection::addAttributeToSort()
 * @see \Mirasvit\LayeredNavigation\Model\ResourceModel\Fulltext\SearchCollection::setOrder()
 * @see ArgumentsCompositeProcessor::process()
 */
class ApplySortingToCollectionPlugin
{
    /**
     * @var int
     */
    static  $increment         = 1;

    private $criterionRepository;

    private $criteriaApplierService;

    private $categoryContextService;

    private $collectionService;

    private $pinToTopEnabled   = false;

    public function __construct(
        CriteriaApplierService $criteriaApplierService,
        CriterionRepository    $criterionRepository,
        CategoryContextService $categoryContextService,
        CollectionService      $collectionService
    ) {
        $this->criterionRepository    = $criterionRepository;
        $this->criteriaApplierService = $criteriaApplierService;
        $this->categoryContextService = $categoryContextService;
        $this->collectionService      = $collectionService;
    }

    /**
     * @param ProductCollection $collection
     * @param string|array      $attribute
     * @param string            $dir
     *
     * @return array
     */
    public function beforeAddAttributeToSort(ProductCollection $collection, $attribute, $dir = Select::SQL_DESC): array
    {
        return $this->beforeSetOrder($collection, $attribute, $dir);
    }

    /**
     * Apply sort criteria to collection.
     *
     * @param ProductCollection $collection
     * @param string|array      $attribute
     * @param string            $dir
     *
     * @return array
     */
    public function beforeSetOrder(ProductCollection $collection, $attribute, string $dir = Select::SQL_DESC): array
    {
        if (is_array($attribute)) {
            if (isset($attribute['mst_sort'])) {
                $dir       = $attribute['mst_sort']['dir'];
                $attribute = $attribute['mst_sort']['code'];
            } else {
                $dir       = array_values($attribute)[0];
                $attribute = array_keys($attribute)[0];
            }
        }

        self::$increment++;

        if (!$collection->getFlag('increment')) {
            $collection->setFlag('increment', self::$increment);
        }

        if ($collection->getFlag($attribute)) { #already applied
            return [$attribute, $dir];
        }

        $collection->setFlag($attribute, true);

        $this->criteriaApplierService->setGlobalRankingFactors($collection);

        $criterion = $attribute ? $this->criterionRepository->getByCode($attribute) : $this->criteriaApplierService->getDefaultCriterion();

        if ($criterion) {
            $this->criteriaApplierService->setCriterion($collection, $criterion, $dir);

            if (!$collection->getFlag($criterion->getCode())) {
                $collection->setFlag($criterion->getCode(), true);
            }
        }

        if (!$collection->isLoaded()) {
            $this->criteriaApplierService->sortCollection($collection);

            if ($this->pinToTopEnabled) {
                $this->applyPinToTopToCollection($collection);
            }
        }

        return [$attribute, $dir];
    }

    /**
     * @param object $subject
     * @param string $fieldName
     * @param array  $args
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */

    public function beforeProcess($subject, string $fieldName, array $args): array
    {
        $this->populateCategoryContext($args);

        if (isset($args['sort']['mst_pin'])) {
            $this->pinToTopEnabled = $args['sort']['mst_pin'] !== false;
            unset($args['sort']['mst_pin']);
        }

        if (isset($args['sort']['mst_sort'])) {
            $attribute = $args['sort']['mst_sort']['code'];

            if (!$attribute) {
                $defaultCriterion = $this->criteriaApplierService->getDefaultCriterion();

                $attribute = $defaultCriterion ? $defaultCriterion->getCode() : null;
            }

            $dir                            = $args['sort']['mst_sort']['dir'];
            $args['sort']                   = [];
            $args['sort']['sorting_global'] = 'DESC';
            $args['sort'][$attribute]       = $dir;

            $categoryUid = $args['filter']['category_uid']['eq']
                ?? $args['filter']['category_uid']['in']
                ?? null;

            if ($categoryUid) {
                $this->criteriaApplierService->setCategoryUid($categoryUid);
            }
        }

        return [$fieldName, $args];
    }

    /**
     * Set category context for pin_to_top field resolution
     */
    private function populateCategoryContext(array $args): void
    {
        if (!isset($args['filter'])) {
            return;
        }

        $categoryUid = $args['filter']['category_uid']['eq'] ?? null;

        if ($categoryUid) {
            $this->categoryContextService->setCategoryUid($categoryUid);
        }

        $categoryId = $args['filter']['category_id']['eq'] ?? null;

        if ($categoryId) {
            $this->categoryContextService->setCategoryId((int)$categoryId);
        }
    }

    private function applyPinToTopToCollection(ProductCollection $collection): void
    {
        $categoryId = $this->categoryContextService->getCategoryId();

        if ($categoryId === null) {
            return;
        }

        $select = $collection->getSelect();

        $this->collectionService->joinPinnedProductsTable($select, $categoryId);

        $orders = $select->getPart(Select::ORDER);
        $select->reset(Select::ORDER);

        $pinningCase = new Zend_Db_Expr(
            'CASE WHEN ' . CollectionService::PINNED_PRODUCT_ALIAS . '.product_id IS NOT NULL THEN 0 ELSE 1 END ASC'
        );

        $select->order($pinningCase);

        foreach ($orders as $order) {
            $select->order($order);
        }
    }
}
