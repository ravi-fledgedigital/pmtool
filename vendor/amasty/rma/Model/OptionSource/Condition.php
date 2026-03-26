<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\OptionSource;

use Amasty\Rma\Api\Data\ConditionInterface;
use Amasty\Rma\Model\Condition\ResourceModel\CollectionFactory as ConditionCollectionFactory;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;

class Condition implements OptionSourceInterface
{
    /**
     * @var ConditionCollectionFactory
     */
    private $conditionCollectionFactory;

    public function __construct(
        ConditionCollectionFactory $conditionCollectionFactory
    ) {
        $this->conditionCollectionFactory = $conditionCollectionFactory;
    }

    public function toOptionArray(): array
    {
        $result = [];
        $conditions = $this->conditionCollectionFactory->create()
            ->addFieldToSelect(
                [ConditionInterface::CONDITION_ID, ConditionInterface::TITLE]
            )->setOrder(ConditionInterface::POSITION, Collection::SORT_ORDER_ASC)
            ->addNotDeletedFilter()
            ->addFieldToFilter(ConditionInterface::STATUS, Status::ENABLED);

        $conditions = $conditions->getData();
        if (!empty($conditions)) {
            $result[] = ['value' => '', 'label' => __('Please choose')];
            foreach ($conditions as $condition) {
                $result[] = [
                    'value' => $condition[ConditionInterface::CONDITION_ID],
                    'label' => $condition[ConditionInterface::TITLE]
                ];
            }
        }

        return $result;
    }
}
