<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway;

use Firebear\ImportExport\Logger\Logger;
use Firebear\ImportExport\Traits\General as GeneralTrait;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Inventory\Model\SourceRepository;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use NetSuite\Classes\AddRequest;
use NetSuite\Classes\GetRequest;
use NetSuite\Classes\InventoryAdjustment;
use NetSuite\Classes\InventoryAdjustmentInventory;
use NetSuite\Classes\InventoryAdjustmentInventoryList;
use NetSuite\Classes\InventoryItem;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use NetSuite\NetSuiteService;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class StockSourceQty
 * @package Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway
 */
class StockSourceQty extends AbstractGateway
{
    use GeneralTrait;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * Product constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductRepository $productRepository
     * @param Logger $logger
     * @param ConsoleOutput $output
     * @param SourceRepository $sourceRepository
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ProductRepository $productRepository,
        Logger $logger,
        ConsoleOutput $output,
        SourceRepository $sourceRepository
    ) {
        parent::__construct($scopeConfig);
        $this->productRepository = $productRepository;
        $this->_logger = $logger;
        $this->output = $output;
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * @param $data
     * @return bool|void
     * @throws NoSuchEntityException
     */
    public function exportStockSourceQty($data)
    {
        $this->initService();
        $sourceCode = $data['source_code'];
        $sourceEntity = $this->sourceRepository->get($sourceCode);
        $locationNetSuiteInternalId = $sourceEntity->getData('netsuite_internal_id');
        if ($locationNetSuiteInternalId) {
            $product = $this->productRepository->get($data['sku'], true);
            $productData = $product->getData();
            if ($productData['netsuite_internal_id']) {
                $existInventoryItem = $this->getExistInventoryItem($productData['netsuite_internal_id']);
                if (!$existInventoryItem) {
                    return false;
                }
                $inventoryAdjustment = $this->getInventoryAdjustment($existInventoryItem,
                    $locationNetSuiteInternalId,
                    $data
                );
                if (!$inventoryAdjustment) {
                    return false;
                }
                $addRequest = new AddRequest();
                $addRequest->record = $inventoryAdjustment;
                $addResponse = $this->service->add($addRequest);
                if (!$addResponse->writeResponse->status->isSuccess) {
                    $errorMessage = __(
                        'Quantity has not been updated.' .
                            ' Sku: %1. Location internal id: %2. Message: %3',
                        [
                            $productData['sku'],
                            $locationNetSuiteInternalId,
                            $addResponse->writeResponse->status->statusDetail[0]->message
                        ]
                    );
                    $this->addLogWriteln($errorMessage, $this->output, 'error');
                }
            }
        }
    }

    /**
     * @param $netsuiteInternalId
     * @return false|InventoryItem
     */
    protected function getExistInventoryItem($netsuiteInternalId)
    {
        $getRequest = new \NetSuite\Classes\GetRequest();
        $getRequest->baseRef = new \NetSuite\Classes\RecordRef();
        $getRequest->baseRef->internalId = $netsuiteInternalId;
        $getRequest->baseRef->type = RecordType::inventoryItem;
        $inventoryItemResponse = $this->service->get($getRequest);
        if ($inventoryItemResponse->readResponse->status->isSuccess) {
            return $inventoryItemResponse->readResponse->record;
        } else {
            $errorMessage = __(
                'Failed to get information about the inventory item.' .
                ' NetSuite internal Id: %1. Message: %2',
                [
                    $netsuiteInternalId,
                    $inventoryItemResponse->readResponse->status->statusDetail[0]->message
                ]
            );
            $this->addLogWriteln($errorMessage, $this->output, 'error');
            return false;
        }
    }

    /**
     * @param $existInventoryItem
     * @param $locationNetSuiteInternalId
     * @param $data
     * @return false|InventoryAdjustment
     */
    private function getInventoryAdjustment($existInventoryItem, $locationNetSuiteInternalId, $data)
    {
        $chartOfAccount = $existInventoryItem->cogsAccount;
        $department = $existInventoryItem->department;
        $fund = $existInventoryItem->class;
        $existInventoryItemInternalId = $existInventoryItem->internalId;
        $locationRecordRef = new RecordRef();
        $locationRecordRef->internalId = $locationNetSuiteInternalId;
        $locationRecordRef->type = RecordType::location;
        $getLocationRequest = new GetRequest();
        $getLocationRequest->baseRef = $locationRecordRef;
        $locationResponse = $this->service->get($getLocationRequest);
        if ($locationResponse->readResponse->status->isSuccess) {
            $locationData = $locationResponse->readResponse->record;
            $subsidiaryList = $locationData->subsidiaryList;
            if ($subsidiaryList) {
                //If the Inventory feature is enabled,a location can be associated with only one subsidiary.
                $subsidiaryInternalId = $subsidiaryList->recordRef[0]->internalId;
            }
        } else {
            $errorMessage = __(
                'Failed to get information about the location.' .
                ' SKU: %1. Location internal id: %2. Message: %3',
                [
                    $data['sku'],
                    $locationNetSuiteInternalId,
                    $locationResponse->readResponse->status->statusDetail[0]->message
                ]
            );
            $this->addLogWriteln($errorMessage, $this->output, 'error');
            return false;
        }
        if ($chartOfAccount) {
            $locations = $existInventoryItem->locationsList->locations;
            foreach ($locations as $key => $location) {
                if ($location->location == $locationNetSuiteInternalId) {
                    $locationQuantityAvailable = $location->quantityAvailable;
                    break;
                }
            }
        }
        if (isset($locationQuantityAvailable)) {
            $subsidiary = new \NetSuite\Classes\RecordRef();
            $subsidiary->internalId = $subsidiaryInternalId;
            $quantityDifference = $data['quantity'] - $locationQuantityAvailable;
            if ($quantityDifference !== 0.0) {
                $inventoryAdjustment = new InventoryAdjustment();
                $inventoryAdjustment->account = $chartOfAccount;
                $inventoryList = new InventoryAdjustmentInventoryList();
                $inventoryAdjustmentInventory = new InventoryAdjustmentInventory();
                $location = new \NetSuite\Classes\Location();
                $location->internalId = $locationNetSuiteInternalId;
                $inventoryAdjustmentInventory->location = $location;
                $inventoryAdjustmentInventory->adjustQtyBy = $quantityDifference;
                $item = new RecordRef();
                $item->internalId = $existInventoryItemInternalId;
                $inventoryAdjustmentInventory->item = $item;
                $inventoryList->inventory = $inventoryAdjustmentInventory;
                $inventoryAdjustment->inventoryList = $inventoryList;
                $inventoryAdjustment->subsidiary = $subsidiary;
                $inventoryAdjustment->department = $department;
                $inventoryAdjustment->class = $fund;
                return $inventoryAdjustment;
            }
        }
        return false;
    }
}
