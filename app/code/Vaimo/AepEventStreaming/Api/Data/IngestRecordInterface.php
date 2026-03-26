<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Api\Data;

/**
 * @source https://experienceleague.adobe.com/docs/experience-platform/ingestion/tutorials/streaming-record-data.html?lang=en#ingest-data
 */
interface IngestRecordInterface
{
    /**
     * Used for debugging in orders and customer
     */
    public const ATTRIBUTE_CODE = 'aep_last_action_id';

    /**
     * The ID of the previously created streaming connection.
     * @return string
     */
    public function getInletId(): string;

    /**
     * A unique identifier generated server-side for the record you just sent.
     * This ID helps Adobe trace this record’s lifecycle through various systems and with debugging.
     * @return string
     */
    public function getActionId(): string;

    /**
     * A timestamp (epoch in milliseconds) that shows what time the request was received.
     * @return int
     */
    public function getReceivedTime(): int;
}
