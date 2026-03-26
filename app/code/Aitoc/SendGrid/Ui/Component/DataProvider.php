<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Ui\Component;

use Aitoc\SendGrid\Model\ApiWork;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * @var ApiWork
     */
    private $apiWork;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Reporting $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param ApiWork $apiWork
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Reporting $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        \Aitoc\SendGrid\Model\ApiWork $apiWork,
        array $meta = [],
        array $data = []
    ) {
        $this->apiWork = $apiWork;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    /**
     * Get SingleSends Informtion form Sendgrid
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getData()
    {
        $singleSends = $this->apiWork->getSingleSends();
        $sendsResult = $singleSends['result'] ?? [];
        $data['totalRecords'] = count($sendsResult);
        foreach ($sendsResult as $attributeCode => $attributeData) {
            $data['items'][] = $attributeData;
        }
        if (!isset($data['items'])) {
            $data['items'][] = [];
        }
        return $data;
    }
}
