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
use Mirasvit\Sorting\Service\Autocomplete\Provider\DefaultSortingOptionProvider;
use Mirasvit\Sorting\Repository\CriterionRepository;

class DefaultAdvancedSortingOptionProvider
{
    private CriterionRepository             $criterionRepository;

    private DefaultSortingOptionProvider    $defaultSortingOptionProvider;

    private CriterionFieldResolverComposite $criterionFieldResolverComposite;

    public function __construct(
        CriterionRepository             $criterionRepository,
        CriterionFieldResolverComposite $criterionFieldResolverComposite,
        DefaultSortingOptionProvider    $defaultSortingOptionProvider
    ) {
        $this->criterionRepository             = $criterionRepository;
        $this->criterionFieldResolverComposite = $criterionFieldResolverComposite;
        $this->defaultSortingOptionProvider    = $defaultSortingOptionProvider;
    }

    /**
     * @return array<string>|null
     */
    public function execute(): ?array
    {
        $collection = $this->criterionRepository->getCollection();
        $collection->addFieldToFilter(CriterionInterface::IS_ACTIVE, 1)
            ->addFieldToFilter(CriterionInterface::IS_SEARCH_DEFAULT, 1)
            ->setOrder(CriterionInterface::IS_SEARCH_DEFAULT, 'desc')
            ->setOrder(CriterionInterface::POSITION, 'asc')
            ->getSelect()->limit(1);

        /* @var CriterionInterface $criterion */
        $criterion = $collection->getFirstItem();
        if (null === $criterion->getCode()) {
            return $this->defaultSortingOptionProvider->execute();
        }

        $result         = $this->criterionFieldResolverComposite->resolve($criterion);
        $result['name'] = $criterion->getName();
        $result['code'] = $criterion->getCode();

        return $result;
    }
}
