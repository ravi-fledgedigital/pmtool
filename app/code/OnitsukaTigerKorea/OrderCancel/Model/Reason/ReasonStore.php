<?php

namespace OnitsukaTigerKorea\OrderCancel\Model\Reason;

use OnitsukaTigerKorea\OrderCancel\Api\Data\ReasonStoreInterface;
use Magento\Framework\Model\AbstractModel;

class ReasonStore extends AbstractModel implements ReasonStoreInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(ResourceModel\ReasonStore::class);
        $this->setIdFieldName(ReasonStoreInterface::REASON_STORE_ID);
    }

    /**
     * @param int $reasonStoreId
     *
     * @return ReasonStoreInterface
     */
    public function setReasonStoreId(int $reasonStoreId): ReasonStoreInterface
    {
        return $this->setData(ReasonStoreInterface::REASON_STORE_ID, $reasonStoreId);
    }

    /**
     * @return int
     */
    public function getReasonStoreId(): int
    {
        return (int) $this->_getData(ReasonStoreInterface::REASON_STORE_ID);
    }

    /**
     * @param int $reasonId
     *
     * @return ReasonStoreInterface
     */
    public function setReasonId(int $reasonId): ReasonStoreInterface
    {
        return $this->setData(ReasonStoreInterface::REASON_ID, $reasonId);
    }

    /**
     * @return int
     */
    public function getReasonId(): int
    {
        return (int)$this->_getData(ReasonStoreInterface::REASON_ID);
    }

    /**
     * @param int $storeId
     *
     * @return ReasonStoreInterface
     */
    public function setStoreId(int $storeId): ReasonStoreInterface
    {
        return $this->setData(ReasonStoreInterface::STORE_ID, $storeId);
    }

    /**
     * @return int
     */
    public function getStoreId(): int
    {
        return (int)$this->_getData(ReasonStoreInterface::STORE_ID);
    }

    /**
     * @param string $label
     *
     * @return ReasonStoreInterface
     */
    public function setLabel(string $label): ReasonStoreInterface
    {
        return $this->setData(ReasonStoreInterface::LABEL, $label);
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->_getData(ReasonStoreInterface::LABEL);
    }
}
