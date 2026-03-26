<?php
declare(strict_types=1);

namespace OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\InputException;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use OnitsukaTiger\Command\Console\Output\CliOutput;
use OnitsukaTiger\Logger\StoreShipping\Logger;
use OnitsukaTiger\NetSuite\Model\SourceMapping;
use OnitsukaTiger\NetSuite\Model\SuiteTalk\Invoice;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;
use OnitsukaTiger\Store\Model\Store;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Sync extends StoreShipping
{

    const NEED_TO_SYNC = 0;
    const SYNCED = 1;

    /**
     * @var ShipmentCollectionFactory
     */
    protected $shipmentCollectionFactory;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var Invoice
     */
    protected $suiteTalk;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var CliOutput
     */
    protected $console;

    /**
     * @var SourceMapping
     */
    protected $sourceMapping;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @param SourceRepositoryInterface $sourceRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param ShipmentCollectionFactory $shipmentCollectionFactory
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param Invoice $suiteTalk
     * @param Logger $logger
     * @param CliOutput $console
     * @param SourceMapping $sourceMapping
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        SourceRepositoryInterface   $sourceRepository,
        ScopeConfigInterface $scopeConfig,
        ShipmentCollectionFactory   $shipmentCollectionFactory,
        ShipmentRepositoryInterface $shipmentRepository,
        Invoice                     $suiteTalk,
        Logger                      $logger,
        CliOutput                   $console,
        SourceMapping $sourceMapping,
        ManagerInterface $eventManager
    )
    {
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->suiteTalk = $suiteTalk;
        $this->logger = $logger;
        $this->console = $console;
        $this->sourceMapping = $sourceMapping;
        $this->eventManager = $eventManager;
        parent::__construct($sourceRepository, $scopeConfig, $logger);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $errors = [];
        $shipmentsColProcessing = $this->shipmentCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('store_id', [Store::TH_TH, Store::EN_TH]);

        $shipmentsColProcessing->getSelect()
            ->join(
                ["shipment_source" => 'inventory_shipment_source'],
                'main_table.entity_id = shipment_source.shipment_id',
                array('source_code')
            )
            ->where('shipment_source.source_code LIKE "%_ps_%"');

        $shipmentsColProcessing->getSelect()
            ->join(
                ["shipment_attr" => 'shipment_extension_attributes'],
                'main_table.entity_id = shipment_attr.shipment_id',
                array('status', 'shipment_store_synced')
            )
            ->where('shipment_attr.shipment_store_synced != ' . self::SYNCED);

        foreach ($shipmentsColProcessing as $shipment) {
            try {
                $shipment = $this->shipmentRepository->get($shipment->getEntityId());
            } catch (\Exception $exception) {
                $msg = sprintf('Shipment [%s] of Order [%s] has been removed', $shipment->getIncrementId(), $shipment->getOrder()->getIncrementId());
                $this->logger->error($msg);
                $this->console->error($msg, $output);
                continue;
            }
            try {
                if (!$this->isShippingFromWareHouse($shipment->getExtensionAttributes()->getSourceCode())) {
                    $netSuiteLocation = $this->sourceMapping->getNetSuiteLocation($shipment->getExtensionAttributes()->getSourceCode());
                    $internalId = $this->suiteTalk->searchInternalIdInOrderData($shipment->getOrder()->getIncrementId(), $netSuiteLocation);
                    if ($internalId) {
                        $result = $this->suiteTalk->update($internalId, $shipment);
                        if ($result->status->isSuccess) {
                            //update synced with shipment store
                            $shipment->getExtensionAttributes()->setShipmentStoreSynced(self::SYNCED);
                            $this->shipmentRepository->save($shipment);

                            // Deduct Stock when Update Order Information success
                            $this->eventManager->dispatch('after_update_order_information', ['shipment' => $shipment]);

                            $msg = sprintf('Updated Order Information into NetSuite of shipment [%s]', $shipment->getIncrementId());
                            $this->logger->info($msg);
                            $this->console->success($msg, $output);
                            $shipment->getOrder()->addCommentToStatusHistory(sprintf('Updated Order Information into NetSuite of shipment [%s]', $shipment->getIncrementId()))->save();

                        } else {
                            $msg = sprintf('Shipment Id: [%s]: Failed API call : %s', $shipment->getIncrementId(), $result->status->statusDetail[0]->message);
                            $this->logger->error($msg);
                            $this->console->error($msg, $output);
                        }
                    }else {
                        $msg = sprintf('Not found internal id of Order: [%s]', $shipment->getOrder()->getIncrementId());
                        $this->logger->error($msg);
                        $this->console->error($msg, $output);
                    }
                }
            } catch (\Throwable $e){
                $errors[] = [
                    'id' => $shipment->getIncrementId(),
                    'msg' => $e->getMessage()
                ];
            }
        }
        if (!empty($errors)) {
            foreach ($errors as $error){
                $msg = sprintf("Error Shipment %s : %s ",$error['id'], $error['msg']);
                $this->logger->error($msg);
                $this->console->error($msg,$output);
            }
        }
    }
}
