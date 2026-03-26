<?php

namespace OnitsukaTigerKorea\OrderCancel\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTigerKorea\OrderCancel\Api\Data\ReasonInterface;
use OnitsukaTigerKorea\OrderCancel\Api\Data\ReasonStoreInterface;

/**
 * Interface ReasonRepositoryInterface
 */
interface ReasonRepositoryInterface
{
    /**
     * @param int $reasonId
     * @param int|null $storeId
     *
     * @return ReasonInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $reasonId, int $storeId = null): ReasonInterface;

    /**
     * @param int $storeId
     * @param bool $enabledOnly
     *
     * @return ReasonInterface[]
     */
    public function getReasonsByStoreId(int $storeId, bool $enabledOnly = true): array;

    /**
     * @param ReasonInterface $reason
     *
     * @return ReasonInterface
     * @throws CouldNotSaveException
     */
    public function save(ReasonInterface $reason): ReasonInterface;

    /**
     * @param ReasonInterface $reason
     *
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function delete(ReasonInterface $reason): bool;

    /**
     * @param int $reasonId
     *
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $reasonId): bool;

    /**
     * @return ReasonInterface
     */
    public function getEmptyReasonModel(): ReasonInterface;

    /**
     * @return ReasonStoreInterface
     */
    public function getEmptyReasonStoreModel(): ReasonStoreInterface;
}
