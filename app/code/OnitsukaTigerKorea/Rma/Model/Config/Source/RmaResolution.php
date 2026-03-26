<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\Rma\Model\Config\Source;

use Amasty\Rma\Model\Resolution\ResourceModel\CollectionFactory;

class RmaResolution implements \Magento\Framework\Data\OptionSourceInterface {

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    )
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $resolutionRma = $this->collectionFactory->create();
        $result = [];
        foreach($resolutionRma as $resolution){
            $result[] = [
                'value' => $resolution->getResolutionId(),
                'label' => $resolution->getTitle()
            ];
        }
        return $result;
    }
}
