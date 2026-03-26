<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BannerPageBuilder\Test\Unit\Model\Stage\Renderer;

use Magento\Banner\Model\ResourceModel\Banner\CollectionFactory;
use Magento\BannerPageBuilder\Model\ResourceModel\DynamicBlock\Content;
use Magento\BannerPageBuilder\Model\Stage\Renderer\DynamicBlock\PlaceholderFilter;
use Magento\PageBuilder\Model\Stage\HtmlFilter;
use Magento\PageBuilder\Model\Stage\Renderer\WidgetDirective;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\BannerPageBuilder\Model\Stage\Renderer\DynamicBlock;
use Magento\Banner\Model\ResourceModel\Banner\Collection;
use Magento\Banner\Model\Banner;

class DynamicBlockTest extends TestCase
{
    /**
     * @var Collection|MockObject
     */
    private $colInterceptorMock;

    /**
     * @var Banner|MockObject
     */
    private $bannerMock;

    /**
     * @var DynamicBlock|MockObject
     */
    private $dynamicBlock;

    /**
     * @var CollectionFactory|MockObject
     */
    private $bannerColFactoryMock;

    /**
     * @var Content|MockObject
     */
    private $contentMock;

    /**
     * @var WidgetDirective|MockObject
     */
    private $widgetDirMock;

    /**
     * @var PlaceholderFilter|MockObject
     */
    private $placeHolderFilterMock;

    /**
     * @var HtmlFilter|MockObject
     */
    private $htmlFilterMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->bannerColFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->colInterceptorMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->widgetDirMock = $this->getMockBuilder(WidgetDirective::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->htmlFilterMock = $this->getMockBuilder(HtmlFilter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contentMock = $this->getMockBuilder(Content::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->placeHolderFilterMock = $this->getMockBuilder(PlaceholderFilter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dynamicBlock = new DynamicBlock(
            $this->bannerColFactoryMock,
            $this->widgetDirMock,
            $this->htmlFilterMock,
            $this->contentMock,
            $this->placeHolderFilterMock
        );
        $this->bannerMock = $this->getMockBuilder(Banner::class)
            ->disableOriginalConstructor()
            ->addMethods(['getName','getIsEnabled'])
            ->getMock();
    }

    /**
     *
     * @dataProvider dataProvider
     * @param string $getByIdReturnValue
     * @param array $param
     * @return void
     */
    public function testRender(string $getByIdReturnValue, array $param): void
    {
        $this->bannerColFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->colInterceptorMock);
        $this->colInterceptorMock->method('addFieldToSelect')->willReturnSelf();
        $this->colInterceptorMock->method('addFieldToFilter')->willReturnSelf();
        $this->colInterceptorMock->method('load')->willReturnSelf();
        $this->colInterceptorMock->method('count')->willReturn(1);
        $this->colInterceptorMock->method('getFirstItem')->willReturn($this->bannerMock);
        $this->bannerMock->method('getName')->willReturn('test');
        $this->bannerMock->method('getIsEnabled')->willReturn(1);
        $this->contentMock->method('getById')->willReturn($getByIdReturnValue);
        $this->widgetDirMock->method('render')->willReturn([
            'content'=> $getByIdReturnValue,
            'error' => null
        ]);
        $this->placeHolderFilterMock->method('addPlaceholders')
            ->withAnyParameters()->willReturn("");
        $this->dynamicBlock->render($param);
    }

    /**
     * @return array
     */
    public function dataProvider(): array
    {
        return [
            [
                'someText',
                [
                'isAjax' => true,
                'role' => 'dynamic_block',
                'block_id' => 4,
                'directive' => '{{widget type="Magento\Banner\Block\Widget\Banner"
                                display_mode="fixed" rotate="" template="widget/block.phtml"
                                banner_ids="4" unique_id="4" type_name="Dynamic Blocks Rotator"}}',
                'form_key' => '4dd9fSfjPORjKudx'
                ]
            ],
            [
                '',
                [
                    'isAjax' => true,
                    'role' => 'dynamic_block',
                    'block_id' => 4,
                    'directive' => '{{widget type="Magento\Banner\Block\Widget\Banner"
                                display_mode="fixed" rotate="" template="widget/block.phtml"
                                banner_ids="4" unique_id="4" type_name="Dynamic Blocks Rotator"}}',
                    'form_key' => '4dd9fSfjPORjKudx'
                ]
            ]
        ];
    }
}
