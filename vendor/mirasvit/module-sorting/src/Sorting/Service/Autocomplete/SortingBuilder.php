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

namespace Mirasvit\Sorting\Service\Autocomplete;

use Mirasvit\Sorting\Service\Autocomplete\Provider\DefaultAdvancedSortingOptionProvider;
use Mirasvit\Sorting\Service\Autocomplete\Provider\DefaultSortingOptionProvider;
use Mirasvit\Sorting\Service\Autocomplete\Provider\SortingOptionsProvider;

class SortingBuilder implements SortingBuilderInterface
{
    private array                                $sortingOptions;

    private DefaultAdvancedSortingOptionProvider $defaultAdvancedSortingOptionProvider;

    private DefaultSortingOptionProvider         $defaultSortingOptionProvider;

    private SortingOptionsProvider               $sortingOptionsProvider;

    public function __construct(
        DefaultAdvancedSortingOptionProvider $defaultAdvancedSortingOptionProvider,
        DefaultSortingOptionProvider         $defaultSortingOptionProvider,
        SortingOptionsProvider               $sortingOptionsProvider
    ) {
        $this->defaultAdvancedSortingOptionProvider = $defaultAdvancedSortingOptionProvider;
        $this->defaultSortingOptionProvider         = $defaultSortingOptionProvider;
        $this->sortingOptionsProvider               = $sortingOptionsProvider;
        $this->reset();
    }

    public function reset(): void
    {
        $this->sortingOptions = [];
    }

    public function setDefaultAdvancedSortingOption(): void
    {
        $sortingOption = $this->defaultAdvancedSortingOptionProvider->execute();
        if (null === $sortingOption) {
            return;
        }

        $this->sortingOptions[$sortingOption['code']] = $this->processSortingOption($sortingOption);
    }

    public function setSortingOptions(): void
    {
        $sortingOptions = $this->sortingOptionsProvider->execute();
        foreach ($sortingOptions as $sortingOption) {
            $this->sortingOptions[$sortingOption['code']] = $sortingOption;
        }
    }

    public function setDefaultBasicSortingOption(): void
    {
        $sortingOption                                = $this->defaultSortingOptionProvider->execute();
        $this->sortingOptions[$sortingOption['code']] = $this->processSortingOption($sortingOption);
    }

    public function getSortingOptions(): array
    {
        return $this->sortingOptions;
    }

    /**
     * @param array<string> $sortingOption
     *
     * @return array<string>
     */
    private function processSortingOption(array $sortingOption): array
    {
        $sortingOption['sorting_field'] = [
            'order'     => $sortingOption['order'],
            'direction' => $sortingOption['direction'],
        ];

        return $sortingOption;
    }
}
