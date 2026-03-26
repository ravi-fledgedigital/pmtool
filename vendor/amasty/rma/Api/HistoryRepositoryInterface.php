<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api;

interface HistoryRepositoryInterface
{
    /**
     * Create
     *
     * @param \Amasty\Rma\Api\Data\HistoryInterface $history
     * @return \Amasty\Rma\Api\Data\HistoryInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function create(\Amasty\Rma\Api\Data\HistoryInterface $history);

    /**
     * Get by id
     *
     * @param int $eventId
     * @return \Amasty\Rma\Api\Data\HistoryInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($eventId);

    /**
     * Lists
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * @param int $requestId
     *
     * @return \Amasty\Rma\Api\Data\HistoryInterface[]
     */
    public function getRequestEvents($requestId);

    /**
     * @return \Amasty\Rma\Api\Data\HistoryInterface
     */
    public function getEmptyEventModel();

    /**
     * @return \Amasty\Rma\Model\History\ResourceModel\Collection
     */
    public function getEmptyEventCollection();
}
