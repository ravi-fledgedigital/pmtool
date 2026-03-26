<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Api;

use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * API test that checks retrieving and saving orders with gift card account data.
 */
class RetrieveOrderTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/orders';
    private const SERVICE_READ_NAME = 'salesOrderRepositoryV1';
    private const SERVICE_VERSION = 'V1';
    private const ORDER_INCREMENT_ID = '100000001';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    /**
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/order_with_gift_card_account.php
     */
    public function testOrderGet()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->objectManager->create(\Magento\Sales\Model\Order::class);

        $order->loadByIncrementId(self::ORDER_INCREMENT_ID);

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $order->getId(),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'get'
            ]
        ];

        $expected = [
            'extension_attributes' => [
                'gift_cards' => [
                    [
                        'code' => 'TESTCODE1',
                        'amount' => 10,
                        'base_amount' => 10,
                    ],
                    [
                        'code' => 'TESTCODE2',
                        'amount' => 15,
                        'base_amount' => 15,
                    ],
                ],
                'base_gift_cards_amount' => 20,
                'gift_cards_amount' => 20,
                'base_gift_cards_invoiced' => 10,
                'gift_cards_invoiced' => 10,
                'base_gift_cards_refunded' => 5,
                'gift_cards_refunded' => 5
            ]
        ];

        $this->assertOrderDataContains(
            $expected,
            $this->_webApiCall($serviceInfo, ['id' => $order->getId()])
        );
    }

    /**
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/order_with_gift_card_account.php
     */
    public function testOrderGetList()
    {
        /** @var \Magento\Framework\Api\FilterBuilder $filterBuilder */
        $filterBuilder = $this->objectManager->create(
            \Magento\Framework\Api\FilterBuilder::class
        );

        /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->create(
            \Magento\Framework\Api\SearchCriteriaBuilder::class
        );

        $searchCriteriaBuilder->addFilters(
            [
                $filterBuilder
                    ->setField('status')
                    ->setValue('processing')
                    ->setConditionType('eq')
                    ->create(),
            ]
        );

        $requestData = [
            'searchCriteria' => $searchCriteriaBuilder->create()->__toArray()
        ];

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '?' . http_build_query($requestData),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'getList'
            ]
        ];

        $result = $this->_webApiCall($serviceInfo, $requestData);

        $this->assertArrayHasKey('items', $result);
        $this->assertCount(1, $result['items']);

        $expected = [
            'extension_attributes' => [
                'gift_cards' => [
                    [
                        'code' => 'TESTCODE1',
                        'amount' => 10,
                        'base_amount' => 10,
                    ],
                    [
                        'code' => 'TESTCODE2',
                        'amount' => 15,
                        'base_amount' => 15,
                    ],
                ],
                'base_gift_cards_amount' => 20,
                'gift_cards_amount' => 20,
                'base_gift_cards_invoiced' => 10,
                'gift_cards_invoiced' => 10,
                'base_gift_cards_refunded' => 5,
                'gift_cards_refunded' => 5
            ]
        ];

        $this->assertOrderDataContains($expected, $result['items'][0]);
    }

    /**
     * @magentoApiDataFixture Magento/GiftCardAccount/_files/order_with_gift_card_account.php
     */
    public function testOrderSave()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->objectManager->create(\Magento\Sales\Model\Order::class);

        $order->loadByIncrementId(self::ORDER_INCREMENT_ID);

        $getServiceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $order->getId(),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'get'
            ]
        ];

        $postServiceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'save'
            ]
        ];

        $result = $this->_webApiCall($getServiceInfo, ['id' => $order->getId()]);

        $expected = [
            'extension_attributes' => [
                'gift_cards' => [
                    [
                        'amount' => 2,
                        'base_amount' => 2,
                    ],
                    [
                        'amount' => 3,
                        'base_amount' => 3,
                    ],
                ],
                'base_gift_cards_amount' => 4,
                'gift_cards_amount' => 4,
                'base_gift_cards_invoiced' => 2,
                'gift_cards_invoiced' => 2,
                'base_gift_cards_refunded' => 1,
                'gift_cards_refunded' => 1
            ]
        ];

        $this->_webApiCall($postServiceInfo, ['entity' => array_replace_recursive($result, $expected)]);

        $result = $this->_webApiCall($getServiceInfo, ['id' => $order->getId()]);

        $this->assertOrderDataContains(
            $expected['extension_attributes'],
            $result['extension_attributes']
        );
    }

    /**
     * @param array $expected
     * @param array $actual
     * @param string $keyFormat
     */
    private function assertOrderDataContains(array $expected, array $actual, string $keyFormat = '%s')
    {
        foreach ($expected as $key => $value) {
            $keyFormat = sprintf($keyFormat, $key);
            $this->assertArrayHasKey(
                $key,
                $actual,
                "Expected value for key $keyFormat' is missed"
            );
            if (is_array($value)) {
                $this->assertOrderDataContains($value, $actual[$key], "{$keyFormat}[%s]");
            } else {
                $this->assertEquals(
                    $value,
                    $actual[$key],
                    "Expected value for key $keyFormat doesn't match"
                );
            }
        }
    }
}
