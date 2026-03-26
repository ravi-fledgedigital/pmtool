<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Model;

use Magento\AdobeImsApi\Api\GetAccessTokenInterface;
use Magento\CommerceBackendUix\Model\Config\ConfigPath;
use Magento\CommerceBackendUix\Model\Config\TestingMode;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\AdminAdobeIms\Service\ImsConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Represent the Admin UI SDK config model responsible for retrieving config settings for Admin UI SDK
 */
class Config
{
    private const XML_PATH_REGISTRY_URL = 'admin/admin_ui_sdk/registry_url';
    private const XML_PATH_REGISTRY_URL_STAGE = 'admin/admin_ui_sdk/registry_url_stage';
    private const XML_PATH_EXTENSION_MANAGER_URL = 'admin/admin_ui_sdk/extension_manager_url';
    private const XML_PATH_EXTENSION_MANAGER_URL_STAGE = 'admin/admin_ui_sdk/extension_manager_url_stage';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ImsConfig $imsConfig
     * @param GetAccessTokenInterface $accessToken
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private ImsConfig $imsConfig,
        private GetAccessTokenInterface $accessToken
    ) {
    }

    /**
     * Returns if Admin UI SDK config is enabled
     *
     * @return bool
     */
    public function isAdminUISDKEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(ConfigPath::ADMIN_UI_SDK_ENABLED_CONFIG_PATH);
    }

    /**
     * Returns if Admin UI SDK testing mode is enabled
     *
     * @return bool
     */
    public function isTestingEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(ConfigPath::ENABLE_TESTING_CONFIG_PATH);
    }

    /**
     * Return if Admin UI SDK local testing is enabled
     *
     * @return bool
     */
    public function isLocalTestingEnabled(): bool
    {
        $testingMode = $this->scopeConfig->getValue(ConfigPath::TESTING_MODE_CONFIG_PATH);
        return $this->isTestingEnabled() && $testingMode === TestingMode::LOCAL_TESTING;
    }

    /**
     * Return if Admin UI SDK sandbox testing is enabled
     *
     * @return bool
     */
    public function isSandboxTestingEnabled(): bool
    {
        $testingMode = $this->scopeConfig->getValue(ConfigPath::TESTING_MODE_CONFIG_PATH);
        return $this->isTestingEnabled() && $testingMode === TestingMode::SANDBOX;
    }

    /**
     * Returns the selected app status for sandbox testing
     *
     * @return array
     */
    public function getSelectedAppStatus(): array
    {
        $appStatus = $this->scopeConfig->getValue(ConfigPath::APP_STATUS_CONFIG_PATH) ?? '';
        return explode(',', $appStatus);
    }

    /**
     * Returns id Admin IMS Module is mocked
     *
     * @return bool
     */
    public function isMockAdminIMSModule(): bool
    {
        return $this->isLocalTestingEnabled()
            && (bool)$this->scopeConfig->getValue(ConfigPath::MOCK_ADMIN_IMS_CONFIG_PATH);
    }

    /**
     * Returns the registry base URL
     *
     * @return string|null
     */
    public function getRegistryBaseURL(): ?string
    {
        if ($this->isLocalTestingEnabled()) {
            return $this->scopeConfig->getValue(ConfigPath::MOCKED_SERVICE_BASE_URL_CONFIG_PATH);
        }

        $registryPath = $this->isStaging()
            ? self::XML_PATH_REGISTRY_URL_STAGE
            : self::XML_PATH_REGISTRY_URL;

        return $this->scopeConfig->getValue($registryPath, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Return the extension manager URL
     *
     * @return string
     */
    public function getExtensionManagerUrl(): string
    {
        $extensionManagerPath = $this->isStaging()
            ? self::XML_PATH_EXTENSION_MANAGER_URL_STAGE
            : self::XML_PATH_EXTENSION_MANAGER_URL;

        return $this->scopeConfig->getValue($extensionManagerPath, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Returns if staging mode activated based on the Admin IMS module
     *
     * @return bool
     */
    private function isStaging(): bool
    {
        return str_contains($this->imsConfig->getBackendLogoutUrl(), 'stg1');
    }

    /**
     * Generates and returns the IMS Token based on the config
     *
     * @return string|null
     */
    public function getIMSToken(): ?string
    {
        return $this->isMockAdminIMSModule()
            ? $this->scopeConfig->getValue(ConfigPath::MOCKED_IMS_TOKEN_CONFIG_PATH)
            : $this->accessToken->execute();
    }

    /**
     * Returns the IMS OrgId based on the config
     *
     * @return string|null
     */
    public function getOrganizationId(): ?string
    {
        return $this->isMockAdminIMSModule()
            ? $this->scopeConfig->getValue(ConfigPath::MOCKED_IMS_ORG_ID_CONFIG_PATH) . '@AdobeOrg'
            : $this->imsConfig->getOrganizationId() . '@AdobeOrg';
    }

    /**
     * Returns the client id configured in the Admin IMS module
     *
     * @return string
     */
    public function getClientId(): string
    {
        return $this->scopeConfig->getValue(ImsConfig::XML_PATH_API_KEY);
    }

    /**
     * Returns true if the database logging is enabled
     *
     * @return bool
     */
    public function isDatabaseLoggingEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(ConfigPath::ENABLE_DATABASE_LOGGING_CONFIG_PATH);
    }

    /**
     * Returns the log level configured in the Admin UI SDK
     *
     * @return int
     */
    public function getLogLevel(): int
    {
        return (int)$this->scopeConfig->getValue(ConfigPath::DATABASE_LOGS_LEVEL_CONFIG_PATH);
    }

    /**
     * Returns the retention period for the database logs
     *
     * @return int
     */
    public function getLogRetentionPeriod(): int
    {
        return (int)$this->scopeConfig->getValue(ConfigPath::DATABASE_LOGS_RETENTION_PERIOD_CONFIG_PATH);
    }
}
