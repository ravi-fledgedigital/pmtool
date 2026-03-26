<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Resolution\DataProvider;

use Amasty\Rma\Api\Data\ResolutionInterface;
use Amasty\Rma\Model\Resolution\Repository;
use Amasty\Rma\Model\Resolution\ResourceModel\CollectionFactory;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\Request\DataPersistorInterface;

class Form extends AbstractDataProvider
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var array
     */
    private $loadedData;

    public function __construct(
        CollectionFactory $collectionFactory,
        Repository $repository,
        StoreManagerInterface $storeManager,
        DataPersistorInterface $dataPersistor,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->repository = $repository;
        $this->dataPersistor = $dataPersistor;
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $this->getCollection()->addFieldToSelect(ResolutionInterface::RESOLUTION_ID);
        $data = parent::getData();
        if (isset($data['items'][0])) {
            $resolutionId = $data['items'][0][ResolutionInterface::RESOLUTION_ID];
            $resolution = $this->repository->getById($resolutionId);
            $this->loadedData[$resolutionId] = $resolution->getData();
            $selectedStores = [];
            foreach ($resolution->getStores() as $store) {
                $this->loadedData[$resolutionId]['storelabel' . $store->getStoreId()] = $store->getLabel();
                $selectedStores[] = (string)$store->getStoreId();
            }
            $this->loadedData[$resolutionId]['store_ids'] = $selectedStores;
        }
        $data = $this->dataPersistor->get(RegistryConstants::RESOLUTION_DATA);

        if (!empty($data)) {
            $resolutionId = isset($data[RegistryConstants::RESOLUTION_ID])
                ? $data[RegistryConstants::RESOLUTION_ID]
                : null;
            $this->loadedData[$resolutionId] = $data;
            $this->dataPersistor->clear(RegistryConstants::RESOLUTION_DATA);
        }

        return $this->loadedData;
    }

    public function getMeta()
    {
        $meta = parent::getMeta();

        $storeCount = 0;
        foreach ($this->storeManager->getWebsites() as $website) {
            $meta['labels']['children']['website' . $website->getId()]['arguments']['data']['config'] = [
                'label' => $website->getName(),
                'collapsible' => true,
                'opened' => false,
                'visible' => true,
                'componentType' => 'fieldset'
            ];
            foreach ($website->getGroups() as $storeGroup) {
                $meta['labels']['children']['website' . $website->getId()]
                ['children']['group' . $storeGroup->getId()]['arguments']['data']['config'] = [
                    'label' => $storeGroup->getName(),
                    'collapsible' => true,
                    'opened' => true,
                    'visible' => true,
                    'componentType' => 'fieldset'
                ];

                foreach ($storeGroup->getStores() as $store) {
                    $storeCount++;
                    $meta['labels']['children']['website' . $website->getId()]
                    ['children']['group' . $storeGroup->getId()]['children']['store' . $store->getId()]
                    ['arguments']['data']['config'] = [
                        'label' => $store->getName(),
                        'collapsible' => true,
                        'opened' => true,
                        'visible' => true,
                        'componentType' => 'fieldset'
                    ];
                    $meta['labels']['children']['website' . $website->getId()]
                    ['children']['group' . $storeGroup->getId()]['children']['store' . $store->getId()]
                    ['children']['storelabel' . $store->getId()]['arguments']['data']['config'] = [
                        'label' => __('Label'),
                        'dataType' => 'text',
                        'formElement' => 'input',
                        'component' => 'Amasty_Rma/js/form/element/abstract',
                        'visible' => true,
                        'componentType' => 'field',
                        'tooltip' => [
                            'description' => __('A \'Title\' will be displayed to store admins in the '
                                . 'backend while a \'Label\' will be displayed to customers on the frontend')
                        ],
                        'source' => 'storelabel' . $store->getId()
                    ];
                }
            }
        }

        if ($storeCount === 1) {
            $meta['labels']['children']['website' . $website->getId()]['arguments']['data']['opened'] = false;
        }

        return $meta;
    }
}
