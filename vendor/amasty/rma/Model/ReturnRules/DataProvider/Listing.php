<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\ReturnRules\DataProvider;

use Amasty\Rma\Api\Data\ResolutionInterface;
use Amasty\Rma\Model\ReturnRules\ResourceModel\CollectionFactory;
use Amasty\Rma\Model\Resolution\ResourceModel\CollectionFactory as ResolutionCollectionFactory;
use Amasty\Rma\Model\OptionSource\Status;
use Amasty\Rma\Api\Data\ReturnRulesInterface;
use Amasty\Rma\Api\ReturnRulesRepositoryInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class Listing extends AbstractDataProvider
{
    /**
     * @var ResolutionCollectionFactory
     */
    private $resolutionCollectionFactory;

    /**
     * @var ReturnRulesRepositoryInterface
     */
    private $repository;

    public function __construct(
        CollectionFactory $collectionFactory,
        ResolutionCollectionFactory $resolutionCollectionFactory,
        ReturnRulesRepositoryInterface $repository,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->resolutionCollectionFactory = $resolutionCollectionFactory;
        $this->repository = $repository;
    }

    public function getData()
    {
        /** @var \Amasty\Rma\Api\Data\ResolutionInterface[] $resolutions */
        $resolutions = $this->resolutionCollectionFactory->create()
            ->addNotDeletedFilter()
            ->getItems();
        $data = parent::getData();

        foreach ($data['items'] as &$record) {
            foreach ($resolutions as $resolution) {
                if ($resolution->getStatus() === Status::DISABLED) {
                    continue;
                }
                $ruleResolutionValue = $this->repository->getRuleResolution(
                    $resolution->getResolutionId(),
                    $record[ReturnRulesInterface::ID]
                )->getValue();

                if ($ruleResolutionValue === null) {
                    $ruleResolutionValue = $record[ReturnRulesInterface::DEFAULT_RESOLUTION];
                }

                $record['resolution_' . $resolution->getResolutionId()] = $ruleResolutionValue == '0'
                    ? '-'
                    : $ruleResolutionValue;
            }
        }

        return $data;
    }

    public function getMeta()
    {
        /** @var \Amasty\Rma\Api\Data\ResolutionInterface[] $resolutions */
        $resolutions = $this->resolutionCollectionFactory->create()->addNotDeletedFilter()->getItems();
        $meta = [
            'amrma_returnrules_columns' => [
                'children' => []
            ]
        ];

        foreach ($resolutions as $resolution) {
            if ($resolution->getStatus() === Status::DISABLED) {
                continue;
            }
            $meta['amrma_returnrules_columns']['children']['resolution_' . $resolution->getResolutionId()] =
                $this->prepareResolutionColumn(
                    $resolution->getResolutionId(),
                    $resolution->getTitle()
                );
        }

        return $meta;
    }

    /**
     * @param int $resolutionId
     * @param string $title
     *
     * @return array
     */
    private function prepareResolutionColumn($resolutionId, $title)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'component' => 'Magento_Ui\js\grid\columns\column',
                        'componentType' => 'column',
                        'dataType' => 'text',
                        'label' => $title . ' Period',
                        'sortOrder' => 40 + (10 * $resolutionId),
                        'sortable' => false
                    ]
                ]
            ],
            'attributes' => [
                'class' => \Magento\Ui\Component\Listing\Columns\Column::class,
                'component' => 'Magento_Ui\js\grid\columns\column',
                'name' => 'resolution_' . $resolutionId
            ]
        ];
    }
}
