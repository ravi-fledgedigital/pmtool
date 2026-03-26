<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response;

use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Http\Message\ResponseInterface;

/**
 * Creates an array of OperationInterface instances based on response.
 */
class ResponseOperationFactory
{
    public const OPERATION = 'op';

    /**
     * @param OperationFactory $operationFactory
     * @param Json $json
     */
    public function __construct(
        private OperationFactory $operationFactory,
        private Json $json,
    ) {
    }

    /**
     * Creates an array of instances of OperationInterface based on the response from the webhook.
     *
     * @param ResponseInterface $response
     * @param Hook $hook
     * @return OperationInterface[]
     * @throws ResponseException If response has wrong format
     */
    public function create(ResponseInterface $response, Hook $hook): array
    {
        if ($response->getStatusCode() !== 200) {
            throw new ResponseException(
                __('Response status is not success: %1, %2', $response->getStatusCode(), $response->getReasonPhrase())
            );
        }

        $responseContent = $response->getBody()->getContents();

        return $this->createFromString($responseContent, $hook);
    }

    /**
     * Creates an array of instances of OperationInterface given a json response from a webhook.
     *
     * @param string $response
     * @param Hook $hook
     * @return OperationInterface[]
     * @throws ResponseException If response json has wrong format
     */
    public function createFromString(string $response, Hook $hook): array
    {
        try {
            $responseJson = $this->json->unserialize($response);
        } catch (\InvalidArgumentException $e) {
            throw new ResponseException(__('Unable to parse response %1', $e->getMessage()));
        }

        if (!is_array($responseJson)) {
            throw new ResponseException(__('The response has a wrong format'));
        }

        if (isset($responseJson[self::OPERATION])) {
            return [$this->createOperation($responseJson, $hook)];
        }

        $operations = [];
        foreach ($responseJson as $responseJsonOperation) {
            if (!isset($responseJsonOperation[self::OPERATION])) {
                throw new ResponseException(__('The response has a wrong format. Unable to read operation value.'));
            }
            $operations[] = $this->createOperation($responseJsonOperation, $hook);
        }

        if (empty($operations)) {
            throw new ResponseException(__('The response must contain at least one operation.'));
        }

        return $operations;
    }

    /**
     * Creates an instance of OperationInterface.
     *
     * @param array $responseJson
     * @param Hook $hook
     * @return OperationInterface
     * @throws ResponseException
     */
    private function createOperation(array $responseJson, Hook $hook): OperationInterface
    {
        try {
            return $this->operationFactory->create(
                $responseJson[self::OPERATION],
                $hook,
                $responseJson
            );
        } catch (NotFoundException|InvalidArgumentException $e) {
            throw new ResponseException(__('Can not process the response: %1', $e->getMessage()));
        }
    }
}
