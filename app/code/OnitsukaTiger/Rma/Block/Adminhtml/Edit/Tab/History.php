<?php
namespace OnitsukaTiger\Rma\Block\Adminhtml\Edit\Tab;

use Amasty\Rma\Api\Data\StatusInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Amasty\Rma\Model\Request\ResourceModel\Grid\Collection;
use Magento\Framework\App\RequestInterface as AppRequest;
use Magento\Framework\Registry;
use Magento\Backend\Block\Widget\Grid\Extended;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Model\Status\OptionSource\Status;
use Amasty\Rma\Model\Status\ResourceModel\Status as ResourceModelStatus;
use Amasty\Rma\Model\OptionSource\State as ResourceModelState;
use Amasty\Rma\Model\OptionSource\Manager as ResourceModelManager;
/**
 * Adminhtml customer orders grid block
 *
 * @api
 * @since 100.0.2
 */
class History extends Extended
{

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * @var Collection
     */
    protected $collectionFactory;

    /**
     * @var AppRequest
     */
    protected $request;

    /**
     * @var ResourceModelStatus
     */
    protected $status;

    /**
     * @var ResourceModelState
     */
    protected $resourceState;
    /**
     * @var ResourceModelManager
     */
    protected $resourceManager;

    public function __construct(
        Context $context,
        Data $backendHelper,
        Collection $collection,
        Registry $coreRegistry,
        AppRequest $request,
        Status $status,
        ResourceModelState $resourceState,
        ResourceModelManager $resourceManager,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->collectionFactory = $collection;
        $this->request = $request;
        $this->status = $status;
        $this->resourceState = $resourceState;
        $this->resourceManager = $resourceManager;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('customer_orders_history_rma_grid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('desc');
        $this->setUseAjax(true);
    }

    /**
     * Apply various selection filters to prepare the sales order grid collection.
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $customerId = $this->request->getParam('id');
        $collection = $this->collectionFactory
            ->addFieldToFilter('main_table.customer_id',$customerId);

        $collection->join(
            'sales_order',
            'main_table.' . \Amasty\Rma\Api\Data\RequestInterface::ORDER_ID . ' = sales_order.entity_id',
            [
                'sales_order.increment_id',
            ]
        )->join(
            ['st' => $collection->getTable(ResourceModelStatus::TABLE_NAME)],
            'main_table.' . RequestInterface::STATUS . ' = st.' . StatusInterface::STATUS_ID,
            [
                'st.' . StatusInterface::STATE,
            ]
        );
        $collection->addFilterToMap('status','main_table.status');
        $collection->addFilterToMap('state','st.state');
        $collection->addFilterToMap('store_id','main_table.store_id');
        $collection->addFilterToMap('created_at','main_table.created_at');
        $collection->addFilterToMap('manager_id','main_table.manager_id');

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @inheritdoc
     */
    protected function _prepareColumns()
    {
        $this->addColumn('request_id', [
            'header' => __('ID'),
            'index' => 'request_id'
        ]);
        $this->addColumn('store_id', [
            'header' => __('Store'),
            'index' => 'store_id',
            'type' => 'store',
            'sortable' => true,
        ]);
        $this->addColumn('increment_id', [
            'header' => __('Order #'),
            'index' => 'increment_id',
        ]);
        $this->addColumn('created_at', [
            'header' => __('Request Date'),
            'type' => 'datetime',
            'index' => 'created_at',
        ]);
        $this->addColumn('manager_id', [
            'header' => __('Manager'),
            'index' => 'manager_id',
            'type' => 'options',
            'options' => $this->resourceManager->toArray(),
            'renderer' => \OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer\Manager::class
        ]);
        $this->addColumn('status', [
            'header' => __('Status'),
            'index' => 'status',
            'type' => 'options',
            'options' => $this->status->toArray(),
            'renderer' => \OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer\Status::class,
        ]);
        $this->addColumn('state', [
            'header' => __('State'),
            'index' => 'state',
            'type' => 'options',
            'options' => $this->resourceState->toArray(),
            'renderer' => \OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer\State::class
        ]);
        $this->addColumn(
            'action',
            [
                'header' =>  __('Action'),
                'filter' => false,
                'sortable' => false,
                'width' => '100px',
                'renderer' => \OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer\Action::class
            ]
        );
        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl(
            'amrma/request/view',
            ['request_id' => $row->getData('request_id')]
        );
    }

    /**
     * @inheritdoc
     */
    public function getGridUrl()
    {
        return $this->getUrl('amrma_cancel/customer/history', ['_current' => true]);
    }
}
