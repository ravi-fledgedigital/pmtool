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

namespace Magento\CommerceBackendUix\Test\Unit\Model\Parser;

use Magento\CommerceBackendUix\Model\Logs\LoggerHandler;
use Magento\CommerceBackendUix\Model\Parser\GridColumnParser;
use Magento\CommerceBackendUix\Model\Sanitizer\GridColumnSanitizer;
use Magento\CommerceBackendUix\Model\Sanitizer\InputSanitizer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GridColumnParserTest extends TestCase
{
    /**
     * @var GridColumnParser
     */
    private GridColumnParser $parser;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $loggerHandler = new LoggerHandler($loggerMock);

        $inputSanitizer = new InputSanitizer(
            $loggerHandler,
            'grid columns',
            ['columnId', 'label', 'type', 'align']
        );
        $gridColumnSanitizer = new GridColumnSanitizer($loggerHandler, $inputSanitizer);
        $this->parser = new GridColumnParser($gridColumnSanitizer, ['product', 'order', 'customer']);
    }

    /**
     * Test parse empty grid columns registration
     *
     * @return void
     */
    public function testParseEmptyGridColumnsRegistration(): void
    {
        $parsedRegistrations = [];

        $this->parser->parse([], $parsedRegistrations, 'test-extension');

        $this->assertEquals([], $parsedRegistrations);
    }

    /**
     * Test parse customer grid columns registration
     *
     * @return void
     */
    public function testParseCustomerGridColumnsRegistration(): void
    {
        $loadedRegistrations = $this->getCustomerRegistration();
        $parsedRegistrations = [];

        $this->parser->parse($loadedRegistrations, $parsedRegistrations, 'test-extension');

        $this->assertEquals(
            [
                'customer' => [
                    'gridColumns' => [
                        'data' => [
                            'meshId' => 'test-mesh-id',
                            'apiKey' => 'test-api-key',
                        ],
                        'properties' => [
                            [
                                'columnId' => 'customer_id',
                                'label' => 'Customer ID',
                                'type' => 'text',
                                'align' => 'left'
                            ],
                            [
                                'columnId' => 'customer_name',
                                'label' => 'Customer Name',
                                'type' => 'text',
                                'align' => 'left'
                            ]
                        ]
                    ]
                ]
            ],
            $parsedRegistrations
        );
    }

    /**
     * Test parse product grid columns registration
     *
     * @return void
     */
    public function testParseProductGridColumnsRegistration(): void
    {
        $loadedRegistrations = $this->getProductRegistration();
        $parsedRegistrations = [];

        $this->parser->parse($loadedRegistrations, $parsedRegistrations, 'test-extension');

        $this->assertEquals(
            [
                'product' => [
                    'gridColumns' => [
                        'data' => [
                            'meshId' => 'test-mesh-id',
                            'apiKey' => 'test-api-key'
                        ],
                        'properties' => [
                            [
                                'columnId' => 'product_id',
                                'label' => 'Product ID',
                                'type' => 'text',
                                'align' => 'left'
                            ],
                            [
                                'columnId' => 'product_name',
                                'label' => 'Product Name',
                                'type' => 'text',
                                'align' => 'left'
                            ]
                        ]
                    ]
                ]
            ],
            $parsedRegistrations
        );
    }

    /**
     * Test parse order grid columns registration
     *
     * @return void
     */
    public function testParseOrderGridColumnsRegistration(): void
    {
        $loadedRegistrations = $this->getOrderRegistration();
        $parsedRegistrations = [];

        $this->parser->parse($loadedRegistrations, $parsedRegistrations, 'test-extension');

        $this->assertEquals(
            [
                'order' => [
                    'gridColumns' => [
                        'data' => [
                            'meshId' => 'test-mesh-id',
                            'apiKey' => 'test-api-key'
                        ],
                        'properties' => [
                            [
                                'columnId' => 'order_id',
                                'label' => 'Order ID',
                                'type' => 'text',
                                'align' => 'left'
                            ],
                            [
                                'columnId' => 'order_name',
                                'label' => 'Order Name',
                                'type' => 'text',
                                'align' => 'left'
                            ]
                        ]
                    ]
                ]
            ],
            $parsedRegistrations
        );
    }

    /**
     * Test parse order and customer grid columns registration
     *
     * @return void
     */
    public function testParseOrderAndCustomerGridColumnsRegistration(): void
    {
        $loadedRegistrations = array_merge($this->getOrderRegistration(), $this->getCustomerRegistration());
        $parsedRegistrations = [];

        $this->parser->parse($loadedRegistrations, $parsedRegistrations, 'test-extension');

        $this->assertEquals(
            [
                'order' => [
                    'gridColumns' => [
                        'data' => [
                            'meshId' => 'test-mesh-id',
                            'apiKey' => 'test-api-key'
                        ],
                        'properties' => [
                            [
                                'columnId' => 'order_id',
                                'label' => 'Order ID',
                                'type' => 'text',
                                'align' => 'left'
                            ],
                            [
                                'columnId' => 'order_name',
                                'label' => 'Order Name',
                                'type' => 'text',
                                'align' => 'left'
                            ]
                        ]
                    ]
                ],
                'customer' => [
                    'gridColumns' => [
                        'data' => [
                            'meshId' => 'test-mesh-id',
                            'apiKey' => 'test-api-key'
                        ],
                        'properties' => [
                            [
                                'columnId' => 'customer_id',
                                'label' => 'Customer ID',
                                'type' => 'text',
                                'align' => 'left'
                            ],
                            [
                                'columnId' => 'customer_name',
                                'label' => 'Customer Name',
                                'type' => 'text',
                                'align' => 'left'
                            ]
                        ]
                    ]
                ]
            ],
            $parsedRegistrations
        );
    }

    /**
     * Get order registration sample
     *
     * @return array
     */
    private function getOrderRegistration(): array
    {
        return [
            'order' => [
                'gridColumns' => [
                    'data' => [
                        'meshId' => 'test-mesh-id',
                        'apiKey' => 'test-api-key'
                    ],
                    'properties' => [
                        [
                            'columnId' => 'order_id',
                            'label' => 'Order ID',
                            'type' => 'text',
                            'align' => 'left'
                        ],
                        [
                            'columnId' => 'order_name',
                            'label' => 'Order Name',
                            'type' => 'text',
                            'align' => 'left'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get customer registration sample
     *
     * @return array
     */
    private function getCustomerRegistration(): array
    {
        return [
            'customer' => [
                'gridColumns' => [
                    'data' => [
                        'meshId' => 'test-mesh-id',
                        'apiKey' => 'test-api-key'
                    ],
                    'properties' => [
                        [
                            'columnId' => 'customer_id',
                            'label' => 'Customer ID',
                            'type' => 'text',
                            'align' => 'left'
                        ],
                        [
                            'columnId' => 'customer_name',
                            'label' => 'Customer Name',
                            'type' => 'text',
                            'align' => 'left'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get product registration sample
     *
     * @return array
     */
    private function getProductRegistration(): array
    {
        return [
            'product' => [
                'gridColumns' => [
                    'data' => [
                        'meshId' => 'test-mesh-id',
                        'apiKey' => 'test-api-key'
                    ],
                    'properties' => [
                        [
                            'columnId' => 'product_id',
                            'label' => 'Product ID',
                            'type' => 'text',
                            'align' => 'left'
                        ],
                        [
                            'columnId' => 'product_name',
                            'label' => 'Product Name',
                            'type' => 'text',
                            'align' => 'left'
                        ]
                    ]
                ]
            ]
        ];
    }
}
