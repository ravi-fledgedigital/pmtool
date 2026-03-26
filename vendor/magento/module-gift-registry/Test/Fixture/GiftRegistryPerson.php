<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Fixture;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\DataObject;
use Magento\GiftRegistry\Model\PersonFactory;
use Magento\GiftRegistry\Model\ResourceModel\Person as PersonResource;
use Magento\TestFramework\Fixture\Api\DataMerger;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class GiftRegistryPerson implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'person_id' => null,
        'entity_id' => null,
        'firstname' => null,
        'lastname' => null,
        'email' => 'registrant%uniqid%@mail.com',
        'role' => null,
        'custom_values' => null,
        ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY => []
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
     * @var PersonResource
     */
    private PersonResource $personResource;

    public function __construct(
        ServiceFactory     $serviceFactory,
        ProcessorInterface $dataProcessor,
        DataMerger         $dataMerger,
        PersonResource     $personResource
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->dataProcessor = $dataProcessor;
        $this->dataMerger = $dataMerger;
        $this->personResource = $personResource;
    }

    public function apply(array $data = []): ?DataObject
    {
        $personRegistryService = $this->serviceFactory->create(
            PersonFactory::class,
            'create'
        );
        $data = $this->prepareData($data);
        /** @var \Magento\GiftRegistry\Model\Person $entity */
        $entity = $personRegistryService->execute([]);
        $entity->addData($data);
        $entity->save();

        return $entity;
    }

    public function revert(DataObject $data): void
    {
        $service = $this->serviceFactory->create(PersonFactory::class, 'create');
        /** @var \Magento\GiftRegistry\Model\Person $personEntity */
        $personEntity = $service->execute([]);
        $personEntity->addData($data->toArray());

        if ($data->getId()) {
            $this->personResource->delete($personEntity);
        }
    }

    /**
     * Prepare gift registry person data
     *
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $data = $this->dataMerger->merge(self::DEFAULT_DATA, $data);
        return $this->dataProcessor->process($this, $data);
    }
}
