<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2025 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Model\Data;

use Magento\AdobeCommerceWebhooks\Api\Data\DeveloperConsoleOauthInterface;
use Magento\Framework\DataObject;

/**
 * @inheritDoc
 */
class DeveloperConsoleOauth extends DataObject implements DeveloperConsoleOauthInterface
{
    /**
     * @inheritDoc
     */
    public function setClientId(string $clientId): DeveloperConsoleOauthInterface
    {
        return $this->setData(DeveloperConsoleOauthInterface::CLIENT_ID, $clientId);
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return (string)$this->getData(DeveloperConsoleOauthInterface::CLIENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setClientSecret(string $clientSecret): DeveloperConsoleOauthInterface
    {
        return $this->setData(DeveloperConsoleOauthInterface::CLIENT_SECRET, $clientSecret);
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(): string
    {
        return (string)$this->getData(DeveloperConsoleOauthInterface::CLIENT_SECRET);
    }

    /**
     * @inheritDoc
     */
    public function setOrgId(string $orgId): DeveloperConsoleOauthInterface
    {
        return $this->setData(DeveloperConsoleOauthInterface::ORG_ID, $orgId);
    }

    /**
     * @inheritDoc
     */
    public function getOrgId(): string
    {
        return (string)$this->getData(DeveloperConsoleOauthInterface::ORG_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEnvironment(string $environment): DeveloperConsoleOauthInterface
    {
        return $this->setData(DeveloperConsoleOauthInterface::ENVIRONMENT, $environment);
    }

    /**
     * @inheritDoc
     */
    public function getEnvironment(): string
    {
        return (string)$this->getData(DeveloperConsoleOauthInterface::ENVIRONMENT);
    }
}
