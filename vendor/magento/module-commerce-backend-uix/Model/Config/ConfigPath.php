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

namespace Magento\CommerceBackendUix\Model\Config;

/**
 * Contains the paths to the configuration settings for the Admin UI SDK.
 */
class ConfigPath
{
    public const ADMIN_UI_SDK_ENABLED_CONFIG_PATH = 'admin_ui_sdk/general_config/enable_admin_ui_sdk';
    public const ENABLE_TESTING_CONFIG_PATH = 'admin_ui_sdk/local_testing/enable_testing';
    public const TESTING_MODE_CONFIG_PATH = 'admin_ui_sdk/local_testing/testing_mode';
    public const APP_STATUS_CONFIG_PATH = 'admin_ui_sdk/local_testing/app_status';
    public const MOCK_ADMIN_IMS_CONFIG_PATH = 'admin_ui_sdk/local_testing/mock_admin_ims';
    public const MOCKED_SERVICE_BASE_URL_CONFIG_PATH = 'admin_ui_sdk/local_testing/server_base_url';
    public const MOCKED_IMS_TOKEN_CONFIG_PATH = 'admin_ui_sdk/local_testing/ims_token';
    public const MOCKED_IMS_ORG_ID_CONFIG_PATH = 'admin_ui_sdk/local_testing/ims_org_id';
    public const ENABLE_DATABASE_LOGGING_CONFIG_PATH = 'admin_ui_sdk/database_logging/enable_database_logging';
    public const DATABASE_LOGS_LEVEL_CONFIG_PATH = 'admin_ui_sdk/database_logging/logs_level';
    public const DATABASE_LOGS_RETENTION_PERIOD_CONFIG_PATH = 'admin_ui_sdk/database_logging/retention_period';
}
