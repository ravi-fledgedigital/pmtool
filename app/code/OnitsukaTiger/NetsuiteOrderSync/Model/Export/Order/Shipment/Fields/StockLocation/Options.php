<?php
namespace OnitsukaTiger\NetsuiteOrderSync\Model\Export\Order\Shipment\Fields\StockLocation;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var \Magento\Inventory\Model\ResourceModel\Source\Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $options;

    public function __construct(
        \Magento\Inventory\Model\ResourceModel\Source\Collection $collection
    ) {
        $this->collection = $collection;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->collection as $item) {
            $options[] = ['label' => $item->getName(), 'value' => $item->getSourceCode()];
        }
        $this->options = $options;

        return $this->options;
    }
}
