<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\OptionSource;

use Amasty\Rma\Api\Data\ResolutionInterface;
use Amasty\Rma\Model\Resolution\ResourceModel\CollectionFactory as ResolutionCollectionFactory;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;

class Resolution implements OptionSourceInterface
{
    /**
     * @var ResolutionCollectionFactory
     */
    private $resolutionCollectionFactory;

    public function __construct(
        ResolutionCollectionFactory $resolutionCollectionFactory
    ) {
        $this->resolutionCollectionFactory = $resolutionCollectionFactory;
    }

    public function toOptionArray(): array
    {
        $result = [];
        $resolutions = $this->resolutionCollectionFactory->create()
            ->addFieldToSelect(
                [ResolutionInterface::RESOLUTION_ID, ResolutionInterface::TITLE]
            )->setOrder(ResolutionInterface::POSITION, Collection::SORT_ORDER_ASC)
            ->addNotDeletedFilter()
            ->addFieldToFilter(ResolutionInterface::STATUS, Status::ENABLED)
            ->getData();

        if (!empty($resolutions)) {
            $result[] = ['value' => '', 'label' => __('Please choose')];
            foreach ($resolutions as $resolution) {
                $result[] = [
                    'value' => $resolution[ResolutionInterface::RESOLUTION_ID],
                    'label' => $resolution[ResolutionInterface::TITLE]
                ];
            }
        }

        return $result;
    }
}
