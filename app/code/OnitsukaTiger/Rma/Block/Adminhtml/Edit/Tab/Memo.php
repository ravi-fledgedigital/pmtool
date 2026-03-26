<?php
namespace OnitsukaTiger\Rma\Block\Adminhtml\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Model\Order\ConfigFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\History\Collection as Collection;
use Magento\Framework\App\RequestInterface as AppRequest;
use Magento\Framework\Registry;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Adminhtml customer memo grid block
 *
 * @api
 * @since 100.0.2
 */
class Memo extends Extended
{
    const IS_AMIN = 'is_admin';
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * @var AppRequest
     */
    protected $request;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

     /**
     * Config factory
     *
     * @var ConfigFactory
     */
    protected $configFactory;

    public function __construct(
        Context $context,
        Data $backendHelper,
        Collection $collection,
        Registry $coreRegistry,
        AppRequest $request,
        CustomerRepositoryInterface $customerRepository,
        ConfigFactory $configFactory,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->_collection = $collection;
        $this->request = $request;
        $this->customerRepository = $customerRepository;
        $this->configFactory = $configFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('customer_orders_history_memo_grid');
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
        $customer = $this->customerRepository->getById($customerId);

        $this->_collection
             ->join(
                 'sales_order',
                 'main_table.' .OrderStatusHistoryInterface::PARENT_ID. ' = sales_order.entity_id',
                 [
                     'sales_order.increment_id',
                     'sales_order.customer_id',
                 ])
            ->addFieldToFilter(self::IS_AMIN,true)
            ->addFieldToFilter(OrderStatusHistoryInterface::ENTITY_NAME,'order')
            ->addFieldToFilter(
                ['sales_order.customer_id', 'sales_order.customer_email'],
                [
                    ['eq' => $customerId],
                    ['eq' =>  $customer->getEmail()]
                ])
            ->addFilterToMap('status','main_table.status')
            ->addFilterToMap('created_at','main_table.created_at');

        return parent::_prepareCollection();
    }

    /**
     * @inheritdoc
     */
    protected function _prepareColumns()
    {

        $this->addColumn('increment_id', [
            'header' => __('Order #'),
            'index' => 'increment_id',
        ]);

        $this->addColumn('comment', [
            'header' => __('Comment'),
            'sortable' => false,
            'index' => 'comment',
            'renderer' => \OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer\Comment::class,
        ]);
        $this->addColumn('status', [
            'header' => __('Status'),
            'index' => 'status',
            'type' => 'options',
            'options' => $this->configFactory->create()->getStatuses(),
            'renderer' => \OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer\OrderStatus::class,
        ]);
        $this->addColumn('created_at', [
            'header' => __('Created At'),
            'type' =>'datetime',
            'index' => 'created_at'
        ]);
        $this->addColumn(
            'action',
            [
                'header' =>  __('Action'),
                'filter' => false,
                'sortable' => false,
                'width' => '100px',
                'renderer' => \OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer\OrderAction::class
            ]
        );
        return parent::_prepareColumns();
    }
    public function getRowUrl($row)
    {
        return null;
    }
    /**
     * @inheritdoc
     */
    public function getGridUrl()
    {
        return $this->getUrl('amrma_cancel/customer/memo', ['_current' => true]);
    }

}
