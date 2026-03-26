<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Controller\Adminhtml\Statistics;

use Magento\Backend\App\Action\Context;

class Statistics extends \Magento\Backend\App\Action
{
    /**
     * @var \Aitoc\SendGrid\Model\ApiWork
     */
    private $apiWork;

    public function __construct(
        Context $context,
        \Aitoc\SendGrid\Model\ApiWork $apiWork
    ) {
        parent::__construct($context);
        $this->apiWork = $apiWork;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = ['dates' => [], 'metrics' => []];
        foreach ($this->getSingleSendData() as $key => $value) {
            $data['dates'][] = $value['date'];

            foreach ($value['stats'][0]['metrics'] as $label => $unit_value) {
                if (! isset($data['metrics'][$label])) {
                    $data['metrics'][$label] = [
                        'label' => ucwords(str_replace("_", " ", $label)),
                        'values' => []
                    ];
                }

                $data['metrics'][$label]['values'][] = $unit_value;
            }
        }

        return $this->getResponse()->setBody(json_encode($data));
    }

    /**
     * @return array
     */
    private function getSingleSendData()
    {
        $request = $this->getRequest();
        $singleSendValue = $request->getParam('singlesend');
        if ($singleSendValue == 'all') {
            $results = $this->apiWork->getStatistics($request->getParam('start'), $request->getParam('end'));
        } else {
            $start = date('Y-m-d', strtotime($singleSendValue . '- 2 day'));
            $end = date('Y-m-d', strtotime($singleSendValue . '+ 2 day'));
            $results = $this->apiWork->getStatistics($start, $end);
        }

        return $results;
    }
}
