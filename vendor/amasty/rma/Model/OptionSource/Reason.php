<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\OptionSource;

use Amasty\Rma\Api\Data\ReasonInterface;
use Amasty\Rma\Model\Reason\ResourceModel\CollectionFactory as ReasonCollectionFactory;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;

class Reason implements OptionSourceInterface
{
    /**
     * @var ReasonCollectionFactory
     */
    private $reasonCollectionFactory;

    public function __construct(
        ReasonCollectionFactory $reasonCollectionFactory
    ) {
        $this->reasonCollectionFactory = $reasonCollectionFactory;
    }

    public function toOptionArray(): array
    {
        $result = [];
        $reasons = $this->reasonCollectionFactory->create()
            ->addFieldToSelect(
                [ReasonInterface::REASON_ID, ReasonInterface::TITLE, ReasonInterface::PAYER]
            )->setOrder(ReasonInterface::POSITION, Collection::SORT_ORDER_ASC)
            ->addNotDeletedFilter()
            ->addFieldToFilter(ReasonInterface::STATUS, Status::ENABLED)
            ->getData();

        if (!empty($reasons)) {
            $result[] = ['value' => '', 'label' => __('Please choose')];
            foreach ($reasons as $reason) {
                $result[] = [
                    'value' => $reason[ReasonInterface::REASON_ID],
                    'label' => $reason[ReasonInterface::TITLE],
                    'payer' => $reason[ReasonInterface::PAYER]
                ];
            }
        }

        return $result;
    }
}
