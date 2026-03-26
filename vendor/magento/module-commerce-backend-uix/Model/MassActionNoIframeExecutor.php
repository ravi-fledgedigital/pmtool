<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Model;

use Exception;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\CommerceBackendUix\Model\BannerNotificationFilter;

/**
 * Execute mass action without displaying an iFrame, this class handles execution for all grids
 */
class MassActionNoIframeExecutor
{
    private const CONTENT_TYPE_HEADER = 'Content-Type';
    private const APPLICATION_JSON = 'application/json';
    private const ACCEPT_HEADER = 'Accept';
    private const AUTHORIZATION_HEADER = 'Authorization';
    private const IMS_ORGANIZATION_HEADER = 'x-gw-ims-org-id';
    private const REQUEST_ID_HEADER = 'X-Request-ID';
    private const BEARER = 'Bearer ';
    private const STATUS_CODE_TIMEOUT = 408;
    private const STATUS_CODE_INTERNAL_SERVER_ERROR = 500;
    private const STATUS_CODE_OK = 200;
    private const PATH = 'path';
    private const TIMEOUT = 'timeout';
    private const DEFAULT_TIMEOUT = 10; //seconds

    /**
     * @param ClientInterface $httpClient
     * @param Cache $cache
     * @param Config $config
     * @param Json $json
     * @param MessageManagerInterface $messageManager
     * @param IdentityGeneratorInterface $identityGenerator
     * @param MassActionNoIframeErrorHandler $errorHandler
     * @param BannerNotificationFilter $bannerNotificationFilter
     */
    public function __construct(
        private ClientInterface $httpClient,
        private Cache $cache,
        private Config $config,
        private Json $json,
        private MessageManagerInterface $messageManager,
        private IdentityGeneratorInterface $identityGenerator,
        private MassActionNoIframeErrorHandler $errorHandler,
        private BannerNotificationFilter $bannerNotificationFilter
    ) {
    }

    /**
     * Executes action of mass action without iFrame
     *
     * @param array $massAction
     * @param array $selectedIds
     * @param string $extensionId
     * @param string $gridType
     * @return void
     */
    public function execute(array $massAction, array $selectedIds, string $extensionId, string $gridType): void
    {
        $extensionUrl = $this->cache->getExtensionUrlByExtensionId($extensionId);
        $actionUrl = str_replace('index.html', $massAction[self::PATH], $extensionUrl);
        $data = [
            'selectedIds' => $selectedIds
        ];
        $requestId = $this->identityGenerator->generateId();
        $this->httpClient->setHeaders(
            [
                self::CONTENT_TYPE_HEADER => self::APPLICATION_JSON,
                self::ACCEPT_HEADER => self::APPLICATION_JSON,
                self::AUTHORIZATION_HEADER => self::BEARER . $this->config->getIMSToken(),
                self::IMS_ORGANIZATION_HEADER => $this->config->getOrganizationId(),
                self::REQUEST_ID_HEADER => $requestId
            ]
        );
        $this->httpClient->setTimeout($massAction[self::TIMEOUT] ?? self::DEFAULT_TIMEOUT);

        try {
            $this->httpClient->post($actionUrl, $this->json->serialize($data));
            if ($this->httpClient->getStatus() === self::STATUS_CODE_OK) {
                $this->messageManager->addSuccessMessage(
                    __($this->getSuccessMessage($selectedIds, $gridType, $massAction))
                );
            } else {
                try {
                    $errorMessage =
                        $this->json->unserialize($this->httpClient->getBody())['error'] ?? 'Unknown error.';
                } catch (\InvalidArgumentException $exception) {
                    $errorMessage = $exception->getMessage();
                }
                $this->errorHandler->onError(
                    $massAction,
                    $requestId,
                    $gridType,
                    $selectedIds,
                    $this->httpClient->getStatus(),
                    $errorMessage
                );
            }
        } catch (Exception $exception) {
            $errorMessage = $exception->getMessage();
            $statusCode = str_contains($errorMessage, 'Operation timed out')
                ? self::STATUS_CODE_TIMEOUT
                : self::STATUS_CODE_INTERNAL_SERVER_ERROR;
            $this->errorHandler->onError($massAction, $requestId, $gridType, $selectedIds, $statusCode, $errorMessage);
        }
    }

    /**
     * Returns success message for mass action
     *
     * @param array $selectedIds
     * @param string $gridType
     * @param array $massAction
     * @return string
     */
    private function getSuccessMessage(array $selectedIds, string $gridType, array $massAction): string
    {
        $defaultSuccessMessage = count($selectedIds) . ' item(s) were updated.';
        $notification = $this->bannerNotificationFilter
            ->getMassActionBannerNotification($gridType, $massAction['actionId']);
        return $notification['successMessage'] ?? $defaultSuccessMessage;
    }
}
