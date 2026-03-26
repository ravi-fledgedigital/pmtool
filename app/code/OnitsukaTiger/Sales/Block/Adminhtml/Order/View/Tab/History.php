<?php
namespace OnitsukaTiger\Sales\Block\Adminhtml\Order\View\Tab;

class History extends \Magento\Sales\Block\Adminhtml\Order\View\Tab\History
{

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $data
        );
    }

    public function getFullHistory()
    {
        $order = $this->getOrder();

        $history = [];
        foreach ($order->getAllStatusHistory() as $orderComment) {
            $history[] = $this->_prepareHistoryItem(
                $orderComment->getStatusLabel(),
                $orderComment->getIsCustomerNotified(),
                $this->getOrderAdminDate($orderComment->getCreatedAt()),
                $orderComment->getComment()
            );
        }

        foreach ($order->getCreditmemosCollection() as $_memo) {
            $history[] = $this->_prepareHistoryItem(
                __('Credit memo #%1 created', $_memo->getIncrementId()),
                $_memo->getEmailSent(),
                $this->getOrderAdminDate($_memo->getCreatedAt())
            );

            foreach ($_memo->getCommentsCollection() as $_comment) {
                $history[] = $this->_prepareHistoryItem(
                    __('Credit memo #%1 comment added', $_memo->getIncrementId()),
                    $_comment->getIsCustomerNotified(),
                    $this->getOrderAdminDate($_comment->getCreatedAt()),
                    $_comment->getComment()
                );
            }
        }

        foreach ($order->getShipmentsCollection() as $_shipment) {
            $history[] = $this->_prepareHistoryItem(
                __('Shipment #%1 created', $_shipment->getIncrementId()),
                $_shipment->getEmailSent(),
                $this->getOrderAdminDate($_shipment->getCreatedAt())
            );

            foreach ($_shipment->getCommentsCollection() as $_comment) {
                $history[] = $this->_prepareHistoryItem(
                    __('Shipment #%1 comment added', $_shipment->getIncrementId()),
                    $_comment->getIsCustomerNotified(),
                    $this->getOrderAdminDate($_comment->getCreatedAt()),
                    $_comment->getComment()
                );
            }
        }

        foreach ($order->getInvoiceCollection() as $_invoice) {
            $history[] = $this->_prepareHistoryItem(
                __('Invoice #%1 created', $_invoice->getIncrementId()),
                $_invoice->getEmailSent(),
                $this->getOrderAdminDate($_invoice->getData('created_at'))
            );

            foreach ($_invoice->getCommentsCollection() as $_comment) {
                $history[] = $this->_prepareHistoryItem(
                    __('Invoice #%1 comment added', $_invoice->getIncrementId()),
                    $_comment->getIsCustomerNotified(),
                    $this->getOrderAdminDate($_comment->getData('created_at')),
                    $_comment->getComment()
                );
            }
        }

        foreach ($order->getTracksCollection() as $_track) {
            $history[] = $this->_prepareHistoryItem(
                __('Tracking number %1 for %2 assigned', $_track->getNumber(), $_track->getTitle()),
                false,
                $this->getOrderAdminDate($_track->getCreatedAt())
            );
        }

        usort($history, [__CLASS__, 'sortHistoryByTimestamp']);
        return $history;
    }
}
