<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\Response;

use Magento\Framework\Serialize\SerializerInterface;
use Psr\Http\Message\ResponseInterface;
use Vaimo\AepEventStreaming\Api\Data\IngestRecordInterface;
use Vaimo\AepEventStreaming\Exception\MissingActionIdException;

class IngestRecord implements IngestRecordInterface
{
    private const KEY_INLET_ID = 'inletId';
    private const KEY_ACTION_ID = 'xactionId';
    private const KEY_RECEIVED_TIME = 'receivedTimeMs';

    private SerializerInterface $serializer;
    private string $inletId;
    private string $actionId;
    private int $receivedTime;

    /**
     * @throws \InvalidArgumentException
     * @throws MissingActionIdException
     */
    public function __construct(
        SerializerInterface $serializer,
        ResponseInterface $response
    ) {
        $this->serializer = $serializer;
        $this->decodeResponse($response);
    }

    public function getInletId(): string
    {
        return $this->inletId;
    }

    public function getActionId(): string
    {
        return $this->actionId;
    }

    public function getReceivedTime(): int
    {
        return $this->receivedTime;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws MissingActionIdException
     */
    private function decodeResponse(ResponseInterface $response): void
    {
        $jsonBody = $this->serializer->unserialize($response->getBody());

        if (
            !isset($jsonBody[self::KEY_INLET_ID])
            || !isset($jsonBody[self::KEY_ACTION_ID])
            || !isset($jsonBody[self::KEY_RECEIVED_TIME])
        ) {
            throw new \InvalidArgumentException('Invalid json data. Missing field.');
        }

        if (empty($jsonBody[self::KEY_ACTION_ID])) {
            throw new MissingActionIdException();
        }

        $this->inletId = $jsonBody[self::KEY_INLET_ID];
        $this->actionId = $jsonBody[self::KEY_ACTION_ID];
        $this->receivedTime = (int) $jsonBody[self::KEY_RECEIVED_TIME];
    }
}
