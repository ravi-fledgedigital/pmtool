<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api;

/**
 * Interface StatusRepositoryInterface
 */
interface StatusRepositoryInterface
{
    /**
     * @param int $statusId
     * @param int|null $storeId
     *
     * @return \Amasty\Rma\Api\Data\StatusInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($statusId, $storeId = null);

    /**
     * @param int $storeId
     * @param bool $enabledOnly
     * @param bool $withDeleted
     *
     * @return \Amasty\Rma\Api\Data\StatusInterface[]
     */
    public function getStatusesByStoreId($storeId, $enabledOnly = true, $withDeleted = false);

    /**
     * @param \Amasty\Rma\Api\Data\StatusInterface $status
     *
     * @return \Amasty\Rma\Api\Data\StatusInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Amasty\Rma\Api\Data\StatusInterface $status);

    /**
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function clearDeleted();

    /**
     * @param \Amasty\Rma\Api\Data\StatusInterface $status
     *
     * @return bool true on success
     */
    public function delete(\Amasty\Rma\Api\Data\StatusInterface $status);

    /**
     * @param int $statusId
     *
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($statusId);

    /**
     * @return int
     */
    public function getInitialStatusId();

    /**
     * @return int
     */
    public function getCancelStatusId();

    /**
     * @return \Amasty\Rma\Api\Data\StatusInterface
     */
    public function getEmptyStatusModel();

    /**
     * @return \Amasty\Rma\Api\Data\StatusStoreInterface
     */
    public function getEmptyStatusStoreModel();

    /**
     * @return \Amasty\Rma\Model\Status\ResourceModel\Collection
     */
    public function getEmptyStatusCollection();
}
