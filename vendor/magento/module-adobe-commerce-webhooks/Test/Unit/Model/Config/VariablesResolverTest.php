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

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\Config;

use Magento\AdobeCommerceWebhooks\Model\Config\VariablesResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see VariablesResolver
 */
class VariablesResolverTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private ScopeConfigInterface|MockObject $scopeConfigMock;

    /**
     * @var VariablesResolver
     */
    private VariablesResolver $variableResolver;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

        $this->variableResolver = new VariablesResolver($this->scopeConfigMock);
    }

    public function testResolveEmptyString(): void
    {
        $this->scopeConfigMock->expects(self::never())
            ->method('getValue');

        self::assertEquals('', $this->variableResolver->resolve(''));
    }

    #[DataProvider('resolveStringWithoutVariables')]
    public function testResolveStringWithoutVariables(string $string): void
    {
        $this->scopeConfigMock->expects(self::never())
            ->method('getValue');

        self::assertEquals($string, $this->variableResolver->resolve($string));
    }

    /**
     * @return array
     */
    public static function resolveStringWithoutVariables(): array
    {
        return [
            ['Some random string'],
            ['Some random string {env} {config}'],
            ['{env: Some random string'],
            ['{config: Some random string'],
            ['env: Some random string'],
        ];
    }

    public function testResolveStringWithConfigVariable():void
    {
        $string = 'Some String {config:config/test/value}';

        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with('config/test/value')
            ->willReturn('- test');

        self::assertEquals('Some String - test', $this->variableResolver->resolve($string));
    }

    public function testResolveStringWithMultipleConfigVariable():void
    {
        $string = '{config:config/test/value_one} - {config:config/test/value_two} - {config:config/test/value_three}';

        $this->scopeConfigMock->expects(self::exactly(3))
            ->method('getValue')
            ->willReturnCallback(function (string $path) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals('config/test/value_one', $path);
                        return 'one';
                    case 1:
                        self::assertEquals('config/test/value_two', $path);
                        return 'two';
                    case 2:
                        self::assertEquals('config/test/value_three', $path);
                        return 'three';
                };
                return null;
            });

        self::assertEquals('one - two - three', $this->variableResolver->resolve($string));
    }

    public function testResolveUrlStringWithConfigVariable(): void
    {
        $string = 'https://localhost:3003/{config:config/test/environment}/{config:config/test/action}';

        $this->scopeConfigMock->expects(self::exactly(2))
            ->method('getValue')
            ->willReturnCallback(function (string $path) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals('config/test/environment', $path);
                        return 'production';
                    case 1:
                        self::assertEquals('config/test/action', $path);
                        return 'execute';
                };
                return null;
            });

        self::assertEquals('https://localhost:3003/production/execute', $this->variableResolver->resolve($string));
    }

    public function testResolveStringWithEnvironmentVariable()
    {
        putenv('TEST_VAR=plus environment variable');
        $string = 'Some String {env:TEST_VAR}';

        $this->scopeConfigMock->expects(self::never())
            ->method('getValue');

        self::assertEquals('Some String plus environment variable', $this->variableResolver->resolve($string));
        putenv('TEST_VAR');
    }

    public function testResolveStringWithEnvironmentAndConfigurationVariable()
    {
        putenv('TEST_VAR=environment');
        $string = '|{config:config/test/environment} - {env:TEST_VAR}|';

        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with('config/test/environment')
            ->willReturn('production');

        self::assertEquals('|production - environment|', $this->variableResolver->resolve($string));
        putenv('TEST_VAR');
    }
}
