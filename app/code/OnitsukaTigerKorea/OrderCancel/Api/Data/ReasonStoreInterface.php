<?php

namespace OnitsukaTigerKorea\OrderCancel\Api\Data;

/**
 * Interface ReasonStoreInterface
 */
interface ReasonStoreInterface
{
    public const REASON_STORE_ID = 'reason_store_id';
    public const REASON_ID = 'reason_id';
    public const STORE_ID = 'store_id';
    public const LABEL = 'label';

    /**
     * @param int $reasonStoreId
     *
     * @return ReasonStoreInterface
     */
    public function setReasonStoreId(int $reasonStoreId): ReasonStoreInterface;

    /**
     * @return int
     */
    public function getReasonStoreId(): int;

    /**
     * @param int $reasonId
     *
     * @return ReasonStoreInterface
     */
    public function setReasonId(int $reasonId): ReasonStoreInterface;

    /**
     * @return int
     */
    public function getReasonId(): int;

    /**
     * @param int $storeId
     *
     * @return ReasonStoreInterface
     */
    public function setStoreId(int $storeId): ReasonStoreInterface;

    /**
     * @return int
     */
    public function getStoreId(): int;

    /**
     * @param string $label
     *
     * @return ReasonStoreInterface
     */
    public function setLabel(string $label): ReasonStoreInterface;

    /**
     * @return string
     */
    public function getLabel(): string;
}
