<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api;

/**
 * Interface ConditionRepositoryInterface
 */
interface ConditionRepositoryInterface
{
    /**
     * @param int $conditionId
     * @param int|null $storeId
     *
     * @return \Amasty\Rma\Api\Data\ConditionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($conditionId, $storeId = null);

    /**
     * @param int $storeId
     * @param bool $enabledOnly
     * @param bool $withDeleted
     *
     * @return \Amasty\Rma\Api\Data\ConditionInterface[]
     */
    public function getConditionsByStoreId($storeId, $enabledOnly = true, $withDeleted = false);

    /**
     * @param \Amasty\Rma\Api\Data\ConditionInterface $condition
     *
     * @return \Amasty\Rma\Api\Data\ConditionInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Amasty\Rma\Api\Data\ConditionInterface $condition);

    /**
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function clearDeleted();

    /**
     * @param \Amasty\Rma\Api\Data\ConditionInterface $condition
     *
     * @return bool true on success
     */
    public function delete(\Amasty\Rma\Api\Data\ConditionInterface $condition);

    /**
     * @param int $conditionId
     *
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($conditionId);

    /**
     * @return \Amasty\Rma\Api\Data\ConditionInterface
     */
    public function getEmptyConditionModel();

    /**
     * @return \Amasty\Rma\Api\Data\ConditionStoreInterface
     */
    public function getEmptyConditionStoreModel();
}
