<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained from
 * Adobe.
 */
declare(strict_types=1);

namespace Magento\Multicoupon\Plugin\SalesOrder\Model\SalesOrderRepository;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Customer\Test\Fixture\Customer as CustomerFixture;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Multicoupon\Test\Fixture\AddSalesOrderCoupons as AddCouponsFixture;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart as CustomerCartFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for SalesOrderRepository/GetList plugin
 */
class GetListTest extends TestCase
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    private const COUPON_CODES = ['COUPON-A'=>1.5, 'COUPON-B'=>2.5];

    private const SINGLE_COUPON_CODE = ['COUPON-C'=>1.4];

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = Bootstrap::getObjectManager()->get(OrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->filterBuilder = Bootstrap::getObjectManager()->get(FilterBuilder::class);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'billingAddress'),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'shippingAddress'),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(
            AddCouponsFixture::class,
            ['order_id' => '$order.id$', 'coupon_codes' => self::COUPON_CODES]
        )
    ]
    public function testAfterGetListMultiCouponsConfigEnabled()
    {
        $customer = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('customer');

        $searchCriteria = $this->getSearchCriteria('customer_email', $customer->getEmail());
        $searchResult = $this->orderRepository->getList($searchCriteria);
        $items = $searchResult->getItems();

        $order = array_pop($items);
        $couponCodes = $order->getExtensionAttributes()->getCouponCodes();
        $this->assertEquals(array_keys(self::COUPON_CODES), $couponCodes);
        $couponDiscounts = $order->getExtensionAttributes()->getCouponDiscounts();
        $this->assertEquals(self::COUPON_CODES, $couponDiscounts);
    }

    #[
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'billingAddress'),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'shippingAddress'),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(
            AddCouponsFixture::class,
            ['order_id' => '$order.id$', 'coupon_codes' => self::COUPON_CODES]
        )
    ]
    public function testAfterGetListMultiCouponsConfigNotEnabled()
    {
        $customer = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('customer');

        $searchCriteria = $this->getSearchCriteria('customer_email', $customer->getEmail());
        $searchResult = $this->orderRepository->getList($searchCriteria);
        $items = $searchResult->getItems();

        $order = array_pop($items);
        $couponCodes = $order->getExtensionAttributes()->getCouponCodes();
        $this->assertEmpty($couponCodes);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'billingAddress'),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'shippingAddress'),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order')
    ]
    public function testAfterGetListEmptyCoupons()
    {
        $customer = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('customer');

        $searchCriteria = $this->getSearchCriteria('customer_email', $customer->getEmail());
        $searchResult = $this->orderRepository->getList($searchCriteria);
        $items = $searchResult->getItems();

        $order = array_pop($items);
        $couponCodes = $order->getExtensionAttributes()->getCouponCodes();
        $this->assertEmpty($couponCodes);
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'billingAddress'),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'shippingAddress'),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(
            AddCouponsFixture::class,
            ['order_id' => '$order.id$', 'coupon_codes' => self::SINGLE_COUPON_CODE]
        )
    ]
    public function testAfterGetOnlyOneCouponAdded()
    {
        $customer = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('customer');

        $searchCriteria = $this->getSearchCriteria('customer_email', $customer->getEmail());
        $searchResult = $this->orderRepository->getList($searchCriteria);
        $items = $searchResult->getItems();

        $order = array_pop($items);

        $this->assertEquals(['COUPON-C'], $order->getExtensionAttributes()->getCouponCodes());
    }

    #[
        Config('sales/multicoupon/maximum_number_of_coupons_per_order', '2', 'store'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(ProductFixture::class, ['price' => 100.00], as: 'product'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 1]
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'billingAddress'),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$'], as: 'shippingAddress'),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(
            AddCouponsFixture::class,
            ['order_id' => '$order.id$', 'coupon_codes' => self::COUPON_CODES]
        )
    ]
    public function testAfterGetMultipleCouponAdded()
    {
        $customer = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage()->get('customer');

        $searchCriteria = $this->getSearchCriteria('customer_email', $customer->getEmail());
        $searchResult = $this->orderRepository->getList($searchCriteria);
        $items = $searchResult->getItems();

        $order = array_pop($items);

        $this->assertEmpty($order->getCouponCode());
    }

    /**
     * Get search criteria
     *
     * @param string $field
     * @param string $filterValue
     * @return SearchCriteria
     */
    private function getSearchCriteria(string $field, string $filterValue): SearchCriteria
    {
        $filters = [];
        $filters[] = $this->filterBuilder->setField($field)
            ->setConditionType('=')
            ->setValue($filterValue)
            ->create();
        $this->searchCriteriaBuilder->addFilters($filters);

        return $this->searchCriteriaBuilder->create();
    }
}
