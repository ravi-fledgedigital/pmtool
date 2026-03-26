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
use Magento\CommerceBackendUix\Model\ResourceModel\MassActionFailedRequest as ResourceModel;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @inheritDoc
 */
class MassActionFailedRequestRepository implements MassActionFailedRequestRepositoryInterface
{
    /**
     * @param ResourceModel $resourceModel
     * @param MassActionFailedRequestFactory $requestFactory
     */
    public function __construct(
        private ResourceModel $resourceModel,
        private MassActionFailedRequestFactory $requestFactory
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function getByRequestId(string $requestId): MassActionFailedRequestInterface
    {
        $requestModel = $this->requestFactory->create();
        $this->resourceModel->load($requestModel, $requestId, MassActionFailedRequestInterface::FIELD_REQUEST_ID);

        if (!$requestModel->getRequestId()) {
            throw new NoSuchEntityException(
                __("The mass action request doesn't exist. Verify the request id and try again.")
            );
        }

        return $requestModel;
    }

    /**
     * @inheritDoc
     *
     * @throws AlreadyExistsException
     */
    public function save(MassActionFailedRequestInterface $request): void
    {
        $this->resourceModel->save($request);
    }
}
