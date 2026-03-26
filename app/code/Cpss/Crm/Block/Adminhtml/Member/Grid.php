<?php

namespace Cpss\Crm\Block\Adminhtml\Member;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    protected $itemFactory;

    protected $cpssInfo;

    protected $collectionFactory;


    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Sales\Model\Order\ItemFactory $itemFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Cpss\Crm\Block\Adminhtml\Member\Info $cpssInfo,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->itemFactory = $itemFactory;
        $this->moduleManager = $moduleManager;
        $this->cpssInfo = $cpssInfo;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setFilterVisibility(false);
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(false);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $options = $this->cpssInfo->getJobCodeOptions();

        $page = $this->_request->getParam("page") ? $this->_request->getParam("page") : 1;
        $limit = $this->_request->getParam("limit") ? $this->_request->getParam("limit") : 20;

        $collection = $this->collectionFactory->create();
        $pages = $this->cpssInfo->getHistoryPageTotal("point", $limit, "TMP");
        if (!empty($pages)) {
            $pageTotal = $pages['result']['pages'];
            $pointHistory = $this->cpssInfo->getPointHistory("point", $limit, $page);

            if (empty($pointHistory))
                return null;
    
            $result = $pointHistory['result'];
            $history = $result['history'];
    
            $collection->setLastPageNumber($pageTotal);
            foreach ($history as &$item) {
                $varienObject = new \Magento\Framework\DataObject();
    
                $item['operate'] = $this->cpssInfo->formatDateFromCpss($item['operate']);
                $item['point'] = $item['direction'] == "ADD" ? number_format($item['point']) : number_format(-$item['point']);
                $item['ope'] = $item['ope'] == "EXPR" ? __("Expired with out of date") : ($options[$item['scode']] ?? "");
    
                $item['operate'] = "<center>" . $item['operate'] . "</center>";
                $item['point'] = "<center>" . $item['point'] . "</center>";
                $item['ope'] = "<center>" . $item['ope'] . "</center>";
    
                if ($item['status'] == "CAN") {
                    $item['point'] = '<span style="text-decoration: line-through;">' . $item['point'] . '</span>';
                    $item['ope'] = '<span style="text-decoration: line-through;">' . $item['ope'] . '</span>';
                }
    
                $varienObject->setData($item);
                $collection->addItem($varienObject);
            }
    
            $collection->setPageSize(20);
            $collection->setCurPage(1);
        }
        

        $this->setCollection($collection);
        parent::_prepareCollection();

        return $this;
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'operate',
            [
                'header' => __('Date'),
                'index' => 'operate',
                'filter'    => false,
                'sortable'  => false,
                'renderer'  => '\Cpss\Crm\Block\Adminhtml\Member\Renderer'
            ]
        );
        $this->addColumn(
            'ope',
            [
                'header' => __('Reason'),
                'index' => 'ope',
                'filter'    => false,
                'sortable'  => false,
                'renderer'  => '\Cpss\Crm\Block\Adminhtml\Member\Renderer'
            ]
        );
        $this->addColumn(
            'point',
            [
                'header' => __('Points'),
                'index' => 'point',
                'filter'    => false,
                'sortable'  => false,
                'renderer'  => '\Cpss\Crm\Block\Adminhtml\Member\Renderer'
            ]
        );

        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            'customer/index/edit',
            [
                'id' => $this->_request->getParam('id')
            ]
        );
    }

    /**
     * get grid url
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return '';
    }
}
