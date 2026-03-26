<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Tabs\DataProvider;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Amasty\CustomTabs\Controller\Adminhtml\RegistryConstants;
use Amasty\CustomTabs\Model\Source\Status;
use Amasty\CustomTabs\Model\Tabs\Repository;
use Amasty\CustomTabs\Model\Tabs\ResourceModel\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\Store;
use Magento\Ui\DataProvider\AbstractDataProvider;

class Form extends AbstractDataProvider
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var int
     */
    private int $storeId;

    /**
     * @var array
     */
    private array $fieldsByStore;

    public function __construct(
        CollectionFactory $tabsCollectionFactory,
        Repository $repository,
        RequestInterface $request,
        DataPersistorInterface $dataPersistor,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $fieldsByStore = [],
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $tabsCollectionFactory->create();
        $this->request = $request;
        $this->repository = $repository;
        $this->dataPersistor = $dataPersistor;
        $this->fieldsByStore = $fieldsByStore;
        $this->storeId = $this->getStoreId();
    }

    private function getStoreId(): int
    {
        return (int)$this->request->getParam('store', Store::DEFAULT_STORE_ID);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $this->collection->addStoreWithDefault($this->storeId);

        $data = parent::getData();
        $tabId = 0;
        if ($data['totalRecords'] > 0) {
            if (isset($data['items'][0]['tab_id'])) {
                $tabId = (int)$data['items'][0]['tab_id'];
                $data[$tabId] = $this->repository->getByIdAndStore($tabId, $this->storeId)->getData();
            }
        }

        $data[$tabId]['store_id'] = $this->storeId;
        $data[$tabId]['status'] = $this->getTabStatus($tabId);

        if ($savedData = $this->dataPersistor->get(RegistryConstants::TAB_DATA)) {
            $savedTabId = isset($savedData[TabsInterface::TAB_ID]) ? $savedData[TabsInterface::TAB_ID] : null;
            if (isset($data[$savedTabId])) {
                $data[$savedTabId] = array_merge($data[$savedTabId], $savedData);
            } else {
                $data[$savedTabId] = $savedData;
            }
            $this->dataPersistor->clear(RegistryConstants::TAB_DATA);
        }

        return $data;
    }

    private function getTabStatus(int $tabId): string
    {
        $item = $this->repository->getByIdAndStore($tabId, $this->storeId);
        if ($this->shouldUseCustomStatus($item)) {
            return (string)$item->getStatus();
        }

        return (string)Status::DISABLED;
    }

    private function shouldUseCustomStatus($item): bool
    {
        return $item !== null && $item->getStatus() !== null;
    }

    /**
     * @deprecated
     *
     * @return TabsInterface|null
     */
    protected function getCurrentTab()
    {
        $tabId = (int)$this->request->getParam(TabsInterface::TAB_ID);
        $tab = null;

        if ($tabId) {
            try {
                $tab = $this->repository->getById($tabId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $tab = null;
            }
        }

        return $tab;
    }

    /**
     * @return array
     */
    public function getMeta(): array
    {
        $meta = parent::getMeta();

        if ($this->storeId !== 0) {
            $tabId = (int)$this->request->getParam(TabsInterface::TAB_ID);
            $meta = $this->changeFields($tabId);
            $meta = $this->hideProductsFieldset($meta);
        }

        return $meta;
    }

    private function changeFields(int $itemId): array
    {
        $item = $this->repository->getByIdAndStore($itemId, $this->storeId);

        return $this->enrichmentFields($this->fieldsByStore, $item);
    }

    private function enrichmentFields(array $fields, DataObject $item): array
    {
        $result = [];

        foreach ($fields as $key => $field) {
            if (is_array($field) && !isset($field['is_new'])) {
                $result[$key] = $this->enrichmentFields($field, $item);
            } elseif (is_string($field)) {
                if ($item->getData($field . '_from_default') !== null) {
                    $result['children'][$field] = [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'service' => [
                                        'template' => 'ui/form/element/helper/service',
                                    ],
                                    'disabled' => $item->getData($field . '_from_default') === '1'
                                ]
                            ]
                        ],
                        'is_new' => true
                    ];
                } else {
                    $result['children'][$field] = [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'disabled' => true
                                ]
                            ]
                        ],
                        'is_new' => true
                    ];
                }

            }
        }

        return $result;
    }

    private function hideProductsFieldset(array $meta): array
    {
        $meta['products']['arguments']['data']['config']['visible'] = false;

        return $meta;
    }
}
