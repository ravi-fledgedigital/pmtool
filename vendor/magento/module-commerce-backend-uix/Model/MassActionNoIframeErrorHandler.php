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

use Magento\CommerceBackendUix\Api\Data\MassActionFailedRequestInterface;
use Magento\CommerceBackendUix\Api\MassActionFailedRequestRepositoryInterface;
use Magento\CommerceBackendUix\Model\Logs\LoggerHandler;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Handle mass action execution error to log in database and send an event
 */
class MassActionNoIframeErrorHandler
{
    private const ACTION_ID = 'actionId';

    /**
     * @param LoggerHandler $logger
     * @param Json $json
     * @param MessageManagerInterface $messageManager
     * @param EventManager $eventManager
     * @param MassActionFailedRequestFactory $requestFactory
     * @param MassActionFailedRequestRepositoryInterface $requestRepository
     * @param DateTime $dateTime
     * @param BannerNotificationFilter $bannerNotificationFilter
     */
    public function __construct(
        private LoggerHandler $logger,
        private Json $json,
        private MessageManagerInterface $messageManager,
        private EventManager $eventManager,
        private MassActionFailedRequestFactory $requestFactory,
        private MassActionFailedRequestRepositoryInterface $requestRepository,
        private DateTime $dateTime,
        private BannerNotificationFilter $bannerNotificationFilter
    ) {
    }

    /**
     * Handles error on requests to log and send event
     *
     * @param array $massAction
     * @param string $requestId
     * @param string $gridType
     * @param array $selectedIds
     * @param int $statusCode
     * @param string $errorMessage
     * @return void
     */
    public function onError(
        array $massAction,
        string $requestId,
        string $gridType,
        array $selectedIds,
        int $statusCode,
        string $errorMessage
    ): void {
        $this->logError($statusCode, $errorMessage);
        $this->renderErrorNotificationBanner($gridType, $massAction);
        $data = $this->getData($massAction, $requestId, $gridType, $selectedIds, $statusCode, $errorMessage);
        $this->saveFailedRequest($data);
        $this->eventManager->dispatch('admin_ui_sdk_mass_action_request_failed', ['request' => $data]);
    }

    /**
     * Log error with status code and message
     *
     * @param int $statusCode
     * @param string $errorMessage
     * @return void
     */
    private function logError(int $statusCode, string $errorMessage): void
    {
        $this->logger->error(sprintf(
            'Error while executing mass action. Status code %d. Details: %s',
            $statusCode,
            $errorMessage
        ));
    }

    /**
     * Render notification banner message
     *
     * @param string $gridType
     * @param array $massAction
     * @return void
     */
    private function renderErrorNotificationBanner(string $gridType, array $massAction): void
    {
        $notification = $this->bannerNotificationFilter
            ->getMassActionBannerNotification($gridType, $massAction[self::ACTION_ID]);
        $errorMessage = $notification['errorMessage'] ??
            'There was an error executing the mass action. Check logs for more information.';

        $this->messageManager->addErrorMessage(__($errorMessage));
    }

    /**
     * Save failed requests in database
     *
     * @param array $data
     * @return void
     */
    private function saveFailedRequest(array $data): void
    {
        $request = $this->requestFactory->create(['data' => $data]);
        $request->setRequestTimestamp((string) $this->dateTime->timestamp());
        $this->requestRepository->save($request);
    }

    /**
     * Returns an array format of data
     *
     * @param array $massAction
     * @param string $requestId
     * @param string $gridType
     * @param array $selectedIds
     * @param int $statusCode
     * @param string $errorMessage
     * @return array
     */
    private function getData(
        array $massAction,
        string $requestId,
        string $gridType,
        array $selectedIds,
        int $statusCode,
        string $errorMessage
    ): array {
        return [
            MassActionFailedRequestInterface::FIELD_REQUEST_ID => $requestId,
            MassActionFailedRequestInterface::FIELD_ACTION_ID => $massAction[self::ACTION_ID],
            MassActionFailedRequestInterface::FIELD_GRID_TYPE => $gridType,
            MassActionFailedRequestInterface::FIELD_ERROR_STATUS => $statusCode,
            MassActionFailedRequestInterface::FIELD_ERROR_MESSAGE => $errorMessage,
            MassActionFailedRequestInterface::FIELD_SELECTED_IDS => $this->json->serialize($selectedIds)
        ];
    }
}
