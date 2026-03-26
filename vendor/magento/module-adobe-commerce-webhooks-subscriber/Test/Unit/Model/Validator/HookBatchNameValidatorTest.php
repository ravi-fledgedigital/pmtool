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
use Magento\AdobeCommerceWebhooksSubscriber\Model\Validator\HookBatchNameValidator;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see HookBatchNameValidator
 */
class HookBatchNameValidatorTest extends TestCase
{
    /**
     * @var HookBatchNameValidator
     */
    private HookBatchNameValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new HookBatchNameValidator();
    }

    public function testValidateWithValidName(): void
    {
        $hook = $this->createMock(Hook::class);
        $hook->expects(self::exactly(2))
            ->method('getBatchName')
            ->willReturn('batch_one');

        $this->validator->validate($hook);
    }

    public function testValidateWithInvalidNameLength(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            sprintf(
                'The batch name length must be less than or equal to %d characters.',
                HookBatchNameValidator::MAX_BATCH_NAME_LENGTH
            )
        );

        $hook = $this->createMock(Hook::class);
        $hook->expects(self::exactly(2))
            ->method('getBatchName')
            ->willReturn(str_repeat('a', 129));

        $this->validator->validate($hook);
    }

    /**
     * @param string $hookBatchName
     * @return void
     * @throws ValidatorException
     */
    #[DataProvider('invalidBatchNameCharactersDataProvider')]
    public function testValidateWithInvalidNameCharacters(string $hookBatchName): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'The hook batch name can contain only alphanumeric characters and underscores.'
        );

        $hook = $this->createMock(Hook::class);
        $hook->expects(self::once())
            ->method('getBatchName')
            ->willReturn($hookBatchName);

        $this->validator->validate($hook);
    }

    /**
     * @return array
     */
    public static function invalidBatchNameCharactersDataProvider(): array
    {
        return [
            ['batch_one_a\'];}'],
            ['batch_one_.a"];}'],
            ['batch_on.a\'];}'],
            ['batch_on_.a{}'],
            ['batch_on_ .a{}'],
        ];
    }
}
