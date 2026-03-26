<?php
namespace OnitsukaTiger\NetsuiteReturnOrderSync\Model;

use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\StatusInterface;
use Amasty\Rma\Model\OptionSource\State;
use Amasty\Rma\Model\Request\ResourceModel\CollectionFactory;
use Amasty\Rma\Model\Request\ResourceModel\RequestItemCollection;
use Amasty\Rma\Model\Request\ResourceModel\RequestItemCollectionFactory;
use Amasty\Rma\Model\Status\ResourceModel\Collection;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;

class SplitRmaRequestProcess {

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var CollectionFactory
     */
    protected $requestCollectionFactory;

    /**
     * @var RequestItemCollectionFactory
     */
    protected $requestItemCollectionFactory;

    /**
     * @var \Amasty\Rma\Model\Status\ResourceModel\CollectionFactory
     */
    protected $statusCollectionFactory;

    /**
     * @var OrderItemCollectionFactory
     */
    private OrderItemCollectionFactory $orderItemCollectionFactory;

    /**
     * SplitRmaRequestProcess constructor.
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param CollectionFactory $requestCollectionFactory
     * @param RequestItemCollectionFactory $requestItemCollectionFactory
     * @param \Amasty\Rma\Model\Status\ResourceModel\CollectionFactory $statusCollectionFactory
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     */
    public function __construct(
        OrderItemRepositoryInterface                             $orderItemRepository,
        CollectionFactory                                        $requestCollectionFactory,
        RequestItemCollectionFactory                             $requestItemCollectionFactory,
        \Amasty\Rma\Model\Status\ResourceModel\CollectionFactory $statusCollectionFactory,
        OrderItemCollectionFactory                               $orderItemCollectionFactory
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->requestCollectionFactory = $requestCollectionFactory;
        $this->requestItemCollectionFactory = $requestItemCollectionFactory;
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
    }

    /**
     * @param RequestInterface $request
     * @param $shipments
     * @return array
     */
    public function execute (RequestInterface $request, $shipments)
    {
        $shipmentItemSelection = [];
        $itemTdReturn = [];
        foreach ($request->getRequestItems() as $returnItem) {
            $orderItem = $this->orderItemRepository->get($returnItem->getOrderItemId());
            $orderItemReturn = $orderItem->getParentItemId();  // convert from simple -> config
            $itemTdReturn[$orderItemReturn] = [
                'qty' => $returnItem->getQty(),
                'resolution_id' => $returnItem->getResolutionId(),
                'reason_id' => $returnItem->getReasonId(),
                'condition_id' => $returnItem->getConditionId(),
                'request_qty' => $returnItem->getRequestQty(),
                'order_item_id' => $returnItem->getOrderItemId()
            ];

        }

        $shipmentItems = $this->getAllItemsInShipment($shipments);
        foreach ($shipmentItems as $shipmentItem) {
            foreach ($shipmentItem as $shipItem) {
                $orderItemShipment = $shipItem['order_item_id'];
                $qtyShipment = $shipItem['qty'];
                $qtyToDeduct = min($qtyShipment, $itemTdReturn[$orderItemShipment]['qty'] ?? 0);
                if ($qtyToDeduct > 0) {
                    $shipmentItemSelection[$shipItem['shipment_incrementId']][] = [
                        'shipmentId' => $shipItem['shipment_id'],
                        'sku' => $shipItem['sku'],
                        'qtyToDeduct' => $qtyToDeduct,
                        'qtyAvailable' => $qtyShipment,
                        'order_item_id' => $itemTdReturn[$orderItemShipment]['order_item_id'] ?? '',
                        'resolution_id' => $itemTdReturn[$orderItemShipment]['resolution_id'] ?? '',
                        'reason_id' => $itemTdReturn[$orderItemShipment]['reason_id'] ?? '',
                        'condition_id' => $itemTdReturn[$orderItemShipment]['condition_id'] ?? '',
                        'request_qty' => $qtyToDeduct
                    ];

                    if (isset($itemTdReturn[$orderItemShipment])) {
                        $itemTdReturn[$orderItemShipment]['qty'] -= $qtyToDeduct;
                    }
                }
            }
        }

        $isShippable = true;
        foreach ($itemTdReturn as $itemToReturn) {
            if (!$this->isZero($itemToReturn['qty'])) {
                $isShippable = false;
                break;
            }
        }

        return [
            'shipmentItemSelection' => $shipmentItemSelection,
            'isShippable' => $isShippable
        ];

    }

