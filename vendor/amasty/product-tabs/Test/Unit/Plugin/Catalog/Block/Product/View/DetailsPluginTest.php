<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Test\Unit\Plugin\Catalog\Block\Product\View;

use Amasty\CustomTabs\Test\Unit\Traits;
use Amasty\CustomTabs\Plugin\Catalog\Block\Product\View\DetailsPlugin;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Amasty\CustomTabs\Model\Tabs\Tabs as TabModel;
use Magento\Catalog\Block\Product\View\Details;

/**
 * Class ConfigPluginTest
 *
 * @see DetailsPlugin
 */
class DetailsPluginTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @var DetailsPlugin|MockObject $plugin
     */
    private $plugin;

    /**
     * @var Details|MockObject $subject
     */
    private $subject;

    /**
     * @covers DetailsPlugin::afterGetGroupSortedChildNames
     */
    public function testAfterGetGroupSortedChildNamesDisabled()
    {
        $this->plugin = $this->getMockBuilder(DetailsPlugin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isModuleEnabled', 'overrideTabs'])
            ->getMock();
        $this->subject = $this->getMockBuilder(Details::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->plugin->expects($this->any())->method('isModuleEnabled')->willReturn(false);
        $tabs = ['10' => 'test'];
        $this->assertEquals($tabs, $this->plugin->afterGetGroupSortedChildNames($this->subject, $tabs));
    }

    /**
     * @covers DetailsPlugin::afterGetGroupSortedChildNames
     */
    public function testAfterGetGroupSortedChildNamesEnabled()
    {
        $this->plugin = $this->getMockBuilder(DetailsPlugin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isModuleEnabled', 'overrideTabs'])
            ->getMock();
        $this->subject = $this->getMockBuilder(Details::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $tabs = ['10' => 'test'];
        $this->plugin->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->plugin->expects($this->any())->method('overrideTabs')->willReturn(['20' => 'test1']);
        $this->assertNotEquals($tabs, $this->plugin->afterGetGroupSortedChildNames($this->subject, $tabs));
    }

    /**
     * @covers DetailsPlugin::getOnlyNotObservedTabs
     */
    public function testGetOnlyNotObservedTabs()
    {
        $this->plugin = $this->getMockBuilder(DetailsPlugin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExistingTabs'])
            ->getMock();

        $tabs = ['tab1', 'tab2', 'tab3', 'tab4', 'tab5'];
        $this->plugin->expects($this->any())->method('getExistingTabs')->willReturn(['tab1', 'tab2', 'tab3']);
        $this->assertFalse(
            (bool)array_diff(
                $this->invokeMethod($this->plugin, 'getOnlyNotObservedTabs', [$tabs]),
                ['tab4', 'tab5']
            )
        );
    }

    /**
     * @covers DetailsPlugin::addCustomProductTabs
     */
    public function testAddCustomProductTabs()
    {
        $this->plugin = $this->getMockBuilder(DetailsPlugin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomTabs', 'createBlock'])
            ->getMock();

        $this->subject = $this->getMockBuilder(TabModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSortOrder'])
            ->getMock();
        $this->subject->expects($this->any())->method('getSortOrder')->willReturnOnConsecutiveCalls(1, 5, null);

        $this->plugin->expects($this->any())->method('getCustomTabs')->willReturn([$this->subject]);
        $this->plugin->expects($this->any())->method('createBlock')->willReturn('alias');
        $childNames = [5 => 'alias5'];

        $this->assertArrayHasKey(1, $this->invokeMethod($this->plugin, 'addCustomProductTabs', [$childNames]));
        $this->assertArrayHasKey(6, $this->invokeMethod($this->plugin, 'addCustomProductTabs', [$childNames]));
        $this->assertArrayHasKey(
            PHP_INT_MAX - 1,
            $this->invokeMethod($this->plugin, 'addCustomProductTabs', [$childNames])
        );
    }
}
