<?php

namespace OnitsukaTigerKorea\Customer\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Data\OptionSourceInterface;
use Amasty\Storelocator\Model\ResourceModel\Location\CollectionFactory;

class OfflineStoreValue extends AbstractSource implements OptionSourceInterface
{
    /**
     * @var \Amasty\Storelocator\Model\ResourceModel\Location\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Constructor
     *
     * @param \Amasty\Storelocator\Model\ResourceModel\Location\CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions(): array
    {
        if (!$this->_options) {
            $this->_options = [];

            $this->_options[] = ['value' => '', 'label' => __('선택하기')];
            $this->_options[] = ['value' => 'onlinemall', 'label' => '온라인몰'];

            $storeId = 5;
            $collection = $this->collectionFactory->create();

            $collection->addFieldToFilter('status', 1);
            $collection->getSelect()->where('FIND_IN_SET(?, `main_table`.`stores`)', $storeId);
            $collection->getSelect()->order('position ASC');

            foreach ($collection as $item) {
                $label = $item->getName();
                $label = str_replace('오니츠카타이거', '', $label);
                $label = trim($label);

                $this->_options[] = [
                    'value' => $item->getId(),
                    'label' => $label
                ];
            }
        }

        return $this->_options;
    }



    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return $this->getAllOptions();
    }
}
