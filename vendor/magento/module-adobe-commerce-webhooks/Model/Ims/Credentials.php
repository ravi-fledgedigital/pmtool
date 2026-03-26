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

namespace Magento\AdobeCommerceWebhooks\Model\Ims;

use Magento\Framework\DataObject;

/**
 * @inheritDoc
 */
class Credentials extends DataObject implements CredentialsInterface
{
    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return (string)$this->getData(self::CLIENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function getClientSecret(): string
    {
        return (string)$this->getData(self::CLIENT_SECRET);
    }

    /**
     * @inheritDoc
     */
    public function getOrgId(): string
    {
        return (string)$this->getData(self::ORG_ID);
    }

    /**
     * @inheritDoc
     */
    public function getScopes(): string
    {
        return (string)$this->getData(self::SCOPES);
    }

    /**
     * @inheritDoc
     */
    public function getEnvironment(): string
    {
        return (string)$this->getData(self::ENVIRONMENT);
    }
}
