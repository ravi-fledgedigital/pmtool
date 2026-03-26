<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\Rma\Model\Config\Source;

use Amasty\Rma\Model\Status\ResourceModel\CollectionFactory;

class RmaStatus implements \Magento\Framework\Data\OptionSourceInterface {

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
        $statusRma = $this->collectionFactory->create();
        $result = [];
        foreach($statusRma as $status){
            $result[] = [
                'value' => $status->getStatusId(),
                'label' => $status->getTitle()
            ];
        }
        return $result;
    }
}
