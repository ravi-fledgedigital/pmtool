<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerFinance\Model\Import\Eav\Customer;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\CustomerFinance\Helper\Data;
use Magento\CustomerImportExport\Model\ResourceModel\Import\Customer\StorageFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\StringUtils;
use Magento\ImportExport\Model\Export\Factory;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\CustomerFinance\Model\ResourceModel\Customer\Attribute\Finance\Collection as FinanceCollection;
use Magento\ImportExport\Model\ImportFactory;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\User\Api\Data\UserInterfaceFactory;
use Magento\User\Model\ResourceModel\User;

/**
 * Import customer finance entity model
 *
 * @method array getData() getData()
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Finance extends \Magento\CustomerImportExport\Model\Import\AbstractCustomer
{
    /**
     * The customer finance attribute collection class name
     */
    public const ATTRIBUTE_COLLECTION_NAME = FinanceCollection::class;

    /**#@+
     * Permanent column names
     *
     * Names that begins with underscore is not an attribute. This name convention is for
     * to avoid interference with same attribute name.
     */
    public const COLUMN_EMAIL = '_email';

    public const COLUMN_WEBSITE = '_website';

    public const COLUMN_FINANCE_WEBSITE = '_finance_website';

    /**#@-*/

    /**#@+
     * Error codes
     */
    private const ERROR_FINANCE_WEBSITE_IS_EMPTY = 'financeWebsiteIsEmpty';

    private const ERROR_INVALID_FINANCE_WEBSITE = 'invalidFinanceWebsite';

    private const ERROR_DUPLICATE_PK = 'duplicateEmailSiteFinanceSite';

    /**#@-*/

    /**
     * Permanent entity columns
     *
     * @var string[]
     */
    protected $_permanentAttributes = [self::COLUMN_WEBSITE, self::COLUMN_EMAIL, self::COLUMN_FINANCE_WEBSITE];

    /**
     * Column names that holds values with particular meaning
     *
     * @var string[]
     */
    protected $_specialAttributes = [
        self::COLUMN_ACTION,
        self::COLUMN_WEBSITE,
        self::COLUMN_EMAIL,
        self::COLUMN_FINANCE_WEBSITE,
    ];

    /**
     * Valid column names for validation
     *
     * @var array
     */
    protected $validColumnNames = [
        FinanceCollection::COLUMN_CUSTOMER_BALANCE,
        FinanceCollection::COLUMN_REWARD_POINTS,
    ];

    /**
     * Comment for finance data import
     *
     * @var string
     */
    protected $_comment;

    /**
     * Address attributes collection
     *
     * @var \Magento\CustomerFinance\Model\ResourceModel\Customer\Attribute\Finance\Collection
     */
    protected $_attributeCollection;

    /**
     * Helper to check whether modules are enabled/disabled
     *
     * @var Data
     */
    protected $_customerFinanceData;

    /**
     * Admin user object
     *
     * @var \Magento\User\Model\User
     */
    protected $_adminUser;

    /**
     * Store imported row primary keys
     *
     * @var array
     */
    protected $_importedRowPks = [];

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var BalanceFactory
     */
    protected $_balanceFactory;

    /**
     * @var RewardFactory
     */
    protected $_rewardFactory;

    /**
     * @var UserContextInterface
     */
    private UserContextInterface $userContext;

    /**
     * @var UserInterfaceFactory
     */
    private UserInterfaceFactory $userFactory;

    /**
     * @var User
     */
    private User $userResource;

    /**
     * @param StringUtils $string
     * @param ScopeConfigInterface $scopeConfig
     * @param ImportFactory $importFactory
     * @param Helper $resourceHelper
     * @param ResourceConnection $resource
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param StoreManagerInterface $storeManager
     * @param Factory $collectionFactory
     * @param Config $eavConfig
     * @param StorageFactory $storageFactory
     * @param Session $authSession
     * @param Data $customerFinanceData
     * @param CustomerFactory $customerFactory
     * @param BalanceFactory $balanceFactory
     * @param RewardFactory $rewardFactory
     * @param array $data
     * @param UserContextInterface|null $userContext
     * @param UserInterfaceFactory|null $userFactory
     * @param User|null $userResource
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        StringUtils                        $string,
        ScopeConfigInterface               $scopeConfig,
        ImportFactory                      $importFactory,
        Helper                             $resourceHelper,
        ResourceConnection                 $resource,
        ProcessingErrorAggregatorInterface $errorAggregator,
        StoreManagerInterface              $storeManager,
        Factory                            $collectionFactory,
        Config                             $eavConfig,
        StorageFactory                     $storageFactory,
        Session                            $authSession,
        Data                               $customerFinanceData,
        CustomerFactory                    $customerFactory,
        BalanceFactory                     $balanceFactory,
        RewardFactory                      $rewardFactory,
        array                              $data = [],
        UserContextInterface               $userContext = null,
        UserInterfaceFactory               $userFactory = null,
        User                               $userResource = null
    ) {
        // entity type id has no meaning for finance import
        $data['entity_type_id'] = -1;

        parent::__construct(
            $string,
            $scopeConfig,
            $importFactory,
            $resourceHelper,
            $resource,
            $errorAggregator,
            $storeManager,
            $collectionFactory,
            $eavConfig,
            $storageFactory,
            $data
        );

        $this->_rewardFactory = $rewardFactory;
        $this->_customerFactory = $customerFactory;
        $this->_balanceFactory = $balanceFactory;
        $this->_customerFinanceData = $customerFinanceData;
        $this->userContext = $userContext ?? ObjectManager::getInstance()->get(UserContextInterface::class);
        $this->userFactory = $userFactory ?? ObjectManager::getInstance()->get(UserInterfaceFactory::class);
        $this->userResource = $userResource ?? ObjectManager::getInstance()->get(User::class);

        $this->_adminUser = $data['admin_user'] ?? $authSession->getUser();

        $this->addMessageTemplate(
            self::ERROR_FINANCE_WEBSITE_IS_EMPTY,
            __('Please specify a finance information website.')
        );
        $this->addMessageTemplate(
            self::ERROR_INVALID_FINANCE_WEBSITE,
            __('Please specify a valid finance information website.')
        );
        $this->addMessageTemplate(
            self::ERROR_DUPLICATE_PK,
            __('A row with this email, website, and finance website combination already exists.')
        );
        $this->_initAttributes();
    }

    /**
     * Initialize entity attributes
     *
     * @return $this
     */
    protected function _initAttributes()
    {
        /** @var $attribute \Magento\Eav\Model\Attribute */
        foreach ($this->_attributeCollection as $attribute) {
            $this->_attributes[$attribute->getAttributeCode()] = [
                'id' => $attribute->getId(),
                'code' => $attribute->getAttributeCode(),
                'is_required' => $attribute->getIsRequired(),
                'type' => $attribute->getBackendType(),
            ];
        }
        return $this;
    }

    /**
     * Import data rows
     *
     * @return boolean
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    protected function _importData()
    {
        if (!$this->_customerFinanceData->isRewardPointsEnabled()
            && !$this->_customerFinanceData->isCustomerBalanceEnabled()
        ) {
            return false;
        }

        $customer = $this->_customerFactory->create();

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNumber => $rowData) {

                if (!$this->validateRow($rowData, $rowNumber)) {
                    continue;
                }

                $customerId = $this->_getCustomerId($rowData[self::COLUMN_EMAIL], $rowData[self::COLUMN_WEBSITE]);
                $websiteId = $this->_websiteCodeToId[$rowData[self::COLUMN_FINANCE_WEBSITE]];

                if ($customer->getId() != $customerId) {
                    $customer->reset();
                    $customer->load($customerId);
                }

                $behavior = $this->getBehavior($rowData);

                foreach ($this->_attributes as $attributeCode => $attributeParams) {
                    switch ($behavior) {
                        case Import::BEHAVIOR_DELETE:
                            $this->handleDeleteBehavior($attributeCode, $customer, $websiteId);
                            break;

                        case Import::BEHAVIOR_ADD_UPDATE:
                            if (isset($rowData[$attributeCode]) && strlen($rowData[$attributeCode])) {
                                $this->handleAddUpdateBehavior(
                                    $attributeCode,
                                    $customer,
                                    $websiteId,
                                    $rowData[$attributeCode]
                                );
                            }
                            break;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Delete reward points or customer balance for customer.
     *
     * @param string $attributeCode
     * @param Customer $customer
     * @param int $websiteId
     * @return void
     */
    private function handleDeleteBehavior(string $attributeCode, Customer $customer, int $websiteId): void
    {
        if ($attributeCode == FinanceCollection::COLUMN_REWARD_POINTS) {
            $this->_deleteRewardPoints($customer, $websiteId);
        } elseif ($attributeCode == FinanceCollection::COLUMN_CUSTOMER_BALANCE) {
            $this->_deleteCustomerBalance($customer, $websiteId);
        }
    }

    /**
     * Add or update reward points or customer balance for customer.
     *
     * @param string $attributeCode
     * @param Customer $customer
     * @param int $websiteId
     * @param float|int $value
     * @return void
     */
    private function handleAddUpdateBehavior(
        string $attributeCode,
        Customer $customer,
        int $websiteId,
        float|int $value
    ): void {
        if ($attributeCode == FinanceCollection::COLUMN_REWARD_POINTS) {
            $this->_updateRewardPointsForCustomer($customer, $websiteId, $value);
        } elseif ($attributeCode == FinanceCollection::COLUMN_CUSTOMER_BALANCE) {
            $this->_updateCustomerBalanceForCustomer($customer, $websiteId, $value);
        }
    }

    /**
     * Update reward points value for customerEtn
     *
     * @param Customer $customer
     * @param int $websiteId
     * @param int $value reward points value
     * @return Reward
     */
    protected function _updateRewardPointsForCustomer(Customer $customer, $websiteId, $value)
    {
        $rewardModel = $this->_rewardFactory->create();
        $rewardModel->setCustomer($customer)->setWebsiteId($websiteId)->loadByCustomer();

        return $this->_updateRewardValue($rewardModel, $value);
    }

    /**
     * Update reward points value for reward model
     *
     * @param Reward $rewardModel
     * @param int $value reward points value
     * @return Reward
     */
    protected function _updateRewardValue(Reward $rewardModel, $value)
    {
        $pointsDelta = $value - $rewardModel->getPointsBalance();
        if ($pointsDelta != 0) {
            $rewardModel->setPointsDelta(
                $pointsDelta
            )->setAction(
                Reward::REWARD_ACTION_ADMIN
            )->setComment(
                $this->_getComment()
            )->updateRewardPoints();
        }

        return $rewardModel;
    }

    /**
     * Update store credit balance for customer
     *
     * @param Customer $customer
     * @param int $websiteId
     * @param float $value store credit balance
     * @return Balance
     */
    protected function _updateCustomerBalanceForCustomer(
        Customer $customer,
        $websiteId,
        $value
    ) {
        $balanceModel = $this->_balanceFactory->create();
        $balanceModel->setCustomer($customer)->setWebsiteId($websiteId)->loadByCustomer();

        return $this->_updateCustomerBalanceValue($balanceModel, $value);
    }

    /**
     * Update balance for customer balance model
     *
     * @param Balance $balanceModel
     * @param float $value store credit balance
     * @return Balance
     */
    protected function _updateCustomerBalanceValue(Balance $balanceModel, $value)
    {
        $amountDelta = $value - $balanceModel->getAmount();
        if ($amountDelta != 0) {
            $balanceModel->setAmountDelta($amountDelta)->setComment($this->_getComment())->save();
        }

        return $balanceModel;
    }

    /**
     * Delete reward points value for customer (just set it to 0)
     *
     * @param Customer $customer
     * @param int $websiteId
     * @return void
     */
    protected function _deleteRewardPoints(Customer $customer, $websiteId)
    {
        $this->_updateRewardPointsForCustomer($customer, $websiteId, 0);
    }

    /**
     * Delete store credit balance for customer (just set it to 0)
     *
     * @param Customer $customer
     * @param int $websiteId
     * @return void
     */
    protected function _deleteCustomerBalance(Customer $customer, $websiteId)
    {
        $this->_updateCustomerBalanceForCustomer($customer, $websiteId, 0);
    }

    /**
     * Retrieve comment string
     *
     * @return string
     */
    protected function _getComment()
    {
        if (!$this->_comment) {
            if ($this->_adminUser === null) {
                $userId = $this->userContext->getUserId();
                if ($userId) {
                    $this->_adminUser = $this->userFactory->create();
                    $this->userResource->load($this->_adminUser, $userId);
                }
            }
            if ($this->_adminUser !== null) {
                $this->_comment = __('Data was imported by %1', $this->_adminUser->getUsername());
            }
        }

        return $this->_comment;
    }

    /**
     * Imported entity type code getter
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'customer_finance';
    }

    /**
     * Validate data row for add/update behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _validateRowForUpdate(array $rowData, $rowNumber)
    {
        if ($this->_checkUniqueKey($rowData, $rowNumber)) {
            if (empty($rowData[self::COLUMN_FINANCE_WEBSITE])) {
                $this->addRowError(self::ERROR_FINANCE_WEBSITE_IS_EMPTY, $rowNumber, self::COLUMN_FINANCE_WEBSITE);
            } else {
                $email = strtolower($rowData[self::COLUMN_EMAIL]);
                $website = $rowData[self::COLUMN_WEBSITE];
                $financeWebsite = $rowData[self::COLUMN_FINANCE_WEBSITE];
                $customerId = $this->_getCustomerId($email, $website);

                $defaultStoreId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
                if (!isset(
                    $this->_websiteCodeToId[$financeWebsite]
                ) || $this->_websiteCodeToId[$financeWebsite] == $defaultStoreId
                ) {
                    $this->addRowError(self::ERROR_INVALID_FINANCE_WEBSITE, $rowNumber, self::COLUMN_FINANCE_WEBSITE);
                } elseif ($customerId === false) {
                    $this->addRowError(self::ERROR_CUSTOMER_NOT_FOUND, $rowNumber);
                } elseif ($this->_checkRowDuplicate($customerId, $financeWebsite)) {
                    $this->addRowError(self::ERROR_DUPLICATE_PK, $rowNumber);
                } else {
                    // check simple attributes
                    foreach ($this->_attributes as $attributeCode => $attributeParams) {
                        if (in_array($attributeCode, $this->_ignoredAttributes)) {
                            continue;
                        }
                        if (isset($rowData[$attributeCode]) && strlen($rowData[$attributeCode])) {
                            $this->isAttributeValid($attributeCode, $attributeParams, $rowData, $rowNumber);
                        } elseif ($attributeParams['is_required']) {
                            $this->addRowError(self::ERROR_VALUE_IS_REQUIRED, $rowNumber, $attributeCode);
                        }
                    }
                }
            }
        }
    }

    /**
     * Validate data row for delete behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    protected function _validateRowForDelete(array $rowData, $rowNumber)
    {
        if ($this->_checkUniqueKey($rowData, $rowNumber)) {
            if (empty($rowData[self::COLUMN_FINANCE_WEBSITE])) {
                $this->addRowError(self::ERROR_FINANCE_WEBSITE_IS_EMPTY, $rowNumber, self::COLUMN_FINANCE_WEBSITE);
            } else {
                $email = strtolower($rowData[self::COLUMN_EMAIL]);
                $website = $rowData[self::COLUMN_WEBSITE];
                $financeWebsite = $rowData[self::COLUMN_FINANCE_WEBSITE];

                $defaultStoreId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
                if (!isset(
                    $this->_websiteCodeToId[$financeWebsite]
                ) || $this->_websiteCodeToId[$financeWebsite] == $defaultStoreId
                ) {
                    $this->addRowError(self::ERROR_INVALID_FINANCE_WEBSITE, $rowNumber, self::COLUMN_FINANCE_WEBSITE);
                } elseif (!$this->_getCustomerId($email, $website)) {
                    $this->addRowError(self::ERROR_CUSTOMER_NOT_FOUND, $rowNumber);
                }
            }
        }
    }

    /**
     * Check whether row with such email, website, finance website combination was already found in import file
     *
     * @param int $customerId
     * @param string $financeWebsite
     * @return bool
     */
    protected function _checkRowDuplicate($customerId, $financeWebsite)
    {
        $financeWebsiteId = $this->_websiteCodeToId[$financeWebsite];
        if (!isset($this->_importedRowPks[$customerId][$financeWebsiteId])) {
            $this->_importedRowPks[$customerId][$financeWebsiteId] = true;
            return false;
        } else {
            return true;
        }
    }
}
