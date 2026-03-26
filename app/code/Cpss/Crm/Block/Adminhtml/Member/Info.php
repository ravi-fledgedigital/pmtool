<?php

namespace Cpss\Crm\Block\Adminhtml\Member;

class Info extends \Magento\Backend\Block\Template
{
    protected $cpssApiRequest;
    protected $helperData;
    protected $jobCodes = "";
    protected $collectionFactory;

    /**
     * @var \Cpss\Pos\Helper\Data
     */
    private $posHelper;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serialize;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Cpss\Crm\Model\CpssApiRequest $cpssApiRequest,
        \Cpss\Crm\Helper\Data $helperData,
        \Cpss\Pos\Helper\Data $posHelper,
        \Magento\Framework\Serialize\SerializerInterface $serialize,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        private \Magento\Customer\Model\CustomerFactory $customerFactory,
        array $data = []
    ) {
        $this->cpssApiRequest = $cpssApiRequest;
        $this->helperData = $helperData;
        $this->posHelper = $posHelper;
        $this->serialize = $serialize;
        $this->collectionFactory = $collectionFactory;

        parent::__construct($context, $data);
    }

    public function getCurrentIdWithPrefix()
    {
        return $this->helperData->getCpssMembeIdPrefix() . $this->_request->getParam('id');
    }

    public function getWebsiteIdByCustomer()
    {
        $customerId = $this->getCurrentIdWithPrefix();
        $customer = $this->customerFactory->create()->load($customerId);

        return $customer->getWebsiteId();
    }

    public function getInfo()
    {
        $id = $this->getCurrentIdWithPrefix();
        $websiteId = $this->getWebsiteIdByCustomer();
        // $id .= $this->helperData->getCountryCode();
        $result = $this->cpssApiRequest->getMemberStatus($id, "point", 0, '', $websiteId);
        if (isset($result['X-CPSS-Result']) && $result['X-CPSS-Result'] == '000-000-000') {
            return json_decode($result['Body'][0][0], true);
        }

        return [];
    }

    public function getPointHistory($ptypes = "point", $lines = 50, $page = 1)
    {
        $id = $this->getCurrentIdWithPrefix();
        $websiteId = $this->getWebsiteIdByCustomer();
        // $id .= $this->helperData->getCountryCode();
        $result = $this->cpssApiRequest->getPointHistory($id, $ptypes, $lines, $page, "TMP", '', $websiteId);

        if (isset($result['X-CPSS-Result']) && $result['X-CPSS-Result'] == '000-000-000') {
            return json_decode($result['Body'][0][0], true);
        }

        return [];
    }

    public function getHistoryPageTotal($ptypes = "point", $lines = 50, $filters = "TMP")
    {
        $id = $this->getCurrentIdWithPrefix();
        $websiteId = $this->getWebsiteIdByCustomer();
        $result = $this->cpssApiRequest->getHistoryPageTotal($id, $ptypes, $lines, $filters, '', $websiteId);
        if (isset($result['X-CPSS-Result']) && $result['X-CPSS-Result'] == '000-000-000') {
            return json_decode($result['Body'][0][0], true);
        }

        return [];
    }

    /**
     * Call CPSS API get_nearest_expires
     *
     * @return array
     */
    public function getNearestExpires()
    {
        $websiteId = $this->getWebsiteIdByCustomer();

        $result = $this->cpssApiRequest->getNearestExpires($this->getCurrentIdWithPrefix(), '', $websiteId);
        if (isset($result['X-CPSS-Result']) && $result['X-CPSS-Result'] == '000-000-000') {
            return json_decode($result['Body'][0][0], true);
        }

        return [];
    }

    public function formatDateFromCpss($unixDate)
    {
        if (strlen($unixDate) >= 13) {
            $unixDate = substr($unixDate, 0, 10);
            $date = date("Y-m-d H:i:s",$unixDate);
            // return $this->posHelper->convertTimezone($date, "UTC", "Y/m/d");
            return $this->posHelper->convertTimezone($date, "UTC", "d/m/Y");
        }
        return null;
    }

    public function getPointHistoryByPagination()
    {
        return $this->getLayout()->createBlock(
            \Cpss\Crm\Block\Adminhtml\Member\Grid::class
        )->toHtml();
    }

    public function getJobCodeOptions()
    {
        $options = [];
        if ($this->jobCodes == "") {
            $jobCodes = $this->helperData->getConfigValue("crm/job_code_configuration/scodes");
            if ($jobCodes) {
                $unserializedJobCodes = $this->serialize->unserialize($jobCodes);
                foreach (array_values($unserializedJobCodes) as $k => $v) {
                    $options[$v["scode"]] = $v["scode_explaination"];
                }
                $this->jobCodes = $options;
            }
        }

        return $this->jobCodes;
    }
}
