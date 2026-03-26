<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Model;

use Magento\Cms\Model\Block;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\ResourceModel\Page\Query\PageIdsList;
use Magento\CmsStaging\Model\BlockApplier;
use Magento\Framework\Indexer\CacheContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BlockApplierTest extends TestCase
{
    /**
     * @var CacheContext|MockObject
     */
    private $cacheContext;

    /**
     * @var BlockApplier|MockObject
     */
    private $blockApplier;

    /**
     * @var PageIdsList|MockObject
     */
    private $pageIdsList;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->cacheContext = $this->createMock(CacheContext::class);
        $this->pageIdsList = $this->createMock(PageIdsList::class);
        $this->blockApplier = new BlockApplier($this->cacheContext, $this->pageIdsList);
    }

    /**
     * @return array
     */
    public static function entityIdsDataProvider(): array
    {
        return [
            [[1, 2], [1]],
            [[], []],
        ];
    }

    /**
     * @dataProvider entityIdsDataProvider
     * @param array $entityIds
     * @param array $pageEntityIds
     */
    public function testRegisterCmsCacheTag(array $entityIds, array $pageEntityIds)
    {
        if (!empty($entityIds)) {
            $this->pageIdsList->expects($this->once())
                ->method('execute')
                ->with($entityIds)
                ->willReturn($pageEntityIds);
            $this->cacheContext->expects($this->exactly(2))
                ->method('registerEntities')
                ->willReturnCallback(function ($arg1, $arg2) use ($entityIds, $pageEntityIds) {
                    if ($arg1 == Block::CACHE_TAG && $arg2 == $entityIds) {
                        return null;
                    } elseif ($arg1 == Page::CACHE_TAG && $arg2 == $pageEntityIds) {
                        return null;
                    }
                });
        } else {
            $this->cacheContext->expects($this->never())->method('registerEntities');
            $this->pageIdsList->expects($this->never())->method('execute');
        }

        $this->blockApplier->execute($entityIds);
    }
}
