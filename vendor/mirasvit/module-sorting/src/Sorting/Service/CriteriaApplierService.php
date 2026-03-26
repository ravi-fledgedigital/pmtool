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

namespace Mirasvit\Sorting\Service;

use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Mirasvit\Sorting\Api\Data\CriterionInterface;
use Mirasvit\Sorting\Api\Data\RankingFactorInterface;
use Mirasvit\Sorting\Model\ConfigProvider;
use Mirasvit\Sorting\Model\Indexer;
use Mirasvit\Sorting\Repository\CriterionRepository;
use Mirasvit\Sorting\Repository\RankingFactorRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\State as AppState;
use Magento\Customer\Model\Session as CustomerSession;
use Mirasvit\Core\Service\CompatibilityService;
use Mirasvit\Sorting\Service\Collection\CollectionService;
use Zend_Db_Expr;

/** @SuppressWarnings(PHPMD) */
class CriteriaApplierService
{
    const FLAG_NO_SORT   = 'NO_SORT';
    const FLAG_GLOBAL    = 'sorting_global';
    const FLAG_CRITERION = 'sorting_criterion';
    const FLAG_DIRECTION = 'sorting_direction';
    const FLAG_IS_WIDGET = 'sorting_is_widget';

    private $rankingFactorRepository;

    private $configProvider;

    private $collectionService;

    private $criterionRepository;

    private $currentCriterion;

    private $request;

    private $storeManager;

    private $criteriaManagenentService;

    private $appState;

    private $customerSession;

    private $registry;

    private $categoryUid = null;

    public function __construct(
        RankingFactorRepository      $rankingFactorRepository,
        ConfigProvider               $configProvider,
        CriterionRepository          $criterionRepository,
        Collection\CollectionService $collectionService,
        RequestInterface             $request,
        StoreManagerInterface        $storeManager,
        CriteriaManagementService    $criteriaManagementService,
        AppState                     $appState,
        CustomerSession              $customerSession,
        Registry                     $registry
    ) {
        $this->rankingFactorRepository = $rankingFactorRepository;
        $this->configProvider          = $configProvider;
        $this->collectionService       = $collectionService;
        $this->criterionRepository     = $criterionRepository;
        $this->request                 = $request;
        $this->storeManager            = $storeManager;
        $this->appState                = $appState;
        $this->customerSession         = $customerSession;
        $this->registry                = $registry;

        $this->criteriaManagenentService = $criteriaManagementService;
    }

    public function setGlobalRankingFactors(AbstractCollection $collection): AbstractCollection
    {
        $rankingFactors = $this->rankingFactorRepository->getCollection();
        $rankingFactors->addFieldToFilter(RankingFactorInterface::IS_ACTIVE, 1)
            ->addFieldToFilter(RankingFactorInterface::IS_GLOBAL, 1);

        if ($rankingFactors->getSize()) {
            $collection->setFlag(self::FLAG_GLOBAL, true);
        }

        return $collection;
    }

    public function setCriterion(AbstractCollection $collection, CriterionInterface $criterion, ?string $dir = null): AbstractCollection
    {
        if ($dir !== null && strtolower($dir) !== 'asc' && strtolower($dir) !== 'desc') {
            $dir = null;
        }

        $collection->setFlag(self::FLAG_CRITERION, $criterion);
        $collection->setFlag(self::FLAG_DIRECTION, $dir);

        $this->currentCriterion = $criterion;

        return $collection;
    }

    public function getCurrentCriterion(): ?CriterionInterface
    {
        return $this->currentCriterion;
    }

