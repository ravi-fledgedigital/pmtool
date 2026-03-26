<?php
/** phpcs:ignoreFile */

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model;

use Vaimo\AepBase\Model\Config as BaseConfig;
use Vaimo\AepEventStreaming\Api\ConfigInterface;

class Config extends BaseConfig implements ConfigInterface
{
    private const SCHEMA_REF_ID = 'https://ns.adobe.com/{TENANT_ID}/schemas/{SCHEMA_ID}';

    private const XPATH_ENABLED = 'aep_event_streaming/general/enabled';
    private const XPATH_DEBUG_MODE = 'aep_event_streaming/general/debug_mode';
    private const XPATH_DEBUG_MODE_PARAM = 'aep_event_streaming/general/debug_mode_param';
    private const XPATH_CLIENT_ID = 'aep_event_streaming/general/client_id';
    private const XPATH_TENANT_ID = 'aep_event_streaming/general/tenant_id';
    private const XPATH_CLIENT_SECRET = 'aep_event_streaming/general/client_secret';
    private const XPATH_GRANT_TYPE = 'aep_event_streaming/general/grant_type';
    private const XPATH_CLIENT_SCOPE = 'aep_event_streaming/general/scope';
    private const XPATH_AUTH_TOKEN_ENDPOINT = 'aep_event_streaming/auth_token/endpoint';
    private const XPATH_ORGANISATION_ID = 'aep_event_streaming/auth_token/organisation_id';
    private const XPATH_TECHNICAL_ACCOUNT_ID = 'aep_event_streaming/auth_token/technical_account_id';
    private const XPATH_TOKEN_AUDIENCE = 'aep_event_streaming/auth_token/token_audience';
    private const XPATH_API_KEY = 'aep_event_streaming/auth_token/api_key';
    private const XPATH_METASCOPE = 'aep_event_streaming/auth_token/metascope';
    private const XPATH_JWT_EXPIRATION = 'aep_event_streaming/auth_token/jwt_expiration';

    private const XPATH_CUSTOMER_ENDPOINT = 'aep_event_streaming/customer_sync/endpoint';
    private const XPATH_CUSTOMER_SCHEMA_ID = 'aep_event_streaming/customer_sync/schema_id';
    private const XPATH_CUSTOMER_DATASET_ID = 'aep_event_streaming/customer_sync/dataset_id';
    private const XPATH_CUSTOMER_FLOW_ID = 'aep_event_streaming/customer_sync/flow_id';

    private const XPATH_ORDER_ENDPOINT = 'aep_event_streaming/order_sync/endpoint';
    private const XPATH_ORDER_SCHEMA_ID = 'aep_event_streaming/order_sync/schema_id';
    private const XPATH_ORDER_DATASET_ID = 'aep_event_streaming/order_sync/dataset_id';
    private const XPATH_ORDER_FLOW_ID = 'aep_event_streaming/order_sync/flow_id';

    public function isEnabled(): bool
    {
        return parent::isEnabled() && $this->scopeConfig->isSetFlag(self::XPATH_ENABLED);
    }

    public function getClientId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_CLIENT_ID);
    }

    public function getClientSecret(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_CLIENT_SECRET);
    }

    public function getGrantType(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_GRANT_TYPE);
    }

    public function getClientScope(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_CLIENT_SCOPE);
    }

    public function getAuthTokenEndpoint(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_AUTH_TOKEN_ENDPOINT);
    }

    public function getOrganisationId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_ORGANISATION_ID);
    }

    public function getTechnicalAccountId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_TECHNICAL_ACCOUNT_ID);
    }

    public function getTokenAudience(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_TOKEN_AUDIENCE);
    }

    public function getApiKey(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_API_KEY);
    }

    public function getMetaScope(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_METASCOPE);
    }

    public function getJWTExpirationTime(): int
    {
        return (int) $this->scopeConfig->getValue(self::XPATH_JWT_EXPIRATION);
    }

    public function getTenantId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_TENANT_ID);
    }

    public function getCustomerSchemaId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_CUSTOMER_SCHEMA_ID);
    }

    public function getCustomerEndpoint(): string
    {
        return $this->addDebugParam($this->scopeConfig->getValue(self::XPATH_CUSTOMER_ENDPOINT));
    }

    public function getCustomerDatasetId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_CUSTOMER_DATASET_ID);
    }

    public function getCustomerFlowId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_CUSTOMER_FLOW_ID);
    }

    public function getOrderSchemaId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_ORDER_SCHEMA_ID);
    }

    public function getOrderEndpoint(): string
    {
        return $this->addDebugParam($this->scopeConfig->getValue(self::XPATH_ORDER_ENDPOINT));
    }

    public function getOrderDatasetId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_ORDER_DATASET_ID);
    }

    public function getOrderFlowId(): string
    {
        return $this->scopeConfig->getValue(self::XPATH_ORDER_FLOW_ID);
    }

    public function getSchemaRefId(string $schemaId): string
    {
        return \str_replace(
            ['{TENANT_ID}', '{SCHEMA_ID}'],
            [$this->getTenantId(), $schemaId],
            self::SCHEMA_REF_ID
        );
    }

    public function addDebugParam(string $url): string
    {
        if (!$this->scopeConfig->isSetFlag(self::XPATH_DEBUG_MODE)) {
            return $url;
        }

        return $url . $this->scopeConfig->getValue(self::XPATH_DEBUG_MODE_PARAM);
    }
}
