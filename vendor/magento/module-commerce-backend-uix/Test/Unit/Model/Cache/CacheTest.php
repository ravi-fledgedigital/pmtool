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

namespace Magento\CommerceBackendUix\Test\Unit\Model\Cache;

use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\Config;
use Magento\CommerceBackendUix\Model\UiGridType;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Cache Unit Tests
 */
class CacheTest extends TestCase
{
    /**
     * @var Cache
     */
    private Cache $cache;

    /**
     * @var CacheInterface
     */
    private CacheInterface $cacheMock;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializerMock;

    /**
     * @var Config&MockObject
     */
    private Config $configMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->cacheMock = $this->getMockBuilder(CacheInterface::class)->getMockForAbstractClass();
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)->getMockForAbstractClass();
        $this->configMock = $this->createMock(Config::class);
        $this->cache = new Cache($this->cacheMock, $this->serializerMock, $this->configMock);
    }

    /**
     * Test getRegisteredExtensions method
     *
     * @return void
     */
    public function testExtensionsAreCorrectlyRegistered(): void
    {
        $extensions = [
            'extId' => 'extUrl'
        ];

        $savedExtensionsMock = [
            'orgId' => $extensions
        ];

        $this->configMock->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn('orgId');

        $this->cacheMock->expects($this->once())
            ->method('load')
            ->willReturn($savedExtensionsMock);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($savedExtensionsMock);

        $this->assertEquals($extensions, $this->cache->getRegisteredExtensions());
    }

    /**
     * Test getRegisteredExtensions method with multiple organizations
     *
     * @return void
     */
    public function testExtensionsAreCorrectlyRetrievedByOrg(): void
    {
        $extensionsOrg1 = [
            'extId' => 'extUrl'
        ];

        $extensionsOrg2 = [
            'extId2' => 'extUrl2'
        ];

        $savedExtensionsMock = [
            'orgId1' => $extensionsOrg1,
            'orgId2' => $extensionsOrg2
        ];

        $this->configMock->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn('orgId2');

        $this->cacheMock->expects($this->once())
            ->method('load')
            ->willReturn($savedExtensionsMock);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($savedExtensionsMock);

        $this->assertEquals($extensionsOrg2, $this->cache->getRegisteredExtensions());
    }

    /**
     * Test search for an existing product mass action
     *
     * @return void
     */
    public function testGetProductMassActionWithExistingMassAction(): void
    {
        $massActionRegistrationMock = $this->getMockProductMassActionRegistration();

        $registrationsMock = [
            'orgId' => $massActionRegistrationMock
        ];

        $this->configMock->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn('orgId');

        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with('admin_ui_sdk-registrations')
            ->willReturn($registrationsMock);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($registrationsMock);

        $result = $this->cache->getMassAction(
            UiGridType::PRODUCT_LISTING_GRID,
            'commerce_first_app::second-mass-action'
        );

        $this->assertEquals($massActionRegistrationMock['product']['massActions'][1], $result);
    }

    /**
     * Test search for non-existent product mass action
     *
     * @return void
     */
    public function testGetProductMassActionWithNonExistentMassAction(): void
    {
        $massActionsMock = $this->getMockProductMassActionRegistration();
        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with('admin_ui_sdk-registrations')
            ->willReturn($massActionsMock);
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($massActionsMock);
        $result = $this->cache->getMassAction(UiGridType::PRODUCT_LISTING_GRID, 'notFoundAction');

        $this->assertNull($result);
    }

    /**
     * Test search for an existing order mass action
     *
     * @return void
     */
    public function testGetSalesOrderGridMassActionWithExistingMassAction(): void
    {
        $massActionsMock = $this->getMockSalesOrderGridMassActionRegistration();

        $registrationsMock = [
            'orgId' => $massActionsMock
        ];

        $this->configMock->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn('orgId');

        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with('admin_ui_sdk-registrations')
            ->willReturn($registrationsMock);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($registrationsMock);

        $result = $this->cache->getMassAction(
            UiGridType::SALES_ORDER_GRID,
            'commerce_first_app::test-order-mass-action'
        );

        $this->assertEquals($massActionsMock['order']['massActions'][0], $result);
    }

    /**
     * Test search for non-existent product mass action
     *
     * @return void
     */
    public function testGetSalesOrderGridMassActionWithNonExistentMassAction(): void
    {
        $massActionsMock = $this->getMockSalesOrderGridMassActionRegistration();
        $this->cacheMock->expects($this->once())
            ->method('load')
            ->willReturn($massActionsMock);
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($massActionsMock);

        $result = $this->cache->getMassAction(UiGridType::SALES_ORDER_GRID, 'notFoundId');

        $this->assertNull($result);
    }

    /**
     * Returns product mass actions mock data
     *
     * @return array[]
     */
    private function getMockProductMassActionRegistration(): array
    {
        return  [
            'product' => [
                'massActions' => [
                    [
                        'actionId' => 'commerce_first_app::test-mass-action',
                        'label' => 'Test Mass Action',
                        'type' => 'commerce_first_app.test-mass-action',
                        'path' => '#/test-mass-action',
                        'productSelectLimit' => 2,
                        'displayIframe' => true,
                        'extensionId' => 'sampleapp'
                    ],
                    [
                        'actionId' => 'commerce_first_app::second-mass-action',
                        'label' => 'Test Second Mass Action',
                        'type' => 'commerce_first_app.second-mass-action',
                        'path' => '#/second-mass-action',
                        'productSelectLimit' => 2,
                        'displayIframe' => true,
                        'extensionId' => 'sampleapp'
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns sales order mass actions mock data
     *
     * @return array[]
     */
    private function getMockSalesOrderGridMassActionRegistration(): array
    {
        return [
            'order' => [
                'massActions' => [
                    [
                        'actionId' => 'commerce_first_app::test-order-mass-action',
                        'label' => 'Test Mass Action',
                        'type' => 'commerce_first_app.test-order-mass-action',
                        'path' => '#/test-order-mass-action',
                        'orderSelectLimit' => 2,
                        'displayIframe' => true,
                        'extensionId' => 'sampleapp',
                        'selectionLimit' => 1
                    ],
                    [
                        'actionId' => 'commerce_first_app::second-mass-action',
                        'label' => 'Test Second Mass Action',
                        'type' => 'commerce_first_app.second-mass-action',
                        'path' => '#/second-mass-action',
                        'orderSelectLimit' => 2,
                        'displayIframe' => true,
                        'extensionId' => 'sampleapp',
                        'selectionLimit' => 2
                    ]
                ]
            ]
        ];
    }
}
