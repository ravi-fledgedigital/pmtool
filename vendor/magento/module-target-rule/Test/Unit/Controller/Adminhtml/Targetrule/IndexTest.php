<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Controller\Adminhtml\Targetrule;

use Magento\Backend\Model\Menu;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;
use Magento\Framework\View\Page\Title;
use Magento\TargetRule\Controller\Adminhtml\Targetrule\Index;

class IndexTest extends AbstractTest
{
    /**
     * @var Index
     */
    protected $controller;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new Index(
            $this->contextMock,
            $this->registryMock,
            $this->dateMock
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $menuModelMock = $this->getMockBuilder(Menu::class)
            ->disableOriginalConstructor()
            ->getMock();
        $menuModelMock
            ->expects($this->any())
            ->method('getParentItems')
            ->willReturn([]);

        $this->menuBlockMock
            ->expects($this->any())
            ->method('getMenuModel')
            ->willReturn($menuModelMock);

        $titleMock = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $titleMock
            ->expects($this->exactly(1))
            ->method('prepend')
            ->willReturnCallback(function ($arg1) use ($titleMock) {
                if ($arg1 instanceof Phrase && $arg1->getText() === 'Related Products Rules') {
                    return null;
                }
            });
        $this->viewMock
            ->expects($this->any())
            ->method('getPage')
            ->willReturn(new DataObject(['config' => new DataObject(['title' => $titleMock])]));

        $this->controller->execute();
    }
}
