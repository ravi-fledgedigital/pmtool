<?php

namespace OnitsukaTiger\Sales\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class View extends \Magento\SalesArchive\Controller\Adminhtml\Order\View
{
public function __construct(
    \Magento\Backend\App\Action\Context $context,
    \Magento\Framework\Registry $coreRegistry,
    \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
    \Magento\Framework\Translate\InlineInterface $translateInline,
    \Magento\Framework\View\Result\PageFactory $resultPageFactory,
    \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
    \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
    \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
    OrderManagementInterface $orderManagement,
    OrderRepositoryInterface $orderRepository,
    LoggerInterface $logger,
    \Magento\SalesArchive\Model\Archive $archiveModel
   ) {
    parent::__construct(
        $context,
        $coreRegistry,
        $fileFactory,
        $translateInline,
        $resultPageFactory,
        $resultJsonFactory,
        $resultLayoutFactory,
        $resultRawFactory,
        $orderManagement,
        $orderRepository,
        $logger,
        $archiveModel
    );
   }

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::actions_view';

    /**
     * View order detail
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $order = $this->_initOrder();
        $this->_session->setData("store_id", $order->getStoreId());
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($order) {
            try {
                $resultPage = $this->_initAction();
                $resultPage->getConfig()->getTitle()->prepend(__('Orders'));
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addErrorMessage(__('Exception occurred during order load'));
                $resultRedirect->setPath('sales/order/index');
                return $resultRedirect;
            }
            $resultPage->getConfig()->getTitle()->prepend(sprintf("#%s", $order->getIncrementId()));
            return $resultPage;
        }
        $resultRedirect->setPath('sales/*/');
        return $resultRedirect;
    }
}
