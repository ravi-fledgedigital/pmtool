<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 *  ADOBE CONFIDENTIAL
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

namespace Magento\CheckoutAddressSearch\Test\Unit\Plugin\Customer\Model\Address;

use Magento\CheckoutAddressSearch\Model\Config as CustomerAddressSearchConfig;
use Magento\CheckoutAddressSearch\Plugin\Customer\Model\Address\CustomerAddressDataProvider;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address\CustomerAddressDataProvider as AddressDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerAddressDataProviderTest extends TestCase
{
    /**
     * @var CustomerAddressSearchConfig|MockObject
     */
    private CustomerAddressSearchConfig $config;

    /**
     * @var CustomerAddressDataProvider
     */
    private CustomerAddressDataProvider $provider;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(CustomerAddressSearchConfig::class);
        $this->provider = new CustomerAddressDataProvider($this->config);

        parent::setUp();
    }

    /**
     * @return void
     */
    public function testBeforeGetAddressDataByCustomer()
    {
        $subject = $this->createMock(AddressDataProvider::class);
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $addressLimit = null;
        $searchLimit = 5;
        $this->config->expects($this->once())->method('isEnabledAddressSearch')->willReturn(true);
        $this->config->expects($this->once())->method('getSearchLimit')->willReturn($searchLimit);
        $expectedResult = [$customer, $searchLimit];

        $this->assertSame(
            $expectedResult,
            $this->provider->beforeGetAddressDataByCustomer($subject, $customer, $addressLimit)
        );
    }
}
