<?php

namespace OnitsukaTiger\Cegid\Model;

use Amasty\Rma\Model\Request\ResourceModel\CollectionFactory;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as orderCollectionFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\StoreManagerInterface;

class Cron
{
    const STATUS_APPROVE = 1;
    private ReturnStatusProcessor $returnStatusProcessor;
    private ReturnProcessor $returnProcessor;
    private CollectionFactory $collectionFactory;
    private Config $config;
    private StoreManagerInterface $storeManager;
    private orderCollectionFactory $orderCollectionFactory;
    private CreditMemo $creditMemo;
    private OrderRepository $orderRepository;

    /**
     * @param ReturnStatusProcessor $returnStatusProcessor
     * @param ReturnProcessor $returnProcessor
     * @param CollectionFactory $collectionFactory
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param orderCollectionFactory $orderCollectionFactory
     * @param CreditMemo $creditMemo
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        ReturnStatusProcessor $returnStatusProcessor,
        ReturnProcessor        $returnProcessor,
        CollectionFactory      $collectionFactory,
        Config                  $config,
        StoreManagerInterface   $storeManager,
        orderCollectionFactory  $orderCollectionFactory,
        CreditMemo              $creditMemo,
        OrderRepository    $orderRepository
    ) {
        $this->returnStatusProcessor = $returnStatusProcessor;
        $this->returnProcessor = $returnProcessor;
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->creditMemo = $creditMemo;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function execute()
    {
        $this->returnProcessor->execute();
        $this->returnStatusProcessor->execute();
        $stores = $this->storeManager->getStores(true, true);
        $statusWhenApprove = $this->config->getReturnStatusReceived();
        $rmaRequestCollection = $this->collectionFactory->create()
            ->addFilterToMap('status', 'main_table.status')
            ->addFilterToMap('status_return', 'onitsukatiger_cegid_returnaction.status')
            ->addFilterToMap('request_id', 'main_table.request_id')
            ->addFieldToFilter('status', $statusWhenApprove)
            ->addFieldToFilter('status_return', self::STATUS_APPROVE)
            ->addFieldToFilter('store_id', [
                'in' => [
                    $stores['web_sg_en']->getStoreId(),
                    $stores['web_my_en']->getStoreId(),
                    $stores['web_th_en']->getStoreId(),
                    $stores['web_th_th']->getStoreId(),
                    $stores['web_vn_en']->getStoreId(),
                    $stores['web_vn_vi']->getStoreId()
                ]
            ])->join(
                'onitsukatiger_cegid_returnaction',
                'main_table.' . 'request_id' . ' = onitsukatiger_cegid_returnaction.request_id',
            );
        foreach ($rmaRequestCollection as $item) {
            $order = $this->orderRepository->get($item->getOrderId());
            if ($order->getStatus() != "closed") {
                $this->creditMemo->createCreditMemo($order, $item->getRequestId(), $item->getReturnactionId());
            }
        }
    }
}
