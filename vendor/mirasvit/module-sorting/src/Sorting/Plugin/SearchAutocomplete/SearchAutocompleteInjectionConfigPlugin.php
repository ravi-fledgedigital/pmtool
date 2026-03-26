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

namespace Mirasvit\Sorting\Plugin\SearchAutocomplete;

use Mirasvit\SearchAutocomplete\Block\Injection as Subject;
use Mirasvit\Sorting\Service\AutocompleteSortingService;

class SearchAutocompleteInjectionConfigPlugin
{
    private AutocompleteSortingService $autocompleteSortingService;

    public function __construct(
        AutocompleteSortingService $autocompleteSortingService
    ) {
        $this->autocompleteSortingService = $autocompleteSortingService;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetAvailableOrders(Subject $subject, callable $proceed): array
    {
        if (!$this->autocompleteSortingService->isAutocompleteFastMode()) {
            return $proceed();
        }

        return $this->autocompleteSortingService->getAutocompleteSortingOptions();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetDefaultOrder(Subject $subject, callable $proceed): string
    {
        if (!$this->autocompleteSortingService->isAutocompleteFastMode()) {
            return $proceed();
        }
        $defaultSorting = $this->autocompleteSortingService->getAutocompleteDefaultSorting();

        return $defaultSorting['code'];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetDefaultDirection(Subject $subject, callable $proceed): string
    {
        if (!$this->autocompleteSortingService->isAutocompleteFastMode()) {
            return $proceed();
        }

        $defaultSorting = $this->autocompleteSortingService->getAutocompleteDefaultSorting();

        return $defaultSorting['direction'] ?? 'desc';
    }
}
