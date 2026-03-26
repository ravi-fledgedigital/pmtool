<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Setup\Operation;

use Amasty\Base\Model\MagentoVersion;
use Amasty\Base\Model\Serializer;
use Amasty\Rma\Api\ChatRepositoryInterface;
use Amasty\Rma\Api\ConditionRepositoryInterface;
use Amasty\Rma\Api\Data\ConditionInterface;
use Amasty\Rma\Api\Data\MessageFileInterface;
use Amasty\Rma\Api\Data\ReasonInterface;
use Amasty\Rma\Api\Data\RequestCustomFieldInterfaceFactory;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\RequestItemInterface;
use Amasty\Rma\Api\Data\ResolutionInterface;
use Amasty\Rma\Api\Data\StatusInterface;
use Amasty\Rma\Api\Data\StatusStoreInterface;
use Amasty\Rma\Api\ReasonRepositoryInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Api\ResolutionRepositoryInterface;
use Amasty\Rma\Api\ReturnRulesRepositoryInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\OptionSource\Grid;
use Amasty\Rma\Model\OptionSource\ShippingPayer;
use Amasty\Rma\Model\OptionSource\State;
use Amasty\Rma\Model\OptionSource\Status;
use Amasty\Rma\Model\Resolution\ResourceModel\CollectionFactory as ResolutionCollectionFactory;
use Amasty\Rma\Model\Status\OptionSource\AutoEvents;
use Amasty\Rma\Model\Status\ResourceModel\StatusStore;
use Amasty\Rma\Utils\FileUpload;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\PageFactory;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class UpgradeTo200
{
    public const REASON_OLD_CONFIG_PATH = 'amrma/properties/reasons';

    public const CONDITION_OLD_CONFIG_PATH = 'amrma/properties/conditions';

    public const RESOLUTION_OLD_CONFIG_PATH = 'amrma/properties/resolutions';

    /**
     * @var array $rmaImages
     */
    private $rmaImages = [
        'approved_rma.jpg' => '/view/adminhtml/web/images/approved_rma.jpg',
        'need_details.png' => '/view/adminhtml/web/images/need_details.png',
        'received_admin.png' => '/view/adminhtml/web/images/received_admin.png',
        'reject_admin.png' => '/view/adminhtml/web/images/reject_admin.png',
        'resolved_rated.png' => '/view/adminhtml/web/images/resolved_rated.png',
        'resolved_admin.png' => '/view/adminhtml/web/images/resolved_admin.png'
    ];

    public const STATUS_IMAGE_PATH = '/view/adminhtml/web/images/approved_rma.jpg';

    /**
     * @var int $notExistReason
     */
    private $notExistReasonId;

    /**
     * @var int $reasonMapping
     */
    private $notExistResolutionId;

    /**
     * @var int $notExistConditionId
     */
    private $notExistConditionId;

    /**
     * @var array $dataForTempEntity
     */
    private $dataForTempEntity = ['title' => 'temp', 'payer' => 0, 'status' => 1, 'position' => 0, 'is_deleted' => 1];

    /**
     * @var array $reasonMapping
     */
    private $reasonMapping = [];

    /**
     * @var array $resolutionMapping
     */
    private $resolutionMapping = [];

    /**
     * @var array $conditionMapping
     */
    private $conditionMapping = [];

    /**
     * @var array $statusMapping
     */
    private $statusMapping = [];

    /**
     * @var array $requestMapping
     */
    private $requestMapping = [];

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var ReasonRepositoryInterface
     */
    private $reasonRepository;

    /**
     * @var ResolutionRepositoryInterface
     */
    private $resolutionRepository;

    /**
     * @var ConditionRepositoryInterface
     */
    private $conditionRepository;

    /**
     * @var StatusRepositoryInterface
     */
    private $statusRepository;

    /**
     * @var StatusStore
     */
    private $statusStoreResource;

    /**
     * @var RequestRepositoryInterface
     */
    private $requestRepository;

    /**
     * @var ChatRepositoryInterface
     */
    private $chatRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var DataObject
     */
    private $dataObject;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ReturnRulesRepositoryInterface
     */
    private $returnRulesRepository;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ConfigInterface
     */
    private $configInterface;

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var TypeListInterface
     */
    private $typeList;

    /**
     * @var TemplateFactory
     */
    private $emailTemplate;

    /**
     * @var ResolutionCollectionFactory
     */
    private $resolutionCollectionFactory;

    /**
     * @var FileUpload
     */
    private $fileUpload;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var File
     */
    private $ioFile;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var RequestCustomFieldInterfaceFactory
     */
    private $customFieldFactory;

    public function __construct(
        Serializer $serializer,
        ReasonRepositoryInterface $reasonRepository,
        ResolutionRepositoryInterface $resolutionRepository,
        ConditionRepositoryInterface $conditionRepository,
        StatusRepositoryInterface $statusRepository,
        RequestRepositoryInterface $requestRepository,
        ChatRepositoryInterface $chatRepository,
        ReturnRulesRepositoryInterface $returnRulesRepository,
        ResolutionCollectionFactory $resolutionCollectionFactory,
        StatusStore $statusStoreResource,
        StoreManagerInterface $storeManager,
        ProductCollectionFactory $productCollectionFactory,
        FileUpload $fileUpload,
        DataObject $dataObject,
        LoggerInterface $logger,
        ConfigInterface $configInterface,
        PageFactory $pageFactory,
        PageRepositoryInterface $pageRepository,
        TypeListInterface $typeList,
        TemplateFactory $emailTemplate,
        Filesystem $filesystem,
        Reader $reader,
        File $ioFile,
        CustomerRepository $customerRepository,
        MagentoVersion $magentoVersion,
        RequestCustomFieldInterfaceFactory $customFieldFactory
    ) {
        $this->serializer = $serializer;
        $this->reasonRepository = $reasonRepository;
        $this->resolutionRepository = $resolutionRepository;
        $this->conditionRepository = $conditionRepository;
        $this->statusRepository = $statusRepository;
        $this->statusStoreResource = $statusStoreResource;
        $this->requestRepository = $requestRepository;
        $this->chatRepository = $chatRepository;
        $this->storeManager = $storeManager;
        $this->dataObject = $dataObject;
        $this->logger = $logger;
        $this->returnRulesRepository = $returnRulesRepository;
        $this->resolutionCollectionFactory = $resolutionCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->configInterface = $configInterface;
        $this->pageFactory = $pageFactory;
        $this->pageRepository = $pageRepository;
        $this->typeList = $typeList;
        $this->emailTemplate = $emailTemplate;
        $this->fileUpload = $fileUpload;
        $this->filesystem = $filesystem;
        $this->reader = $reader;
        $this->ioFile = $ioFile;
        $this->customerRepository = $customerRepository;
        $this->magentoVersion = $magentoVersion;
        $this->customFieldFactory = $customFieldFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param string $currentVersion
     */
    public function execute(ModuleDataSetupInterface $setup, $currentVersion = '')
    {
        $this->createReturnPolicy();
        $this->saveAndSetEmails();
        $this->moveStatusImageToMedia();

        if (!$currentVersion) {
            // first installation
            $this->createDefaultResolutions();
            $this->createDefaultReasons();
            $this->createDefaultItemConditions();
            $this->createDefaultReturnRule();
            $this->createDefaultStatuses();
        } else {
            // update current version
            $this->moveResolutions($setup);
            $this->moveReasons($setup);
            $this->moveConditions($setup);
            $this->moveStatuses($setup);
            $this->moveRequests($setup);
            $this->moveComments($setup);
            $this->moveExtraFields($setup);
        }
    }

    private function moveStatusImageToMedia()
    {
        try {
            $mediaPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)
                ->getAbsolutePath() . 'wysiwyg' . DIRECTORY_SEPARATOR . FileUpload::MEDIA_PATH;
            $this->ioFile->checkAndCreateFolder($mediaPath);

            foreach ($this->rmaImages as $imageName => $imagePath) {
                $imagePath = $this->reader->getModuleDir('', 'Amasty_Rma') . $imagePath;

                if ($this->ioFile->fileExists($imagePath)) {
                    $this->ioFile->cp($imagePath, $mediaPath . $imageName);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    private function saveDefaultEntity($repository, $model, $storeModel, $dataObject)
    {
        try {
            $model->addData($dataObject->getData());
            $storeModel->setLabel($dataObject->getDataByKey('label'));
            $storeModel->setDescription($dataObject->getDataByKey('description'));
            $storeModel->setStoreId(0);
            $model->setStores([$storeModel]);
            $repository->save($model);
        } catch (CouldNotSaveException $e) {
            // do nothing
            null;
        }
    }

    private function createDefaultResolutions()
    {
        foreach ($this->getDefaultResolutions() as $resolution) {
            $this->dataObject->unsetData()->addData($resolution);
            $this->saveDefaultEntity(
                $this->resolutionRepository,
                $this->resolutionRepository->getEmptyResolutionModel(),
                $this->resolutionRepository->getEmptyResolutionStoreModel(),
                $this->dataObject
            );
        }
    }

    private function createDefaultReasons()
    {
        foreach ($this->getDefaultReasons() as $reason) {
            $this->dataObject->unsetData()->addData($reason);
            $this->saveDefaultEntity(
                $this->reasonRepository,
                $this->reasonRepository->getEmptyReasonModel(),
                $this->reasonRepository->getEmptyReasonStoreModel(),
                $this->dataObject
            );
        }
    }

    private function createDefaultItemConditions()
    {
        foreach ($this->getDefaultItemConditions() as $itemCondition) {
            $this->dataObject->unsetData()->addData($itemCondition);
            $this->saveDefaultEntity(
                $this->conditionRepository,
                $this->conditionRepository->getEmptyConditionModel(),
                $this->conditionRepository->getEmptyConditionStoreModel(),
                $this->dataObject
            );
        }
    }

    private function createDefaultReturnRule()
    {
        try {
            $ruleResolutionsArray = [];
            /** @var \Amasty\Rma\Model\ReturnRules\ReturnRules $returnRuleModel */
            $returnRuleModel = $this->returnRulesRepository->getEmptyRuleModel();
            $returnRuleModel->setName('Rule Example');
            $returnRuleModel->setStatus(0);
            $returnRuleModel->setPriority(5);
            $returnRuleModel->setDefaultResolution(30);
            $ruleId = $this->returnRulesRepository->save($returnRuleModel);
            $resolutions = $this->resolutionCollectionFactory->create()->getItems();

            foreach ($resolutions as $resolution) {
                /** @var \Amasty\Rma\Model\Resolution\Resolution $resolution */
                if ($resolution->getStatus() === Status::DISABLED) {
                    continue;
                }

                $value = null;
                $ruleResolutionModel = $this->returnRulesRepository->getEmptyRuleResolutionModel();

                if ($resolution->getTitle() == 'Return') {
                    $value = 15;
                }

                $ruleResolutionModel->setValue($value)->setResolutionId($resolution->getResolutionId());
                $ruleResolutionsArray[] = $ruleResolutionModel;
            }

            $returnRuleModel->setResolutions($ruleResolutionsArray);
            $this->returnRulesRepository->save($returnRuleModel);
        } catch (CouldNotSaveException $e) {
            // do nothing
            null;
        }
    }

    private function createDefaultStatuses()
    {
        foreach ($this->getDefaultStatuses() as $status) {
            $this->dataObject->unsetData()->addData($status);
            $this->saveDefaultEntity(
                $this->statusRepository,
                $this->statusRepository->getEmptyStatusModel(),
                $this->statusRepository->getEmptyStatusStoreModel(),
                $this->dataObject
            );
        }
    }

    private function createReturnPolicy()
    {
        /** @var \Magento\Cms\Api\Data\PageInterface $page */
        $page = $this->pageFactory->create();
        $page->setIsActive(1);
        $page->setTitle('This is a sample of the Return Policy ');
        $page->setContentHeading('This is a sample of the Return Policy ');
        $page->setIdentifier('amasty-rma-return-policy');
        $page->setContent($this->getReturnPolicyPageContent());
        $page->setPageLayout('1column');
        $page->setStoreId(['0']);

        try {
            $page = $this->pageRepository->save($page);
            $this->configInterface->saveConfig(
                'amrma/' . ConfigProvider::RETURN_POLICY_PAGE,
                $page->getId(),
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
            $this->typeList->invalidate(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        } catch (\Exception $e) {
            null;
        }
    }

    /**
     * @return string
     */
    private function getReturnPolicyPageContent()
    {
        return preg_replace("/\r|\n/", "", '
            <p><strong>Return &amp; Refund Policy</strong></p>
            <p>Thanks for shopping at My Site (change this).<br />If you are not entirely
            satisfied with your purchase, we\'re here to help.</p>
            <p><strong>Returns</strong></p>
            <p>You have 30 (change this) calendar days to return an item from the date you received it.
            <br />To be eligible for a return, your item must be unused and in the same condition that
            you received it.<br />Your item must be in the original packaging.<br />
            Your item needs to have the receipt or proof of purchase.</p>
            <p><strong>Refunds</strong></p>
            <p>Once we receive your item, we will inspect it and notify you that we have received your returned
            <br />item. We will immediately notify you on the status of your refund after inspecting the item.
            <br />If your return is approved, we will initiate a refund to your credit card
            (or original method of payment).<br />You will receive the credit within a certain amount of days,
            depending on your card issuer\'s policies.</p>
            <p><strong>Shipping</strong></p>
            <p>You will be responsible for paying for your own shipping costs for returning your item.
            Shipping costs are non­refundable.<br />If you receive a refund, the cost of return shipping will
            be deducted from your refund.</p>
            <p><strong>Contact Us</strong></p>
            <p>If you have any questions on how to return your item to us, contact us.</p>
        ');
    }

    public function saveAndSetEmails()
    {
        $this->saveAndSetEmail(
            'Amasty RMA creation admin notification template',
            'amrma_email_admin_template',
            ConfigProvider::XPATH_ADMIN_TEMPLATE,
            Area::AREA_ADMINHTML
        );
        $this->saveAndSetEmail(
            'Amasty RMA creation customer notification template',
            'amrma_email_user_template',
            ConfigProvider::XPATH_USER_TEMPLATE
        );
        $this->saveAndSetEmail(
            'Amasty RMA Manager Sent New Message',
            'amrma_email_new_message_template',
            ConfigProvider::XPATH_NEW_MESSAGE_TEMPLATE
        );
        $this->typeList->invalidate(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
    }

    /**
     * @param string $code
     * @param string $originalCode
     * @param string $configPath
     * @param string $area
     */
    private function saveAndSetEmail($code, $originalCode, $configPath, $area = Area::AREA_FRONTEND)
    {
        try {
            /** @var \Magento\Email\Model\Template $mailTemplate */
            $mailTemplate = $this->emailTemplate->create();

            if (version_compare($this->magentoVersion->get(), '2.3.4', '>=')) {
                $mailTemplate->setIsLegacy(1);
            }

            $mailTemplate->setDesignConfig(
                ['area' => $area, 'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID]
            )->loadDefault(
                $originalCode
            )->setTemplateCode(
                $code
            )->setOrigTemplateCode(
                $originalCode
            )->setId(
                null
            )->save();
            $this->configInterface->saveConfig(
                $configPath,
                $mailTemplate->getId(),
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        } catch (\Exception $e) {
            null;
        }
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    private function moveReasons($setup)
    {
        $connection = $setup->getConnection();
        $reasonsSelect = $connection->select()->from(
            ['reasons' => $setup->getTable('core_config_data')]
        )->where('path = ?', self::REASON_OLD_CONFIG_PATH)->order('scope_id');
        $reasons = $connection->fetchAll($reasonsSelect);
        $preparedReasons = $this->prepareOldConfigData($reasons);

        try {
            foreach ($preparedReasons as $reasonKey => $reasonsArray) {
                $this->dataObject->unsetData()->addData($reasonsArray);
                $reasonsStore = [];
                $reasonModel = $this->reasonRepository->getEmptyReasonModel();
                $reasonModel->setStatus(1);
                $reasonModel->setTitle($this->dataObject->getDataByKey(0));

                foreach ($reasonsArray as $storeId => $oldStoreReason) {
                    // collecting data about reasons on store view level
                    /** @var \Amasty\Rma\Model\Reason\ReasonStore $reasonStoreModel */
                    $reasonStoreModel = $this->reasonRepository->getEmptyReasonStoreModel();
                    $reasonStoreModel->setLabel($oldStoreReason);
                    $reasonStoreModel->setStoreId($storeId);
                    $reasonsStore[] = $reasonStoreModel;
                }

                $reasonModel->setStores($reasonsStore);
                $reasonId = $this->reasonRepository->save($reasonModel)->getReasonId();
                $this->reasonMapping[$reasonModel->getTitle()] = $reasonId;
            }
        } catch (CouldNotSaveException $e) {
            // do nothing
            null;
        }
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    private function moveResolutions($setup)
    {
        $connection = $setup->getConnection();
        $resolutionsSelect = $connection->select()->from(
            ['resolutions' => $setup->getTable('core_config_data')]
        )->where('path = ?', self::RESOLUTION_OLD_CONFIG_PATH)->order('scope_id');
        $resolutions = $connection->fetchAll($resolutionsSelect);
        $preparedResolutions = $this->prepareOldConfigData($resolutions);

        try {
            foreach ($preparedResolutions as $resolutionKey => $resolutionsArray) {
                $this->dataObject->unsetData()->addData($resolutionsArray);
                $resolutionsStore = [];
                $resolutionModel = $this->resolutionRepository->getEmptyResolutionModel();
                $resolutionModel->setStatus(1);
                $resolutionModel->setTitle($this->dataObject->getDataByKey(0));

                foreach ($resolutionsArray as $storeId => $oldStoreResolution) {
                    // collecting data about resolutions on store view level
                    /** @var \Amasty\Rma\Model\Resolution\ResolutionStore $resolutionStoreModel */
                    $resolutionStoreModel = $this->resolutionRepository->getEmptyResolutionStoreModel();
                    $resolutionStoreModel->setLabel($oldStoreResolution);
                    $resolutionStoreModel->setStoreId($storeId);
                    $resolutionsStore[] = $resolutionStoreModel;
                }

                $resolutionModel->setStores($resolutionsStore);
                $resolutionId = $this->resolutionRepository->save($resolutionModel)->getResolutionId();
                $this->resolutionMapping[$resolutionModel->getTitle()] = $resolutionId;
            }
        } catch (CouldNotSaveException $e) {
            // do nothing
            null;
        }
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    private function moveConditions($setup)
    {
        $connection = $setup->getConnection();
        $conditionsSelect = $connection->select()->from(
            ['conditions' => $setup->getTable('core_config_data')]
        )->where('path = ?', self::CONDITION_OLD_CONFIG_PATH)->order('scope_id');
        $conditions = $connection->fetchAll($conditionsSelect);
        $preparedConditions = $this->prepareOldConfigData($conditions);

        try {
            foreach ($preparedConditions as $conditionKey => $conditionsArray) {
                $this->dataObject->unsetData()->addData($conditionsArray);
                $conditionsStore = [];
                $conditionModel = $this->conditionRepository->getEmptyConditionModel();
                $conditionModel->setStatus(1);
                $conditionModel->setTitle($this->dataObject->getDataByKey(0));

                foreach ($conditionsArray as $storeId => $oldStoreCondition) {
                    // collecting data about conditions on store view level
                    /** @var \Amasty\Rma\Model\Condition\ConditionStore $conditionStoreModel */
                    $conditionStoreModel = $this->conditionRepository->getEmptyConditionStoreModel();
                    $conditionStoreModel->setLabel($oldStoreCondition);
                    $conditionStoreModel->setStoreId($storeId);
                    $conditionsStore[] = $conditionStoreModel;
                }

                $conditionModel->setStores($conditionsStore);
                $conditionId = $this->conditionRepository->save($conditionModel)->getConditionId();
                $this->conditionMapping[$conditionModel->getTitle()] = $conditionId;
            }
        } catch (CouldNotSaveException $e) {
            // do nothing
            null;
        }
    }

    /**
     * @param array $oldConfigData
     *
     * @return array $preparedData
     */
    private function prepareOldConfigData($oldConfigData)
    {
        $preparedData = [];

        try {
            foreach ($oldConfigData as $entity) {
                $this->dataObject->unsetData()->addData($entity);
                $oldEntityArray = $this->serializer->unserialize($this->dataObject->getDataByKey('value'));
                $storeId = $this->dataObject->getDataByKey('scope_id');

                foreach ($oldEntityArray as $key => $value) {
                    $this->dataObject->unsetData()->addData($value);
                    $preparedData[$key][$storeId] = $this->dataObject->getDataByKey('value');
                }
            }
        } catch (\InvalidArgumentException $e) {
            $this->logger->critical(
                'Unable to unserialize config value for Rma conditions.',
                ['exception' => $e->getMessage()]
            );
        }

        return $preparedData;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    private function moveStatuses(ModuleDataSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $rmaStatusesSelect = $connection->select()->from(
            ['status_table' => $setup->getTable('amasty_amrma_status')],
            [
                'status_id' => 'id',
                'is_enabled' => 'is_active'
            ]
        )->joinLeft(
            ['label_table' => $setup->getTable('amasty_amrma_status_label')],
            'status_table.id = label_table.status_id',
            [
                'title' => 'label',
                'store_id' => 'store_id'
            ]
        )->order(['status_id ASC', 'status_key ASC']);
        $rmaStatuses = $connection->fetchAll($rmaStatusesSelect);
        $oldStatusId = 0;
        $initialStatus = true;

        try {
            foreach ($rmaStatuses as $statusData) {
                $this->dataObject->unsetData()->addData($statusData);

                if ($oldStatusId == $this->dataObject->getDataByKey(StatusInterface::STATUS_ID)) {
                    // saving statusStoreModel for different stores
                    $statusStoreModel = $this->statusRepository->getEmptyStatusStoreModel();
                    $statusStoreModel->setLabel($this->dataObject->getDataByKey(StatusInterface::TITLE));
                    $statusStoreModel->setStoreId($this->dataObject->getDataByKey(StatusStoreInterface::STORE_ID));
                    $statusStoreModel->setStatusId($this->statusMapping[$oldStatusId]);
                    $this->statusStoreResource->save($statusStoreModel);
                    continue;
                }

                /** @var \Amasty\Rma\Model\Status\Status $statusModel */
                $statusModel = $this->statusRepository->getEmptyStatusModel();
                $statusModel->setTitle($this->dataObject->getDataByKey(StatusInterface::TITLE));
                $statusModel->setIsEnabled($this->dataObject->getDataByKey(StatusInterface::IS_ENABLED));

                if ($initialStatus) {
                    $statusModel->setIsEnabled(true)
                        ->setIsInitial(true);
                    $initialStatus = false;
                }

                $newStatusId = $this->statusRepository->save($statusModel)->getStatusId();
                $oldStatusId = $this->dataObject->getDataByKey(StatusInterface::STATUS_ID);
                $this->statusMapping[$oldStatusId] = $newStatusId;
            }
        } catch (CouldNotSaveException $e) {
            // do nothing
            null;
        }
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    private function moveRequests(ModuleDataSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $rmaRequestsSelect = $connection->select()->from(
            ['request_table' => $setup->getTable('amasty_amrma_request')],
            [
                'old_id' => 'id',
                'modified_at' => 'updated_at',
                'status' => 'status_id',
                'order_id' => 'order_id',
                'store_id' => 'store_id',
                'created_at' => 'created_at',
                'customer_id' => 'customer_id',
                'field_1' => 'field_1',
                'field_2' => 'field_2',
                'field_3' => 'field_3',
                'field_4' => 'field_4',
                'field_5' => 'field_5'
            ]
        );

        $rmaRequests = $connection->fetchAll($rmaRequestsSelect);

        try {
            foreach ($rmaRequests as $requestData) {
                $this->dataObject->unsetData()->addData($requestData);
                /** @var \Amasty\Rma\Model\Request\Request $requestModel */
                $requestModel = $this->requestRepository->getEmptyRequestModel();
                $requestModel->addData($this->dataObject->getData());
                $requestModel->setStatus(
                    $this->statusMapping[$this->dataObject->getDataByKey(RequestInterface::STATUS)]
                );

                if ($customerId = $this->dataObject->getData('customer_id')) {
                    $customerName = $this->customerRepository->getById($customerId)->getFirstname();
                    $requestModel->setCustomerName($customerName);
                }

                $customFieldsData = [];

                for ($i = 1; $i <= 5; $i++) {
                    if ($this->dataObject->getData('field_' . $i)) {
                        $customFieldsData[] = $this->customFieldFactory->create([
                            'key' => 'field_' . $i,
                            'value' => $this->dataObject->getData('field_' . $i)
                        ]);
                    }
                }

                if ($customFieldsData) {
                    $requestModel->setCustomFields($customFieldsData);
                }

                $oldRequestId = $this->dataObject->getDataByKey('old_id');
                $requestModel->setRequestItems($this->getRequestItems($setup, $oldRequestId));
                $newRequestId = $this->requestRepository->save($requestModel)->getRequestId();
                $this->requestMapping[$oldRequestId] = $newRequestId;
            }
        } catch (CouldNotSaveException $e) {
            // do nothing
            null;
        }
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param int $requestId
     *
     * @return RequestItemInterface[]
     */
    private function getRequestItems(ModuleDataSetupInterface $setup, $requestId)
    {
        $returnItems = [];
        $connection = $setup->getConnection();
        $rmaRequestItemsSelect = $connection->select()->from(
            ['request_items' => $setup->getTable('amasty_amrma_item')]
        )->where('request_id = ?', $requestId);
        $rmaRequestItems = $connection->fetchAll($rmaRequestItemsSelect);

        try {
            foreach ($rmaRequestItems as $item) {
                $this->dataObject->unsetData()->addData($item);

                if (isset($this->resolutionMapping[$this->dataObject->getDataByKey('resolution')])) {
                    $resolutionId = $this->resolutionMapping[$this->dataObject->getDataByKey('resolution')];
                } else {
                    if (!$this->notExistResolutionId) {
                        $resolutionModel = $this->resolutionRepository->getEmptyResolutionModel()
                            ->addData($this->dataForTempEntity);
                        $this->notExistResolutionId = $this->resolutionRepository->save($resolutionModel)
                            ->getResolutionId();
                    }

                    $resolutionId = $this->notExistResolutionId;
                }

                if (isset($this->reasonMapping[$this->dataObject->getDataByKey('reason')])) {
                    $reasonId = $this->reasonMapping[$this->dataObject->getDataByKey('reason')];
                } else {
                    if (!$this->notExistReasonId) {
                        $reasonModel = $this->reasonRepository->getEmptyReasonModel()
                            ->addData($this->dataForTempEntity);
                        $this->notExistReasonId = $this->reasonRepository->save($reasonModel)->getReasonId();
                    }

                    $reasonId = $this->notExistReasonId;
                }

                if (isset($this->conditionMapping[$this->dataObject->getDataByKey('condition')])) {
                    $conditionId = $this->conditionMapping[$this->dataObject->getDataByKey('condition')];
                } else {
                    if (!$this->notExistConditionId) {
                        $conditionModel = $this->conditionRepository->getEmptyConditionModel()
                            ->addData($this->dataForTempEntity);
                        $this->notExistConditionId = $conditionId = $this->conditionRepository->save($conditionModel)
                            ->getConditionId();
                    }

                    $conditionId = $this->notExistConditionId;
                }

                $returnItems[] = $this->requestRepository->getEmptyRequestItemModel()
                    ->addData($this->dataObject->getData())
                    ->setResolutionId($resolutionId)
                    ->setReasonId($reasonId)
                    ->setConditionId($conditionId)
                    ->setItemStatus(0)
                    ->setRequestQty($this->dataObject->getData('qty'));
            }
        } catch (CouldNotSaveException $e) {
            // do nothing
            null;
        }

        return $returnItems;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    private function moveComments(ModuleDataSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $rmaRequestsCommentsSelect = $connection->select()->from(
            ['requests_comments' => $setup->getTable('amasty_amrma_comment')],
            [
                'is_manager' => 'is_admin',
                'message' => 'value',
                'created_at' => 'created_at',
                'request_id' => 'request_id',
                'old_id' => 'id'
            ]
        );
        $rmaRequestsComments = $connection->fetchAll($rmaRequestsCommentsSelect);

        try {
            foreach ($rmaRequestsComments as $item) {
                $this->dataObject->unsetData()->addData($item);
                if (!isset($this->requestMapping[$this->dataObject->getDataByKey('request_id')])) {
                    continue;
                }
                /** @var \Amasty\Rma\Model\Chat\Message $messageModel */
                $messageModel = $this->chatRepository->getEmptyMessageModel();
                $messageModel->addData($this->dataObject->getData());
                $messageModel->setRequestId($this->requestMapping[$this->dataObject->getDataByKey('request_id')]);
                $this->chatRepository->save($messageModel);
                $oldMessageId = (int)$this->dataObject->getDataByKey('old_id');
                $messageModel->setMessageFiles($this->getMessageFiles($setup, $oldMessageId));
                $this->chatRepository->save($messageModel);
            }
        } catch (CouldNotSaveException $e) {
            // do nothing
            null;
        }
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param int $messageId
     *
     * @return MessageFileInterface[]
     */
    private function getMessageFiles(ModuleDataSetupInterface $setup, $messageId)
    {
        $files = [];
        $connection = $setup->getConnection();
        $rmaCommentFilesSelect = $connection->select()->from(
            ['requests_comments' => $setup->getTable('amasty_amrma_comment_file')],
            [
                'message_id' => 'comment_id',
                'filepath' => 'file',
                'filename' => 'name'
            ]
        )->where('comment_id = ?', $messageId);
        $rmaCommentFiles = $connection->fetchAll($rmaCommentFilesSelect);

        foreach ($rmaCommentFiles as $file) {
            $this->dataObject->unsetData()->addData($file);
            //phpcs:ignore
            $fileName = pathinfo($this->dataObject->getDataByKey('filepath'), PATHINFO_BASENAME);
            $filePath = $this->dataObject->getDataByKey('filepath');

            if ($fileHash = $this->fileUpload->moveFileToTmp($filePath)) {
                $files[] = $this->chatRepository->getEmptyMessageFileModel()
                    ->setFilepath($fileHash)
                    ->setFilename($fileName);
            }
        }

        return $files;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    private function moveExtraFields($setup)
    {
        $connection = $setup->getConnection();
        $extraFieldsSelect = $connection->select()->from(
            ['extra_fields' => $setup->getTable('core_config_data')]
        )->where('path LIKE "%amrma/extra/field_%"');
        $extraFields = $connection->fetchAll($extraFieldsSelect);
        $preparedExtraFields = $this->prepareExtraFields($extraFields);

        if ($preparedExtraFields) {
            foreach ($preparedExtraFields as $scope => $scopeValue) {
                foreach ($scopeValue as $storeId => $value) {
                    $customFields = $this->serializer->serialize($value);
                    $this->configInterface->saveConfig(
                        'amrma/' . ConfigProvider::CUSTOM_FIELDS,
                        $customFields,
                        $scope,
                        $storeId
                    );
                }
            }
        }
    }

    /**
     * @param array $extraFields
     *
     * @return array $preparedExtraFields
     */
    private function prepareExtraFields($extraFields)
    {
        $preparedFields = [];

        foreach ($extraFields as $field) {
            $this->dataObject->unsetData()->addData($field);
            $oldValue = $this->dataObject->getDataByKey('value');
            $oldPathParts = explode('/', $this->dataObject->getDataByKey('path'));
            $newFieldCode = array_pop($oldPathParts);

            if (!empty($oldValue)) {
                $key = '_' . time() . '_' . rand(100, 999); // key for dynamic row
                $scope = $this->dataObject->getDataByKey('scope');
                $scopeId = $this->dataObject->getDataByKey('scope_id');
                $preparedFields[$scope][$scopeId][$key] = [
                    'code' => $newFieldCode,
                    'label' => $oldValue
                ];
            }
        }

        return $preparedFields;
    }

    /**
     * @return array
     */
    private function getDefaultResolutions()
    {
        return [
            1 => [
                ResolutionInterface::TITLE => 'Exchange',
                ResolutionInterface::STATUS => Status::ENABLED,
                ResolutionInterface::LABEL => 'Exchange',
                ResolutionInterface::POSITION => 1
            ],
            2 => [
                ResolutionInterface::TITLE => 'Return',
                ResolutionInterface::STATUS => Status::ENABLED,
                ResolutionInterface::LABEL => 'Return',
                ResolutionInterface::POSITION => 2
            ],
            3 => [
                ResolutionInterface::TITLE => 'Repair',
                ResolutionInterface::STATUS => Status::ENABLED,
                ResolutionInterface::LABEL => 'Repair',
                ResolutionInterface::POSITION => 3
            ],
            4 => [
                ResolutionInterface::TITLE => 'Store Credit',
                ResolutionInterface::STATUS => Status::DISABLED,
                ResolutionInterface::LABEL => 'Return (Store Credit)',
                ResolutionInterface::POSITION => 4
            ]
        ];
    }

    /**
     * @return array
     */
    private function getDefaultReasons()
    {
        return [
            1 => [
                ReasonInterface::TITLE => 'Wrong Product Description',
                ReasonInterface::PAYER => ShippingPayer::STORE_OWNER,
                ReasonInterface::STATUS => Status::ENABLED,
                ReasonInterface::LABEL => 'Wrong Product Description',
                ReasonInterface::POSITION => 1
            ],
            2 => [
                ReasonInterface::TITLE => 'Wrong Product Delivered',
                ReasonInterface::PAYER => ShippingPayer::STORE_OWNER,
                ReasonInterface::STATUS => Status::ENABLED,
                ReasonInterface::LABEL => 'Wrong Product Delivered',
                ReasonInterface::POSITION => 2
            ],
            3 => [
                ReasonInterface::TITLE => 'Wrong Product Ordered',
                ReasonInterface::PAYER => ShippingPayer::CUSTOMER,
                ReasonInterface::STATUS => Status::ENABLED,
                ReasonInterface::LABEL => 'Wrong Product Ordered',
                ReasonInterface::POSITION => 3
            ],
            4 => [
                ReasonInterface::TITLE => 'Product Did Not Meet Customer\'s Expectations',
                ReasonInterface::PAYER => ShippingPayer::CUSTOMER,
                ReasonInterface::STATUS => Status::ENABLED,
                ReasonInterface::LABEL => 'Product Did Not Meet My Expectations',
                ReasonInterface::POSITION => 4
            ],
            5 => [
                ReasonInterface::TITLE => 'No Longer Needed/Wanted',
                ReasonInterface::PAYER => ShippingPayer::CUSTOMER,
                ReasonInterface::STATUS => Status::ENABLED,
                ReasonInterface::LABEL => 'No Longer Needed/Wanted',
                ReasonInterface::POSITION => 5
            ],
            6 => [
                ReasonInterface::TITLE => 'Defective/Does not Work Properly',
                ReasonInterface::PAYER => ShippingPayer::STORE_OWNER,
                ReasonInterface::STATUS => Status::ENABLED,
                ReasonInterface::LABEL => 'Defective/Does not Work Properly',
                ReasonInterface::POSITION => 6
            ],
            7 => [
                ReasonInterface::TITLE => 'Damaged During Shipping',
                ReasonInterface::PAYER => ShippingPayer::STORE_OWNER,
                ReasonInterface::STATUS => Status::ENABLED,
                ReasonInterface::LABEL => 'Damaged During Shipping',
                ReasonInterface::POSITION => 7
            ],
            8 => [
                ReasonInterface::TITLE => 'Late Delivery of Items',
                ReasonInterface::PAYER => ShippingPayer::STORE_OWNER,
                ReasonInterface::STATUS => Status::ENABLED,
                ReasonInterface::LABEL => 'Late Delivery of Items',
                ReasonInterface::POSITION => 8
            ]
        ];
    }

    /**
     * @return array
     */
    private function getDefaultItemConditions()
    {
        return [
            1 => [
                ConditionInterface::TITLE => 'Unopened',
                ConditionInterface::STATUS => Status::ENABLED,
                ConditionInterface::LABEL => 'Unopened',
                ConditionInterface::POSITION => 1
            ],
            2 => [
                ConditionInterface::TITLE => 'Opened',
                ConditionInterface::STATUS => Status::ENABLED,
                ConditionInterface::LABEL => 'Opened',
                ConditionInterface::POSITION => 2
            ],
            3 => [
                ConditionInterface::TITLE => 'Damaged',
                ConditionInterface::STATUS => Status::ENABLED,
                ConditionInterface::LABEL => 'Damaged',
                ConditionInterface::POSITION => 3
            ]
        ];
    }

    /**
     * @return array
     */
    private function getDefaultStatuses()
    {
        return [
            1 => [
                StatusInterface::TITLE => 'New Request',
                StatusInterface::IS_INITIAL => 1,
                StatusInterface::AUTO_EVENT => 0,
                StatusInterface::IS_ENABLED => 1,
                StatusInterface::PRIORITY => 10,
                StatusInterface::STATE => State::PENDING,
                StatusInterface::GRID => Grid::MANAGE,
                StatusInterface::COLOR => '#021a6f',
                StatusInterface::LABEL => 'New',
                StatusStoreInterface::DESCRIPTION =>
                    '<p>Your request has been created and is pending approval.
Store administrators will check it and inform you if the product(s) can be sent back.
In case any details are needed, we will contact you. Please wait for further instructions.</p>'
            ],
            2 => [
                StatusInterface::TITLE => 'Need Details',
                StatusInterface::IS_INITIAL => 0,
                StatusInterface::AUTO_EVENT => 0,
                StatusInterface::IS_ENABLED => 1,
                StatusInterface::PRIORITY => 20,
                StatusInterface::STATE => State::PENDING,
                StatusInterface::GRID => Grid::PENDING,
                StatusInterface::COLOR => '#3f51b5',
                StatusInterface::LABEL => 'Need Details',
                StatusStoreInterface::DESCRIPTION =>
                    '<p>The administrator needs some additional details for the approval.
Please, check the chat messages below.</p>
<p><img src="{{media url="wysiwyg/amasty/rma/need_details.png"}}" alt="rma_need_details" width="687" height="354">&nbsp;
</p>'
            ],
            3 => [
                StatusInterface::TITLE => 'Updated by Customer (Processing)',
                StatusInterface::IS_INITIAL => 0,
                StatusInterface::AUTO_EVENT => AutoEvents::CUSTOMER_ADDED_COMMENT,
                StatusInterface::STATE => State::PENDING,
                StatusInterface::IS_ENABLED => 1,
                StatusInterface::PRIORITY => 30,
                StatusInterface::GRID => Grid::MANAGE,
                StatusInterface::COLOR => '#3f51b5',
                StatusInterface::LABEL => 'Updated by Customer'
            ],
            4 => [
                StatusInterface::TITLE => 'Approved by Admin',
                StatusInterface::IS_INITIAL => 0,
                StatusInterface::AUTO_EVENT => 0,
                StatusInterface::IS_ENABLED => 1,
                StatusInterface::PRIORITY => 40,
                StatusInterface::STATE => State::AUTHORIZED,
                StatusInterface::GRID => Grid::PENDING,
                StatusInterface::COLOR => '#3f51b5',
                StatusInterface::LABEL => 'Approved',
                StatusStoreInterface::DESCRIPTION =>
                    '<p><strong>So your return request got approved. That’s a win! </strong><br>
<strong>What’s next? To return your purchase to domain.com, follow the step-by-step instructions below.</strong></p>
<p><img src="{{media url="wysiwyg/amasty/rma/approved_rma.jpg"}}" alt="rma_approved" width="905" height="170">&nbsp;
</p>
<p>1. Print out the shipping label and packing slip in just one click. See the buttons below:</p>
<p>{{widget type="Amasty\Rma\Block\Widget\PackingSlipButton" label="Print Packing Slip"}}
{{widget type="Amasty\Rma\Block\Widget\ShippingLabelButton" label="Download Shipping Label"}}</p>
<p>2. If possible, put your purchase(s) in the original package. We remind you that only returns
that are in good condition are refunded. Prior to sending your return, make sure you’ve removed
all additional labels from the package. Then put the packing slip you’ve printed into the package.</p>
<p>3. Now place the shipping label you’ve printed onto the package.</p>
<p>4. You should send the package with a pre-paid traceable method like Insured Parcel Post or UPS.
NOTE:  Some charges are non-refundable.
We don’t return Shipping and Handling costs, gift box costs and other charges.</p>'
            ],
            5 => [
                StatusInterface::TITLE => 'Updated by Customer (Approved)',
                StatusInterface::IS_INITIAL => 0,
                StatusInterface::AUTO_EVENT => AutoEvents::CUSTOMER_ADDED_COMMENT,
                StatusInterface::STATE => State::AUTHORIZED,
                StatusInterface::IS_ENABLED => 1,
                StatusInterface::PRIORITY => 50,
                StatusInterface::GRID => Grid::MANAGE,
                StatusInterface::COLOR => '#3f51b5',
                StatusInterface::LABEL => 'Updated by Customer'
            ],
            6 => [
                StatusInterface::TITLE => 'Shipped by Customer',
                StatusInterface::IS_INITIAL => 0,
                StatusInterface::AUTO_EVENT => AutoEvents::CUSTOMER_ADDED_TRACKING_NUMBER,
                StatusInterface::STATE => State::AUTHORIZED,
                StatusInterface::IS_ENABLED => 1,
                StatusInterface::PRIORITY => 60,
                StatusInterface::GRID => Grid::MANAGE,
                StatusInterface::COLOR => '#3F51B5',
                StatusInterface::LABEL => 'Shipped'
            ],
            7 => [
                StatusInterface::TITLE => 'Received by Admin',
                StatusInterface::IS_INITIAL => 0,
                StatusInterface::AUTO_EVENT => 0,
                StatusInterface::STATE => State::RECEIVED,
                StatusInterface::IS_ENABLED => 1,
                StatusInterface::PRIORITY => 70,
                StatusInterface::GRID => Grid::MANAGE,
                StatusInterface::COLOR => '#9C27B0',
                StatusInterface::LABEL => 'Received',
                StatusStoreInterface::DESCRIPTION =>
                    '<p>Your return shipment has been successfully delivered to our store.
Now we need some time to check it and then resolve your request.
The administrator will reply to you as soon as possible.</p>
<p><img src="{{media url="wysiwyg/amasty/rma/received_admin.png"}}" alt="received_admin" width="601" height="358">&nbsp;
</p>'
            ],
            8 => [
                StatusInterface::TITLE => 'Updated by Customer (Delivered)',
                StatusInterface::IS_INITIAL => 0,
                StatusInterface::AUTO_EVENT => AutoEvents::CUSTOMER_ADDED_COMMENT,
                StatusInterface::STATE => State::RECEIVED,
                StatusInterface::IS_ENABLED => 1,
                StatusInterface::PRIORITY => 80,
                StatusInterface::GRID => Grid::MANAGE,
                StatusInterface::COLOR => '#3f51b5',
                StatusInterface::LABEL => 'Updated by Customer'
            ],
            9 => [
                StatusInterface::TITLE => 'Resolved by Admin',
                StatusInterface::IS_INITIAL => 0,
                StatusInterface::AUTO_EVENT => 0,
                StatusInterface::STATE => State::RESOLVED,
                StatusInterface::IS_ENABLED => 1,
                StatusInterface::PRIORITY => 90,
                StatusInterface::GRID => Grid::ARCHIVED,
                StatusInterface::COLOR => '#197d19',
                StatusInterface::LABEL => 'Resolved',
                StatusStoreInterface::DESCRIPTION =>
                    '<p>Your return request is complete. Please rate our service so that we could improve it.</p>
<p><img src="{{media url="wysiwyg/amasty/rma/resolved_admin.png"}}" alt="resolved_admin" width="631" height="451">&nbsp;
</p>'
            ],
            10 => [
                StatusInterface::TITLE => 'Updated by Customer (Completed)',
                StatusInterface::IS_INITIAL => 0,
                StatusInterface::AUTO_EVENT => AutoEvents::CUSTOMER_ADDED_COMMENT,
                StatusInterface::IS_ENABLED => 1,
                StatusInterface::PRIORITY => 100,
                StatusInterface::STATE => State::RESOLVED,
                StatusInterface::GRID => Grid::MANAGE,
                StatusInterface::COLOR => '#3f51b5',
                StatusInterface::LABEL => 'Updated by Customer'
            ],
            11 => [
                StatusInterface::TITLE => 'Resolved and Rated',
                StatusInterface::IS_INITIAL => 0,
                StatusInterface::AUTO_EVENT => AutoEvents::CUSTOMER_RATED_RMA,
                StatusInterface::IS_ENABLED => 1,
                StatusInterface::PRIORITY => 110,
                StatusInterface::STATE => State::RESOLVED,
                StatusInterface::GRID => Grid::ARCHIVED,
                StatusInterface::COLOR => '#197d19',
                StatusInterface::LABEL => 'Resolved and Rated',
                StatusStoreInterface::DESCRIPTION =>
                    '<p>Dear customer, thank you so much for your feedback!</p>
<p>We will use it to provide you with better service next time.</p>
<p><img src="{{media url="wysiwyg/amasty/rma/resolved_rated.png"}}" alt="resolved_rated" width="507" height="351">&nbsp;
</p>'
            ],
            12 => [
                StatusInterface::TITLE => 'Canceled by Customer',
                StatusInterface::IS_INITIAL => 0,
                StatusInterface::AUTO_EVENT => 3,
                StatusInterface::IS_ENABLED => 1,
                StatusInterface::PRIORITY => 120,
                StatusInterface::STATE => State::CANCELED,
                StatusInterface::GRID => Grid::ARCHIVED,
                StatusInterface::COLOR => '#6e6e6e',
                StatusInterface::LABEL => 'Canceled'
            ],
            13 => [
                StatusInterface::TITLE => 'Rejected by Admin',
                StatusInterface::IS_INITIAL => 0,
                StatusInterface::AUTO_EVENT => 0,
                StatusInterface::IS_ENABLED => 1,
                StatusInterface::PRIORITY => 130,
                StatusInterface::STATE => State::CANCELED,
                StatusInterface::GRID => Grid::ARCHIVED,
                StatusInterface::COLOR => '#c33c3c',
                StatusInterface::LABEL => 'Rejected',
                StatusStoreInterface::DESCRIPTION =>
                    '<p>Sorry, seems that the request doesn\'t meet the requirements of our Return Policy
and has been rejected.</p>
<p>Please, check the chat for details.</p>
<p><img src="{{media url="wysiwyg/amasty/rma/reject_admin.png"}}" alt="reject_admin" width="544" height="338">&nbsp;
</p>'
            ],
        ];
    }
}
