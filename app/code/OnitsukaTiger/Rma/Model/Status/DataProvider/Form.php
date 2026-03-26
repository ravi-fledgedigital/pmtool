<?php
namespace OnitsukaTiger\Rma\Model\Status\DataProvider;

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

class Form extends \Amasty\Rma\Model\Status\DataProvider\Form
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
        parent::__construct($collectionFactory, $repository, $storeManager, $emailCollectionFactory, $dataPersistor, $request, $name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->repository = $repository;
        $this->emailCollectionFactory = $emailCollectionFactory;
        $this->dataPersistor = $dataPersistor;
        $this->request = $request;
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
                ['value' => '1', 'label' => 'Default Approved Template'],
                ['value' => '4', 'label' => 'Default Initiated Template'],
                ['value' => '2', 'label' => 'Default Rejected Template'],
                ['value' => '3', 'label' => 'Default Completed Template'],
            ],
            'frontend' => [
                ['value' => 0, 'label' => 'Default Template'],
                ['value' => '1', 'label' => 'Default Approved Template'],
                ['value' => '4', 'label' => 'Default Initiated Template'],
                ['value' => '2', 'label' => 'Default Rejected Template'],
                ['value' => '3', 'label' => 'Default Completed Template'],
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
}
