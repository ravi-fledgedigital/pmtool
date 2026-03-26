<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PageBuilderProductRecommendations\Ui\Component;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\ProductRecommendationsAdmin\Model\ServiceClientInterface;
use Magento\ServicesId\Model\ServicesConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Populate grid on second slide out
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    private const SEPARATOR = '_';

    /**
     * @var ServiceClientInterface
     */
    private $serviceClient;

    /**
     * @var ServicesConfigInterface
     */
    private $servicesConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param ServiceClientInterface $serviceClient
     * @param ServicesConfigInterface $servicesConfig
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        ServiceClientInterface $serviceClient,
        ServicesConfigInterface $servicesConfig,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
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
        $this->serviceClient = $serviceClient;
        $this->servicesConfig = $servicesConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Get Product Recommendation Units
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData()
    {
        $storeId = $this->request->getParam('store_id');
        if ($storeId === null) {
            $storeId = $this->storeManager->getDefaultStoreView()->getId();
        }
        $storeCode = $this->storeManager->getStore($storeId)->getCode();

        $uri = sprintf(
            '%s/%s/units/search?field=pageType&value=PageBuilder&sort=createdAt&order=desc',
            $this->servicesConfig->getEnvironmentId(),
            $storeCode
        );
        $url = $this->serviceClient->getUrl('v1', $uri);
        $result = $this->serviceClient->request('GET', $url);
        $pageSize = 0;
        $pageCurrent = 0;
        if (isset($this->request->getParam('paging')['pageSize'])) {
            $pageSize = (int)$this->request->getParam('paging')['pageSize'];
        }
        if (isset($this->request->getParam('paging')['current'])) {
            $pageCurrent = (int)$this->request->getParam('paging')['current'];
        }
        $pageOffset = ($pageCurrent - 1) * $pageSize;
        return $this->resultToOutput($result, $pageSize, $pageOffset);
    }

    /**
     * Convert result to fit the select grid component
     *
     * @param array $result
     * @param int $pageSize
     * @param int $pageOffset
     * @return array
     */
    public function resultToOutput($result, $pageSize, $pageOffset)
    {
        $output = [];
        $end = $pageOffset + $pageSize;
        $output['totalRecords'] = $result['total'] ?? 0;
        $output['items'] = $result['results'] ?? [];
        if ($pageSize) {
            $output['items'] = array_slice($output['items'], $pageOffset, $end - $pageOffset);
        }
        foreach ($output['items'] as &$value) {
            $value['unit_id'] = $value['unitId'];
            $value['unitId_unitName'] = $value['unitId'] . self::SEPARATOR . $value['unitName'];
            $value['createdAt'] = date("M d, Y g:i:s A", strtotime($value['createdAt']));
        }
        return $output;
    }

    public function setLimit($offset, $size)
    {
    }

    public function addOrder($field, $direction)
    {
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
    }
}
