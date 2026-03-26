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

use Mirasvit\Sorting\Service\CriteriaApplierService;

/**
 * @see \Magento\Framework\Api\SearchCriteria::setSortOrders()
 * @SuppressWarnings(PHPMD)
 */
class ApplyCriteriaToElasticsearchPlugin
{
    private $criteriaApplierService;

    public function __construct(
        CriteriaApplierService $criteriaApplierService
    ) {
        $this->criteriaApplierService  = $criteriaApplierService;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteria $subject
     * @param array                                 $orders
     *
     * @return array
     */
    public function beforeSetSortOrders($subject, $orders): array
    {
        if (!$this->criteriaApplierService->shouldAffectOrders()) {
            return [$orders];
        }

        $newOrders = $this->criteriaApplierService->prepareCriteria($orders);

        return [$newOrders];
    }
}
