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

namespace Magento\AdobeCommerceWebhooksSubscriber\Test\Unit\Model\Validator;

use Magento\AdobeCommerceWebhooks\Api\Data\DeveloperConsoleOauthInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Validator\DeveloperConsoleOauthValidator;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Hook;
use Magento\AdobeCommerceWebhooks\Model\Ims\CredentialsInterface;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see DeveloperConsoleOauthValidator
 */
class DeveloperConsoleOauthValidatorTest extends TestCase
{
    /**
     * @var DeveloperConsoleOauthValidator
     */
    private DeveloperConsoleOauthValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new DeveloperConsoleOauthValidator();
    }

    public function testValidate(): void
    {
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn(
                [
                    DeveloperConsoleOauthInterface::DC_OAUTH_ENABLED => 'true',
                    DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_ID => 'client_id',
                    DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_SECRET => 'client_secret',
                    DeveloperConsoleOauthInterface::DC_OAUTH_ORG_ID => 'org_id',
                    DeveloperConsoleOauthInterface::DC_OAUTH_ENVIRONMENT => CredentialsInterface::ENVIRONMENT_PRODUCTION
                ]
            );

        $this->validator->validate($hook);
    }

    public function testValidateEmptyFields(): void
    {
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn(
                [
                    DeveloperConsoleOauthInterface::DC_OAUTH_ENABLED => 'false',
                    DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_ID => '',
                    DeveloperConsoleOauthInterface::DC_OAUTH_ORG_ID => 'org_id',
                    DeveloperConsoleOauthInterface::DC_OAUTH_ENVIRONMENT => CredentialsInterface::ENVIRONMENT_PRODUCTION
                ]
            );

        $this->validator->validate($hook);
    }

    public function testValidateNotEnabled(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'Values for the following fields must be provided for Developer Console OAuth: client_id, client_secret'
        );
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn(
                [
                    DeveloperConsoleOauthInterface::DC_OAUTH_ENABLED => 'true',
                    DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_ID => '',
                    DeveloperConsoleOauthInterface::DC_OAUTH_ORG_ID => 'org_id',
                    DeveloperConsoleOauthInterface::DC_OAUTH_ENVIRONMENT => CredentialsInterface::ENVIRONMENT_PRODUCTION
                ]
            );

        $this->validator->validate($hook);
    }

    public function testValidateInvalidEnvironment(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'The Developer Console OAuth environment must be set to either "production" or "staging".'
        );
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookData')
            ->willReturn(
                [
                    DeveloperConsoleOauthInterface::DC_OAUTH_ENABLED => 'true',
                    DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_ID => 'client_id',
                    DeveloperConsoleOauthInterface::DC_OAUTH_CLIENT_SECRET => 'client_secret',
                    DeveloperConsoleOauthInterface::DC_OAUTH_ORG_ID => 'org_id',
                    DeveloperConsoleOauthInterface::DC_OAUTH_ENVIRONMENT => 'development'
                ]
            );

        $this->validator->validate($hook);
    }
}
