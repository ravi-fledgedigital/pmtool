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

namespace Magento\CommerceBackendUix\Api;

/**
 * Mass Actions failed requests storage interface
 *
 * @api
 */
interface MassActionFailedRequestRepositoryInterface
{
    /**
     * Load mass actions failed request by request id
     *
     * @param string $requestId
     * @return \Magento\CommerceBackendUix\Api\Data\MassActionFailedRequestInterface
     */
    public function getByRequestId(
        string $requestId
    ): \Magento\CommerceBackendUix\Api\Data\MassActionFailedRequestInterface;

    /**
     * Saving the mass action.
     *
     * @param \Magento\CommerceBackendUix\Api\Data\MassActionFailedRequestInterface $request
     * @return void
     */
    public function save(\Magento\CommerceBackendUix\Api\Data\MassActionFailedRequestInterface $request): void;
}
