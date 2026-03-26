<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Status\DataProvider;

use Amasty\Rma\Api\Data\StatusInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Model\OptionSource\State;
use Amasty\Rma\Model\Status\ResourceModel\CollectionFactory;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Magento\Email\Model\ResourceModel\Template\CollectionFactory as EmailCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface as HttpRequestInterface;

class Form extends AbstractDataProvider
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StatusRepositoryInterface
     */
    private $repository;

    /**
     * @var EmailCollectionFactory
     */
    private $emailCollectionFactory;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var array
     */
    private $loadedData;

    /**
     * @var HttpRequestInterface
     */
    private $request;

    public function __construct(
        CollectionFactory $collectionFactory,
        StatusRepositoryInterface $repository,
        StoreManagerInterface $storeManager,
        EmailCollectionFactory $emailCollectionFactory,
        DataPersistorInterface $dataPersistor,
        HttpRequestInterface $request,
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
        $this->emailCollectionFactory = $emailCollectionFactory;
        $this->dataPersistor = $dataPersistor;
        $this->request = $request;
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $this->getCollection()->addFieldToSelect(StatusInterface::STATUS_ID);
        $data = parent::getData();
        if (isset($data['items'][0])) {
            $statusId = $data['items'][0][StatusInterface::STATUS_ID];
            $status = $this->repository->getById($statusId);
            $this->loadedData[$statusId] = $status->getData();
            /** @var \Amasty\Rma\Api\Data\StatusStoreInterface $store */
            foreach ($status->getStores() as $store) {
                //phpcs:ignore
                $this->loadedData[$statusId] = array_merge(
                    $this->loadedData[$statusId],
                    [
                        'storelabel' . $store->getStoreId() => $store->getLabel(),
                        'storedescription' . $store->getStoreId() => $store->getDescription(),
                        'send_to_customer' . $store->getStoreId() => (string)$store->isSendEmailToCustomer(),
                        'customer_template' . $store->getStoreId() => $store->getCustomerEmailTemplate(),
                        'customer_custom_text' . $store->getStoreId() => $store->getCustomerCustomText(),
                        'send_to_admin' . $store->getStoreId() => (string)$store->isSendEmailToAdmin(),
                        'admin_template' . $store->getStoreId() => $store->getAdminEmailTemplate(),
                        'admin_custom_text' . $store->getStoreId() => $store->getAdminCustomText(),
                        'send_to_chat' . $store->getStoreId() => (string)$store->isSendToChat(),
                        'chat_message' . $store->getStoreId() => $store->getChatMessage()
                    ]
                );
            }
        }
        $data = $this->dataPersistor->get(RegistryConstants::STATUS_DATA);

        if (!empty($data)) {
            $statusId = isset($data[RegistryConstants::STATUS_ID]) ? $data[RegistryConstants::STATUS_ID] : null;
            $this->loadedData[$statusId] = $data;
            $this->dataPersistor->clear(RegistryConstants::STATUS_DATA);
        }

        return $this->loadedData;
    }

    public function getMeta()
    {
        $meta = parent::getMeta();

        if ($statusId = $this->request->getParam(RegistryConstants::STATUS_ID)) {
            try {
                $status = $this->repository->getById($statusId);
                if ($status->getState() === State::CANCELED) {
                    $meta['general']['children']['is_enabled']['arguments']['data']['config']['visible'] = false;
                    $meta['general']['children']['is_initial']['arguments']['data']['config']['visible'] = false;
                }
            } catch (\Exception $e) {
                null;
            }
        }

        /** @var \Magento\Email\Model\ResourceModel\Template\Collection $emailCollection */
        $emailCollection = $this->emailCollectionFactory->create();
        $emailCollection->addFieldToFilter(
            'orig_template_code',
            ['amrma_email_empty_frontend', 'amrma_email_empty_backend']
        )->addFieldToSelect(['template_id', 'template_code', 'orig_template_code']);

        $mailTemplates = [
            'backend' => [
                ['value' => 0, 'label' => 'Default Template'],
            ],
            'frontend' => [
                ['value' => 0, 'label' => 'Default Template'],
            ]
        ];
        foreach ($emailCollection->getData() as $emailTemplate) {
            if ($emailTemplate['orig_template_code'] == 'amrma_email_empty_frontend') {
                $mailTemplates['frontend'][] = [
                    'value' => $emailTemplate['template_id'],
                    'label' => $emailTemplate['template_code']
                ];
            } else {
                $mailTemplates['backend'][] = [
                    'value' => $emailTemplate['template_id'],
                    'label' => $emailTemplate['template_code']
                ];
            }
        }

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
                    $elementPath = 'amrma_status_form.amrma_status_form.labels.'
                        . 'website' . $website->getId() . '.'
                        . 'group' . $storeGroup->getId() . '.'
                        . 'store' . $store->getId() . '.';
                    $this->setStoreMeta(
                        $meta['labels']['children']['website' . $website->getId()]['children']
                            ['group' . $storeGroup->getId()]['children']['store' . $store->getId()],
                        $elementPath,
                        $store->getId(),
                        $store->getName(),
                        $mailTemplates
                    );
                }
            }
        }

        $elementPath = 'amrma_status_form.amrma_status_form.labels.'
            . 'store0.';
        $this->setStoreMeta(
            $meta['labels']['children']['store0'],
            $elementPath,
            0,
            __('All Store Views'),
            $mailTemplates
        );

        if ($storeCount === 1) {
            $meta['labels']['children']['website' . $website->getId()]['arguments']['data']['opened'] = false;
        }

        return $meta;
    }

    public function setStoreMeta(&$metaToStore, $elementPath, $storeId, $label, $mailTemplates)
    {

        $metaToStore['arguments']['data']['config'] = [
            'label' => $label,
            'collapsible' => true,
            'opened' => true,
            'visible' => true,
            'componentType' => 'fieldset'
        ];
        $metaToStore['children']['storelabel' . $storeId]['arguments']['data']['config'] = [
            'label' => __('Label'),
            'dataType' => 'text',
            'formElement' => 'input',
            'visible' => true,
            'componentType' => 'field',
            'source' => 'storelabel' . $storeId
        ];
        $metaToStore['children']['storedescription' . $storeId]['arguments']['data']['config'] = [
            'label' => __('Text for \'Returns: how it works\''),
            'dataType' => 'text',
            'dataScope' => 'storedescription' . $storeId,
            'formElement' => 'wysiwyg',
            'template' => 'ui/form/field',
            'componentType' => 'wysiwyg',
            'wysiwyg' => true,
            'elementSelector' => '[name="' . 'storedescription' . $storeId . '"]',
            'visible' => true,
            'source' => 'storedescription' . $storeId
        ];
        $metaToStore['children']['storedescription' . $storeId]['arguments']['config']['wysiwyg'] = true;
        $metaToStore['children']['storedescription' . $storeId]['arguments']['config']['wysiwygConfigData'] = [
            'is_pagebuilder_enabled' => false,
        ];

        /** Customer Email Start */

        $metaToStore['children']['send_to_customer' . $storeId]['arguments']['data']['config'] = [
            'label' => __('Send Email to Customer'),
            'notice' => __(
                'The notification will be send to the customer when status value changes to the current one.'
            ),
            'dataType' => 'boolean',
            'formElement' => 'checkbox',
            'prefer' => 'toggle',
            'valueMap' => ['true' => '1', 'false' => '0'],
            'default' => 0,
            'visible' => true,
            'componentType' => 'field',
            'source' => 'send_to_customer' . $storeId,
            'switcherConfig' => [
                'enabled' => true,
                'rules' => [
                    [
                        'value' => 0,
                        'actions' => [
                            [
                                'target' => $elementPath . 'customer_custom_text' . $storeId,
                                'callback' => 'hide'
                            ],
                            [
                                'target' => $elementPath . 'customer_template' . $storeId,
                                'callback' => 'hide'
                            ],
                        ]
                    ],
                    [
                        'value' => 1,
                        'actions' => [
                            [
                                'target' => $elementPath . 'customer_custom_text' . $storeId,
                                'callback' => 'show'
                            ],
                            [
                                'target' => $elementPath . 'customer_template' . $storeId,
                                'callback' => 'show'
                            ]
                        ]
                    ]
                ],
            ],
        ];

        $metaToStore['children']['customer_template' . $storeId]['arguments']['data']['config'] = [
            'label' => __('Customer Email Template'),
            'dataType' => 'select',
            'formElement' => 'select',
            'visible' => true,
            'componentType' => 'field',
            'source' => 'customer_template' . $storeId
        ];
        $metaToStore['children']['customer_template' . $storeId]['arguments']['data']['options'] =
            $mailTemplates['frontend'];

        $metaToStore['children']['customer_custom_text' . $storeId]['arguments']['data']['config'] = [
            'label' => __('Customer Email Custom Text'),
            'dataType' => 'text',
            'formElement' => 'textarea',
            'visible' => true,
            'componentType' => 'field',
            'source' => 'customer_custom_text' . $storeId
        ];
        /** End Customer Email */

        /** Admin Email */
        $metaToStore['children']['send_to_admin' . $storeId]['arguments']['data']['config'] = [
            'label' => __('Send Email to Admin'),
            'notice' => __(
                'The notification will be send to the admin when status value changes to the current one.'
            ),
            'dataType' => 'boolean',
            'formElement' => 'checkbox',
            'prefer' => 'toggle',
            'valueMap' => ['true' => '1', 'false' => '0'],
            'default' => 0,
            'visible' => true,
            'componentType' => 'field',
            'source' => 'send_to_admin' . $storeId,
            'switcherConfig' => [
                'enabled' => true,
                'rules' => [
                    [
                        'value' => 0,
                        'actions' => [
                            [
                                'target' => $elementPath . 'admin_custom_text' . $storeId,
                                'callback' => 'hide'
                            ],
                            [
                                'target' => $elementPath . 'admin_template' . $storeId,
                                'callback' => 'hide'
                            ],
                        ]
                    ],
                    [
                        'value' => 1,
                        'actions' => [
                            [
                                'target' => $elementPath . 'admin_custom_text' . $storeId,
                                'callback' => 'show'
                            ],
                            [
                                'target' => $elementPath . 'admin_template' . $storeId,
                                'callback' => 'show'
                            ]
                        ]
                    ]
                ],
            ],
        ];

        $metaToStore['children']['admin_template' . $storeId]['arguments']['data']['config'] = [
            'label' => __('Admin Email Template'),
            'dataType' => 'select',
            'formElement' => 'select',
            'visible' => true,
            'componentType' => 'field',
            'source' => 'admin_template' . $storeId
        ];
        $metaToStore['children']['admin_template' . $storeId]['arguments']['data']['options'] =
            $mailTemplates['backend'];

        $metaToStore['children']['admin_custom_text' . $storeId]['arguments']['data']['config'] = [
            'label' => __('Admin Email Custom Text'),
            'dataType' => 'text',
            'formElement' => 'textarea',
            'visible' => true,
            'componentType' => 'field',
            'source' => 'admin_custom_text' . $storeId
        ];
        /** End Admin Email */

        /** Chat */
        $metaToStore['children']['send_to_chat' . $storeId]['arguments']['data']['config'] = [
            'label' => __('Send Text to Chat'),
            'notice' => __(
                'The notification will be send to the chat when status value changes to the current one.'
            ),
            'dataType' => 'boolean',
            'formElement' => 'checkbox',
            'prefer' => 'toggle',
            'valueMap' => ['true' => '1', 'false' => '0'],
            'default' => 0,
            'visible' => true,
            'componentType' => 'field',
            'source' => 'chat_text' . $storeId,
            'switcherConfig' => [
                'enabled' => true,
                'rules' => [
                    [
                        'value' => 0,
                        'actions' => [
                            [
                                'target' => $elementPath . 'chat_message' . $storeId,
                                'callback' => 'hide'
                            ]
                        ]
                    ],
                    [
                        'value' => 1,
                        'actions' => [
                            [
                                'target' => $elementPath . 'chat_message' . $storeId,
                                'callback' => 'show'
                            ]
                        ]
                    ]
                ],
            ],
        ];

        $metaToStore['children']['chat_message' . $storeId]['arguments']['data']['config'] = [
            'label' => __('Chat Text'),
            'dataType' => 'text',
            'formElement' => 'textarea',
            'visible' => true,
            'componentType' => 'field',
            'source' => 'chat_message' . $storeId
        ];
        /** End Chat */
    }
}
