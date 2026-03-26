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

namespace Magento\CommerceBackendUix\Controller\Adminhtml\Redirect;

use InvalidArgumentException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\CommerceBackendUix\Model\ControllerResponse;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\CommerceBackendUix\Model\BannerNotificationFilter;

/**
 * Controller to redirect to a given URL
 */
class Redirect extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_CommerceBackendUix::admin';

    /**
     * @param Context $context
     * @param ControllerResponse $controllerResponse
     * @param BannerNotificationFilter $bannerNotificationFilter
     * @param array $validUrls
     * @param array $massActionTypeMap
     */
    public function __construct(
        private Context $context,
        private ControllerResponse $controllerResponse,
        private BannerNotificationFilter $bannerNotificationFilter,
        private array $validUrls = [],
        private array $massActionTypeMap = []
    ) {
        parent::__construct($context);
    }

    /**
     * Redirects to the given URL
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        try {
            $extensionPoint = $this->getRequest()->getParam('extensionPoint');
            if ($this->getRequest()->getParam('error')) {
                $this->messageManager->addErrorMessage(__($this->getErrorMessage($extensionPoint)));
            } else {
                $this->messageManager->addSuccessMessage(__($this->getSuccessMessage($extensionPoint)));
            }
            return $this->resultFactory
                ->create(ResultFactory::TYPE_REDIRECT)
                ->setPath($this->getRedirectUrl($extensionPoint));
        } catch (InvalidArgumentException $error) {
            return $this->controllerResponse->getErrorResponse($error);
        }
    }

    /**
     * Get redirect URL by extension point
     *
     * @param string $extensionPoint
     * @throws InvalidArgumentException
     * @return string
     */
    private function getRedirectUrl(string $extensionPoint): string
    {
        $url = $this->validUrls[$extensionPoint] ?? null;
        if (!$url) {
            throw new InvalidArgumentException(sprintf('Invalid extension point: %s', $extensionPoint));
        }
        if ($extensionPoint === 'orderViewButton') {
            $orderId = $this->getRequest()->getParam('orderId');
            return sprintf('%s/%s', $url, $orderId);
        }
        return $url;
    }

    /**
     * Returns success message for selected extension point
     *
     * @param string $extensionPoint
     * @return string
     */
    private function getSuccessMessage(string $extensionPoint): string
    {
        $bannerNotification = $this->getBannerNotification($extensionPoint);
        return $bannerNotification['successMessage'] ?? 'Execution completed successfully.';
    }

    /**
     * Returns error message for selected extension point
     *
     * @param string $extensionPoint
     * @return string
     */
    private function getErrorMessage(string $extensionPoint): string
    {
        $bannerNotification = $this->getBannerNotification($extensionPoint);
        return $bannerNotification['errorMessage'] ?? 'Execution failed. Kindly refer to the app for more info.';
    }

    /**
     * Returns banner notification for selected extension point
     *
     * @param string $extensionPoint
     * @return array
     */
    private function getBannerNotification(string $extensionPoint): array
    {
        if ($extensionPoint === 'orderViewButton') {
            return $this->getOrderViewButtonBannerNotification();
        }
        if (isset($this->massActionTypeMap[$extensionPoint])) {
            return $this->getMassActionBannerNotification($extensionPoint);
        }
        return [];
    }

    /**
     * Returns order view button banner notification
     *
     * @throws InvalidArgumentException
     * @return array
     */
    private function getOrderViewButtonBannerNotification(): array
    {
        $viewButtonId = $this->getRequest()->getParam('orderViewButtonId');
        if ($viewButtonId === null) {
            throw new InvalidArgumentException('Invalid button id');
        }
        return $this->bannerNotificationFilter->getOrderViewButtonBannerNotification($viewButtonId);
    }

    /**
     * Returns mass action banner notification
     *
     * @param string $extensionPoint
     * @throws InvalidArgumentException
     * @return array
     */
    private function getMassActionBannerNotification(string $extensionPoint): array
    {
        $massActionId = $this->getRequest()->getParam('massActionId');
        if ($massActionId === null) {
            throw new InvalidArgumentException('Invalid mass action id');
        }
        return $this->bannerNotificationFilter->getMassActionBannerNotification(
            $this->massActionTypeMap[$extensionPoint],
            $massActionId
        );
    }
}
