<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TargetRule\Model\ResourceModel\Rule as ResourceModel;
use Magento\TargetRule\Model\RuleFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

/**
 * Target Rule fixture
 *
 * Example 1: Using array list. (sku in (simple1,simple3))
 *
 * ```php
 *    #[
 *        DataFixture(
 *            RuleFixture::class,
 *            [
 *                'conditions' => [
 *                    [
 *                        'attribute' => 'sku',
 *                        'operator' => '()',
 *                        'value' => 'simple1,simple3'
 *                    ]
 *                ]
 *            ],
 *            'rule'
 *        )
 *    ]
 *    public function testRule(): void
 *    {
 *
 *    }
 * ```
 *
 * Example 2: Using associative array. (sku=simple1 OR sku=simple3)
 *
 * ```php
 *    #[
 *        DataFixture(
 *            RuleFixture::class,
 *            [
 *                'conditions' => [
 *                    'aggregator' => 'any',
 *                    'conditions' => [
 *                        [
 *                            'attribute' => 'sku',
 *                            'value' => 'simple1'
 *                        ],
 *                        [
 *                            'attribute' => 'sku',
 *                            'value' => 'simple3'
 *                        ]
 *                    ],
 *                ],
 *            ],
 *            'rule'
 *        )
 *    ]
 *    public function testRule(): void
 *    {
 *
 *    }
 * ```
 *
 * Example 3: Using nested conditions. (category_ids in (1, 2) AND attribute_set_id=default) OR (sku=simple3)
 *
 * ```php
 *    #[
 *        DataFixture(
 *            RuleFixture::class,
 *            [
 *                'conditions' => [
 *                    'aggregator' => 'any',
 *                    'conditions' => [
 *                        [
 *                            [
 *                                'attribute' => 'category_ids',
 *                                'operator' => '()',
 *                                'value' => '1,2',
 *                            ],
 *                           [
 *                                'attribute' => 'attribute_set_id',
 *                                'value' => 'default',
 *                           ],
 *                        ],
 *                        [
 *                            'attribute' => 'sku',
 *                            'value' => 'simple3'
 *                        ]
 *                    ],
 *                ],
 *            ],
 *            'rule'
 *        )
 *    ]
 *    public function testRule(): void
 *    {
 *
 *    }
 * ```
 */
class Rule implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'name' => 'rule%uniqid%',
        'sort_order' => 0,
        'is_active' => 1,
        'apply_to' => \Magento\TargetRule\Model\Rule::RELATED_PRODUCTS,
        'conditions' => [],
        'actions' => [],
    ];

    /**
     * @var ProcessorInterface
     */
    private $dataProcessor;

    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param ProcessorInterface $dataProcessor
     * @param ResourceModel $resourceModel
     * @param RuleFactory $ruleFactory
     * @param Json $serializer
     */
    public function __construct(
        ProcessorInterface $dataProcessor,
        ResourceModel $resourceModel,
        RuleFactory $ruleFactory,
        Json $serializer
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->resourceModel = $resourceModel;
        $this->ruleFactory = $ruleFactory;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        /** @var \Magento\TargetRule\Model\Rule $model */
        $model = $this->ruleFactory->create();
        $data = $this->prepareData($data);
        $conditions = $this->prepareConditions(
            $data['conditions'],
            Conditions::DEFAULT_DATA,
            Condition::DEFAULT_DATA
        );
        $actions = $this->prepareConditions(
            $data['actions'],
            Actions::DEFAULT_DATA + Conditions::DEFAULT_DATA,
            Action::DEFAULT_DATA + Condition::DEFAULT_DATA
        );
        unset($data['conditions'], $data['actions']);
        $model->setData($data);

        $model->setConditionsSerialized($this->serializer->serialize($conditions));
        $model->setActionsSerialized($this->serializer->serialize($actions));

        $this->resourceModel->save($model);

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        /** @var \Magento\TargetRule\Model\Rule $model */
        $model = $this->ruleFactory->create();
        $this->resourceModel->load($model, $data->getId());
        if ($model->getId()) {
            $this->resourceModel->delete($model);
        }
    }

    /**
     * Prepare rule data
     *
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $data = array_merge(self::DEFAULT_DATA, $data);

        return $this->dataProcessor->process($this, $data);
    }

    /**
     * Prepare conditions data
     *
     * @param DataObject|array $conditions
     * @param array $defaultConditionsData
     * @param array $defaultConditionData
     * @return array
     */
    private function prepareConditions(
        DataObject|array $conditions,
        array $defaultConditionsData,
        array $defaultConditionData
    ): array {
        $conditionsArray = $conditions instanceof DataObject
            ? $conditions->toArray()
            : $conditions;
        $conditionsArray = array_is_list($conditionsArray)
            ? ['conditions' => $conditionsArray]
            : $conditionsArray;
        $conditionsArray += $defaultConditionsData;
        $subConditions = $conditionsArray['conditions'];
        $conditionsArray['conditions'] = [];
        foreach ($subConditions as $condition) {
            $conditionArray = $condition instanceof DataObject
                ? $condition->toArray()
                : $condition;
            $conditionArray = array_is_list($conditionArray)
                ? ['conditions' => $conditionArray]
                : $conditionArray;
            // Condition is a combine
            if (isset($conditionArray['conditions'])) {
                $conditionArray = $this->prepareConditions(
                    $conditionArray,
                    $defaultConditionsData,
                    $defaultConditionData
                );
            } else {
                $conditionArray += $defaultConditionData;
            }
            $conditionsArray['conditions'][] = $conditionArray;
        }
        return $conditionsArray;
    }
}
