<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Tabs\Loader;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Amasty\CustomTabs\Api\TabsRepositoryInterface;
use Amasty\CustomTabs\Model\Source\Type;
use Amasty\CustomTabs\Model\Source\Status;
use Amasty\CustomTabs\Model\Tabs\TabsFactory;
use Magento\Customer\Ui\Component\Listing\Column\Group\Options as CustomerOptions;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\LayoutInterface;

class SaveHandler
{
    public const TABS_NAME_IN_LAYOUT = 'product.info.details';

    public const DEFAULT_CONTENT_VARIABLE = '{{default_tab_content}}';
    public const DEFAULT_TITLE_VARIABLE = '{{default_tab_title}}';

    /**
     * @var array
     */
    private $importFields = [
        TabsInterface::TAB_TITLE => 'title',
        TabsInterface::SORT_ORDER => 'sort_order'
    ];

    /**
     * @var TabsRepositoryInterface
     */
    private $tabsRepository;

    /**
     * @var \Amasty\CustomTabs\Model\Tabs\TabsFactory
     */
    private $tabsFactory;

    /**
     * @var string
     */
    private $customerGroups;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        TabsRepositoryInterface $tabsRepository,
        TabsFactory $tabsFactory,
        CustomerOptions $customerOptions,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->tabsRepository = $tabsRepository;
        $this->tabsFactory = $tabsFactory;
        $this->customerGroups = implode(
            ',',
            array_keys($customerOptions->toOptionArray())
        );
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param LayoutInterface $layout
     * @param int $storeId
     */
    public function execute($layout, $storeId, $storeIds)
    {
        $this->saveDefaultTabs($layout, $storeId, $storeIds);
    }

    /**
     * @param LayoutInterface $layout
     * @param int $storeId
     */
    private function saveDefaultTabs($layout, $storeId, $storeIds)
    {
        foreach ($layout->getChildNames(self::TABS_NAME_IN_LAYOUT) as $tabName) {
            if (strpos($tabName, \Amasty\CustomTabs\Block\Product\View\ProductTab::NAME_IN_LAYOUT) !== false) {
                continue;
            }

            $tabData = [
                TabsInterface::TAB_NAME => $tabName,
                TabsInterface::NAME_IN_LAYOUT => $tabName,
                TabsInterface::CONTENT => self::DEFAULT_CONTENT_VARIABLE,
                TabsInterface::STATUS => Status::ENABLED,
                TabsInterface::CUSTOMER_GROUPS => $this->customerGroups,
                TabsInterface::TAB_TITLE => self::DEFAULT_TITLE_VARIABLE,
                TabsInterface::TAB_ANCHOR => $layout->getElementAlias($tabName)
            ];

            $tabConfig = $layout->getReaderContext()->getScheduledStructure()->getElement($tabName);
            $tabData = $this->retrieveImportFields($tabData, $tabConfig);
            $tabData = $this->retrieveTypeInfo($tabData, $tabConfig);

            $this->saveTab($tabData, $storeIds);
        }
    }

    /**
     * @param array $tabData
     * @param array $tabConfig
     *
     * @return array
     */
    private function retrieveImportFields($tabData, $tabConfig)
    {
        if (isset($tabConfig[1]['arguments'])) {
            foreach ($this->importFields as $tabField => $field) {
                if (isset($tabConfig[1]['arguments'][$field]) && is_scalar($tabConfig[1]['arguments'][$field])) {
                    $tabData[$tabField] = $tabConfig[1]['arguments'][$field];
                }
            }
        }

        return $tabData;
    }

    /**
     * @param array $tabData
     * @param array $tabConfig
     *
     * @return array
     */
    private function retrieveTypeInfo($tabData, $tabConfig)
    {
        if (!empty($tabConfig[1]['attributes']['class'])) {
            $classPath = explode('\\', $tabConfig[1]['attributes']['class']);
            $tabData[TabsInterface::TAB_TYPE] = $classPath[0] === 'Magento'
                ? Type::MAGENTO
                : Type::ANOTHER_MODULES;
            $tabData[TabsInterface::MODULE_NAME] = $classPath[0] . '_' . $classPath[1];
        }

        return $tabData;
    }

    /**
     * @param array $tabData
     * @param int $storeId
     *
     * @return int
     */
    private function saveTab($tabData, $storeIds)
    {
        $tab = $this->loadTab($tabData[TabsInterface::NAME_IN_LAYOUT]);
        if (!$tab->getTabId()) {
            $tab->addData($tabData);
            $tab->addStores($storeIds);
            $this->tabsRepository->save($tab);
        }

        return $tab->getTabId();
    }

    /**
     * @param string $nameInLayout
     *
     * @return TabsInterface
     */
    private function loadTab($nameInLayout)
    {
        $this->searchCriteriaBuilder->addFilter(TabsInterface::NAME_IN_LAYOUT, $nameInLayout);
        $tabs = $this->tabsRepository->getList($this->searchCriteriaBuilder->create());
        if ($tabs->getTotalCount()) {
            $tab = $tabs->getItems()[0];
        } else {
            $tab = $this->tabsFactory->create();
        }

        return $tab;
    }
}
