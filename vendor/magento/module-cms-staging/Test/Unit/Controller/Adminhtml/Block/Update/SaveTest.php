<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Controller\Adminhtml\Block\Update;

use Magento\Backend\App\Action\Context;
use Magento\CmsStaging\Controller\Adminhtml\Block\Update\Save as SaveController;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaveTest extends TestCase
{
    /** @var SaveController */
    protected $controller;

    /** @var Context|MockObject */
    protected $context;

    /** @var \Magento\Staging\Model\Entity\Update\Save|MockObject */
    protected $stagingUpdateSave;

    /** @var RequestInterface|MockObject */
    protected $request;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods([
                'getPostValue'
            ])
            ->onlyMethods([
                'getParam',
                'getModuleName',
                'setModuleName',
                'getActionName',
                'setActionName',
                'setParams',
                'getParams',
                'getCookie',
                'isSecure',
            ])
            ->getMockForAbstractClass();
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->stagingUpdateSave = $this->getMockBuilder(\Magento\Staging\Model\Entity\Update\Save::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->controller = new SaveController($this->context, $this->stagingUpdateSave);
    }

    public function testExecute()
    {
        $blockId = 1;
        $entityData = [];
        $staging = [];
        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['block_id'] => $blockId,
                ['staging'] => $staging
            });
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($entityData);
        $this->stagingUpdateSave
            ->expects($this->once())
            ->method('execute')
            ->with([
                'entityId' => $blockId,
                'stagingData' => $staging,
                'entityData' => $entityData
            ])
            ->willReturn(true);
        $this->assertTrue($this->controller->execute());
    }
}
