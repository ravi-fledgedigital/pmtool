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

use GuzzleHttp\Promise\PromiseInterface;
use Magento\AdobeCommerceWebhooks\Model\Cache\HookResponseCache;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Request\RequestParams;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\WebhookBatchRunnerException;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Converts batch of responses from webhooks to the operation representations
 */
class BatchResponseConverter implements BatchResponseConverterInterface
{
    /**
     * @param ResponseOperationFactory $responseOperationFactory
     * @param LoggerInterface $logger
     * @param HookResponseCache $hookResponseCache
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        private ResponseOperationFactory $responseOperationFactory,
        private LoggerInterface $logger,
        private HookResponseCache $hookResponseCache,
        private EventManagerInterface $eventManager,
    ) {
    }

    /**
     * Converts batch of responses from webhooks to the array of operation representations.
     *
     * Each hook may have multiple operations based on the response.
     *
     * @param array $responses
     * @param Hook[] $hooks
     * @param RequestParams[] $requestParams
     * @return array[]
     * @throws WebhookBatchRunnerException
     */
    public function convert(array $responses, array $hooks, array $requestParams): array
    {
        $operations = [];

        $this->sortResponsesByHookPriority($responses, $hooks);

        foreach ($responses as $hookName => $response) {
            $hook = $hooks[$hookName];

            try {
                switch ($response['state']) {
                    case HookResponseCache::CACHED_STATE:
                        $operations[$hookName] = $this->responseOperationFactory->createFromString(
                            $response['value'],
                            $hook
                        );
                        break;
                    case PromiseInterface::FULFILLED:
                        $operations[$hookName] = $this->responseOperationFactory->create($response['value'], $hook);
                        $response['value']->getBody()->rewind();
                        $bodyContents = $response['value']->getBody()->getContents();

                        $this->eventManager->dispatch(
                            'adobe_commerce_webhook_response_received',
                            [
                                'hook' => $hook,
                                'response' => $response['value'],
                                'response_body' => $bodyContents,
                            ]
                        );

                        $this->hookResponseCache->saveResponse(
                            $requestParams[$hookName],
                            $bodyContents,
                            $hook
                        );
                        break;
                    case PromiseInterface::REJECTED:
                        $this->handleRejectedPromise($response, $hook);
                        break;
                }
            } catch (ResponseException $e) {
                $errorMessage = sprintf(
                    'Can\'t process response from the hook "%s". Reason: %s',
                    $hook->getName(),
                    $e->getMessage()
                );
                $this->logger->error($errorMessage, ['hook' => $hook, 'destination' => ['internal', 'external']]);
                if ($hook->isRequired()) {
                    throw new WebhookBatchRunnerException(__($hook->getFallbackErrorMessage()), $e, $e->getCode());
                }
            }
        }

        return $operations;
    }

    /**
     * Sort responses by its hook priorities
     *
     * @param array $responses
     * @param Hook[] $hooks
     * @return void
     */
    private function sortResponsesByHookPriority(array &$responses, array $hooks): void
    {
        uksort($responses, function (string $responseA, string $responseB) use ($hooks) {
            return $hooks[$responseA]->getPriority() <=> $hooks[$responseB]->getPriority();
        });
    }

    /**
     * Handles a rejected promise by logging and throwing errors if required.
     *
     * @param array $response
     * @param Hook $hook
     * @return void
     * @throws WebhookBatchRunnerException
     */
    private function handleRejectedPromise(array $response, Hook $hook): void
    {
        $uiMessage = sprintf('The request for hook "%s" failed.', $hook->getName());
        $errorMessage = sprintf(
            $uiMessage . ' Reason: %s',
            $response['reason'] instanceof \Exception ? $response['reason']->getMessage() : 'undefined'
        );
        $this->logger->error(
            $errorMessage,
            ['hook' => $hook, 'ui_message' => $uiMessage, 'destination' => ['internal', 'external']]
        );
        if ($hook->isRequired()) {
            throw new WebhookBatchRunnerException(
                __($hook->getFallbackErrorMessage()),
                $response['reason'] instanceof \Exception ? $response['reason'] : null
            );
        }
    }
}
