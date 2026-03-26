<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api;

/**
 * Interface ReasonRepositoryInterface
 */
interface ReasonRepositoryInterface
{
    /**
     * @param int $reasonId
     * @param int|null $storeId
     *
     * @return \Amasty\Rma\Api\Data\ReasonInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($reasonId, $storeId = null);

    /**
     * @param int $storeId
     * @param bool $enabledOnly
     * @param bool $withDeleted
     *
     * @return \Amasty\Rma\Api\Data\ReasonInterface[]
     */
    public function getReasonsByStoreId($storeId, $enabledOnly = true, $withDeleted = false);

    /**
     * @param \Amasty\Rma\Api\Data\ReasonInterface $reason
     *
     * @return \Amasty\Rma\Api\Data\ReasonInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Amasty\Rma\Api\Data\ReasonInterface $reason);

    /**
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function clearDeleted();

    /**
     * @param \Amasty\Rma\Api\Data\ReasonInterface $reason
     *
     * @return bool true on success
     */
    public function delete(\Amasty\Rma\Api\Data\ReasonInterface $reason);

    /**
     * @param int $reasonId
     *
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($reasonId);

    /**
     * @return \Amasty\Rma\Api\Data\ReasonInterface
     */
    public function getEmptyReasonModel();

    /**
     * @return \Amasty\Rma\Api\Data\ReasonStoreInterface
     */
    public function getEmptyReasonStoreModel();
}
