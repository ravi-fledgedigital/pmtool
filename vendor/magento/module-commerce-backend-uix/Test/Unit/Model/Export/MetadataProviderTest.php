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

namespace Magento\CommerceBackendUix\Test\Unit\Model\Export;

use Exception;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\Ui\Component\Listing\Columns;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Ui\Component\MassAction\Filter;
use Magento\CommerceBackendUix\Model\Export\MetadataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit test class for MetadataProvider class
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MetadataProviderTest extends TestCase
{
    /**
     * @var MetadataProvider
     */
    private MetadataProvider $model;

    protected function setUp(): void
    {
        $filter = $this->createMock(Filter::class);
        $localeDate = $this->createMock(TimezoneInterface::class);
        $localeResolver = $this->createMock(ResolverInterface::class);

        $cache = $this->createMock(Cache::class);
        $cache->expects($this->any())
            ->method('getRegisteredColumns')
            ->willReturn([
                'properties' => [
                    '0' => [
                        'columnId' => 'registeredColumn'
                    ],
                    '1' => [
                        'columnId' => 'registeredColumn2'
                    ]
                ]
            ]);

        $this->model = new MetadataProvider(
            $filter,
            $localeDate,
            $localeResolver,
            $cache
        );
    }

    /**
     * @param string $key
     * @param array $options
     * @param array $expected
     *
     * @throws Exception
     * @dataProvider getRowDataWithoutCurrentComponentProvider
     */
    public function testGetRowDataWithoutCurrentComponent(string $key, array $options, array $expected)
    {
        $document = $this->createMock(Document::class);
        $document->expects($this->any())
            ->method('getData')
            ->willReturn(['column' => '']);

        $attribute = $this->createMock(AttributeInterface::class);
        $attribute->expects($this->any())
            ->method('getValue')
            ->willReturn($key);

        $fields = ['column', 'registeredColumn', 'registeredColumn2'];

        $document->expects($this->any())
            ->method('getCustomAttribute')
            ->willReturn($attribute);

        $result = $this->model->getRowData($document, $fields, $options);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getRowDataWithoutCurrentComponentProvider(): array
    {
        return [
            [
                'key' => 'key_1',
                'options' => [
                    'column' => [
                        'key_1' => 'value_1',
                    ],
                ],
                'expected' => [
                    'value_1',
                    'key_1',
                    'key_1'
                ],
            ],
            [
                'key' => 'key_2',
                'options' => [
                    'column' => [
                        'key_1' => 'value_1',
                    ],
                ],
                'expected' => [
                    'key_2',
                    'key_2',
                    'key_2'
                ],
            ],
            [
                'key' => 'key_1',
                'options' => [],
                'expected' => [
                    'key_1',
                    'key_1',
                    'key_1'
                ],
            ],
        ];
    }

    /**
     * @param string $key
     * @param array $options
     * @param array $expected
     *
     * @throws Exception
     * @dataProvider getRowDataProvider
     */
    public function testGetRowData(string $key, array $options, array $expected)
    {
        $currentComponent = $this->prepareColumns();

        $document = $this->createMock(Document::class);
        $document->expects($this->any())
            ->method('getData')
            ->willReturn(['column' => '']);

        $attribute = $this->createMock(AttributeInterface::class);
        $attribute->expects($this->once())
            ->method('getValue')
            ->willReturn($key);

        $fields = ['column', 'registeredColumn', 'registeredColumn2'];

        $document->expects($this->once())
            ->method('getCustomAttribute')
            ->with($fields[0])
            ->willReturn($attribute);

        // Required to fill currentComponent inside $this->model
        $this->model->getHeaders($currentComponent);

        $result = $this->model->getRowData($document, $fields, $options);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getRowDataProvider(): array
    {
        return [
            [
                'key' => 'key_1',
                'options' => [
                    'column' => [
                        'key_1' => 'value_1',
                    ],
                ],
                'expected' => [
                    'value_1',
                    'valueRegisteredColumn',
                    ''
                ],
            ],
            [
                'key' => 'key_2',
                'options' => [
                    'column' => [
                        'key_1' => 'value_1',
                    ],
                ],
                'expected' => [
                    'key_2',
                    'valueRegisteredColumn',
                    ''
                ],
            ],
            [
                'key' => 'key_1',
                'options' => [],
                'expected' => [
                    'key_1',
                    'valueRegisteredColumn',
                    ''
                ],
            ],
        ];
    }

    /**
     * @return UiComponentInterface
     */
    private function prepareColumns(): UiComponentInterface
    {
        $columnActions = $this->createMock(Column::class);
        $column = $this->createMock(Column::class);
        $column->expects($this->any())
            ->method('getData')
            ->willReturn(['config/label' => 'column_label']);

        $columns = $this->createMock(Columns::class);
        $columns->expects($this->once())
            ->method('getChildComponents')
            ->willReturn([$column, $columnActions]);

        $component = $this->createMock(UiComponentInterface::class);
        $component->expects($this->any())
            ->method('getName')
            ->willReturn('grid');
        $component->expects($this->once())
            ->method('getChildComponents')
            ->willReturn([$columns]);

        $dataProvider = $this->createMock(DataProviderInterface::class);
        $dataProvider->expects($this->any())
            ->method('getName')
            ->willReturn('name');

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getDataSourceData')
            ->with($component)
            ->willReturn([
                'name' => [
                    'config' => [
                        'data' => [
                            'items' => [
                                [
                                    'column' => 'value',
                                    'registeredColumn' => 'valueRegisteredColumn'
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
        $context->expects($this->once())
            ->method('getDataProvider')
            ->willReturn($dataProvider);

        $component->expects($this->once())
            ->method('getContext')
            ->willReturn($context);

        return $component;
    }
}
