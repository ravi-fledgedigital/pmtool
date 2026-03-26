<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Preview;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\DataObject;
use Magento\Staging\Model\Preview\RequestSigner;
use Magento\Staging\Model\Preview\RouteParamsPreprocessor;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RouteParamsPreprocessorTest extends TestCase
{
    private const REQUESTED_TIMESTAMP = 1680670740;
    private const STORE_CODE = 'teststore';
    private const SIGNATURE = 'cb21f53cdf3f1dd35095412776c9e31c4f2b37049a44d6e83de1bc66433a0e2a';
    private const TIMESTAMP = '1680557880';

    /**
     * @var HttpRequest|MockObject
     */
    private $request;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManager;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var RequestSigner|MockObject
     */
    private $requestSigner;

    /**
     * @var RouteParamsPreprocessor
     */
    private $model;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->request = $this->createMock(HttpRequest::class);
        $this->versionManager = $this->createMock(VersionManager::class);
        $this->scopeConfig = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->requestSigner = $this->createMock(RequestSigner::class);
        $this->model = new RouteParamsPreprocessor(
            $this->request,
            $this->versionManager,
            $this->scopeConfig,
            $this->requestSigner
        );
    }

    /**
     * @param array $mocks
     * @param string $areaCode
     * @param string|null $routePath
     * @param array|null $routeParams
     * @param array|null $expected
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        bool $isPreviewVersion,
        bool $isStoreCodeUsedInUrl,
        string $areaCode,
        ?string $routePath,
        ?array $routeParams,
        ?array $expected
    ): void {
        $this->versionManager->method('isPreviewVersion')
            ->willReturn($isPreviewVersion);
        $this->versionManager->method('getRequestedTimestamp')
            ->willReturn(self::REQUESTED_TIMESTAMP);
        $this->scopeConfig->method('getValue')
            ->with(Store::XML_PATH_STORE_IN_URL)
            ->willReturn($isStoreCodeUsedInUrl);
        $this->request->method('getParam')
            ->with('___store')
            ->willReturn(self::STORE_CODE);
        $this->requestSigner->method('generateSignatureParams')
            ->with(self::REQUESTED_TIMESTAMP)
            ->willReturn(
                new DataObject(['__signature' => self::SIGNATURE, '__timestamp' => self::TIMESTAMP])
            );
        $this->assertEquals($expected, $this->model->execute($areaCode, $routePath, $routeParams));
    }

    public function executeDataProvider(): array
    {
        return [
            [
                'isPreviewVersion' => true,
                'isStoreCodeUsedInUrl' => false,
                'areaCode' => 'adminhtml',
                'routePath' => 'path',
                'routeParams' => null,
                'expected' => null
            ],
            [
                'isPreviewVersion' => true,
                'isStoreCodeUsedInUrl' => false,
                'areaCode' => 'frontend',
                'routePath' => null,
                'routeParams' => null,
                'expected' => null
            ],
            [
                'isPreviewVersion' => true,
                'isStoreCodeUsedInUrl' => false,
                'areaCode' => 'frontend',
                'routePath' => null,
                'routeParams' => ['_secure' => true],
                'expected' => ['_secure' => true]
            ],
            [
                'isPreviewVersion' => false,
                'isStoreCodeUsedInUrl' => false,
                'areaCode' => 'frontend',
                'routePath' => 'path',
                'routeParams' => null,
                'expected' => null
            ],
            [
                'isPreviewVersion' => true,
                'isStoreCodeUsedInUrl' => false,
                'areaCode' => 'frontend',
                'routePath' => 'path',
                'routeParams' => null,
                'expected' => [
                    '_query' => [
                        '___version' => self::REQUESTED_TIMESTAMP,
                        '___store' => self::STORE_CODE,
                        '__signature' => self::SIGNATURE,
                        '__timestamp' => self::TIMESTAMP
                    ]
                ]
            ],
            [
                'isPreviewVersion' => true,
                'isStoreCodeUsedInUrl' => false,
                'areaCode' => 'frontend',
                'routePath' => null,
                'routeParams' => ['_direct' => 'path'],
                'expected' => [
                    '_direct' => 'path',
                    '_query' => [
                        '___version' => self::REQUESTED_TIMESTAMP,
                        '___store' => self::STORE_CODE,
                        '__signature' => self::SIGNATURE,
                        '__timestamp' => self::TIMESTAMP
                    ],
                ]
            ],
            [
                'isPreviewVersion' => true,
                'isStoreCodeUsedInUrl' => true,
                'areaCode' => 'frontend',
                'routePath' => null,
                'routeParams' => ['_direct' => 'path'],
                'expected' => [
                    '_direct' => 'path',
                    '_query' => [
                        '___version' => self::REQUESTED_TIMESTAMP,
                        '__signature' => self::SIGNATURE,
                        '__timestamp' => self::TIMESTAMP
                    ],
                ]
            ],
        ];
    }
}
