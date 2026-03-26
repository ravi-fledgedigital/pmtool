<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request\DataProvider;

use Amasty\Base\Helper\Module as AmastyModules;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\StatusInterface;
use Amasty\Rma\Model\OptionSource\Grid;
use Amasty\Rma\Model\Request\ResourceModel\Grid\Collection;
use Amasty\Rma\Model\Request\ResourceModel\Grid\CollectionFactory;
use Amasty\Rma\Model\Status\ResourceModel\CollectionFactory as StatusCollectionFactory;
use Amasty\Rma\Model\Status\ResourceModel\Status;
use Magento\Backend\Model\Session;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface as AppRequest;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class Listing extends AbstractDataProvider
{
    public const MODULE_REPORTS = 'Amasty_RmaReports';
    public const MODULE_RULES = 'Amasty_RmaAutomation';
    public const MODULE_LABELS = 'Amasty_RmaAutomaticShippingLabel';
    public const UTM_REPORTS = '?utm_source=extension&utm_medium=backend&utm_campaign=manage_returns_reports_for_rma';
    public const UTM_RULES =
        '?utm_source=extension&utm_medium=backend&utm_campaign=manage_returns_automation_rules_for_rma';
    public const UTM_LABELS = '?utm_source=extension&utm_medium=backend&utm_campaign=manage_shipping_labels_for_rma';
    /**#@-*/

    /**
     * @var array
     */
    public $statusColor;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var SearchCriteria
     */
    private $searchCriteria;

    /**
     * @var AmastyModules
     */
    private $amastyModules;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var bool
     */
    private $isManageGrid;

    public function __construct(
        CollectionFactory $collectionFactory,
        AppRequest $request,
        StatusCollectionFactory $statusCollectionFactory,
        Session $session,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AmastyModules $amastyModules,
        ModuleListInterface $moduleList,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->searchCriteria = $searchCriteriaBuilder->create()->setRequestName($name);
        $this->collection = $collectionFactory->create()->setSearchCriteria($this->searchCriteria)->addLeadTime();
        $statusIds = [];
        $statusCollection = $statusCollectionFactory->create();
        $statusCollection->addFieldToSelect(StatusInterface::STATUS_ID)
            ->addFieldToSelect(StatusInterface::COLOR);

        if ($request->getActionName() === 'manage') {
            $this->isManageGrid = true;
        }

        switch ($request->getParam('grid', 'pending')) {
            case 'pending':
                $statusCollection->addFieldToFilter(StatusInterface::GRID, Grid::PENDING);
                break;
            case 'archive':
                $statusCollection->addFieldToFilter(StatusInterface::GRID, Grid::ARCHIVED);
                break;
            case 'manage':
                $statusCollection->addFieldToFilter(StatusInterface::GRID, Grid::MANAGE);
                break;
            case 'order_view':
                $orderId = (int) $request->getParam(RequestInterface::ORDER_ID);
                $this->collection->addFieldToFilter(RequestInterface::ORDER_ID, $orderId);
                break;
        }

        foreach ($statusCollection->getData() as $status) {
            $statusIds[] = (int)$status[StatusInterface::STATUS_ID];
            $this->statusColor[$status[StatusInterface::STATUS_ID]] = $status[StatusInterface::COLOR];
        }

        //TODO
        if (empty($statusIds)) {
            $statusIds[] = 9999999999999;
        }

        $this->collection->addFieldToFilter('main_table.' . RequestInterface::STATUS, ['in' => $statusIds]);
        //TODO split database
        $this->collection->join(
            'sales_order',
            'main_table.' . RequestInterface::ORDER_ID . ' = sales_order.entity_id',
            [
                'order_increment_id' => 'sales_order.increment_id'
            ]
        )->join(
            ['st' => $this->collection->getTable(Status::TABLE_NAME)],
            'main_table.' . RequestInterface::STATUS . ' = st.' . StatusInterface::STATUS_ID,
            [
                'st.' . StatusInterface::STATE
            ]
        );

        $data['config']['params']['order_id'] = $request->getParam(RequestInterface::ORDER_ID);

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->session = $session;
        $this->session->setAmRmaReturnUrl(null);
        $this->session->setAmRmaOriginalGrid(null);
        $this->amastyModules = $amastyModules;
        $this->moduleList = $moduleList;
    }

    /**
     * @inheritdoc
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        switch ($filter->getField()) {
            case RequestInterface::STATUS:
                $filter->setField('main_table.' . RequestInterface::STATUS);
                break;
            case RequestInterface::CREATED_AT:
                $filter->setField('main_table.' . RequestInterface::CREATED_AT);
                break;
            case 'days':
                $filter->setField(new \Zend_Db_Expr(Collection::DAYS_EXPRESSION));
                break;
            case 'increment_id':
                $filter->setField('main_table.' . RequestInterface::INCREMENT_ID);
                break;
            case 'order_increment_id':
                $filter->setField('sales_order.' . RequestInterface::INCREMENT_ID);
                break;
        }

        // @phpstan-ignore-next-line as adding return statement cause of backward compatibility issue
        parent::addFilter($filter);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data = parent::getData();
        foreach ($data['items'] as &$item) {
            $item['order_increment_id'] = '#' . $item['order_increment_id'];
            $item['status_color'] = $this->statusColor[$item[RequestInterface::STATUS]];
            if ($item[RequestInterface::RATING] > 0) {
                $item['rating'] = $item[RequestInterface::RATING] . '/5';
            } else {
                $item['rating'] = '';
            }
        }

        return $data;
    }

    /**
     * Returns search criteria
     *
     * @return \Magento\Framework\Api\Search\SearchCriteria
     */
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }

    public function getMeta()
    {
        $meta = parent::getMeta();
        if (!$this->isManageGrid) {
            return $meta;
        }

        $addons = [self::MODULE_REPORTS, self::MODULE_RULES, self::MODULE_LABELS];
        $allExtensions = $this->amastyModules->getAllExtensions();
        $moduleNames = $this->moduleList->getNames();
        $modulesData = [];
        $guideUrls = [
            'Amasty_RmaReports' => 'https://amasty.com/docs/doku.php?id=magento_2:rma#rma_reports_add-on',
            'Amasty_RmaAutomation' => 'https://amasty.com/docs/doku.php?id=magento_2:rma#rma_automation_rules_add-on',
            'Amasty_RmaAutomaticShippingLabel' => 'https://amasty.com/docs/doku.php'
                . '?id=magento_2:rma#rma_shipping_labels_add-on'
        ];

        foreach ($addons as $name) {
            if (!in_array($name, $moduleNames) && !empty($allExtensions[$name])) {
                if ($this->amastyModules->isOriginMarketplace()) {
                    $url = $guideUrls[$name];
                } else {
                    $url = end($allExtensions[$name])['url'];
                }

                switch ($name) {
                    case self::MODULE_REPORTS:
                        $modulesData[] = [
                            'url' => $this->amastyModules->isOriginMarketplace() ? $url :$url . self::UTM_REPORTS ,
                            'name' => __('Detailed Reports'),
                            'info' => __('Analyze the RMA effectiveness with'),
                            'class' => '-reports'
                        ];
                        break;
                    case self::MODULE_RULES:
                        $modulesData[] = [
                            'url' => $this->amastyModules->isOriginMarketplace() ? $url : $url . self::UTM_RULES,
                            'name' => __('Automation Rules'),
                            'info' => __('Speed up the processing of RMA requests with'),
                            'class' => '-rules'
                        ];
                        break;
                    case self::MODULE_LABELS:
                        $modulesData[] = [
                            'url' => $this->amastyModules->isOriginMarketplace() ? $url : $url . self::UTM_LABELS,
                            'name' => __('Shipping Labels for RMA'),
                            'info' => __('Minimize time spent on shipping labels creation with'),
                            'class' => '-labels'
                        ];
                        break;
                }
            }
        }

        if (empty($modulesData)) {
            return $meta;
        }

        $meta = [
            'rma_addons' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'sortOrder' => 1,
                            'componentType' => 'container',
                            'addAllowed' => true,
                            'isAdmin' => true,
                            'compoenent' => 'Amasty_Rma/js/grid/addons',
                            'template' => 'Amasty_Rma/grid/amrma-addons',
                            'visible' => true,
                            'modulesData' => $modulesData
                        ]
                    ]
                ]
            ]
        ];

        return $meta;
    }
}
