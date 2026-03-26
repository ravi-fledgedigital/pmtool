<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/** @noinspection PhpUnusedParameterInspection */
declare(strict_types=1);

namespace OnitsukaTiger\InventorySourceAlgorithm\Model\Algorithms\Result;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionItemInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterface;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterfaceFactory;
use Magento\InventorySourceSelectionApi\Model\GetInStockSourceItemsBySkusAndSortedSource;
use Magento\InventorySourceSelectionApi\Model\GetSourceItemQtyAvailableInterface;

/**
 * Return a default response for sorted source algorithms
 */
class GetOneSourceSortedResult
{
    /**
     * @var SourceSelectionItemInterfaceFactory
     */
    private $sourceSelectionItemFactory;

    /**
     * @var SourceSelectionResultInterfaceFactory
     */
    private $sourceSelectionResultFactory;

    /**
     * @var GetInStockSourceItemsBySkusAndSortedSource
     */
    private $getInStockSourceItemsBySkusAndSortedSource;

    /**
     * @var GetSourceItemQtyAvailableInterface
     */
    private $getSourceItemQtyAvailable;

    /**
     * @param SourceSelectionItemInterfaceFactory $sourceSelectionItemFactory
     * @param SourceSelectionResultInterfaceFactory $sourceSelectionResultFactory
     * @param GetInStockSourceItemsBySkusAndSortedSource $getInStockSourceItemsBySkusAndSortedSource = null
     * @param GetSourceItemQtyAvailableInterface|null $getSourceItemQtyAvailable
     * @SuppressWarnings(PHPMD.LongVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        SourceSelectionItemInterfaceFactory $sourceSelectionItemFactory,
        SourceSelectionResultInterfaceFactory $sourceSelectionResultFactory,
        private SourceRepositoryInterface $sourceRepository,
        GetInStockSourceItemsBySkusAndSortedSource $getInStockSourceItemsBySkusAndSortedSource = null,
        GetSourceItemQtyAvailableInterface $getSourceItemQtyAvailable = null
    )
    {
        $this->sourceSelectionItemFactory = $sourceSelectionItemFactory;
        $this->sourceSelectionResultFactory = $sourceSelectionResultFactory;
        $this->getInStockSourceItemsBySkusAndSortedSource = $getInStockSourceItemsBySkusAndSortedSource ?:
            ObjectManager::getInstance()->get(GetInStockSourceItemsBySkusAndSortedSource::class);
        $this->getSourceItemQtyAvailable = $getSourceItemQtyAvailable ??
            ObjectManager::getInstance()->get(GetSourceItemQtyAvailableInterface::class);
    }

    /**
     * Generate default result for priority based algorithms
     *
     * @param InventoryRequestInterface $inventoryRequest
     * @param SourceInterface[] $sortedSources
     * @return SourceSelectionResultInterface
     */
    public function execute(
        InventoryRequestInterface $inventoryRequest,
        array $sortedSources
    ): SourceSelectionResultInterface
    {
        $sourceItemSelections = [];

        $itemsTdDeliver = [];
        foreach ($inventoryRequest->getItems() as $item) {
            $normalizedSku = $this->normalizeSku($item->getSku());
            $itemsTdDeliver[$normalizedSku] = $item->getQty();
        }

        $sortedSourceCodes = [];
        foreach ($sortedSources as $sortedSource) {
            $sortedSourceCodes[] = $sortedSource->getSourceCode();
        }

        $sourceItems =
            $this->getInStockSourceItemsBySkusAndSortedSource->execute(
                array_keys($itemsTdDeliver),
                $sortedSourceCodes
            );

        $data = [];
        foreach ($sourceItems as $sourceItem) {
            $normalizedSku = $this->normalizeSku($sourceItem->getSku());
            $sourceItemQtyAvailable = $this->getSourceItemQtyAvailable->execute($sourceItem);
            $qtyToDeduct = $sourceItemQtyAvailable >= ($itemsTdDeliver[$normalizedSku] ?? 0) ? $itemsTdDeliver[$normalizedSku] : 0;

            //in case source is not enough qty
            if ($itemsTdDeliver[$normalizedSku] > $sourceItemQtyAvailable) {
                $qtyToDeduct = 0;
            }

            $data[$sourceItem->getSourceCode()][] = [
                'sourceCode' => $sourceItem->getSourceCode(),
                'sku' => $sourceItem->getSku(),
                'qtyToDeduct' => $qtyToDeduct,
                'qtyAvailable' => $sourceItemQtyAvailable
            ];
        }
        // get source code has full item in order
        $findKey = '';
        $isShippable = false;
        foreach ($data as $sourceCode => $sourceCodeItems) {
            $check = 0;
            foreach ($sourceCodeItems as $sourceItem) {
                if ($sourceItem['qtyToDeduct'] == 0) {
                    continue;
                }
                $check++;
            }

            if ($check == count($sourceCodeItems) && $check == count($itemsTdDeliver) && isset($sourceItem)) {
                $findKey = $sourceItem['sourceCode'];
                $isShippable = true;
                break;
            }
        }

        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/sourceAllocationAlgorithm.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info("================ Start Debug Source Algorithm Start ================ ");
        $logger->info("Data: " . print_r($data, true));
        $logger->info("Is Shippable?: " . $isShippable);

        $availableStockArray = [];
        // update data
        foreach ($sourceItems as $sourceItem) {
            $normalizedSku = $this->normalizeSku($sourceItem->getSku());
            $sourceItemQtyAvailable = $this->getSourceItemQtyAvailable->execute($sourceItem);

            if ($sourceItem->getSourceCode() == $findKey) {
                $qtyToDeduct = $itemsTdDeliver[$normalizedSku];
            } else {
                $qtyToDeduct = 0;
            }

            if (
                !$this->isSourceStore($sourceItem->getSourceCode()) &&
                $qtyToDeduct == 0 &&
                $itemsTdDeliver[$normalizedSku] > $sourceItemQtyAvailable &&
                $sourceItemQtyAvailable > 0
            ) {
                $qtyToDeduct = $sourceItemQtyAvailable;
                $itemsTdDeliver[$normalizedSku] = $itemsTdDeliver[$normalizedSku] - $sourceItemQtyAvailable;
            } elseif (
                $qtyToDeduct == 0 &&
                $itemsTdDeliver[$normalizedSku] <= $sourceItemQtyAvailable &&
                $sourceItemQtyAvailable > 0
            ) {
                $qtyToDeduct = $itemsTdDeliver[$normalizedSku];
                $itemsTdDeliver[$normalizedSku] = 0;
                $isShippable = false;
            }
            $availableStockArray[] = [
                'sourceCode' => $sourceItem->getSourceCode(),
                'sku' => $sourceItem->getSku(),
                'qtyToDeduct' => $qtyToDeduct,
                'qtyAvailable' => $sourceItemQtyAvailable
            ];
            $sourceItemSelections[] = $this->sourceSelectionItemFactory->create(
                [
                    'sourceCode' => $sourceItem->getSourceCode(),
                    'sku' => $sourceItem->getSku(),
                    'qtyToDeduct' => $qtyToDeduct,
                    'qtyAvailable' => $sourceItemQtyAvailable
                ]
            );
        }
        $logger->info("Available Stock Array: " . print_r($availableStockArray, true));
        $logger->info("================ Start Debug Source Algorithm End ================ ");

        return $this->sourceSelectionResultFactory->create(
            [
                'sourceItemSelections' => $sourceItemSelections,
                'isShippable' => $isShippable
            ]
        );
    }


    /**
     * Convert SKU to lowercase
     *
     * Normalize SKU by converting it to lowercase.
     *
     * @param string $sku
     * @return string
     */
    private function normalizeSku(string $sku): string
    {
        return mb_convert_case($sku, MB_CASE_LOWER, 'UTF-8');
    }

    /**
     * Check is source warehouse or store
     *
     * @param string $sourceCode
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function isSourceStore(string $sourceCode)
    {
        return $this->sourceRepository->get($sourceCode)->getIsShippingFromStore();
    }
}