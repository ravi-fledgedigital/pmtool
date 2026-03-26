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

namespace Magento\AdobeCommerceWebhooks\Model\WebhookRunner;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use LogicException;
use Magento\AdobeCommerceWebhooks\Model\Cache\HookResponseCache;
use Magento\AdobeCommerceWebhooks\Model\Rule\OperatorException;
use Magento\AdobeCommerceWebhooks\Model\Rule\RuleCheckerInterface;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Batch;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Request\RequestParams;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Request\RequestParamsHookFactory;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\BatchResponseConverterInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Runs batch of webhooks based on its configuration
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class WebhookBatchRunner implements WebhookBatchRunnerInterface
{
    /**
     * @param ClientFactory $clientFactory
     * @param BatchResponseConverterInterface $batchResponseConverter
     * @param LoggerInterface $logger
     * @param HookResponseCache $hookResponseCache
     * @param RequestParamsHookFactory $requestParamsHookFactory
     * @param RuleCheckerInterface $ruleChecker
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        private ClientFactory $clientFactory,
        private BatchResponseConverterInterface $batchResponseConverter,
        private LoggerInterface $logger,
        private HookResponseCache $hookResponseCache,
        private RequestParamsHookFactory $requestParamsHookFactory,
        private RuleCheckerInterface $ruleChecker,
        private EventManagerInterface $eventManager
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Batch $batch, array $webhookData): array
    {
        try {
            /** @var GuzzleHttpClient $client */
            $client = $this->clientFactory->create();

            $hooks = $this->getHooksFromBatch($batch, $webhookData);
            if (empty($hooks)) {
                return [];
            }
            $resolvedRequestParams = $this->resolveRequestParameters($hooks, $webhookData);
            $responses = $this->executeHooks($client, $hooks, $resolvedRequestParams);

            return $this->batchResponseConverter->convert($responses, $hooks, $resolvedRequestParams);
        } catch (InvalidArgumentException|LogicException|OperatorException $e) {
            throw new WebhookBatchRunnerException(__($e->getMessage()), $e, $e->getCode());
        }
    }

    /**
     * Returns an array containing a RequestParams object for each Hook.
     *
     * @param array $hooks
     * @param array $webhookData
     * @return RequestParams[]
     * @throws InvalidArgumentException
     */
    private function resolveRequestParameters(array $hooks, array $webhookData): array
    {
        $requestParams = [];
        foreach ($hooks as $hook) {
            $this->eventManager->dispatch(
                'adobe_commerce_webhook_start_process_hook',
                [
                    'hook' => $hook,
                ]
            );

            $requestParams[$hook->getName()] = $this->requestParamsHookFactory->create(
                $hook,
                $webhookData
            );
        }

        return $requestParams;
    }

    /**
     * Executes hooks in parallel and returns array of responses
     *
     * @param GuzzleHttpClient $client
     * @param Hook[] $hooks
     * @param RequestParams[] $resolvedRequestParams
     * @return array
     * @throws LogicException
     */
    private function executeHooks(
        GuzzleHttpClient $client,
        array $hooks,
        array $resolvedRequestParams
    ): array {
        $promises = [];
        $cachedResponses = [];
        foreach ($hooks as $hook) {
            $hookRequestParams = $resolvedRequestParams[$hook->getName()];
            $cachedResponse = $this->hookResponseCache->getResponse($hookRequestParams, $hook);

            if ($cachedResponse !== null) {
                $cachedResponses[$hook->getName()] = [
                    'state' => HookResponseCache::CACHED_STATE,
                    'value' => $cachedResponse
                ];
                continue;
            }

            $requestOptions = $this->getRequestOptions($hook, $hookRequestParams);
            $promise = $client->requestAsync(
                $hook->getMethod() ?: 'POST',
                $hookRequestParams->getUrl(),
                $requestOptions
            );
            $promise->then(
                function ($response) use ($hook, $hookRequestParams) {
                    $this->eventManager->dispatch(
                        'adobe_commerce_webhook_request_success',
                        [
                            'hook' => $hook,
                            'request_params' => $hookRequestParams,
                            'response' => $response
                        ]
                    );
                    return $response;
                },
                function ($exception) use ($hook, $hookRequestParams) {
                    $this->eventManager->dispatch(
                        'adobe_commerce_webhook_request_rejected',
                        [
                            'hook' => $hook,
                            'request_params' => $hookRequestParams,
                            'exception' => $exception
                        ]
                    );
                    throw $exception;
                }
            );

            $promises[$hook->getName()] = $promise;

            $this->eventManager->dispatch(
                'adobe_commerce_webhook_before_request_sent',
                [
                    'hook' => $hook,
                    'request_params' => $hookRequestParams,
                    'request_options' => $requestOptions
                ]
            );
        }

        return array_merge(Utils::settle($promises)->wait(), $cachedResponses);
    }

    /**
     * Returns an array of hooks with hook name as index.
     *
     * Does not add hook if it does not satisfy all hook rules.
     *
     * @param Batch $batch
     * @param array $webhookData
     * @return Hook[]
     * @throws OperatorException
     */
    private function getHooksFromBatch(Batch $batch, array $webhookData): array
    {
        $hooks = [];
        foreach ($batch->getHooks() as $hook) {
            if ($hook->shouldRemove()) {
                continue;
            }

            if ($this->ruleChecker->verify($hook, $webhookData)) {
                $hooks[$hook->getName()] = $hook;
            }
        }

        return $hooks;
    }

    /**
     * Returns an array of request options for the given hook and request parameters.
     *
     * @param Hook $hook
     * @param RequestParams $hookRequestParams
     * @return array
     */
    private function getRequestOptions(Hook $hook, RequestParams $hookRequestParams): array
    {
        $requestOptions = [
            RequestOptions::HTTP_ERRORS => true,
            RequestOptions::JSON => $hookRequestParams->getBody(),
            RequestOptions::HEADERS => $hookRequestParams->getHeaders(),
            RequestOptions::TIMEOUT => $hook->getTimeout() / 1000,
            RequestOptions::ON_STATS => $this->onStat($hook)
        ];

        if (!$hook->isSslVerificationEnabled()) {
            $requestOptions[RequestOptions::VERIFY] = false;
        } elseif (!empty($hook->getSslCertificatePath())) {
            $requestOptions[RequestOptions::VERIFY] = $hook->getSslCertificatePath();
        }

        return $requestOptions;
    }

    /**
     * Checks that the request execution time doesn't exceed the soft timeout limit and logs a warning otherwise.
     *
     * @param Hook $hook
     * @return callable
     */
    private function onStat(Hook $hook): callable
    {
        return function (TransferStats $stats) use ($hook) {
            $this->eventManager->dispatch(
                'adobe_commerce_webhook_request_completed',
                [
                    'hook' => $hook,
                    'stats' => $stats
                ]
            );

            $transferTime = $stats->getTransferTime() * 1000;
            if (!empty($hook->getSoftTimeout()) &&
                $transferTime > $hook->getSoftTimeout() &&
                $stats->hasResponse()
            ) {
                $this->logger->warning(
                    sprintf(
                        'Request to url %s for hook "%s" exceeded the soft timeout value of %d ms. ' .
                        'Request execution time: %f ms.',
                        $hook->getUrl(),
                        $hook->getName(),
                        $hook->getSoftTimeout(),
                        $transferTime
                    ),
                    ['hook' => $hook, 'destination' => ['external', 'internal']]
                );
            } else {
                $this->logger->debug(
                    sprintf(
                        'Request to url %s for hook "%s" executed in %f ms.',
                        $hook->getUrl(),
                        $hook->getName(),
                        $transferTime
                    ),
                    ['hook' => $hook, 'destination' => ['external', 'internal']]
                );
            }
        };
    }
}
