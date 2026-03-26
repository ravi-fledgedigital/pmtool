<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api;

/**
 * Interface ResolutionRepositoryInterface
 */
interface ResolutionRepositoryInterface
{
    /**
     * @param int $resolutionId
     * @param int|null $storeId
     *
     * @return \Amasty\Rma\Api\Data\ResolutionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($resolutionId, $storeId = null);

    /**
     * @param int $storeId
     * @param bool $enabledOnly
     * @param bool $withDeleted
     *
     * @return \Amasty\Rma\Api\Data\ResolutionInterface[]
     */
    public function getResolutionsByStoreId($storeId, $enabledOnly = true, $withDeleted = false);

    /**
     * @param \Amasty\Rma\Api\Data\ResolutionInterface $resolution
     *
     * @return \Amasty\Rma\Api\Data\ResolutionInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Amasty\Rma\Api\Data\ResolutionInterface $resolution);

    /**
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function clearDeleted();

    /**
     * @param \Amasty\Rma\Api\Data\ResolutionInterface $resolution
     *
     * @return bool true on success
     */
    public function delete(\Amasty\Rma\Api\Data\ResolutionInterface $resolution);

    /**
     * @param int $resolutionId
     *
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($resolutionId);

    /**
     * @return \Amasty\Rma\Api\Data\ResolutionInterface
     */
    public function getEmptyResolutionModel();

    /**
     * @return \Amasty\Rma\Api\Data\ResolutionStoreInterface
     */
    public function getEmptyResolutionStoreModel();
}
