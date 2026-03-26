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

namespace Magento\AdobeCommerceWebhooks\Model\Webhook;

use Magento\Framework\DataObject;

/**
 * Data Object for storing hook configuration
 */
class Hook extends DataObject
{
    public const NAME = 'name';
    public const URL = 'url';
    public const METHOD = 'method';
    public const PRIORITY = 'priority';
    public const REQUIRED = 'required';
    public const SOFT_TIMEOUT = 'softTimeout';
    public const TIMEOUT = 'timeout';
    public const HEADERS = 'headers';
    public const FIELDS = 'fields';
    public const RULES = 'rules';
    public const FALLBACK_ERROR_MESSAGE = 'fallbackErrorMessage';
    public const TTL = 'ttl';
    public const SSL_VERIFICATION = 'sslVerification';
    public const SSL_CERTIFICATE_PATH = 'sslCertificatePath';
    public const REMOVE = 'remove';
    public const XML_DEFINED = 'xml_defined';
    public const BATCH = 'batch';

    public const DEFAULT_ERROR_MESSAGE = 'Cannot perform the operation due to an error.';

    /**
     * Returns hook name
     *
     * @return string
     */
    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    /**
     * Returns hook url
     *
     * @return string
     */
    public function getUrl(): string
    {
        return (string)$this->getData(self::URL);
    }

    /**
     * Returns hook method
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->getData(self::METHOD);
    }

    /**
     * Returns hook priority
     *
     * @return int
     */
    public function getPriority(): int
    {
        return (int)$this->getData(self::PRIORITY);
    }

    /**
     * Returns hook soft timeout
     *
     * @return int
     */
    public function getSoftTimeout(): int
    {
        return (int)$this->getData(self::SOFT_TIMEOUT);
    }

    /**
     * Returns hook timeout
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return (int)$this->getData(self::TIMEOUT);
    }

    /**
     * Returns fallback error message
     *
     * @return string
     */
    public function getFallbackErrorMessage(): string
    {
        return (string)$this->getData(self::FALLBACK_ERROR_MESSAGE) ?: self::DEFAULT_ERROR_MESSAGE;
    }

    /**
     * Returns the cache ttl for the hook request
     *
     * @return int
     */
    public function getTtl(): int
    {
        return (int)$this->getData(self::TTL);
    }

    /**
     * Returns hook headers
     *
     * @return HookHeader[]
     */
    public function getHeaders(): array
    {
        return $this->getData(self::HEADERS) ?: [];
    }

    /**
     * Returns hook fields
     *
     * @return HookField[]
     */
    public function getFields(): array
    {
        return $this->getData(self::FIELDS) ?: [];
    }

    /**
     * Returns hook rules
     *
     * @return HookRule[]
     */
    public function getRules(): array
    {
        return $this->getData(self::RULES) ?: [];
    }

    /**
     * Returns active rules for the hook
     *
     * @return HookRule[]
     */
    public function getActiveRules(): array
    {
        return array_filter($this->getRules(), fn (HookRule $rule) => !$rule->shouldRemove());
    }

    /**
     * Checks if the hook is required
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return (string)$this->getData(self::REQUIRED) !== 'false';
    }

    /**
     * Checks if the hook should enable SSL verification for the request
     *
     * @return bool
     */
    public function isSslVerificationEnabled(): bool
    {
        return (string)$this->getData(self::SSL_VERIFICATION) !== 'false';
    }

    /**
     * Returns the path to the SSL certificate
     *
     * @return string
     */
    public function getSslCertificatePath(): string
    {
        return (string)$this->getData(self::SSL_CERTIFICATE_PATH);
    }

    /**
     * Checks if the hook should be skipped during webhook batch execution
     */
    public function shouldRemove(): bool
    {
        return (string)$this->getData(self::REMOVE) === 'true';
    }

    /**
     * Checks if the hook is defined in the xml configuration
     *
     * @return bool
     */
    public function isXmlDefined(): bool
    {
        return (bool)$this->getData(self::XML_DEFINED);
    }

    /**
     * Returns hook batch
     *
     * @return Batch
     */
    public function getBatch(): Batch
    {
        return $this->getData(self::BATCH);
    }
}
