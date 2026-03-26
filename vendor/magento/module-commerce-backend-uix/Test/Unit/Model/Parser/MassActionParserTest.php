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
use Magento\CommerceBackendUix\Model\Parser\MassActionParser;
use Magento\CommerceBackendUix\Model\Sanitizer\InputSanitizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test class for MassActionParser class
 */
class MassActionParserTest extends TestCase
{
    /**
     * @var MassActionParser
     */
    private $massActionParser;

    /**
     * @var MockObject|(LoggerInterface&MockObject)
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $loggerHandler = new LoggerHandler($this->loggerMock);

        $gridTypes = ['product', 'order', 'customer'];

        $sanitizer = new InputSanitizer($loggerHandler, 'mass actions', ['actionId', 'label', 'path']);
        $this->massActionParser = new MassActionParser($sanitizer, $gridTypes);
    }

    /**
     * Test parse with no mass action registrations
     *
     * @return void
     */
    public function testParseNoMassActionRegistrations(): void
    {
        $parsedRegistrations = [];

        $this->massActionParser->parse([], $parsedRegistrations, 'extensionId');

        $this->assertEmpty($parsedRegistrations);
    }

    /**
     * Test parse product mass action registrations
     *
     * @return void
     */
    public function testParseProductMassActionRegistrations(): void
    {
        $parsedRegistrations = [];
        $loadedRegistrations = [
            'product' => [
                'massActions' => [
                    [
                        'actionId' => 'actionId1',
                        'label' => 'label1',
                        'path' => 'path1'
                    ],
                    [
                        'actionId' => 'actionId2',
                        'label' => 'label2',
                        'path' => 'path2'
                    ]
                ]
            ]
        ];

        $this->massActionParser->parse($loadedRegistrations, $parsedRegistrations, 'extensionId');

        $this->assertEquals(
            [
                'product' => [
                    'massActions' => [
                        [
                            'actionId' => 'actionId1',
                            'label' => 'label1',
                            'path' => 'path1',
                            'extensionId' => 'extensionId',
                            'selectionLimit' => -1
                        ],
                        [
                            'actionId' => 'actionId2',
                            'label' => 'label2',
                            'path' => 'path2',
                            'extensionId' => 'extensionId',
                            'selectionLimit' => -1
                        ]
                    ]
                ]
            ],
            $parsedRegistrations
        );
    }

    /**
     * Test parse order mass action registrations
     *
     * @return void
     */
    public function testParseOrderMassActionRegistrations(): void
    {
        $parsedRegistrations = [];
        $loadedRegistrations = [
            'order' => [
                'massActions' => [
                    [
                        'actionId' => 'actionId1',
                        'label' => 'label1',
                        'path' => 'path1'
                    ],
                    [
                        'actionId' => 'actionId2',
                        'label' => 'label2',
                        'path' => 'path2'
                    ]
                ]
            ]
        ];

        $this->massActionParser->parse($loadedRegistrations, $parsedRegistrations, 'extensionId');

        $this->assertEquals(
            [
                'order' => [
                    'massActions' => [
                        [
                            'actionId' => 'actionId1',
                            'label' => 'label1',
                            'path' => 'path1',
                            'extensionId' => 'extensionId',
                            'selectionLimit' => -1
                        ],
                        [
                            'actionId' => 'actionId2',
                            'label' => 'label2',
                            'path' => 'path2',
                            'extensionId' => 'extensionId',
                            'selectionLimit' => -1
                        ]
                    ]
                ]
            ],
            $parsedRegistrations
        );
    }

    /**
     * Test parse customer mass action registrations
     *
     * @return void
     */
    public function testParseCustomerMassActionRegistrations(): void
    {
        $parsedRegistrations = [];
        $loadedRegistrations = [
            'customer' => [
                'massActions' => [
                    [
                        'actionId' => 'actionId1',
                        'label' => 'label1',
                        'path' => 'path1'
                    ],
                    [
                        'actionId' => 'actionId2',
                        'label' => 'label2',
                        'path' => 'path2'
                    ]
                ]
            ]
        ];

        $this->massActionParser->parse($loadedRegistrations, $parsedRegistrations, 'extensionId');

        $this->assertEquals(
            [
                'customer' => [
                    'massActions' => [
                        [
                            'actionId' => 'actionId1',
                            'label' => 'label1',
                            'path' => 'path1',
                            'extensionId' => 'extensionId',
                            'selectionLimit' => -1
                        ],
                        [
                            'actionId' => 'actionId2',
                            'label' => 'label2',
                            'path' => 'path2',
                            'extensionId' => 'extensionId',
                            'selectionLimit' => -1
                        ]
                    ]
                ]
            ],
            $parsedRegistrations
        );
    }

