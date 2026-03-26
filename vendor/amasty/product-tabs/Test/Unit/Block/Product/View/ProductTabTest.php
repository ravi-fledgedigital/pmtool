<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

/**
 * @codingStandardsIgnoreFile
 */
namespace Amasty\CustomTabs\Test\Unit\Block\Product\View;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Amasty\CustomTabs\Block\Product\View\ProductTab;
use Amasty\CustomTabs\Model\Tabs\Loader\SaveHandler;
use Amasty\CustomTabs\Model\Tabs\Tabs;
use Amasty\CustomTabs\Test\Unit\Traits;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Layout;

/**
 * Class ProductTabTest
 *
 * @see ProductTab
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductTabTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @var ProductTab
     */
    private $block;

    protected function setUp(): void
    {
        $this->block = $this->createPartialMock(ProductTab::class, [
            'getTab',
            'escapeHtml',
            'getHtml',
            'getProductBlock'
        ]);
    }

    /**
     * @covers ProductTab::getTabTitle
     *
     * @dataProvider getTabTitleDataProvider
     *
     * @throws \ReflectionException
     */
    public function testGetTabTitle($tabData, $parentData, $expectedResult)
    {
        $tab = $this->getObjectManager()->getObject(Tabs::class, $tabData);
        $parentBlock = $this->getObjectManager()->getObject(Template::class, $parentData);

        $layout = $this->getMockBuilder(Layout::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBlock'])
            ->getMock();

        $layout->expects($this->any())->method('getBlock')->willReturn($parentBlock);
        $this->block->expects($this->any())->method('getTab')->willReturn($tab);
        $this->block->expects($this->any())->method('escapeHtml')->willReturnArgument(0);

        $this->setProperty($this->block, '_layout', $layout, ProductTab::class);

        $actualResult = $this->block->getTabTitle();

        $this->assertEquals($expectedResult,$actualResult);
    }

    /**
     * @covers ProductTab::addProductBlocks
     *
     * @dataProvider addProductBlocksDataProvider
     *
     * @throws \ReflectionException
     */
    public function testAddProductBlocks($tabData, $productBlockHtml, $expectedResult)
    {
        $tab = $this->getObjectManager()->getObject(Tabs::class, $tabData);

        $this->block->expects($this->any())->method('getTab')->willReturn($tab);
        $this->block->expects($this->any())->method('getProductBlock')->willReturn(null);
        $this->block->expects($this->any())->method('getHtml')->willReturn($productBlockHtml);

        $actualResult = $this->invokeMethod($this->block, 'addProductBlocks', ['']);

        $this->assertEquals($expectedResult,$actualResult);
    }

    /**
     * Data provider for getTabTitle test
     * @return array
     */
    public function getTabTitleDataProvider()
    {
        return [
            [
                [
                    'data' => [
                        TabsInterface::TAB_TITLE => 'test'
                    ]
                ],
                [],
                'test'
            ],
            [
                [
                    'data' => [
                        TabsInterface::TAB_TITLE => 'test' . SaveHandler::DEFAULT_TITLE_VARIABLE
                    ]
                ],
                [
                    'data' => [
                        'title' => 'parent'
                    ]
                ],
                'testparent'
            ]
        ];
    }

    /**
     * Data provider for addProductBlocks test
     * @return array
     */
    public function addProductBlocksDataProvider()
    {
        return [
            [
                [
                    'data' => [
                        TabsInterface::RELATED_ENABLED => true,
                        TabsInterface::UPSELL_ENABLED => true,
                        TabsInterface::CROSSSELL_ENABLED => true
                    ]
                ],
                'test',
                'testtesttest'
            ],
            [
                [
                    'data' => [
                        TabsInterface::RELATED_ENABLED => true
                    ]
                ],
                'test',
                'test'
            ],
            [
                [
                    'data' => [
                        TabsInterface::RELATED_ENABLED => true,
                        TabsInterface::TAB_ID => 777
                    ]
                ],
                'test data-mage-init=\'{"relatedProducts":{"relatedCheckbox":".related.checkbox"}}\'',
                'test data-mage-init=\'{"Amasty_CustomTabs/js/related-products"
                            :{"relatedCheckbox":".am-custom-tab-777 .am-tab-related.checkbox",
                            "selectAllLink":"[data-role=\"select-all\"], [role=\"select-all\"]"}}\''
            ]
        ];
    }
}
