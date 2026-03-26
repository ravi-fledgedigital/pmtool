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

namespace Magento\AdobeCommerceWebhooks\Model\Config;

use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\Config\Dom\UrnResolver;
use Magento\Framework\Exception\NotFoundException;

/**
 * Webhooks resources configuration schema locator
 */
class SchemaLocator implements SchemaLocatorInterface
{
    /**
     * Initialize dependencies.
     *
     * @param UrnResolver $urnResolver
     */
    public function __construct(private UrnResolver $urnResolver)
    {
    }

    /**
     * @inheritDoc
     *
     * @throws NotFoundException
     */
    public function getSchema()
    {
        return $this->urnResolver->getRealPath(
            'urn:magento:module:Magento_AdobeCommerceWebhooks:etc/webhooks.xsd'
        );
    }

    /**
     * @inheritDoc
     *
     * @throws NotFoundException
     */
    public function getPerFileSchema()
    {
        return $this->urnResolver->getRealPath(
            'urn:magento:module:Magento_AdobeCommerceWebhooks:etc/webhooks.xsd'
        );
    }
}
