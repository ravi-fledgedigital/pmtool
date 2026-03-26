<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Setup\Patch\Data;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Amasty\CustomTabs\Api\TabsRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class AddTabAnchors implements DataPatchInterface
{
    /**
     * @var string[]
     */
    private $tabsMap = [
        'product.info.description' => 'description',
        'product.attributes' => 'additional',
        'reviews.tab' => 'reviews'
    ];

    /**
     * @var TabsRepositoryInterface
     */
    private $tabsRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TabsRepositoryInterface $tabsRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        FilterBuilder $filterBuilder,
        LoggerInterface $logger
    ) {
        $this->tabsRepository = $tabsRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->logger = $logger;
    }

    /**
     * @return $this
     */
    public function apply(): self
    {
        foreach ($this->getTabsToUpdate() as $tab) {
            try {
                $tab->setTabAnchor($this->tabsMap[$tab->getNameInLayout()]);
                $this->tabsRepository->save($tab);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        return $this;
    }

    /**
     * @return TabsInterface[]
     */
    private function getTabsToUpdate(): array
    {
        $filterGroups = [];
        $filterNameInLayout = $this->filterBuilder
            ->setField(TabsInterface::NAME_IN_LAYOUT)
            ->setConditionType('in')
            ->setValue(array_keys($this->tabsMap))
            ->create();
        $filterGroups[] = $this->filterGroupBuilder->addFilter($filterNameInLayout)->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->setFilterGroups($filterGroups)
            ->create();

        return $this->tabsRepository->getList($searchCriteria)->getItems();
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
