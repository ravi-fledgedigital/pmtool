<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Observer;

use Magento\Catalog\Model\Attribute\ScopeOverriddenValue;
use Magento\Catalog\Model\Product;
use Magento\CatalogStaging\Model\Product\DateAttributesMetadata;
use Magento\CatalogStaging\Observer\UpdateProductDateAttributes;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Staging\Model\VersionHistoryInterface;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateProductDateAttributesTest extends TestCase
{
    /**
     * @var UpdateProductDateAttributes
     */
    private $observer;

    /**
     * @var VersionManager|MockObject
     */
    private $versionHistory;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $localeDate;

    /**
     * @var DateTimeFactory|MockObject
     */
    private $dateTimeFactory;

    /**
     * @var ScopeOverriddenValue|MockObject
     */
    private $scopeOverriddenValue;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->versionHistory = $this->getMockBuilder(VersionHistoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeDate = $this->getMockBuilder(TimezoneInterface::class)
            ->onlyMethods(['date'])
            ->getMockForAbstractClass();

        $this->dateTimeFactory = $this->getMockBuilder(DateTimeFactory::class)
            ->onlyMethods(['create'])
            ->getMock();

        $this->scopeOverriddenValue = $this->getMockBuilder(ScopeOverriddenValue::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = new UpdateProductDateAttributes(
            $this->localeDate,
            new DateAttributesMetadata(),
            $this->scopeOverriddenValue
        );
    }

    /**
     * Checks execute() method logic in cases when is_new is equal to '1'
     *
     * Test cases:
     *   - update is not created, is_new='1'
     *
     * @return void
     */
    public function testExecuteWithoutExistingUpdateAndIsNewOn(): void
    {
        $isNewProduct = '1';

        $currentDateTime = (new \DateTime('now', new \DateTimeZone('UTC')));
        $formatedDateTime = $currentDateTime->format(DateTime::DATETIME_PHP_FORMAT);

        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'setData'])
            ->getMock();
        $productMock->expects($this->any())
            ->method('getData')
            ->willReturnMap(
                [
                    ['is_new', null, $isNewProduct],
                    ['news_from_date', null, null],
                    ['created_in', null, VersionManager::MIN_VERSION],
                    ['updated_in', null, VersionManager::MAX_VERSION]
                ]
            );
        $productMock->expects($this->any())
            ->method('setData')
            ->willReturnCallback(function ($arg1, $arg2) use ($productMock, $formatedDateTime) {
                if ($arg1 === 'news_from_date' && $arg2 === $formatedDateTime) {
                    // Handle the first call to setData
                } elseif ($arg1 === 'special_from_date' && $arg2 === null) {
                    // Handle the second call to setData
                } elseif ($arg1 === 'news_to_date' && $arg2 === null) {
                    // Handle the third call to setData
                } elseif ($arg1 === 'special_to_date' && $arg2 === null) {
                    // Handle the fourth call to setData
                }
                return $productMock;
            });

        $this->localeDate->expects($this->any())
            ->method('date')
            ->willReturn($currentDateTime);

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn(new DataObject(['product' => $productMock]));

        $this->versionHistory->expects($this->any())
            ->method('getCurrentId')
            ->willReturn(VersionManager::MIN_VERSION);

        $this->observer->execute($observerMock);
    }

    /**
     * Checks execute() method logic in cases when is_new is equal to '0'
     *
     * Test cases:
     *   - update is not created, is_new='0'
     *
     * @return void
     */
    public function testExecuteWithoutExistingUpdateAndIsNewOff(): void
    {
        $currentDateTime = (new \DateTime('now', new \DateTimeZone('UTC')));
        $formatedDateTime = $currentDateTime->format(DateTime::DATETIME_PHP_FORMAT);

        $isNewProduct = '0';

        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'setData'])
            ->getMock();
        $productMock->expects($this->any())
            ->method('getData')
            ->willReturnMap(
                [
                    ['is_new', null, $isNewProduct],
                    ['news_from_date', null, $formatedDateTime],
                    ['created_in', null, VersionManager::MIN_VERSION],
                    ['updated_in', null, VersionManager::MAX_VERSION],
                ]
            );
        $productMock->expects($this->any())
            ->method('setData')
            ->willReturnCallback(function ($arg1, $arg2) use ($formatedDateTime) {
                if ($arg1 == 'news_from_date' && $arg2 == $formatedDateTime) {
                    return null;
                } elseif ($arg1 == 'special_from_date' && $arg2 === null) {
                    return null;
                } elseif ($arg1 == 'news_to_date' && $arg2 === null) {
                    return null;
                } elseif ($arg1 == 'special_to_date' && $arg2 === null) {
                    return null;
                }
            });

        $this->localeDate->expects($this->any())
            ->method('date')
            ->willReturn($currentDateTime);

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn(new DataObject(['product' => $productMock]));

        $this->versionHistory->expects($this->any())
            ->method('getCurrentId')
            ->willReturn(VersionManager::MIN_VERSION);

        $this->observer->execute($observerMock);
    }

    /**
     * @dataProvider dataProviderTestExecuteWithoutExistingUpdate
     * @param string $specialPrice
     * @param string $isNewProduct
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @param string|null $expectedStartTime
     * @param string|null $expectedEndTime
     */
    public function testExecuteWithoutExistingUpdate(
        $specialPrice,
        $isNewProduct,
        $startTime,
        $endTime,
        $expectedStartTime,
        $expectedEndTime
    ) {

        $currentDateTime = (new \DateTime('now', new \DateTimeZone('UTC')));
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'setData'])
            ->getMock();
        $productMock->expects($this->any())
            ->method('getData')
            ->willReturnMap(
                [
                    ['special_price', null, $specialPrice],
                    ['is_new', null, $isNewProduct],
                    ['news_from_date', null, $startTime],
                    ['special_from_date', null, $startTime],
                    ['news_to_date', null, $endTime],
                    ['special_to_date', null, $endTime]
                ]
            );
        $productMock->expects($this->any())
            ->method('setData')
            ->willReturnCallback(function ($arg1, $arg2) use ($expectedStartTime, $expectedEndTime) {
                if ($arg1 == 'news_from_date' && $arg2 == $expectedStartTime) {
                    return null;
                } elseif ($arg1 == 'special_from_date' && $arg2 == $expectedStartTime) {
                    return null;
                } elseif ($arg1 == 'news_to_date' && $arg2 == $expectedEndTime) {
                    return null;
                } elseif ($arg1 == 'special_to_date' && $arg2 == $expectedEndTime) {
                    return null;
                }
            });

        $this->localeDate->expects($this->any())
            ->method('date')
            ->willReturn($currentDateTime);
        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn(new DataObject(['product' => $productMock]));

        $this->versionHistory->expects($this->any())
            ->method('getCurrentId')
            ->willReturn(VersionManager::MIN_VERSION);

        $this->observer->execute($observerMock);
    }

    public static function dataProviderTestExecuteWithoutExistingUpdate(): array
    {
        $startTime = (new \DateTime('+1 day'))->format(DateTime::DATETIME_PHP_FORMAT);
        $endTime = (new \DateTime('+3 days'))->format(DateTime::DATETIME_PHP_FORMAT);

        return [
            [
                'special_price' => '1',
                'is_new' => '1',
                'start_time' => $startTime,
                'end_time' => $endTime,
                'expected_start_time' => $startTime,
                'expected_end_time' => $endTime
            ],
            [
                'special_price' => '',
                'is_new' => '0',
                'start_time' => $startTime,
                'end_time' => $endTime,
                'expected_start_time' => null,
                'expected_end_time' => null
            ]
        ];
    }
}
