<?php
declare(strict_types=1);

namespace OnitsukaTiger\NetSuiteStoreShipping\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;
use Magento\InventoryShipping\Model\GetItemsToDeductFromShipment;
use Magento\InventoryShipping\Model\SourceDeductionRequestFromShipmentFactory;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Shipment;
use OnitsukaTiger\Logger\StoreShipping\Logger;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;
use Symfony\Component\Console\Output\ConsoleOutput;

class SourceDeductionWhenUpdateOrderInformationSuccess implements ObserverInterface {

    const STOCK_POS_FLAG = 1;

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var GetItemsToDeductFromShipment
     */
    private $getItemsToDeductFromShipment;

    /**
     * @var SourceDeductionRequestFromShipmentFactory
     */
    private $sourceDeductionRequestFromShipmentFactory;

    /**
     * @var SourceDeductionServiceInterface
     */
    private $sourceDeductionService;

    /**
     * @var ItemToSellInterfaceFactory
     */
    private $itemsToSellFactory;

    /**
     * @var PlaceReservationsForSalesEventInterface
     */
    private $placeReservationsForSalesEvent;

    /**
     * @var StoreShipping
     */
    protected $storeShipping;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * SourceDeductionWhenShipProcessor constructor.
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param GetItemsToDeductFromShipment $getItemsToDeductFromShipment
     * @param SourceDeductionRequestFromShipmentFactory $sourceDeductionRequestFromShipmentFactory
     * @param SourceDeductionServiceInterface $sourceDeductionService
     * @param ItemToSellInterfaceFactory $itemsToSellFactory
     * @param PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent
     * @param StoreShipping $storeShipping
     * @param ShipmentRepositoryInterface $shipmentRepository ,
     * @param ConsoleOutput $output
     * @param Logger $logger
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        DefaultSourceProviderInterface $defaultSourceProvider,
        GetItemsToDeductFromShipment $getItemsToDeductFromShipment,
        SourceDeductionRequestFromShipmentFactory $sourceDeductionRequestFromShipmentFactory,
        SourceDeductionServiceInterface $sourceDeductionService,
        ItemToSellInterfaceFactory $itemsToSellFactory,
        PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent,
        StoreShipping $storeShipping,
        ShipmentRepositoryInterface $shipmentRepository,
        ConsoleOutput $output,
        Logger $logger
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->getItemsToDeductFromShipment = $getItemsToDeductFromShipment;
        $this->sourceDeductionRequestFromShipmentFactory = $sourceDeductionRequestFromShipmentFactory;
        $this->sourceDeductionService = $sourceDeductionService;
        $this->itemsToSellFactory = $itemsToSellFactory;
        $this->placeReservationsForSalesEvent = $placeReservationsForSalesEvent;
        $this->storeShipping = $storeShipping;
        $this->shipmentRepository = $shipmentRepository;
        $this->output = $output;
        $this->logger = $logger;
    }

    /**
     * @param EventObserver $observer
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(EventObserver $observer)
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        if (!empty($shipment->getExtensionAttributes())
            && !empty($shipment->getExtensionAttributes()->getSourceCode())) {
            $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
        } elseif ($this->isSingleSourceMode->execute()) {
            $sourceCode = $this->defaultSourceProvider->getCode();
        }

        if(
            !$this->storeShipping->enabledModuleStoreShipping($shipment->getStoreId()) ||
            $shipment->getExtensionAttributes()->getStockPosFlag() == self::STOCK_POS_FLAG ||
            $this->storeShipping->isShippingFromWareHouse($sourceCode)
        ) {
            return;
        }

        $shipmentItems = $this->getItemsToDeductFromShipment->execute($shipment);

        if (!empty($shipmentItems)) {
            $sourceDeductionRequest = $this->sourceDeductionRequestFromShipmentFactory->execute(
                $shipment,
                $sourceCode,
                $shipmentItems
            );
            try {
                $this->sourceDeductionService->execute($sourceDeductionRequest);
                $this->placeCompensatingReservation($sourceDeductionRequest);
            }catch (\Exception $e) {
                $this->placeCompensatingReservation($sourceDeductionRequest);
                $this->logger->error(sprintf('Shipment %s has source deduction error. Message: %s', $shipment->getIncrementId(), $e->getMessage()));
                $this->output->writeln(sprintf('<error>'.'Shipment %s has source deduction error. Message: %s' .'</error>', $shipment->getIncrementId(), $e->getMessage()));
            }

            // add flag if source deduction shipment
            $shipment->getExtensionAttributes()->setStockPosFlag(self::STOCK_POS_FLAG);
            $this->shipmentRepository->save($shipment);
        }
    }

    /**
     * Place compensating reservation after source deduction
     *
     * @param SourceDeductionRequestInterface $sourceDeductionRequest
     */
    private function placeCompensatingReservation(SourceDeductionRequestInterface $sourceDeductionRequest): void
    {
        $items = [];
        foreach ($sourceDeductionRequest->getItems() as $item) {
            $items[] = $this->itemsToSellFactory->create([
                'sku' => $item->getSku(),
                'qty' => $item->getQty()
            ]);
        }
        $this->placeReservationsForSalesEvent->execute(
            $items,
            $sourceDeductionRequest->getSalesChannel(),
            $sourceDeductionRequest->getSalesEvent()
        );
    }
}
