<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\GiftRegistry\Model\ResourceModel\Entity as ResourceModel;
use Magento\TestFramework\Fixture\Api\DataMerger;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class GiftRegistry implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'entity_id' => null,
        'type_id' => null,
        'customer_id' => null,
        'website_id' => null,
        'is_public' => null,
        'url_key' => 'url%uniqid%key',
        'title' => null,
        'message' => 'message',
        'shipping_address' => [
            'street' => 'some street'
        ],
        'custom_values' => null,
        'is_active' => null,
        'created_at' => null
    ];

    /**
     * @var ServiceFactory
     */
    private ServiceFactory $serviceFactory;

    /**
     * @var ProcessorInterface
     */
    private ProcessorInterface $dataProcessor;

    /**
     * @var DataMerger
     */
    private DataMerger $dataMerger;

    /**
     * @var ResourceModel
     */
    private ResourceModel $registryResource;

    public function __construct(
        ServiceFactory     $serviceFactory,
        ProcessorInterface $dataProcessor,
        DataMerger         $dataMerger,
        ResourceModel      $registryResource
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->dataProcessor = $dataProcessor;
        $this->dataMerger = $dataMerger;
        $this->registryResource = $registryResource;
    }

    public function apply(array $data = []): ?DataObject
    {
        $giftRegistryService = $this->serviceFactory->create(EntityFactory::class, 'create');

        /** @var \Magento\GiftRegistry\Model\Entity $entity */
        $entity = $giftRegistryService->execute([]);
        $data = $this->prepareData($data);
        $entity->addData(array_merge(['is_add_action' => true], $data));
        $entity->save();

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $service = $this->serviceFactory->create(EntityFactory::class, 'create');
        /** @var \Magento\GiftRegistry\Model\Entity $entity */
        $entity = $service->execute([]);
        $this->registryResource->load($entity, $data->getId());

        if ($data->getId()) {
            $this->registryResource->delete($entity);
        }
    }

    /**
     * Prepare gift registry data
     *
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $data = $this->dataMerger->merge(self::DEFAULT_DATA, $data);
        $data = $this->dataProcessor->process($this, $data);
        $data['shipping_address'] =
            json_encode($data['shipping_address']);
        return $data;
    }
}
