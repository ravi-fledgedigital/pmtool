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

namespace Mirasvit\Sorting\Service\Autocomplete\Provider;

use Mirasvit\Sorting\Api\Data\CriterionInterface;
use Mirasvit\Sorting\Service\Autocomplete\CriterionFieldResolverComposite;
use Mirasvit\Sorting\Repository\CriterionRepository;

class SortingOptionsProvider
{
    private CriterionRepository             $criterionRepository;

    private CriterionFieldResolverComposite $criterionFieldResolverComposite;

    public function __construct(
        CriterionRepository             $criterionRepository,
        CriterionFieldResolverComposite $criterionFieldResolverComposite
    ) {
        $this->criterionRepository             = $criterionRepository;
        $this->criterionFieldResolverComposite = $criterionFieldResolverComposite;
    }

    public function execute(): array
    {
        $options = [];
        
        $collection = $this->criterionRepository->getCollection();
        $collection->addFieldToFilter(CriterionInterface::IS_ACTIVE, 1)
            ->setOrder(CriterionInterface::IS_SEARCH_DEFAULT, 'desc')
            ->setOrder(CriterionInterface::POSITION, 'asc');

        foreach ($collection as $criterion) {
            $sortingFieldInfo = $this->criterionFieldResolverComposite->resolve($criterion);
            if (null === $sortingFieldInfo) {
                continue;
            }

            $code           = $criterion->getCode();
            $options[$code] = [
                'name'          => $criterion->getName(),
                'code'          => $code,
                'sorting_field' => $sortingFieldInfo,
            ];
        }

        return $options;
    }
}
