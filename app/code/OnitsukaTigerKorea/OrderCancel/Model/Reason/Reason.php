<?php

namespace OnitsukaTigerKorea\OrderCancel\Model\Reason;

use OnitsukaTigerKorea\OrderCancel\Api\Data\ReasonInterface;
use Magento\Framework\Model\AbstractModel;
use OnitsukaTigerKorea\OrderCancel\Api\Data\ReasonStoreInterface;

class Reason extends AbstractModel implements ReasonInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(ResourceModel\Reason::class);
        $this->setIdFieldName(ReasonInterface::REASON_ID);
    }

    /**
     * @param int $reasonId
     *
     * @return ReasonInterface
     */
    public function setReasonId(int $reasonId): ReasonInterface
    {
        return $this->setData(ReasonInterface::REASON_ID, $reasonId);
    }

    /**
     * @return int
     */
    public function getReasonId(): int
    {
        return (int)$this->_getData(ReasonInterface::REASON_ID);
    }

    /**
     * @param string $title
     *
     * @return ReasonInterface
     */
    public function setTitle(string $title): ReasonInterface
    {
        return $this->setData(ReasonInterface::TITLE, $title);
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->_getData(ReasonInterface::TITLE);
    }

    /**
     * @param int $status
     * @return ReasonInterface
     */
    public function setStatus(int $status): ReasonInterface
    {
        return $this->setData(ReasonInterface::STATUS, $status);
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return (int)$this->_getData(ReasonInterface::STATUS);
    }

    /**
     * @param ReasonStoreInterface[]
     *
     * @return ReasonInterface
     */
    public function setStores($stores): ReasonInterface
    {
        return $this->setData(ReasonInterface::STORES, $stores);
    }

    /**
     * @return ReasonStoreInterface[]
     */
    public function getStores(): array
    {
        return $this->_getData(ReasonInterface::STORES);
    }

    /**
     * @param string $label
     *
     * @return ReasonInterface
     */
    public function setLabel(string $label): ReasonInterface
    {
        return $this->setData(ReasonInterface::LABEL, $label);
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->_getData(ReasonInterface::LABEL);
    }

}