    public function sortCollection(AbstractCollection $collection): AbstractCollection
    {
        // to avoid conflict with the Advanced Product Feeds
        if ($collection->getFlag(CriteriaApplierService::FLAG_NO_SORT)) {
            return $collection;
        }

        if (strpos($this->request->getFullActionName(), 'checkout') !== false) {
            return $collection;
        }

        $isGrapqlRequest = strpos($this->request->getRequestUri(), 'graphql') !== false;

        if ((bool)$collection->getFlag(self::FLAG_CRITERION) === false
            && ($this->configProvider->isApplySortingForCustomBlocks((int)$this->storeManager->getStore()->getId()) === false || $isGrapqlRequest)) {
            return $collection;
        }

        if (
            (bool)$collection->getFlag(self::FLAG_CRITERION) === false
            && $this->request->getParam('product_list_order') !== 'relevance'
        ) {
            $defaultCriterion = $this->criteriaManagenentService->getDefaultCriterion();
            if ($defaultCriterion) {
                $this->setCriterion($collection, $defaultCriterion);
            }
        }

        $select = $collection->getSelect();

        $memorizeOrders = $select->getPart(Select::ORDER);

        $select->reset(Select::ORDER);

        $dir = $collection->getFlag(self::FLAG_DIRECTION);
        $dir = $dir ? : null;

        $this->collectionService->joinSortingIndex($select);
        #global factors
        $globalExpressions = [];
        foreach ($this->getGlobalFactors() as $factor) {
            $globalExpressions[] = $this->quoteFormula(Indexer::getScoreColumn($factor), $factor->getWeight());
        }
        $this->collectionService->addOrder($select, $globalExpressions, 'desc');

        $criterion = $this->getCurrentCriterion();
        if ($criterion) {
            #criterion factors
            foreach ($criterion->getConditionCluster()->getFrames() as $idx => $frame) {
                $frameExpressions = [];

                if ($dir === null) {
                    $dir = $frame->getDirection();
                }

                $dir = $idx === 0 ? $dir : $frame->getDirection();

                foreach ($frame->getNodes() as $node) {
                    if ($node->getSortBy() == CriterionInterface::CONDITION_SORT_BY_ATTRIBUTE) {
                        $attributeExpr = $this->collectionService->joinAttribute($select, $node->getAttribute(), $this->getCategoryUid());

                        $frameExpressions[] = $attributeExpr;
                    } else {
                        $frameExpressions[] = $this->quoteFormula(Indexer::getScoreColumnById($node->getRankingFactor()), $node->getWeight());
                    }
                }

                $this->collectionService->addOrder($select, $frameExpressions, $dir);
            }
        }

        foreach ($memorizeOrders as $order) {
            $this->collectionService->addOrder($select, [$order], null);
        }

        // set fallback sort order for products with equal scores
        $this->collectionService->addOrder($select, ['e.created_at'], 'DESC');

        $this->applyPinningLogic($select, $collection);

        if ($this->configProvider->isDebug()) {
            DebugService::logCollection($collection);
            DebugService::setCurrentCriterion($this->getCurrentCriterion());
        }

        return $collection;
    }

    /** @return RankingFactorInterface[] */
    private function getGlobalFactors(): array
    {
        $rankingFactors = $this->rankingFactorRepository->getCollection();
        $rankingFactors->addFieldToFilter(RankingFactorInterface::IS_ACTIVE, 1)
            ->addFieldToFilter(RankingFactorInterface::IS_GLOBAL, 1);

        return $rankingFactors->getItems();
    }

    public function getDefaultCriterion(): ?CriterionInterface
    {
        /** @var CriterionInterface $criterion */
        $criterion = $this->criterionRepository->getCollection()
            ->addFieldToFilter(CriterionInterface::IS_ACTIVE, 1)
            ->setOrder(CriterionInterface::IS_DEFAULT, 'desc')
            ->setOrder(CriterionInterface::POSITION, 'asc')
            ->getFirstItem();

        return $criterion->getId() ? $criterion : null;
    }

    private function quoteFormula(string $columnName, int $weight): string
    {
        $storeId = (int)$this->storeManager->getStore()->getId();

        return 'IFNULL(mst_sorting_index_' . $storeId . '.' . $columnName . ', IFNULL(mst_sorting_index_0.' . $columnName . ', 0)) * ' . $weight;
    }

    /**
     * @throws LocalizedException
     */
    public function shouldAffectOrders(bool $isElasticSearch = true): bool
    {
        if ($isElasticSearch && !$this->configProvider->isElasticSearch()) {
            return false;
        }

        // For GraphQL area: enable only for Magento 2.4.8+ where sorting via SearchCriteria is required.
        // On older versions, GraphQL sorting works via beforeAddAttributeToSort plugin on collection.
        if ($this->appState->getAreaCode() === 'graphql' && !$this->isGraphQl248OrHigher()) {
            return false;
        }

        if (
            $this->appState->getAreaCode() === 'webapi_rest'
            && (
                strpos($this->request->getPathInfo(), 'rest/V1/products') === false
                || strpos($this->request->getPathInfo(), 'rest/V1/products/attribute-sets') !== false
                || strpos($this->request->getPathInfo(), 'rest/V1/products/attributes') !== false
            )
        ) {
            return false;
        }

        if (strpos($this->request->getFullActionName(), 'checkout') !== false) {
            return false;
        }

        return true;
    }

    /**
     * Check if current request is GraphQL on Magento 2.4.8+
     */
    private function isGraphQl248OrHigher(): bool
    {
        if ($this->appState->getAreaCode() !== 'graphql') {
            return false;
        }

        return version_compare(CompatibilityService::getVersion(), '2.4.8', '>=');
    }

    private function getGraphQlVariables(): ?array
    {
        $content = $this->request->getContent();

        if (!$content) {
            return null;
        }

        $requestData = json_decode($content, true);

        if (!$requestData || !isset($requestData['variables'])) {
            return null;
        }

        return $requestData['variables'];
    }

