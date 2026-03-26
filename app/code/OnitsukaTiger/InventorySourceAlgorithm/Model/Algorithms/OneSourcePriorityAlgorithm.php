<?php

declare(strict_types=1);

namespace OnitsukaTiger\InventorySourceAlgorithm\Model\Algorithms;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterface;
use Magento\InventorySourceSelectionApi\Model\SourceSelectionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use OnitsukaTiger\InventorySourceAlgorithm\Model\Algorithms\Result\GetOneSourceSortedResult;

/**
 * Class OneSourcePriorityAlgorithm
 * @package OnitsukaTiger\InventorySourceAlgorithm\Model\Algorithms
 */
class OneSourcePriorityAlgorithm implements SourceSelectionInterface {

    /**
     * @var GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    private $getSourcesAssignedToStockOrderedByPriority;

    /**
     * @var GetOneSourceSortedResult
     */
    protected $getOneSourceSortedResult;

    /**
     * OneSourcePriorityAlgorithm constructor.
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority
     * @param GetOneSourceSortedResult $getOneSourceSortedResult
     */
    public function __construct(
        GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority,
        GetOneSourceSortedResult $getOneSourceSortedResult
    )
    {
        $this->getOneSourceSortedResult = $getOneSourceSortedResult;
        $this->getSourcesAssignedToStockOrderedByPriority = $getSourcesAssignedToStockOrderedByPriority;
    }

    /**
     * @param InventoryRequestInterface $inventoryRequest
     * @return SourceSelectionResultInterface
     * @throws InputException
     * @throws LocalizedException
     */
    public function execute(InventoryRequestInterface $inventoryRequest): SourceSelectionResultInterface
    {
        $order = $inventoryRequest->getExtensionAttributes()->getOrder();
        $stockId = $inventoryRequest->getStockId();
        $sortedSources = $this->getEnabledSourcesOrderedByPriorityByStockId(
            $stockId,
            $order
        );
        return $this->getOneSourceSortedResult->execute($inventoryRequest, $sortedSources);
    }

    /**
     * Get enabled sources ordered by priority by $stockId
     *
     * @param int $stockId
     * @param OrderInterface|null $order
     * @return array
     * @throws InputException
     * @throws LocalizedException
     */
    private function getEnabledSourcesOrderedByPriorityByStockId(int $stockId, OrderInterface $order = null): array
    {
        $sources = $this->getSourcesAssignedToStockOrderedByPriority->execute($stockId);
        $sources = array_filter($sources, function (SourceInterface $source) {
            return $source->isEnabled();
        });

        // only for automation reallocation
        if(!$order || empty($order->getData('location_reject'))) { return $sources; }

        $sortSources = [];
        $locationReject = !empty(json_decode($order->getData('location_reject'))) ? json_decode($order->getData('location_reject')) : [];
        foreach($sources as $source){
            if(!in_array($source->getSourceCode(),$locationReject)){
                $sortSources[] = $source;
            }
        }
        return $sortSources;
    }
}
