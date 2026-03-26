<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\History;

use Amasty\Rma\Api\Data\HistoryInterface;
use Magento\Framework\Model\AbstractModel;

class History extends AbstractModel implements HistoryInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Rma\Model\History\ResourceModel\History::class);
        $this->setIdFieldName(HistoryInterface::EVENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function getEventId()
    {
        return (int)$this->_getData(HistoryInterface::EVENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEventId($eventId)
    {
        return $this->setData(HistoryInterface::EVENT_ID, (int)$eventId);
    }

    /**
     * @inheritDoc
     */
    public function getRequestId()
    {
        return (int)$this->_getData(HistoryInterface::REQUEST_ID);
    }

    /**
     * @inheritDoc
     */
    public function setRequestId($requestId)
    {
        return $this->setData(HistoryInterface::REQUEST_ID, (int)$requestId);
    }

    /**
     * @inheritDoc
     */
    public function getEventDate()
    {
        return $this->_getData(HistoryInterface::EVENT_DATE);
    }

    /**
     * @inheritDoc
     */
    public function getEventType()
    {
        return (int)$this->_getData(HistoryInterface::EVENT_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setEventType($eventType)
    {
        return $this->setData(HistoryInterface::EVENT_TYPE, (int)$eventType);
    }

    /**
     * @inheritDoc
     */
    public function getEventData()
    {
        if ($data = $this->_getData(HistoryInterface::EVENT_DATA)) {
            $data = json_decode($data, true);
            if (!json_last_error()) {
                return $data;
            }
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function setEventData($data)
    {
        if (!is_array($data)) {
            $data = [];
        }

        return $this->setData(HistoryInterface::EVENT_DATA, json_encode($data));
    }

    /**
     * @inheritDoc
     */
    public function getEventInitiator()
    {
        return (int)$this->_getData(HistoryInterface::EVENT_INITIATOR);
    }

    /**
     * @inheritDoc
     */
    public function setEventInitiator($initiator)
    {
        return $this->setData(HistoryInterface::EVENT_INITIATOR, (int)$initiator);
    }

    /**
     * @inheritDoc
     */
    public function getEventInitiatorName()
    {
        return $this->getData(HistoryInterface::EVENT_INITIATOR_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setEventInitiatorName($initiatorName)
    {
        return $this->setData(HistoryInterface::EVENT_INITIATOR_NAME, $initiatorName);
    }

    /**
     * @inheritDoc
     */
    public function setMessage($message)
    {
        return $this->setData(HistoryInterface::MESSAGE, $message);
    }

    /**
     * @inheritDoc
     */
    public function getMessage()
    {
        return $this->setData(HistoryInterface::MESSAGE);
    }
}
