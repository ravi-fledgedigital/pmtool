<?php

declare(strict_type=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */
/**
 * @codingStandardsIgnoreFile
 */

namespace Amasty\CustomTabs\Test\Unit\Model\Tabs\Loader;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Amasty\CustomTabs\Model\Tabs\Loader\SaveHandler;
use Amasty\CustomTabs\Model\Source\Type;
use Amasty\CustomTabs\Test\Unit\Traits;

/**
 * Class SaveHandlerTest
 *
 * @see SaveHandler
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveHandlerTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @var SaveHandler
     */
    private $model;

    /**
     * @var \Magento\Customer\Ui\Component\Listing\Column\Group\Options
     */
    private $customOptions;

    protected function setUp(): void
    {
        $this->customOptions = $this->getMockBuilder(\Magento\Customer\Ui\Component\Listing\Column\Group\Options::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toOptionArray'])
            ->getMock();
        $this->customOptions->expects($this->any())->method('toOptionArray')->willReturn([1, 2]);
    }

    /**
     * @covers SaveHandler::retrieveImportFields
     *
     * @dataProvider retrieveImportFieldsDataProvider
     *
     * @throws \ReflectionException
     */
    public function testRetrieveImportFields($tabConfig, $expectedTabData)
    {
        $this->model = $this->getObjectManager()->getObject(SaveHandler::class, [
            'customerOptions' => $this->customOptions
        ]);

        $actualTabData = $this->invokeMethod($this->model, 'retrieveImportFields', [[], $tabConfig]);

        $this->assertEquals($expectedTabData, $actualTabData);

    }

    /**
     * @covers SaveHandler::retrieveTypeInfo
     *
     * @dataProvider retrieveTypeInfoDataProvider
     *
     * @throws \ReflectionException
     */
    public function testRetrieveTypeInfo($tabConfig, $expectedTabData)
    {
        $this->model = $this->getObjectManager()->getObject(SaveHandler::class, [
            'customerOptions' => $this->customOptions
        ]);

        $actualTabData = $this->invokeMethod($this->model, 'retrieveTypeInfo', [[], $tabConfig]);

        $this->assertEquals($expectedTabData, $actualTabData);

    }

    /**
     * Data provider for retrieveTypeInfo test
     * @return array
     */
    public function retrieveTypeInfoDataProvider()
    {
        return [
            [
                [
                    1 => [
                        'attributes' => [
                            'class' => 'Test\\Test\\Block\\Test'
                        ]
                    ]
                ],
                [
                    TabsInterface::TAB_TYPE => Type::ANOTHER_MODULES,
                    TabsInterface::MODULE_NAME => 'Test_Test'
                ]
            ],
            [
                [
                    1 => [
                        'attributes' => [
                            'class' => 'Magento\\Test\\Block\\Test'
                        ]
                    ]
                ],
                [
                    TabsInterface::TAB_TYPE => Type::MAGENTO,
                    TabsInterface::MODULE_NAME => 'Magento_Test'
                ]
            ],
            [
                [],
                []
            ]
        ];
    }

    /**
     * Data provider for retrieveImportFields test
     * @return array
     */
    public function retrieveImportFieldsDataProvider()
    {
        return [
            [
                [
                    1 => [
                        'arguments' => [
                            'title' => 'TestTitle',
                            'sort_order' => 777
                        ]
                    ]
                ],
                [
                    TabsInterface::TAB_TITLE => 'TestTitle',
                    TabsInterface::SORT_ORDER => 777
                ]
            ],
            [
                [],
                []
            ]
        ];
    }
}
