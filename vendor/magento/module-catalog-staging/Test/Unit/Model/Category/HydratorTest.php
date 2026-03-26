<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Category;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Controller\Adminhtml\Category\Save;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\CatalogStaging\Model\Category\Hydrator;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Staging\Model\Entity\RetrieverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HydratorTest extends TestCase
{
    /** @var Context|MockObject */
    protected $context;

    /** @var RetrieverInterface|MockObject */
    protected $categoryFactory;

    /** @var Save|MockObject */
    protected $originalController;

    /** @var ManagerInterface|MockObject */
    protected $eventManager;

    /** @var RequestInterface|MockObject */
    protected $request;

    /** @var \Magento\Framework\Message\ManagerInterface|MockObject */
    protected $messageManager;

    /** @var Category|MockObject */
    protected $category;

    /** @var \Magento\Catalog\Model\ResourceModel\Category|MockObject */
    protected $categoryResource;

    /** @var Hydrator */
    protected $hydrator;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryFactory = $this->getMockBuilder(CategoryFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->originalController = $this->getMockBuilder(Save::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryResource = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->hydrator = new Hydrator(
            $this->context,
            $this->categoryFactory,
            $this->originalController
        );
    }

    public function testHydrate()
    {
        $categoryPosition = 2;
        $useConfig = ['attribute_code' => 'attribute_value'];
        $data = [
            'position' => $categoryPosition,
            'use_config' => $useConfig,
        ];
        $this->context->expects($this->once())
            ->method('getEventManager')
            ->willReturn($this->eventManager);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'catalog_category_prepare_save',
                ['category' => $this->category, 'request' => $this->request]
            );
        $this->category->expects($this->once())
            ->method('addData')
            ->with([
                'position' => $categoryPosition,
                'use_config' => $useConfig,
            ]);
        $this->originalController->expects($this->once())
            ->method('stringToBoolConverting')
            ->with($data)
            ->willReturnArgument(0);
        $this->originalController->expects($this->once())
            ->method('imagePreprocessing')
            ->with($data)
            ->willReturnArgument(0);

        $this->categoryFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->category);
        $this->category->expects($this->atLeastOnce())
            ->method('setData')
            ->willReturnCallback(function ($arg1, $arg2) {
                if ($arg1 === 'attribute_code' && $arg2 === null) {
                    return null;
                } elseif ($arg1 === 'use_post_data_config' && $arg2 === ['attribute_code']) {
                    return null;
                }
            });
        $this->category->expects($this->once())
            ->method('getResource')
            ->willReturn($this->categoryResource);
        $this->category->expects($this->once())
            ->method('validate')
            ->willReturn(true);
        $this->category->expects($this->once())
            ->method('unsetData')
            ->with('use_post_data_config');
        $this->assertSame($this->category, $this->hydrator->hydrate($data));
    }
}
