<?php

namespace Cpss\Crm\Block\Point;

use Magento\Framework\App\Config\Storage\WriterInterface;

class History extends \Magento\Framework\View\Element\Template
{
    protected $cpssApiRequest;
    protected $helperCustomer;
    protected $session;
    protected $helperData;
    protected $serialize;
    protected $jobCodes = "";
    protected $timezone;
    protected $posHelper;
    protected $collectionFactory;
    protected $http;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $session,
        \Cpss\Crm\Model\CpssApiRequest $cpssApiRequest,
        \Cpss\Crm\Helper\Customer $helperCustomer,
        \Cpss\Crm\Helper\Data $helperData,
        \Magento\Framework\Serialize\SerializerInterface $serialize,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Cpss\Pos\Helper\Data $posHelper,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        \Magento\Framework\App\Request\Http $http
    ) {
        $this->cpssApiRequest = $cpssApiRequest;
        $this->helperCustomer = $helperCustomer;
        $this->session = $session;
        $this->helperData = $helperData;
        $this->serialize = $serialize;
        $this->timezone = $timezone;
        $this->posHelper = $posHelper;
        $this->collectionFactory = $collectionFactory;
        $this->http = $http;

        parent::__construct($context);
    }

    /**
     * @param $date
     * @param $format
     * @return false|string
     */
    public function getFormatedDate($date, $format)
    {
        $targetSec = 0;
        if (strlen($date)) {
            $targetSec = strtotime($date);
        }

        return date($format, $targetSec);
    }

    public function getPointHistory($ptypes = "point", $lines = 50, $page = 1)
    {
        $result = $this->cpssApiRequest->getPointHistory($this->session->getMemberId(), $ptypes, $lines, $page);

        if (isset($result['X-CPSS-Result']) && $result['X-CPSS-Result'] == '000-000-000') {
            return json_decode($result['Body'][0][0], true);
        }

        return [];
    }

    public function getHistoryPageTotal($ptypes = "point", $lines = 50, $filters = "TMP")
    {
        $result = $this->cpssApiRequest->getHistoryPageTotal($this->session->getMemberId(), $ptypes, $lines, $filters);
        if (isset($result['X-CPSS-Result']) && $result['X-CPSS-Result'] == '000-000-000') {
            return json_decode($result['Body'][0][0], true);
        }

        return [
            'result' => [
                'pages' => 1
            ],
        ];
    }

    public function getPointHistoryCollection()
    {
        $page = $this->http->getParam("p") ? $this->http->getParam("p") : 1;
        $limit = $this->http->getParam("limit") ? $this->http->getParam("limit") : 10;

        $pages = $this->getHistoryPageTotal("point", $limit, "TMP")['result']['pages'];
        $pointHistory = $this->getPointHistory("point", $limit, $page);

        if (empty($pointHistory))
            return null;

        $result = $pointHistory['result'];
        $history = $result['history'];

        $collection = $this->collectionFactory->create();
        $collection->setLastPageNumber($pages);
        foreach ($history as $item) {
            $varienObject = new \Magento\Framework\DataObject();
            $varienObject->setData($item);
            $collection->addItem($varienObject);
        }

        $collection->setPageSize(10);
        $collection->setCurPage(1);
        return $collection;
    }

    public function getPointHistoryByPagination()
    {
        $pager = $this->getLayout()->createBlock(
            \Magento\Theme\Block\Html\Pager::class,
            'point.history.pager'
        )->setCollection(
            $this->getPointHistoryCollection()
        );

        $this->setChild('pager', $pager);
        return $this->getChildHtml('pager');
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

    public function formatDateFromCpss($unixDate)
    {

        if (strlen($unixDate) >= 13) {
            $unixDate = substr($unixDate, 0, 10);
            $date = date("Y-m-d H:i:s",$unixDate);
            // return $this->posHelper->convertTimezone($date, "UTC", "d/m/y");
            return $this->timezone->date(new \DateTime($date))->format('d/m/y h:i A');
        }
        return null;
    }
}
