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

use Magento\Framework\App\ObjectManager;
use Mirasvit\Sorting\Service\Autocomplete\Provider\DefaultSortingOptionProvider;
use Mirasvit\Sorting\Service\Autocomplete\SortingBuilder;
use Mirasvit\Sorting\Service\Autocomplete\Provider\DefaultAdvancedSortingOptionProvider;

class AutocompleteSortingService
{
    private SortingBuilder                       $sortingBuilder;

    private DefaultAdvancedSortingOptionProvider $defaultAdvancedSortingOptionProvider;

    public function __construct(
        SortingBuilder                       $sortingBuilder,
        DefaultAdvancedSortingOptionProvider $defaultAdvancedSortingOptionProvider
    ) {
        $this->sortingBuilder                       = $sortingBuilder;
        $this->defaultAdvancedSortingOptionProvider = $defaultAdvancedSortingOptionProvider;
    }

    public function getAutocompleteSortingOptions(): ?array
    {
        if (!$this->isAutocompleteFastMode()) {
            return null;
        }

        $builder = $this->sortingBuilder;
        $builder->reset();
        $builder->setDefaultAdvancedSortingOption();
        $builder->setSortingOptions();
        $builder->setDefaultBasicSortingOption();

        return $builder->getSortingOptions();
    }

    public function getAutocompleteDefaultSorting(): array
    {
        return $this->defaultAdvancedSortingOptionProvider->execute();
    }

    public function isAutocompleteFastMode(): bool
    {
        $fastModeConfigServiceClass = 'Mirasvit\SearchAutocomplete\Service\FastModeConfigService';
        if (!class_exists($fastModeConfigServiceClass)) {
            return false;
        }

        $fastModeConfigService = ObjectManager::getInstance()->create($fastModeConfigServiceClass);

        return $fastModeConfigService->isAutocompleteFastMode();
    }
}