    /**
     * @param $shipments
     * @return array
     */
    protected function getAllItemsInShipment($shipments)
    {
        $items = [];
        foreach ($shipments as $shipment) {
            $shipmentItems = [];
            foreach ($shipment->getAllItems() as $shipItem) {
                $shipmentItems[] = [
                    'shipment_item_id' => $shipItem->getEntityId(),
                    'shipment_id' => $shipItem->getParentId(),
                    'store_id' => $shipment->getStoreId(),
                    'shipment_incrementId' => $shipment->getIncrementId(),
                    'sku' => $shipItem->getSku(),
                    'order_item_id' => $shipItem->getOrderItemId(),
                    'qty' => $shipItem->getQty(),
                ];
            }
            $shipmentItems = $this->deductQtyShipmentWhenHasRma($shipmentItems);
            $items[$shipment->getIncrementId()] = $shipmentItems;
        }

        return $items;
    }

    /**
     * Compare float number with some epsilon
     *
     * @param float $floatNumber
     *
     * @return bool
     */
    private function isZero(float $floatNumber): bool
    {
        return $floatNumber < 0.0000001;
    }

    /**
     * @param array $shipmentItems
     * @return array
     */
    private function deductQtyShipmentWhenHasRma(array $shipmentItems): array
    {
        foreach($shipmentItems  as $key => $shipmentItem) {
            $requests = $this->requestCollectionFactory->create()
                ->addFieldToSelect(['order_id', 'store_id', 'status', 'shipment_increment_id'])
                ->addFieldToFilter('store_id', $shipmentItem['store_id'])
                ->addFieldToFilter('status', ['nin' => $this->getCancelStatusId()])
                ->addFieldToFilter('shipment_increment_id', $shipmentItem['shipment_incrementId']);

            if(count($requests) > 0) {
                $requestItemTotal = 0;
                foreach($requests->getItems() as $request) {
                    $requestQty = $this->getRequestQtyProductInRma($request,$shipmentItem);
                    $requestItemTotal += $requestQty;
                }

                $shipmentItems[$key]['qty'] = $shipmentItem['qty'] - $requestItemTotal;
            }
        }
        return $shipmentItems;
    }

    /**
     * @return array
     */
    public function getCancelStatusId(): array
    {
        $status = [];
        $statusCancelCollection = $this->statusCollectionFactory->create()
            ->addFieldToFilter(StatusInterface::STATE, State::CANCELED)->getData();
        foreach ($statusCancelCollection as $statusCancel){
            $status[] = $statusCancel['status_id'];
        }
        return $status;
    }

    /**
     * @param $request
     * @param $shipmentItem
     * @return array|mixed|null
     */
    public function getRequestQtyProductInRma($request, $shipmentItem)
    {
        $qty = 0;
        $orderItemCollection = $this->orderItemCollectionFactory->create()
            ->addFieldToFilter("parent_item_id", $shipmentItem['order_item_id'])
            ->addFieldToFilter("sku", $shipmentItem["sku"])
            ->getFirstItem();
        if($orderItemCollection->getItemId()) {
            $requestItems =  $this->requestItemCollectionFactory->create()
                ->addFieldToSelect('request_qty')
                ->addFieldToFilter('request_id', ['eq' => $request->getRequestId()])
                ->addFieldToFilter('order_item_id', ['eq' => $orderItemCollection->getItemId()]);
            if(count($requestItems) > 0) {
                foreach($requestItems as $requestItem) {
                    $qty += $requestItem->getRequestQty();
                }
            }
        }
        return $qty;
    }
}
