<?php

namespace OnitsukaTiger\Rma\Block\Adminhtml\Buttons\Request;

use Amasty\Rma\Model\Request\Repository;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use OnitsukaTiger\Rma\Helper\Data;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;

class CreditMemoButton extends \Amasty\Rma\Block\Adminhtml\Buttons\Request\CreditMemoButton
{
    /**
     * @var Data
     */
    protected $rmaHelperData;

    /**
     * @var InvoiceCollectionFactory
     */
    protected $invoiceCollectionFactory;

    /**
     * @var InvoiceCollectionFactory
     */
    protected $orderItemCollectionFactory;

    /**
     * CreditMemoButton constructor.
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param Repository $requestRepository
     * @param Data $rmaHelperData
     * @param InvoiceCollectionFactory $invoiceCollectionFactory
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        Repository $requestRepository,
        Data $rmaHelperData,
        InvoiceCollectionFactory $invoiceCollectionFactory,
        OrderItemCollectionFactory $orderItemCollectionFactory
    )
    {
        $this->rmaHelperData = $rmaHelperData;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        parent::__construct($context, $orderRepository, $requestRepository);
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getButtonData()
    {
        $data = [];
        $request = $this->requestRepository->getById($this->getRequestId());
        $order = $this->getOrderById($request->getOrderId());
        $invoices = $this->invoiceCollectionFactory->create();
        $invoices->addFieldToFilter('order_id', $order->getEntityId());
        $invoice = $invoices->getFirstItem();

        if (!$this->rmaHelperData->getRmaToCreditMemoConfig($request->getStoreId())) {
            return parent::getButtonData();
        }
        $qtyRefunded = 0;
        $qtyRma = 0;
        foreach ($order->getCreditmemosCollection() as $creditmemo) {
            if ($creditmemo->getData('rma_request_id') != $this->getRequestId()) {
                continue;
            }
            foreach ($creditmemo->getAllItems() as $key => $creditmemoItem) {
                $orderItemCreditmemo = $this->getOrderItemById($creditmemoItem->getOrderItemId());
                if ($orderItemCreditmemo->getProductType() === 'configurable') {
                    $qtyRefunded += $creditmemoItem->getQty();
                }
            }
        }

        foreach ($request->getRequestItems() as $rmaItem) {
            $qtyRma += $rmaItem->getQty();
        }

        if ($this->authorization->isAllowed('Magento_Sales::creditmemo') && $order->canCreditmemo() && $qtyRma > $qtyRefunded) {
            $onClick = sprintf("location.href = '%s'", $this->getCreditMemoUrlByOrder($order->getEntityId()));
            if ($order->getPayment()->getMethodInstance()->isGateway()) {
                $onClick = sprintf("location.href = '%s'", $this->getCreditMemoUrlByInvoice($invoice->getId(), $order->getEntityId()));
            }
            $data = [
                'label' => __('Credit Memo'),
                'class' => 'credit-memo',
                'on_click' => $onClick,
            ];
        }

        return $data;
    }

    /**
     * @param $invoiceId
     * @param $orderId
     * @return string
     */
    public function getCreditMemoUrlByInvoice($invoiceId, $orderId)
    {
        return $this->getUrl(
            'sales/order_creditmemo/start',
            ['order_id' => $orderId, 'invoice_id' => $invoiceId, 'rma_request_id' => $this->getRequestId()]
        );
    }

    /**
     * @param $orderId
     * @return string
     */
    public function getCreditMemoUrlByOrder($orderId)
    {
        return $this->getUrl('sales/order_creditmemo/start',
            ['order_id' => $orderId, 'rma_request_id' => $this->getRequestId()]
        );
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getOrderItemById($id)
    {
        return $this->orderItemCollectionFactory->create()
            ->addFieldToFilter(OrderItemInterface::ITEM_ID, (int)$id)
            ->fetchItem();
    }
}
