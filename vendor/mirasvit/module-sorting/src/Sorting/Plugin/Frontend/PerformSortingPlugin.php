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

namespace Mirasvit\Sorting\Plugin\Frontend;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierInterface;
use Mirasvit\Sorting\Model\ConfigProvider;
use Mirasvit\Sorting\Service\CriteriaApplierService;use Mirasvit\Sorting\Service\PinnedProductService;

/**
 * Apply sorting using MySQL
 * @see Collection::load
 * @see SearchResultApplierInterface::apply
 */
class PerformSortingPlugin
{
    private const FLAG_PINNED_CATEGORIES_ADDED = 'pinned_category_ids_added';

    private $criteriaApplierService;

    private $config;

    private $pinnedProductService;

    /**
     * @var Collection
     */
    private $collection;

    public function __construct(
        CriteriaApplierService $criteriaApplierService,
        ConfigProvider         $config,
        PinnedProductService   $pinnedProductService
    ) {
        $this->criteriaApplierService = $criteriaApplierService;
        $this->config                 = $config;
        $this->pinnedProductService   = $pinnedProductService;
    }

    public function beforeLoad(Collection $subject, ?bool $print = false, ?bool $log = false): array
    {
        if (!$this->config->isApplicable()) {
            return [$print, $log];
        }

        if (!$subject->isLoaded()) {
            if (!$subject instanceof \Magento\Bundle\Model\ResourceModel\Selection\Collection) {
                $this->criteriaApplierService->sortCollection($subject);
            }

            $this->collection = $subject;
        }

        return [$print, $log];
    }

    /**
     * Adds pinned_category_ids data to products after collection load
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterLoad(Collection $subject, Collection $result): Collection
    {
        if (!$this->config->isDebug()) {
            return $result;
        }

        if ($result->hasFlag(self::FLAG_PINNED_CATEGORIES_ADDED)) {
            return $result;
        }

        $result->setFlag(self::FLAG_PINNED_CATEGORIES_ADDED, true);

        foreach ($result->getItems() as $product) {
            $pinnedCategories = $this->pinnedProductService->getCategoryIds((int)$product->getId());
            $product->setData('pinned_category_ids', $pinnedCategories);
        }

        return $result;
    }

    /**
     * @param mixed $subject
     * @param mixed $result
     *
     * @return mixed
     */
    public function afterApply($subject, $result)
    {
        if (!$this->config->isApplicable()) {
            return $result;
        }

        if ($this->collection && !$this->collection->isLoaded()) {
            $this->criteriaApplierService->sortCollection($this->collection);
        }

        return $result;
    }

    /**
     * Remove order by entity_id
     *
     * @param object $subject
     * @param string $field
     * @param string $direction
     *
     * @return array
     */
    public function beforeSetOrder($subject, $field, $direction = ''): array
    {
        if (!$this->config->isApplicable()) {
            return [$field, $direction];
        }

        if ($field === 'entity_id') {
            $field = '1';
        }

        return [$field, $direction];
    }

}
