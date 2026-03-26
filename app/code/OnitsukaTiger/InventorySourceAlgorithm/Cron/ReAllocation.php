<?php
namespace OnitsukaTiger\InventorySourceAlgorithm\Cron;

class ReAllocation {

    /**
     * @var \OnitsukaTiger\InventorySourceAlgorithm\Model\ReAllocation
     */
    protected $reallocation;

    /**
     * ReAllocation constructor.
     * @param \OnitsukaTiger\InventorySourceAlgorithm\Model\ReAllocation $reallocation
     */
    public function __construct(
        \OnitsukaTiger\InventorySourceAlgorithm\Model\ReAllocation $reallocation
    ) {
        $this->reallocation = $reallocation;
    }

    public function execute() {
        $this->reallocation->execute();
    }
}
