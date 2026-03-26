<?php

namespace OnitsukaTigerKorea\Sales\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTiger\Store\Model\Store;

class AfterCheckoutSubmit implements ObserverInterface

{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * AfterCheckoutSubmit constructor.
     *
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        ProductRepositoryInterface  $productRepository,
    )
    {
        $this->eventManager = $eventManager;
        $this->productRepository = $productRepository;
    }

    /**
     * @param Observer $observer
     * @throws NoSuchEntityException|\Magento\Framework\Exception\CouldNotSaveException
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getOrder();
        $items = $order->getAllItems();
        foreach ($items as $item) {
            if ($item->getStoreId() == Store::KO_KR) {
                $product = $this->productRepository->get($item->getSku());
                $item->setData('sku_wms',$product->getSkuWms());
                $item->save();
            }
        }
    }
}
