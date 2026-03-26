<?php

namespace OnitsukaTigerKorea\OrderCancel\Model\Reason\DataProvider;

use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTigerKorea\OrderCancel\Api\Data\ReasonInterface;
use OnitsukaTigerKorea\OrderCancel\Model\Reason\Repository;
use OnitsukaTigerKorea\OrderCancel\Model\Reason\ResourceModel\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\Request\DataPersistorInterface;

class Form extends AbstractDataProvider
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var Repository
     */
    private Repository $repository;

    /**
     * @var DataPersistorInterface
     */
    private DataPersistorInterface $dataPersistor;

    /**
     * @var array
     */
    private $loadedData;

    /**
     * @param CollectionFactory $collectionFactory
     * @param Repository $repository
     * @param StoreManagerInterface $storeManager
     * @param DataPersistorInterface $dataPersistor
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
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

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $this->getCollection()->addFieldToSelect(ReasonInterface::REASON_ID);
        $data = parent::getData();

        if (isset($data['items'][0])) {
            $reasonId = $data['items'][0][ReasonInterface::REASON_ID];
            $reason = $this->repository->getById($reasonId);
            $this->loadedData[$reasonId] = $reason->getData();
            $selectedStores = [];
            foreach ($reason->getStores() as $store) {
                $this->loadedData[$reasonId]['storelabel' . $store->getStoreId()] = $store->getLabel();
                $selectedStores[] = (string)$store->getStoreId();
            }
            $this->loadedData[$reasonId]['store_ids'] = $selectedStores;
        }
        $data = $this->dataPersistor->get('reason_data');

        if (!empty($data)) {
            $reasonId = $data['reason_id'] ?? null;
            $this->loadedData[$reasonId] = $data;
            $this->dataPersistor->clear('reason_data');
        }

        return $this->loadedData;
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

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
                        'component' => 'OnitsukaTigerKorea_OrderCancel/js/form/element/abstract',
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

        return $meta;
    }
}
