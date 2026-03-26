<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\TargetRule\Test\Fixture\Rule as RuleFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;

class RowsTest extends \Magento\TestFramework\Indexer\TestCase
{
    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor
     */
    protected $_processor;

    /**
     * @var \Magento\TargetRule\Model\Rule
     */
    protected $_rule;

    /**
     * @var \Magento\TestFramework\Fixture\DataFixtureStorage
     */
    private $fixtures;

    protected function setUp(): void
    {
        $this->_processor = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor::class
        );
        $this->_rule = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            \Magento\TargetRule\Model\Rule::class
        );
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Catalog/controllers/_files/products.php
     */
    public function testReindexRows()
    {
        $this->_processor->getIndexer()->setScheduled(false);
        $this->assertFalse($this->_processor->getIndexer()->isScheduled());

        $data = [
            'name' => 'rule',
            'is_active' => '1',
            'apply_to' => 1,
            'use_customer_segment' => '0',
            'customer_segment_ids' => ['0' => ''],
        ];
        $this->_rule->loadPost($data);
        $this->_rule->save();

        $this->_processor->reindexList([$this->_rule->getId()]);

        $this->assertCount(2, $this->_rule->getMatchingProductIds());
    }

    #[
        DataFixture(ProductFixture::class, ['sku' => 'simple1'], as: 'product1'),
        DataFixture(ProductFixture::class, ['sku' => 'simple2'], as: 'product2'),
        DataFixture(ProductFixture::class, ['sku' => 'simple3'], as: 'product3'),
        DataFixture(
            RuleFixture::class,
            [
                'conditions' => [
                    [
                        'attribute' => 'sku',
                        'operator' => '()',
                        'value' => 'simple1,simple3'
                    ]
                ],
            ],
            'rule'
        )
    ]
    public function testConditionIsOneOf(): void
    {
        /**
         * @var \Magento\TargetRule\Model\Rule $rule
         */
        $rule = $this->fixtures->get('rule');
        $product1Id = $this->fixtures->get('product1')->getId();
        $product3Id = $this->fixtures->get('product3')->getId();
        $this->assertEqualsCanonicalizing([$product1Id, $product3Id], $rule->getMatchingProductIds());
    }
}
