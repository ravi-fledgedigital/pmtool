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

namespace Magento\CommerceBackendUix\Model\Logs;

use Magento\CommerceBackendUix\Api\Data\LogInterface;
use Magento\CommerceBackendUix\Api\LogRepositoryInterface;
use Magento\CommerceBackendUix\Model\ResourceModel\Logs as ResourceModel;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * Class to implement the log repository for Admin UI SDK
 */
class LogRepository implements LogRepositoryInterface
{
    /**
     * @param ResourceModel $resourceModel
     */
    public function __construct(private ResourceModel $resourceModel)
    {
    }

    /**
     * @inheritdoc
     */
    public function save(LogInterface $request): void
    {
        $this->resourceModel->save($request);
    }
}
