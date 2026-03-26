<?php
//phpcs:ignoreFile
namespace Cpss\Crm\Block\Adminhtml\Receipt\View\Content;

class Info extends \Magento\Backend\Block\Template
{
    /**
     * @var \Cpss\Crm\Model\ShopReceipt
     */
    protected $shopReceipt;
    protected $session;
    protected $currency;
    protected $customer;
    protected $groupRepository;
    protected $realStore;
    protected $posHelperData;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Cpss\Crm\Model\ShopReceipt $shopReceipt
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Cpss\Crm\Model\ShopReceipt $shopReceipt,
        \Magento\Framework\Session\SessionManager $session,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Cpss\Crm\Model\RealStore $realStore,
        \Cpss\Pos\Helper\Data $posHelperData,
        array $data = []
    ) {
        $this->shopReceipt = $shopReceipt;
        $this->session = $session;
        $this->currency = $currency;
        $this->customer = $customer;
        $this->groupRepository = $groupRepository;
        $this->realStore = $realStore;
        $this->posHelperData = $posHelperData;
        parent::__construct($context, $data);
    }

    /**
     * @return object
     */
    public function getOrder()
    {
        $purchaseId = $this->getRequest()->getParam('purchase_id');
        return $this->shopReceipt->loadByPurchaseId($purchaseId);
    }

    /**
     * Get object created at date
     *
     * @param string $createdAt
     * @return \DateTime
     */
    public function getOrderAdminDate($createdAt)
    {
        return $this->_localeDate->date(new \DateTime($createdAt));
    }

    /**
     * @return array
     * @since 100.1.0
     */
    public function getColumns()
    {
        $columns = [
            'product' => __('Product'),
            'price' => __('Price (Excluding Tax)'),
            'ordered-qty' => __('Qty'),
            'subtotal' => __('Subtotal (Excluding Tax)'),
            'tax-amount' => __('Tax Amount'),
            'discont' => __('Discount Price'),
            'total' => __('Total Item Sales (Including Tax)')
        ];
        return $columns;
    }

    /**
     * Get currency symbol for current locale and currency code
     *
     * @return string
     */
    public function getCurrentCurrencySymbol()
    {
        return $this->currency->getCurrencySymbol();
    }

    /**
     * Get customer data
     *
     * @return object
     */
    public function getCustomerData($customerId)
    {
        return $this->customer->load($customerId);
    }

    /**
     * Get shop name by shop id
     *
     * @return object
     */
    public function getShopName($shopId)
    {
        return $this->realStore->loadById($shopId)->getShopName();
    }

    /**
     * Return text value of customer group
     * by customer id
     *
     * @return string
     */
    public function getCustomerGroupName($customer)
    {
        $customerGroupId = $customer->getGroupId();
        try {
            if ($customerGroupId !== null) {
                return $this->groupRepository->getById($customerGroupId)->getCode();
            }
        } catch (\Exception $e) {
            return '';
        }

        return '';
    }

    /**
     * Return array of additional account data
     * Value is option style array
     *
     * @return array
     */
    public function getCustomerAccountData($customerObject)
    {
        $customer = $customerObject->getData();

        $accountData = [];
        if(isset($customer['dob']) && !empty($customer['dob'])) {
            $data['label'] = __('Date of Birth');
            $data['value'] = $this->formatDate(
                $this->getOrderAdminDate($customer['dob']),
                \IntlDateFormatter::MEDIUM,
                false
            );
            $accountData[] = $data;
        }
        if(isset($customer['gender']) && !empty($customer['gender'])) {
            $data['label'] = __('Gender');
            $data['value'] = $customerObject->getAttribute('gender')
                ->getSource()
                ->getOptionText($customerObject->getData('gender'));
            $accountData[] = $data;
        }

        return $accountData;
    }

    public function formatDateFromCpss($date)
    {
        $date = date("Y-m-d H:i:s", strtotime($date));
        return $this->posHelperData->convertTimezone($date, "UTC", "Y/m/d H:i:s");
    }
}
