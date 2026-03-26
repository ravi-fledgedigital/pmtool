<?php
namespace OnitsukaTiger\InventorySourceAlgorithm\Model\Algorithms;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionItemInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterface;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterfaceFactory;
use Magento\InventorySourceSelectionApi\Model\Algorithms\Result\GetDefaultSortedSourcesResult;
use Magento\Sales\Api\Data\OrderInterface;

class PriorityBasedAlgorithm extends \Magento\InventorySourceSelection\Model\Algorithms\PriorityBasedAlgorithm {

    /**
     * @var GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    private $getSourcesAssignedToStockOrderedByPriority;

    /**
     * @var GetDefaultSortedSourcesResult
     */
    private $getDefaultSortedSourcesResult;

    /**
     * PriorityBasedAlgorithm constructor.
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority
     * @param SourceSelectionItemInterfaceFactory $sourceSelectionItemFactory
     * @param SourceSelectionResultInterfaceFactory $sourceSelectionResultFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param GetDefaultSortedSourcesResult|null $getDefaultSortedSourcesResult
     */
    public function __construct(
        GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority,
        SourceSelectionItemInterfaceFactory $sourceSelectionItemFactory,
        SourceSelectionResultInterfaceFactory $sourceSelectionResultFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemRepositoryInterface $sourceItemRepository,
        GetDefaultSortedSourcesResult $getDefaultSortedSourcesResult = null
    )
    {
        parent::__construct(
            $getSourcesAssignedToStockOrderedByPriority,
            $sourceSelectionItemFactory,
            $sourceSelectionResultFactory,
            $searchCriteriaBuilder,
            $sourceItemRepository,
            $getDefaultSortedSourcesResult
        );

        $this->getSourcesAssignedToStockOrderedByPriority = $getSourcesAssignedToStockOrderedByPriority;
        $this->getDefaultSortedSourcesResult = $getDefaultSortedSourcesResult ?:
            ObjectManager::getInstance()->get(GetDefaultSortedSourcesResult::class);
    }

    public function execute(InventoryRequestInterface $inventoryRequest): SourceSelectionResultInterface
    {
        $order = $inventoryRequest->getExtensionAttributes()->getOrder();
        $stockId = $inventoryRequest->getStockId();
        $sortedSources = $this->getEnabledSourcesOrderedByPriorityByStockId(
            $stockId,
            $order
        );
        return $this->getDefaultSortedSourcesResult->execute($inventoryRequest, $sortedSources);
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
