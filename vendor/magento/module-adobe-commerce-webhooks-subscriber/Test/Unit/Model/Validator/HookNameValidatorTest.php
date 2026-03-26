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

use Magento\AdobeCommerceWebhooksSubscriber\Model\Hook;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Validator\HookNameValidator;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see HookNameValidator
 */
class HookNameValidatorTest extends TestCase
{
    /**
     * @var HookNameValidator
     */
    private HookNameValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new HookNameValidator();
    }

    public function testValidateWithValidName(): void
    {
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::exactly(2))
            ->method('getHookName')
            ->willReturn('hook_one');

        $this->validator->validate($hook);
    }

    public function testValidateWithInvalidNameLength(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            sprintf(
                'The hook name length must be less than or equal to %d characters.',
                HookNameValidator::MAX_HOOK_NAME_LENGTH
            )
        );

        $hook = $this->createMock(Hook::class);
        $hook->expects(self::exactly(2))
            ->method('getHookName')
            ->willReturn(str_repeat('a', 129));

        $this->validator->validate($hook);
    }

    /**
     * @param string $hookName
     * @return void
     * @throws ValidatorException
     */
    #[DataProvider('invalidNameCharactersDataProvider')]
    public function testValidateWithInvalidNameCharacters(string $hookName): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'The hook name can contain only alphanumeric characters and underscores.'
        );

        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getHookName')
            ->willReturn($hookName);

        $this->validator->validate($hook);
    }

    /**
     * @return array
     */
    public static function invalidNameCharactersDataProvider(): array
    {
        return [
            ['hook_a\'];}'],
            ['hook_.a"];}'],
            ['hook_.a\'];}'],
            ['hook_.a{}'],
            ['hook_ .a{}'],
        ];
    }
}
