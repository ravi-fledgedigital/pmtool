<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Model\Catalog\Product\Price;

use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Configuration\Item\Option;
use Magento\CatalogRule\Model\ResourceModel\RuleFactory;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Catalog\Api\Data\ProductTierPriceExtensionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\GiftCard\Model\Catalog\Product\Price\Giftcard;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as GiftCardType;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Gift card product type test for price model
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class GiftcardTest extends TestCase
{
    /**
     * @var Giftcard
     */
    protected $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $ruleFactoryMock = $this->getMockBuilder(RuleFactory::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $timezoneMock = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $managerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $priceCurrencyMock = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $groupManagementMock = $this->getMockBuilder(GroupManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productTierPriceMock = $this->getMockBuilder(ProductTierPriceInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productTierPriceExtensionMock = $this->getMockBuilder(ProductTierPriceExtensionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new Giftcard(
            $ruleFactoryMock,
            $storeManagerMock,
            $timezoneMock,
            $sessionMock,
            $managerMock,
            $priceCurrencyMock,
            $groupManagementMock,
            $productTierPriceMock,
            $scopeConfigMock,
            $productTierPriceExtensionMock
        );
    }

    /**
     * @param array $amounts
     * @param bool $withCustomOptions
     * @param float $expectedPrice
     *
     * @return void
     * @dataProvider getPriceDataProvider
     */
    public function testGetPrice(array $amounts, bool $withCustomOptions, float $expectedPrice): void
    {
        $product = $this->getMockBuilder(Product::class)
            ->addMethods(['getAllowOpenAmount'])
            ->onlyMethods(['getData', 'hasCustomOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())->method('getAllowOpenAmount')->willReturn(false);
        $product->expects($this->any())->method('hasCustomOptions')->willReturn($withCustomOptions);
        $product->expects($this->atLeastOnce())->method('getData')->willReturnMap(
            [['price', null, null], ['giftcard_amounts', null, $amounts]]
        );

        $this->assertEquals($expectedPrice, $this->model->getPrice($product));
    }

    /**
     * @return array
     */
    public static function getPriceDataProvider(): array
    {
        return [
            [[['website_id' => 0, 'value' => '10.0000', 'website_value' => 10]], false, 10],
            [[['website_id' => 0, 'value' => '10.0000', 'website_value' => 10]], true, 0],
            [
                [
                    ['website_id' => 0, 'value' => '10.0000', 'website_value' => 10],
                    ['website_id' => 0, 'value' => '100.0000', 'website_value' => 100]
                ],
                false,
                0
            ]
        ];
    }

    /**
     * @return void
     */
    public function testGetPriceWithFixedAmount(): void
    {
        $price = 3;

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $product->expects($this->exactly(2))->method('getData')->with('price')->willReturn($price);

        $this->assertEquals($price, $this->model->getPrice($product));
    }

    /**
     * Test to get final price without custom option amount
     *
     * @return void
     */
    public function testGetFinalPriceWithoutCustomAmountOption(): void
    {
        $productPrice = 5;
        $optionPrice = 3;

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customOption = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->getMock();

        $product->expects($this->once())->method('getPrice')->willReturn($productPrice);
        $product->expects($this->once())->method('hasCustomOptions')->willReturn(true);
        $customOption->expects($this->once())->method('getValue')->willReturn($optionPrice);
        $product
            ->method('getCustomOption')
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['giftcard_amount'] => $customOption,
                [GiftCardType::GIFTCARD_AMOUNT_IS_CUSTOM] => false,
                ['option_ids'] => null
            });
        $product->expects($this->once())
            ->method('setData')
            ->with('final_price', $productPrice + $optionPrice)->willReturnSelf();
        $product->expects($this->once())
            ->method('getData')
            ->with('final_price')
            ->willReturn($productPrice + $optionPrice);

        $this->assertEquals($productPrice + $optionPrice, $this->model->getFinalPrice(5, $product));
    }

    /**
     * Test to get final price with custom option amount
     *
     * @return void
     */
    public function testGetFinalPriceWithCustomAmountOption(): void
    {
        $productPrice = 15;
        $optionPrice = 10.00;
        $giftCardAmounts = [
            0 => [
                'item_id' => 36,
                'produtc_id' => 5,
                'value' => 10.00
            ]
        ];
        $isCustomGiftCard  = new DataObject([
            'item_id' => 36,
            'produtc_id' => 5,
            'value' => true
        ]);
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->addMethods(['getGiftcardAmounts'])
            ->onlyMethods(['getPrice', 'hasCustomOptions', 'getCustomOption', 'setData', 'getData'])
            ->getMock();

        $customOption = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->getMock();

        $product->expects($this->once())->method('getPrice')->willReturn($productPrice);
        $product->expects($this->once())->method('hasCustomOptions')->willReturn(true);
        $customOption->expects($this->once())->method('getValue')->willReturn($optionPrice);
        $product
            ->method('getCustomOption')
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['giftcard_amount'] => $customOption,
                [GiftCardType::GIFTCARD_AMOUNT_IS_CUSTOM] => $isCustomGiftCard,
                ['option_ids'] => null
            });
        $product->expects($this->once())
            ->method('setData')
            ->with('final_price', $productPrice + $optionPrice)->willReturnSelf();
        $product->expects($this->any())
            ->method('getGiftcardAmounts')
            ->willReturn($giftCardAmounts);
        $product->expects($this->once())
            ->method('getData')
            ->with('final_price')
            ->willReturn($productPrice + $optionPrice);

        $this->assertEquals($productPrice + $optionPrice, $this->model->getFinalPrice(15, $product));
    }
}
