<?php

namespace OnitsukaTigerKorea\Rma\Observer;

use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\ItemRepository;
use OnitsukaTigerKorea\Rma\Model\ResourceModel\ReturnInfo;
use OnitsukaTigerKorea\Rma\Model\ReturnInfoFactory;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\Export\CancelReturn;

class AfterCancelReturn implements ObserverInterface
{

    /**
     * @var CancelReturn
     */
    protected $cancelReturn;
    /**
     * @var ReturnInfoFactory
     */
    private $returnInfoFactory;
    /**
     * @var ReturnInfo
     */
    private $returnInfoResource;
    /**
     * @var ItemRepository
     */
    private $itemRepository;
    /**
     * @var RequestRepositoryInterface
     */
    private $repository;

    /**
     * AfterCancelReturn constructor.
     * @param CancelReturn $cancelReturn
     * @param ReturnInfoFactory $returnInfoFactory
     * @param ReturnInfo $returnInfoResource
     * @param ItemRepository $itemRepository
     * @param RequestRepositoryInterface $repository
     */
    public function __construct(
        CancelReturn $cancelReturn,
        ReturnInfoFactory          $returnInfoFactory,
        ReturnInfo                 $returnInfoResource,
        ItemRepository             $itemRepository,
        RequestRepositoryInterface $repository
    )
    {
        $this->cancelReturn = $cancelReturn;
        $this->returnInfoFactory = $returnInfoFactory;
        $this->returnInfoResource = $returnInfoResource;
        $this->itemRepository = $itemRepository;
        $this->repository = $repository;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        /** @var RequestInterface $request */
        $request = $observer->getData('request');
        if ($request->getStoreId() == \OnitsukaTiger\Store\Model\Store::KO_KR) {
            $this->cancelReturn->execute($request);
        }
        $this->saveReturnCancelInfo($request->getRequestId());
    }

    /**
     * @param $requestId
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saveReturnCancelInfo($requestId)
    {
        $model = $this->repository->getById($requestId);
        $trackingNoId  = 0;
        foreach ($model->getTrackingNumbers() as $no) {
            $trackingNoId = $no->getTrackingNumber();
        }
        $productInfo = [];
        foreach ($model->getRequestItems() as $requestItem) {
            $orderItem = $this->itemRepository->get($requestItem->getOrderItemId());
            $qty = $requestItem->getQty();
            $productInfo[] = [
                'qty' => $qty,
                'sku' => $orderItem->getSku()
            ];
        }
        $returnInfo = $this->returnInfoFactory->create();
        $returnInfo->setData('order_id', $model->getOrderId());
        $returnInfo->setData('return_id', $requestId);
        $returnInfo->setData('tracking_no', $trackingNoId);
        $returnInfo->setData('product_info', json_encode($productInfo));
        $this->returnInfoResource->save($returnInfo);
    }
}
