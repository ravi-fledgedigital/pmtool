<?php
namespace OnitsukaTiger\NetSuiteStoreShipping\Model\Request\DataProvider;

use Amasty\Base\Helper\Module as AmastyModules;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\StatusInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Model\OptionSource\Grid;
use Amasty\Rma\Model\Request\ResourceModel\Grid\Collection;
use Amasty\Rma\Model\Request\ResourceModel\Grid\CollectionFactory;
use Amasty\Rma\Model\Status\ResourceModel\CollectionFactory as StatusCollectionFactory;
use Magento\Backend\Model\Session;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface as AppRequest;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class Listing extends AbstractDataProvider {

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
    protected $isShippingShop;

    protected $managerCollectionFactory;


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
        \Magento\User\Model\ResourceModel\User\CollectionFactory $managerCollectionFactory,
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
            $this->isShippingShop = false;
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

        $user = $managerCollectionFactory->create()->addFieldToFilter('username', $request->getParam('manager_code'))->getFirstItem();
        $this->collection->addFieldToFilter(RequestInterface::MANAGER_ID, $user->getId());

        foreach ($statusCollection->getData() as $status) {
            $statusIds[] = (int)$status[StatusInterface::STATUS_ID];
            $this->statusColor[$status[StatusInterface::STATUS_ID]] = $status[StatusInterface::COLOR];
        }

        //TODO
        if (empty($statusIds)) {
            $statusIds[] = 9999999999999;
        }

        $this->collection->addFieldToFilter('main_table.' . RequestInterface::STATUS, ['in' => $statusIds]);
        $this->collection->addFilterToMap('increment_id', 'sales_order.increment_id')
                         ->addFilterToMap('created_at', 'main_table.created_at')
                         ->addFilterToMap('status', 'main_table.status');

        //TODO split database
        $this->collection->join(
            'sales_order',
            'main_table.' . RequestInterface::ORDER_ID . ' = sales_order.entity_id',
            [
                'sales_order.increment_id',
            ]
        )->join(
            ['st' => $this->collection->getTable(\Amasty\Rma\Model\Status\ResourceModel\Status::TABLE_NAME)],
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

    public function getMeta()
    {
        $meta = parent::getMeta();

        if (!$this->isShippingShop) {
            return $meta;
        }
    }
}
