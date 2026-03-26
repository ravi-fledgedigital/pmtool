<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api\Data;

interface HistoryInterface
{
    /**#@+
     * Constants defined for keys of data array
     */
    public const EVENT_ID = 'event_id';
    public const REQUEST_ID = 'request_id';
    public const EVENT_DATE = 'event_date';
    public const EVENT_TYPE = 'event_type';
    public const EVENT_DATA = 'event_data';
    public const EVENT_INITIATOR = 'event_initiator';
    public const EVENT_INITIATOR_NAME = 'event_initiator_name';
    public const MESSAGE = 'message';
    /**#@-*/

    /**
     * @return int
     */
    public function getEventId();

    /**
     * @param int $eventId
     *
     * @return \Amasty\Rma\Api\Data\HistoryInterface
     */
    public function setEventId($eventId);

    /**
     * @return int
     */
    public function getRequestId();

    /**
     * @param int $requestId
     *
     * @return \Amasty\Rma\Api\Data\HistoryInterface
     */
    public function setRequestId($requestId);

    /**
     * @return string
     */
    public function getEventDate();

    /**
     * @return int
     */
    public function getEventType();

    /**
     * @param int $eventType
     *
     * @return \Amasty\Rma\Api\Data\HistoryInterface
     */
    public function setEventType($eventType);

    /**
     * @return array
     */
    public function getEventData();

    /**
     * @param array $data
     *
     * @return \Amasty\Rma\Api\Data\HistoryInterface
     */
    public function setEventData($data);

    /**
     * @return int
     */
    public function getEventInitiator();

    /**
     * @param int $initiator
     *
     * @return \Amasty\Rma\Api\Data\HistoryInterface
     */
    public function setEventInitiator($initiator);

    /**
     * @return string
     */
    public function getEventInitiatorName();

    /**
     * @param string $initiatorName
     *
     * @return \Amasty\Rma\Api\Data\HistoryInterface
     */
    public function setEventInitiatorName($initiatorName);

    /**
     * @param string $message
     *
     * @return \Amasty\Rma\Api\Data\HistoryInterface
     */
    public function setMessage($message);

    /**
     * @return string
     */
    public function getMessage();
}
