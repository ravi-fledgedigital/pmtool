<?php

declare(strict_types=1);

namespace OnitsukaTigerKorea\Sales\Observer;

use Exception;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoRepository;
use OnitsukaTigerKorea\ConfigurableProduct\Helper\Data;
use OnitsukaTiger\Store\Model\Store;

class AfterCreditMemoSave implements ObserverInterface
{

    public const WAIT_EXPORT = 0;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var CreditmemoRepository
     */
    protected $creditmemoRepository;

    /**
     * @var Data
     */
    protected $helperDataCatalog;

    /**
     * CreditMemoSaveAfter constructor.
     * @param RequestInterface $request
     * @param CreditmemoRepository $creditmemoRepository
     * @param Data $helperDataCatalog
     */
    public function __construct(
        RequestInterface $request,
        CreditmemoRepository $creditmemoRepository,
        Data $helperDataCatalog
    )
    {
        $this->request = $request;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->helperDataCatalog= $helperDataCatalog;
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException|Exception
     */
    public function execute(Observer $observer)
    {
        /* @var $creditmemo Creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();
        if ($creditmemo->getStoreId() == Store::KO_KR) {
            foreach ($creditmemo->getItems() as $item) {
                $result = $this->helperDataCatalog->checkProductSkuWms($item);
                $orderItem = $item->getOrderItem();

                if (!$result && $orderItem->getSkuWms()) {
                    $orderItem->setData('qty_sku_wms_return', $item->getQty() + $orderItem->getQtySkuWmsReturn());
                    if ($item->getQty() > 0) {
                        $orderItem->setData('sku_wms_return_flag', self::WAIT_EXPORT);
                    }
                    $orderItem->save();
                }
            }
        }
    }
}