    private function shouldUseRelevanceForGraphQlSearch(): bool
    {
        if (!$this->isGraphQl248OrHigher()) {
            return false;
        }

        $variables = $this->getGraphQlVariables();

        if ($variables === null) {
            return false;
        }

        if (empty($variables['search'])) {
            return false;
        }

        if (empty($variables['sort'])) {
            return true;
        }

        $sort = $variables['sort'];
        unset($sort['mst_pin']);

        return empty($sort);
    }

    public function prepareCriteria($orders, $isElasticsearch = true): array
    {
        if (!is_array($orders)) {
            $orders = [];
        }
        $newOrders = [];

        $this->addOrder($newOrders, 'sorting_global', 'DESC', $isElasticsearch);

        // For GraphQL search requests without explicit sorting, add relevance after global factors
        if ($this->shouldUseRelevanceForGraphQlSearch()) {
            $this->addOrder($newOrders, 'relevance', 'DESC', $isElasticsearch);

            return $newOrders;
        }

        foreach ($orders as $attr => $spec) {
            if (is_object($spec)) { //rest api request
                /** @var SortOrder $spec */

                $attr      = $spec->getField();
                $direction = $spec->getDirection();
            } else {
                $direction = $spec;
            }

            $criterion = $this->criterionRepository->getByCode($attr);

            if ($criterion) {

                if (!$isElasticsearch && is_array($direction)) {
                    $direction = $direction['direction'];
                }

                foreach ($this->getFrames($criterion, $direction) as $frame => $dir) {
                    $this->addOrder($newOrders, $frame, $dir, $isElasticsearch);
                }
            }
        }

        //restore original order
        foreach ($orders as $attr => $direction) {
            if (is_numeric($attr)) {
                $newOrders[] = $direction;
            } else {
                $newOrders[$attr] = $direction;
            }
        }

        return $newOrders;
    }

    public function getFrames(CriterionInterface $criterion, string $direction): array
    {
        $frameScores = [];

        foreach ($criterion->getConditionCluster()->getFrames() as $frameIdx => $frame) {
            if (count($frame->getNodes()) >= 2) {
                $key = 'sorting_criterion_' . $criterion->getId() . '_frame_' . $frameIdx;

                $frameScores[$key] = $frameIdx === 0 ? $direction : $frame->getDirection();
            } else {
                foreach ($frame->getNodes() as $node) {
                    if ($node->getSortBy() === CriterionInterface::CONDITION_SORT_BY_RANKING_FACTOR) {
                        $key = 'sorting_factor_' . $node->getRankingFactor();
                    } else {
                        $key = $node->getAttribute();
                    }

                    $frameScores[$key] = $frameIdx === 0 ? $direction : $frame->getDirection();
                }
            }
        }

        return $frameScores;
    }

    private function addOrder(array &$orderList, string $attr, string $direction, $isElasticSearch = true): void
    {
        if (!$isElasticSearch && $attr == 'price') {
            $attr = 'price.price';
        }

        if (in_array($this->appState->getAreaCode(), ['webapi_rest', 'graphql'])) {
            $orderList[] = new SortOrder([
                'field'     => $attr,
                'direction' => $direction,
            ]);
        } else {
            if ($isElasticSearch) {
                $orderList[$attr] = $direction;
            } else {
                $orderList[$attr] = ['direction' => $direction];
            }

            if (!$isElasticSearch && $attr == 'price.price') {
                $orderList[$attr]['nestedPath']   = 'price';
                $orderList[$attr]['nestedFilter'] = [
                    'price.customer_group_id' => $this->customerSession->getCustomerGroupId(),
                ];
            }
        }
    }

    /**
     * @return string|array|null
     */
    public function getCategoryUid()
    {
        return $this->categoryUid;
    }

    /**
     * @param string|array|null $uid
     */
    public function setCategoryUid($uid): void
    {
        $this->categoryUid = $uid;
    }

    /**
     * Prioritize pinned products in category listing.
     */
    private function applyPinningLogic(Select $select, AbstractCollection $collection): void
    {
        // Skip pinning for widget collections
        if ($collection->getFlag(self::FLAG_IS_WIDGET)) {
            return;
        }

        // Only apply pinning on actual category pages
        if (!$this->registry->registry('current_category')) {
            return;
        }

        $categoryIds = $this->collectionService->retrieveCategoryIds($select);

        if (!count($categoryIds)) {
            return;
        }

        $categoryId = (int)$categoryIds[0];
        $this->collectionService->joinPinnedProductsTable($select, $categoryId);

        $orders = $select->getPart(Select::ORDER);

        if (!count($orders)) {
            return;
        }

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