    /**
     * Test parse all grids mass action registrations
     *
     * @return void
     */
    public function testParseAllGridsMassActionRegistrations(): void
    {
        $parsedRegistrations = [];
        $loadedRegistrations = [
            'product' => [
                'massActions' => [
                    [
                        'actionId' => 'productActionId',
                        'label' => 'label1',
                        'path' => 'path1'
                    ]
                ]
            ],
            'order' => [
                'massActions' => [
                    [
                        'actionId' => 'orderActionId1',
                        'label' => 'label1',
                        'path' => 'path1'
                    ],
                    [
                        'actionId' => 'orderActionId2',
                        'label' => 'label2',
                        'path' => 'path2'
                    ]
                ]
            ],
            'customer' => [
                'massActions' => [
                    [
                        'actionId' => 'customerActionId1',
                        'label' => 'label1',
                        'path' => 'path1'
                    ],
                    [
                        'actionId' => 'customerActionId2',
                        'label' => 'label2',
                        'path' => 'path2'
                    ]
                ]
            ]
        ];

        $this->massActionParser->parse($loadedRegistrations, $parsedRegistrations, 'extensionId');

        $this->assertEquals(
            [
                'product' => [
                    'massActions' => [
                        [
                            'actionId' => 'productActionId',
                            'label' => 'label1',
                            'path' => 'path1',
                            'extensionId' => 'extensionId',
                            'selectionLimit' => -1
                        ]
                    ]
                ],
                'order' => [
                    'massActions' => [
                        [
                            'actionId' => 'orderActionId1',
                            'label' => 'label1',
                            'path' => 'path1',
                            'extensionId' => 'extensionId',
                            'selectionLimit' => -1
                        ],
                        [
                            'actionId' => 'orderActionId2',
                            'label' => 'label2',
                            'path' => 'path2',
                            'extensionId' => 'extensionId',
                            'selectionLimit' => -1
                        ]
                    ]
                ],
                'customer' => [
                    'massActions' => [
                        [
                            'actionId' => 'customerActionId1',
                            'label' => 'label1',
                            'path' => 'path1',
                            'extensionId' => 'extensionId',
                            'selectionLimit' => -1
                        ],
                        [
                            'actionId' => 'customerActionId2',
                            'label' => 'label2',
                            'path' => 'path2',
                            'extensionId' => 'extensionId',
                            'selectionLimit' => -1
                        ]
                    ]
                ]
            ],
            $parsedRegistrations
        );
    }

    /**
     * Test parse with invalid product mass action registrations
     *
     * @return void
     */
    public function testParseWithInvalidProductMassActionRegistrations(): void
    {
        $parsedRegistrations = [];
        $loadedRegistrations = [
            'product' => [
                'massActions' => [
                    [
                        'actionId' => 'actionId1',
                        'path' => 'path1'
                    ],
                    [
                        'actionId' => 'actionId2',
                        'label' => 'label2'
                    ],
                    [
                        'path' => 'path1',
                        'label' => 'label2'
                    ]
                ]
            ]
        ];

        $this->massActionParser->parse($loadedRegistrations, $parsedRegistrations, 'extensionId');

        $this->assertEquals([], $parsedRegistrations);
    }

    /**
     * Test parse with invalid order mass action registrations
     *
     * @return void
     */
    public function testParseWithInvalidOrderMassActionRegistrations(): void
    {
        $parsedRegistrations = [];
        $loadedRegistrations = [
            'order' => [
                'massActions' => [
                    [
                        'actionId' => 'actionId1',
                        'path' => 'path1'
                    ],
                    [
                        'actionId' => 'actionId2',
                        'label' => 'label2'
                    ],
                    [
                        'path' => 'path1',
                        'label' => 'label2'
                    ]
                ]
            ]
        ];

        $this->massActionParser->parse($loadedRegistrations, $parsedRegistrations, 'extensionId');

        $this->assertEquals([], $parsedRegistrations);
    }

    /**
     * Test parse with invalid customer mass action registrations
     *
     * @return void
     */
    public function testParseWithInvalidCustomerMassActionRegistrations(): void
    {
        $parsedRegistrations = [];
        $loadedRegistrations = [
            'customer' => [
                'massActions' => [
                    [
                        'actionId' => 'actionId1',
                        'path' => 'path1'
                    ],
                    [
                        'actionId' => 'actionId2',
                        'label' => 'label2'
                    ],
                    [
                        'path' => 'path1',
                        'label' => 'label2'
                    ]
                ]
            ]
        ];

        $this->massActionParser->parse($loadedRegistrations, $parsedRegistrations, 'extensionId');

        $this->assertEquals([], $parsedRegistrations);
    }

    /**
     * Test parse with invalid product mass action and valid order mass action registrations
     *
     * @return void
     */
    public function testParseWithInvalidProductMassActionAndValidOrderMassActionRegistrations(): void
    {
        $parsedRegistrations = [];
        $loadedRegistrations = [
            'product' => [
                'massActions' => [
                    [
                        'actionId' => 'actionId1',
                        'path' => 'path1'
                    ],
                    [
                        'actionId' => 'actionId2',
                        'label' => 'label2'
                    ],
                    [
                        'path' => 'path1',
                        'label' => 'label2'
                    ]
                ]
            ],
            'order' => [
                'massActions' => [
                    [
                        'actionId' => 'actionId1',
                        'label' => 'label1',
                        'path' => 'path1'
                    ],
                    [
                        'actionId' => 'actionId2',
                        'label' => 'label2',
                        'path' => 'path2'
                    ]
                ]
            ]
        ];

        $this->massActionParser->parse($loadedRegistrations, $parsedRegistrations, 'extensionId');

        $this->assertEquals(
            [
                'order' => [
                    'massActions' => [
                        [
                            'actionId' => 'actionId1',
                            'label' => 'label1',
                            'path' => 'path1',
                            'extensionId' => 'extensionId',
                            'selectionLimit' => -1
                        ],
                        [
                            'actionId' => 'actionId2',
                            'label' => 'label2',
                            'path' => 'path2',
                            'extensionId' => 'extensionId',
                            'selectionLimit' => -1
                        ]
                    ]
                ]
            ],
            $parsedRegistrations
        );
    }
}
