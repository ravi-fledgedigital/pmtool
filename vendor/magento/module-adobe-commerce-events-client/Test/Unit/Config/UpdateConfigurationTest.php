<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Config;

use Magento\AdobeCommerceEventsClient\Config\Configuration;
use Magento\AdobeCommerceEventsClient\Config\ValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\AdobeCommerceEventsClient\Config\UpdateConfiguration;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for @see UpdateConfiguration
 */
class UpdateConfigurationTest extends TestCase
{
    /**
     * @var WriterInterface|MockObject
     */
    private WriterInterface|MockObject $writerMock;

    /**
     * @var EncryptorInterface|MockObject
     */
    private EncryptorInterface|MockObject $encryptorMock;

    /**
     * @var TypeListInterface|MockObject
     */
    private TypeListInterface|MockObject $appCacheMock;

    /**
     * @var Configuration|MockObject
     */
    private Configuration|MockObject $configMock;

    protected function setUp(): void
    {
        $this->writerMock = $this->createMock(WriterInterface::class);
        $this->encryptorMock = $this->createMock(EncryptorInterface::class);
        $this->appCacheMock = $this->createMock(TypeListInterface::class);
        $this->configMock = $this->createMock(Configuration::class);
    }

    public function testSetConfig()
    {
        $this->configMock->expects(self::once())
            ->method('getData')
            ->willReturn([
                Config::CONFIG_PATH_ENABLED => 1,
                Config::CONFIG_PATH_ENVIRONMENT_ID => 'env_id',
            ]);
        $this->writerMock->expects(self::exactly(2))
            ->method('save')
            ->willReturnCallback(function (string $path) {
                static $count = 0;
                match ($count++) {
                    0 => $this->assertEquals(Config::CONFIG_PATH_ENABLED, $path),
                    1 => $this->assertEquals(Config::CONFIG_PATH_ENVIRONMENT_ID, $path),
                };
            });
        $this->appCacheMock->expects(self::once())
            ->method('cleanType')
            ->with('config');

        $updateConfiguration = $this->createUpdateConfigurationObject();
        $updateConfiguration->update($this->configMock);
    }

    public function testSetNotValidConfig()
    {
        $this->expectException(ValidatorException::class);

        $this->configMock->expects(self::once())
            ->method('getData')
            ->willReturn([
                Config::CONFIG_PATH_ENVIRONMENT_ID => 'env_id',
                Config::CONFIG_PATH_WORKSPACE_CONFIGURATION => '{ wrong json',
            ]);
        $this->writerMock->expects(self::never())
            ->method('save');
        $this->appCacheMock->expects(self::never())
            ->method('cleanType');

        $validatorMock = $this->createMock(ValidatorInterface::class);
        $validatorMock->expects(self::once())
            ->method('validate')
            ->with('{ wrong json')
            ->willThrowException(new ValidatorException(__('wrong json format')));

        $updateConfiguration = $this->createUpdateConfigurationObject([
            Config::CONFIG_PATH_WORKSPACE_CONFIGURATION => [
                'workspace' => $validatorMock
            ]
        ]);
        $updateConfiguration->update($this->configMock);
    }

    /**
     * Creates UpdateConfiguration objects with the list of provided validators
     *
     * @param array $validators
     * @return UpdateConfiguration
     */
    private function createUpdateConfigurationObject(array $validators = []): UpdateConfiguration
    {
        return new UpdateConfiguration(
            $this->writerMock,
            $this->appCacheMock,
            $this->encryptorMock,
            $validators
        );
    }
}
