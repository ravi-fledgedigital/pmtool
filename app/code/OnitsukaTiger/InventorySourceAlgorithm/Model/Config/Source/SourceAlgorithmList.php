<?php
namespace OnitsukaTiger\InventorySourceAlgorithm\Model\Config\Source;

use Magento\InventorySourceSelectionApi\Api\GetSourceSelectionAlgorithmListInterface;

/**
 * Class SourceAlgorithmList
 * @package OnitsukaTiger\InventorySourceAlgorithm\Model\Config\Source
 */
class SourceAlgorithmList implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var GetSourceSelectionAlgorithmListInterface
     */
    private $getSourceSelectionAlgorithmList;

    /**
     * @param GetSourceSelectionAlgorithmListInterface $getSourceSelectionAlgorithmList
     */
    public function __construct(
        GetSourceSelectionAlgorithmListInterface $getSourceSelectionAlgorithmList
    ) {
        $this->getSourceSelectionAlgorithmList = $getSourceSelectionAlgorithmList;
    }


    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $algorithmsList = $this->getSourceSelectionAlgorithmList->execute();
        foreach ($algorithmsList as $algorithm){
            $options[] = [
                'value' => $algorithm->getCode(),
                'label' => $algorithm->getTitle()
            ];
        }

        return $options;
    }
}
