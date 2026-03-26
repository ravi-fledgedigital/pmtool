<?php

namespace OnitsukaTigerKorea\Sales\Plugin\Block\Adminhtml\Order\View;

use Magento\Sales\Block\Adminhtml\Order\View\History as OrderHistory;
use OnitsukaTigerKorea\Sales\Helper\Data;
use Magento\Framework\Registry;
use Magento\Framework\App\RequestInterface;

/**
 * Class History
 * @package OnitsukaTigerKorea\Sales\Plugin\Block\Adminhtml\Order\View
 */
class History
{
    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @param Data $dataHelper
     * @param RequestInterface $request
     * @param Registry $coreRegistry
     */
    public function __construct(
        Data $dataHelper,
        RequestInterface $request,
        Registry $coreRegistry
    )
    {
        $this->dataHelper = $dataHelper;
        $this->request = $request;
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * @param OrderHistory $subject
     * @param $result
     * @return string
     */
    public function afterGetTemplate(OrderHistory $subject, $result) {
        if ($this->request->getFullActionName() == 'sales_order_view') {
            $storeId = $this->_coreRegistry->registry('current_order')->getStoreId();
            if ($this->dataHelper->isSalesEnabled($storeId)) {
                $subject->setTemplate('OnitsukaTigerKorea_Sales::order/view/history.phtml');
            }
        }
        return $result;
    }
}
