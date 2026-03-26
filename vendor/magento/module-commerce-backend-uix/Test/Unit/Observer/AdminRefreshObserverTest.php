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

namespace Magento\CommerceBackendUix\Test\Unit\Observer;

use Magento\CommerceBackendUix\Model\AuthorizationValidator;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\Config;
use Magento\CommerceBackendUix\Model\Extensions\AppRegistryFetcher;
use Magento\CommerceBackendUix\Model\Extensions\ExtensionManagerFetcher;
use Magento\CommerceBackendUix\Model\Extensions\ExtensionsFetcher;
use Magento\CommerceBackendUix\Model\Logs\LoggerHandler;
use Magento\CommerceBackendUix\Model\RegistrationsFetcher;
use Magento\CommerceBackendUix\Observer\AdminRefreshObserver;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Admin Refresh Observer Unit Tests
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AdminRefreshObserverTest extends TestCase
{
    /**
     * @var AdminRefreshObserver
     */
    private AdminRefreshObserver $adminRefreshObserver;

    /**
     * @var Config&MockObject
     */
    private Config $configMock;

    /**
     * @var Cache&MockObject
     */
    private Cache $cacheMock;

    /**
     * @var ClientInterface&MockObject
     */
    private ClientInterface $httpClientMock;

    /**
     * @var Json&MockObject
     */
    private Json $jsonMock;

    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $loggerMock;

    /**
     * @var AuthorizationInterface&MockObject
     */
    private AuthorizationInterface $authorizationInterfaceMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $loggerHandler = new LoggerHandler($this->loggerMock);
        $this->authorizationInterfaceMock =
            $this->getMockBuilder(AuthorizationInterface::class)->getMockForAbstractClass();
        $this->configMock = $this->createMock(Config::class);
        $this->cacheMock = $this->createMock(Cache::class);
        $this->httpClientMock = $this->getMockBuilder(ClientInterface::class)->getMockForAbstractClass();
        $this->jsonMock = $this->createMock(Json::class);

        $this->adminRefreshObserver = new AdminRefreshObserver(
            $this->cacheMock,
            new AuthorizationValidator($this->configMock, $this->authorizationInterfaceMock),
            new ExtensionsFetcher([
                new AppRegistryFetcher($this->configMock, $this->httpClientMock, $loggerHandler, $this->jsonMock),
                new ExtensionManagerFetcher($this->configMock, $this->httpClientMock, $loggerHandler, $this->jsonMock)
            ]),
            new RegistrationsFetcher(
                $this->cacheMock,
                $this->configMock,
                $this->httpClientMock,
                $this->jsonMock,
                $loggerHandler,
                []
            )
        );
    }

    /**
     * Test Execute Authorization not allowed
     *
     * @return void
     */
    public function testExecuteAuthorizationNotAllowed()
    {
        $this->configMock->expects($this->once())
            ->method('isAdminUISDKEnabled')
            ->willReturn(true);

        $this->authorizationInterfaceMock->expects($this->once())
            ->method('isAllowed')
            ->willReturn(false);

        $this->cacheMock->expects($this->never())
            ->method('getRegisteredExtensions');

        $this->cacheMock->expects($this->never())
            ->method('setRegisteredExtensions');

        $observerMock = $this->createMock(Observer::class);
        $this->adminRefreshObserver->execute($observerMock);
    }

    /**
     * Test Execute Admin UI SDK disabled
     *
     * @return void
     */
    public function testExecuteAdminUISDKDisabled()
    {
        $this->configMock->expects($this->once())
            ->method('isAdminUISDKEnabled')
            ->willReturn(false);

        $this->authorizationInterfaceMock->expects($this->never())
            ->method('isAllowed');

        $this->cacheMock->expects($this->never())
            ->method('getRegisteredExtensions');

        $this->cacheMock->expects($this->never())
            ->method('setRegisteredExtensions');

        $observerMock = $this->createMock(Observer::class);
        $this->adminRefreshObserver->execute($observerMock);
    }

    /**
     * Test Execute Cache not empty
     *
     * @return void
     */
    public function testExecuteCacheNotEmpty()
    {
        $this->configMock->expects($this->once())
            ->method('isAdminUISDKEnabled')
            ->willReturn(true);

        $this->authorizationInterfaceMock->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $this->cacheMock
            ->expects($this->once())
            ->method('getRegisteredExtensions')
            ->willReturn([
                'test' => 'https://localhost:9080/index.html',
                'test1' => 'https://localhost:9081/index.html'
            ]);

        $this->cacheMock->expects($this->never())->method('setRegisteredExtensions');

        $observerMock = $this->createMock(Observer::class);
        $this->adminRefreshObserver->execute($observerMock);
    }

    /**
     * Test Execute Cache empty no registrations
     *
     * @return void
     */
    public function testExecuteCacheEmptyNoRegistrations()
    {
        $this->mockSuccessCallConfig();

        $this->httpClientMock->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn('{}');

        $this->jsonMock->expects($this->exactly(2))
            ->method('unserialize')
            ->with('{}')
            ->willReturn([]);

        $this->cacheMock->expects($this->once())
            ->method('setRegisteredExtensions');

        $this->loggerMock->expects($this->never())
            ->method('error');

        $observerMock = $this->createMock(Observer::class);
        $this->adminRefreshObserver->execute($observerMock);
    }

    /**
     * Test Execute Cache with App Registry single extension
     *
     * @return void
     */
    public function testExecuteCacheWithAppRegistrySingleExtension()
    {
        $this->mockSuccessCallConfig();

        $appRegistryResponse = [
            [
                'name' => 'test',
                'title' => 'Test',
                'endpoints' => [
                    'commerce/backend-ui/1' => [
                        'view' => [
                            [
                                'href' => 'https://localhost:9080/index.html'
                            ]
                        ]
                    ]
                ],
                'status' => 'PUBLISHED'
            ]
        ];

        $extensionManagerResponse = [];

        $this->jsonMock
            ->expects($this->exactly(2))
            ->method('unserialize')
            ->willReturn($appRegistryResponse, $extensionManagerResponse);

        $this->cacheMock
            ->expects($this->once())
            ->method('setRegisteredExtensions')
            ->with(
                [
                    'test' => 'https://localhost:9080/index.html'
                ]
            );

        $this->loggerMock->expects($this->never())
            ->method('error');

        $observerMock = $this->createMock(Observer::class);
        $this->adminRefreshObserver->execute($observerMock);
    }

    /**
     * Test Execute Cache with App Registry multiple extensions
     *
     * @return void
     */
    public function testExecuteCacheWithAppRegistryMultipleExtensions()
    {
        $this->mockSuccessCallConfig();

        $appRegistryResponse = [
            [
                'name' => 'test',
                'title' => 'Test',
                'endpoints' => [
                    'commerce/backend-ui/1' => [
                        'view' => [
                            [
                                'href' => 'https://localhost:9080/index.html'
                            ]
                        ]
                    ]
                ],
                'status' => 'PUBLISHED'
            ],
            [
                'name' => 'test1',
                'title' => 'Test 1',
                'endpoints' => [
                    'commerce/backend-ui/1' => [
                        'view' => [
                            [
                                'href' => 'https://localhost:9081/index.html'
                            ]
                        ]
                    ]
                ],
                'status' => 'PUBLISHED'
            ]
        ];

        $extensionManagerResponse = [];

        $this->jsonMock
            ->expects($this->exactly(2))
            ->method('unserialize')
            ->willReturn($appRegistryResponse, $extensionManagerResponse);

        $this->cacheMock
            ->expects($this->once())
            ->method('setRegisteredExtensions')
            ->with(
                [
                    'test' => 'https://localhost:9080/index.html',
                    'test1' => 'https://localhost:9081/index.html'
                ]
            );

        $this->loggerMock->expects($this->never())->method('error');

        $observerMock = $this->createMock(Observer::class);
        $this->adminRefreshObserver->execute($observerMock);
    }

    /**
     * Test Execute Cache with App Registry multiple extensions different status
     *
     * @return void
     */
    public function testExecuteCacheWithAppRegistryMultipleExtensionsDifferentStatus()
    {
        $this->mockSuccessCallConfig();

        $appRegistryResponse = [
            [
                'name' => 'test',
                'title' => 'Test',
                'endpoints' => [
                    'commerce/backend-ui/1' => [
                        'view' => [
                            [
                                'href' => 'https://localhost:9080/index.html'
                            ]
                        ]
                    ]
                ],
                'status' => 'PUBLISHED'
            ],
            [
                'name' => 'test1',
                'title' => 'Test 1',
                'endpoints' => [
                    'commerce/backend-ui/1' => [
                        'view' => [
                            [
                                'href' => 'https://localhost:9081/index.html'
                            ]
                        ]
                    ]
                ],
                'status' => 'DRAFT'
            ]
        ];

        $extensionManagerResponse = [];

        $this->jsonMock
            ->expects($this->exactly(2))
            ->method('unserialize')
            ->willReturn($appRegistryResponse, $extensionManagerResponse);

        $this->cacheMock
            ->expects($this->once())
            ->method('setRegisteredExtensions')
            ->with(
                [
                    'test' => 'https://localhost:9080/index.html'
                ]
            );

        $this->loggerMock->expects($this->never())
            ->method('error');

        $observerMock = $this->createMock(Observer::class);
        $this->adminRefreshObserver->execute($observerMock);
    }

    /**
     * Test Execute Cache with Extension Manager single extension
     *
     * @return void
     */
    public function testExecuteCacheWithExtensionManagerSingleExtension()
    {
        $this->mockSuccessCallConfig();

        $appRegistryResponse = [];

        $extensionManagerResponse = [
            [
                'name' => 'test',
                'title' => 'Test',
                'extensionPoints' => [
                    [
                        'extensionPoint' => 'commerce/backend-ui/1',
                        'url' => 'https://localhost:9080/index.html'
                    ]
                ],
                'status' => 'PUBLISHED'
            ]
        ];

        $this->jsonMock
            ->expects($this->exactly(2))
            ->method('unserialize')
            ->willReturn($appRegistryResponse, $extensionManagerResponse);

        $this->cacheMock
            ->expects($this->once())
            ->method('setRegisteredExtensions')
            ->with(
                [
                    'test' => 'https://localhost:9080/index.html'
                ]
            );

        $this->loggerMock->expects($this->never())
            ->method('error');

        $observerMock = $this->createMock(Observer::class);
        $this->adminRefreshObserver->execute($observerMock);
    }

    /**
     * Test Execute Cache with Extension Manager multiple extensions
     *
     * @return void
     */
    public function testExecuteCacheWithExtensionManagerMultipleExtensions()
    {
        $this->mockSuccessCallConfig();

        $appRegistryResponse = [];

        $extensionManagerResponse = [
            [
                'name' => 'test',
                'title' => 'Test',
                'extensionPoints' => [
                    [
                        'extensionPoint' => 'commerce/backend-ui/1',
                        'url' => 'https://localhost:9080/index.html'
                    ]
                ],
                'status' => 'PUBLISHED'
            ],
            [
                'name' => 'test1',
                'title' => 'Test 1',
                'extensionPoints' => [
                    [
                        'extensionPoint' => 'commerce/backend-ui/1',
                        'url' => 'https://localhost:9081/index.html'
                    ]
                ],
                'status' => 'PUBLISHED'
            ]
        ];

        $this->jsonMock
            ->expects($this->exactly(2))
            ->method('unserialize')
            ->willReturn($appRegistryResponse, $extensionManagerResponse);

        $this->cacheMock
            ->expects($this->once())
            ->method('setRegisteredExtensions')
            ->with(
                [
                    'test' => 'https://localhost:9080/index.html',
                    'test1' => 'https://localhost:9081/index.html'
                ]
            );

        $this->loggerMock->expects($this->never())
            ->method('error');

        $observerMock = $this->createMock(Observer::class);
        $this->adminRefreshObserver->execute($observerMock);
    }

    /**
     * Test Execute Cache with Extension Manager multiple extensions different status
     *
     * @return void
     */
    public function testExecuteCacheWithExtensionManagerMultipleExtensionsDifferentStatus()
    {
        $this->mockSuccessCallConfig();

        $appRegistryResponse = [];

        $extensionManagerResponse = [
            [
                'name' => 'test',
                'title' => 'Test',
                'extensionPoints' => [
                    [
                        'extensionPoint' => 'commerce/backend-ui/1',
                        'url' => 'https://localhost:9080/index.html'
                    ]
                ],
                'status' => 'BETA'
            ],
            [
                'name' => 'test1',
                'title' => 'Test 1',
                'extensionPoints' => [
                    [
                        'extensionPoint' => 'commerce/backend-ui/1',
                        'url' => 'https://localhost:9081/index.html'
                    ]
                ],
                'status' => 'PUBLISHED'
            ]
        ];

        $this->jsonMock
            ->expects($this->exactly(2))
            ->method('unserialize')
            ->willReturn($appRegistryResponse, $extensionManagerResponse);

        $this->cacheMock
            ->expects($this->once())
            ->method('setRegisteredExtensions')
            ->with(
                [
                    'test1' => 'https://localhost:9081/index.html'
                ]
            );

        $this->loggerMock->expects($this->never())->method('error');

        $observerMock = $this->createMock(Observer::class);
        $this->adminRefreshObserver->execute($observerMock);
    }

    /**
     * Test Execute Cache with Extension Manager multiple extensions different extension point
     *
     * @return void
     */
    public function testExecuteCacheWithExtensionManagerMultipleExtensionsDifferentExtensionPoint()
    {
        $this->mockSuccessCallConfig();

        $appRegistryResponse = [];

        $extensionManagerResponse = [
            [
                'name' => 'test',
                'title' => 'Test',
                'extensionPoints' => [
                    [
                        'extensionPoint' => 'commerce/backend-ui/1',
                        'url' => 'https://localhost:9080/index.html'
                    ]
                ],
                'status' => 'PUBLISHED'
            ],
            [
                'name' => 'test1',
                'title' => 'Test 1',
                'extensionPoints' => [
                    [
                        'extensionPoint' => 'aem/backend-ui/1',
                        'url' => 'https://localhost:9081/index.html'
                    ]
                ],
                'status' => 'PUBLISHED'
            ]
        ];

        $this->jsonMock
            ->expects($this->exactly(2))
            ->method('unserialize')
            ->willReturn($appRegistryResponse, $extensionManagerResponse);

        $this->cacheMock
            ->expects($this->once())
            ->method('setRegisteredExtensions')
            ->with(
                [
                    'test' => 'https://localhost:9080/index.html'
                ]
            );

        $this->loggerMock->expects($this->never())
            ->method('error');

        $observerMock = $this->createMock(Observer::class);
        $this->adminRefreshObserver->execute($observerMock);
    }

    /**
     * Test Execute Cache with App Registry and Extension Manager extensions
     *
     * @return void
     */
    public function testExecuteCacheWithAppRegistryAndExtensionManagerExtensions()
    {
        $this->mockSuccessCallConfig();

        $appRegistryResponse = [
            [
                'name' => 'test',
                'title' => 'Test',
                'endpoints' => [
                    'commerce/backend-ui/1' => [
                        'view' => [
                            [
                                'href' => 'https://localhost:9080/index.html'
                            ]
                        ]
                    ]
                ],
                'status' => 'PUBLISHED'
            ]
        ];

        $extensionManagerResponse = [
            [
                'name' => 'test1',
                'title' => 'Test 1',
                'extensionPoints' => [
                    [
                        'extensionPoint' => 'commerce/backend-ui/1',
                        'url' => 'https://localhost:9081/index.html'
                    ]
                ],
                'status' => 'PUBLISHED'
            ]
        ];

        $this->jsonMock
            ->expects($this->exactly(2))
            ->method('unserialize')
            ->willReturn($appRegistryResponse, $extensionManagerResponse);

        $this->cacheMock
            ->expects($this->once())
            ->method('setRegisteredExtensions')
            ->with(
                [
                    'test' => 'https://localhost:9080/index.html',
                    'test1' => 'https://localhost:9081/index.html'
                ]
            );

        $this->loggerMock->expects($this->never())
            ->method('error');

        $observerMock = $this->createMock(Observer::class);
        $this->adminRefreshObserver->execute($observerMock);
    }

    /**
     * Test Execute Cache empty with status error
     *
     * @return void
     */
    public function testExecuteCacheEmptyWithStatusError()
    {
        $this->configMock->expects($this->once())
            ->method('isAdminUISDKEnabled')
            ->willReturn(true);

        $this->authorizationInterfaceMock->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $this->cacheMock->expects($this->exactly(2))
            ->method('getRegisteredExtensions')
            ->willReturn([]);

        $this->httpClientMock->expects($this->exactly(4))
            ->method('getStatus')
            ->willReturn(404);

        $this->httpClientMock->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn('Not Found');

        $this->cacheMock->expects($this->once())
            ->method('setRegisteredExtensions');

        $adminUiSdkPrefix = 'Admin UI SDK -';
        $notFoundError = 'Not Found, Status code: 404';
        $appRegistryNotFoundError = sprintf(
            '%s Error while fetching extensions registrations from App Registry: %s',
            $adminUiSdkPrefix,
            $notFoundError
        );
        $extensionManagerNotFoundError = sprintf(
            '%s Error while fetching extensions registrations from Extension Manager: %s',
            $adminUiSdkPrefix,
            $notFoundError
        );

        $this->loggerMock->expects($this->exactly(2))
            ->method('error')
            ->with($this->logicalOr($appRegistryNotFoundError, $extensionManagerNotFoundError));

        $observerMock = $this->createMock(Observer::class);
        $this->adminRefreshObserver->execute($observerMock);
    }

    /**
     * Mock success to avoid code duplication
     *
     * @return void
     */
    private function mockSuccessCallConfig(): void
    {
        $this->configMock->expects($this->once())
            ->method('isAdminUISDKEnabled')
            ->willReturn(true);

        $this->authorizationInterfaceMock->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $this->cacheMock->expects($this->exactly(2))
            ->method('getRegisteredExtensions')
            ->willReturn([]);

        $this->httpClientMock->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(200);
    }
}
