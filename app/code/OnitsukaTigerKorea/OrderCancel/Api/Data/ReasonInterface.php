<?php

namespace OnitsukaTigerKorea\OrderCancel\Api\Data;

/**
 * Interface ReasonInterface
 */
interface ReasonInterface
{

    public const REASON_ID = 'reason_id';
    public const TITLE = 'title';
    public const STATUS = 'status';
    public const STORES = 'stores';
    public const LABEL = 'label';

    /**
     * @param int $reasonId
     *
     * @return ReasonInterface
     */
    public function setReasonId(int $reasonId): ReasonInterface;

    /**
     * @return int
     */
    public function getReasonId(): int;

    /**
     * @param string $title
     *
     * @return ReasonInterface
     */
    public function setTitle(string $title): ReasonInterface;


    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @param int $status
     *
     * @return ReasonInterface
     */
    public function setStatus(int $status): ReasonInterface;

    /**
     * @return int
     */
    public function getStatus(): int;

    /**
     * @param ReasonStoreInterface[]
     *
     * @return ReasonInterface
     */
    public function setStores($stores): ReasonInterface;


    /**
     * @return ReasonStoreInterface[]
     */
    public function getStores(): array;

    /**
     * @param string $label
     *
     * @return ReasonInterface
     */
    public function setLabel(string $label): ReasonInterface;

    /**
     * @return string
     */
    public function getLabel(): string;

}
