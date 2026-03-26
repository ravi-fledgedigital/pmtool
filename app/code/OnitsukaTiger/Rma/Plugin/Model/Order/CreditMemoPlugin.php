<?php
declare(strict_types=1);

namespace OnitsukaTiger\Rma\Plugin\Model\Order;

use Amasty\Rma\Model\Request\Repository as RmaRepository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use OnitsukaTiger\Rma\Helper\Data;
use Amasty\Rma\Model\Request\Request;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\CollectionFactory as CmItemCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditMemoCollectionFactory;

class CreditMemoPlugin
{
    /**
     * @var CollectionFactory
     */
    protected $orderItemCollectionFactory;

    /**
     * @var RmaRepository
     */
    protected $rmaRepository;

    /**
     * @var Data
     */
    protected $rmaHelperData;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var CmItemCollectionFactory
     */
    protected $cmItemCollectionFactory;

    /**
     * @var CreditMemoCollectionFactory
     */
    protected $creditMemoCollectionFactory;

    /**
     * @var \OnitsukaTiger\Logger\Logger
     */
    protected $logger;

    /**
     * CreditMemoPlugin constructor.
     * @param CollectionFactory $orderItemCollectionFactory
     * @param RmaRepository $rmaRepository
     * @param Data $rmaHelperData
     * @param RequestInterface $request
     * @param CmItemCollectionFactory $cmItemCollectionFactory
     * @param CreditMemoCollectionFactory $creditMemoCollectionFactory
     * @param \OnitsukaTiger\Logger\Logger $logger
     */
    public function __construct(
        CollectionFactory $orderItemCollectionFactory,
        RmaRepository $rmaRepository,
        Data $rmaHelperData,
        RequestInterface $request,
        CmItemCollectionFactory $cmItemCollectionFactory,
        CreditMemoCollectionFactory $creditMemoCollectionFactory,
        \OnitsukaTiger\Logger\Logger $logger
    )
    {
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->rmaRepository = $rmaRepository;
        $this->rmaHelperData = $rmaHelperData;
        $this->request = $request;
        $this->cmItemCollectionFactory = $cmItemCollectionFactory;
        $this->creditMemoCollectionFactory = $creditMemoCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * @param Creditmemo $subject
     * @param $result
     * @return array
     * @throws LocalizedException
     */
    public function afterGetAllItems(Creditmemo $subject, $result)
    {

        if (!$this->request->getParam('rma_request_id') ||
            !$this->rmaHelperData->getRmaToCreditMemoConfig($subject->getStoreId())
        ) {
            return $result;
        }
        $data = [];
        $rmaQtyRefunded = $this->getRmaQtyRefunded();
        $mapItem = $this->mapItemRmaToCreditMemo($rmaQtyRefunded);
        if ($mapItem) {
            foreach ($mapItem as $item) {
                foreach ($result as $key => $value) {
                    if ($item['sku'] === $value->getSku()) {
                        if (!$value->getId()) {
                            if ($this->request->getActionName() === 'updateQty') {
                                if ($value->getQty() > $item['qty_to_refund']) {
                                    throw new \Magento\Framework\Exception\LocalizedException(
                                        __('We found an invalid quantity to refund item sku "%1".', $item['sku'])
                                    );
                                }
                            }
                            if ($this->request->getActionName() != 'save') {
                                $value->setQty($this->request->getActionName() === 'updateQty' ? $value->getQty() : $item['qty_to_refund']);
                            }
                        }
                        $data[$key] = $value;
                    }
                }
            }
            return $data;
        }

        return $result;
    }

    /**
     * @param $rmaQtyToRefund
     * @return array
     */
    public function mapItemRmaToCreditMemo($rmaQtyToRefund)
    {
        $data = [];
        try {
            /** @var Request $rmaRequest */
            $rmaRequest = $this->rmaRepository->getById((int)$this->request->getParam('rma_request_id'));
            foreach ($rmaRequest->getRequestItems() as $rmaItem) {
                $item = $this->orderItemCollectionFactory->create()
                    ->addFieldToFilter(OrderItemInterface::ITEM_ID, (int)$rmaItem->getOrderItemId());
                foreach ($item as $value) {
                    $data[] = [
                        'sku' => $value->getSku(),
                        'qty_to_refund' => $rmaItem->getQty() - (isset($rmaQtyToRefund[$value->getSku()]) ? $rmaQtyToRefund[$value->getSku()] : 0)
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $data;
        }
        return $data;
    }

    /**
     * @return array
     */
    public function getRmaQtyRefunded()
    {
        $rmaQtyToRefund = [];
        $creditMemos = $this->creditMemoCollectionFactory->create()
            ->addFieldToFilter('rma_request_id', $this->request->getParam('rma_request_id'));
        foreach ($creditMemos as $creditMemo) {
            $creditMemoItems = $this->cmItemCollectionFactory->create()
                ->addFieldToFilter('parent_id', $creditMemo->getId());
            foreach ($creditMemoItems as $creditMemoItem) {
                $orderItemCreditMemo = $this->getOrderItemById($creditMemoItem->getOrderItemId());
                if ($orderItemCreditMemo->getProductType() === 'configurable') {
                    $rmaQtyToRefund[$creditMemoItem->getSku()] = (isset($rmaQtyToRefund[$creditMemoItem->getSku()]) ? $rmaQtyToRefund[$creditMemoItem->getSku()] : 0) + $creditMemoItem->getQty();
                }
            }
        }

        return $rmaQtyToRefund;
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
