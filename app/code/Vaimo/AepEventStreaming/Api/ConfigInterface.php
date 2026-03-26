<?php
/** phpcs:ignoreFile */

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Api;

use Vaimo\AepBase\Api\ConfigInterface as BaseConfigInterface;

interface ConfigInterface extends BaseConfigInterface
{
    public function getClientId(): string;

    public function getClientSecret(): string;
    
    public function getGrantType(): string;

    public function getClientScope(): string;

    public function getAuthTokenEndpoint(): string;

    public function getOrganisationId(): string;

    public function getTechnicalAccountId(): string;

    public function getTokenAudience(): string;

    public function getApiKey(): string;

    public function getMetaScope(): string;

    public function getJWTExpirationTime(): int;

    public function getTenantId(): string;

    public function getCustomerSchemaId(): string;

    public function getCustomerEndpoint(): string;

    public function getCustomerDatasetId(): string;

    public function getCustomerFlowId(): string;

    public function getSchemaRefId(string $schemaId): string;

    public function getOrderSchemaId(): string;

    public function getOrderEndpoint(): string;

    public function getOrderDatasetId(): string;

    public function getOrderFlowId(): string;

    public function addDebugParam(string $url): string;
}
