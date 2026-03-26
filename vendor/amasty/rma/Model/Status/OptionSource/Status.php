<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Status\OptionSource;

use Amasty\Rma\Api\Data\StatusInterface;
use Magento\Framework\Option\ArrayInterface;

class Status implements ArrayInterface
{
    /**
     * @var \Amasty\Rma\Model\Status\Repository
     */
    private $repository;

    public function __construct(
        \Amasty\Rma\Model\Status\Repository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $optionArray = [];
        foreach ($this->toArray() as $value => $label) {
            $optionArray[] = ['value' => $value, 'label' => $label];
        }
        return $optionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $result = [];

        $statusCollection = $this->repository->getEmptyStatusCollection()
            ->addFieldToFilter(StatusInterface::IS_ENABLED, 1)
            ->addNotDeletedFilter()
            ->addFieldToSelect([StatusInterface::STATUS_ID, StatusInterface::TITLE])
            ->setOrder(StatusInterface::PRIORITY, \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        foreach ($statusCollection->getData() as $status) {
            $result[$status[StatusInterface::STATUS_ID]] = $status[StatusInterface::TITLE];
        }

        return $result;
    }
}
