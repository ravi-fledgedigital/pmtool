<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\AdminUiSdkCustomFees\Ui\Component\Listing\Column;

use Magento\AdminUiSdkCustomFees\Api\CustomFeesRepositoryInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Generic custom column to prepare data source for registered columns
 */
class CustomColumn extends Column
{
    private const ATTRIBUTE_CODE = 'attribute_code';
    private const DATA_TYPE = 'data_type';
    private const ENTITY_ID = 'entity_id';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomFeesRepositoryInterface $customFeesRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private ScopeConfigInterface $scopeConfig,
        private CustomFeesRepositoryInterface $customFeesRepository,
        private PriceCurrencyInterface $priceCurrency,
        protected $components = [],
        private array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        $namespace = $this->context->getNamespace();
        if (!$this->isNamespaceEligible($namespace)) {
            return $dataSource;
        }
        $config = $this->getData('config');
        $this->fetchColumnData($dataSource, $config, $namespace);
        return $dataSource;
    }

    /**
     * Check if namespace is eligible for custom fees
     *
     * @param string $namespace
     * @return bool
     */
    private function isNamespaceEligible(string $namespace): bool
    {
        return $this->isInvoiceGrid($namespace) || $this->isCreditMemoGrid($namespace);
    }

    /**
     * Check if namespace is invoice grid
     *
     * @param string $namespace
     * @return bool
     */
    private function isInvoiceGrid(string $namespace): bool
    {
        return $namespace === 'sales_order_view_invoice_grid' || $namespace === 'sales_order_invoice_grid';
    }

    /**
     * Check if namespace is credit memo grid
     *
     * @param string $namespace
     * @return bool
     */
    private function isCreditMemoGrid(string $namespace): bool
    {
        return $namespace === 'sales_order_view_creditmemo_grid' || $namespace === 'sales_order_creditmemo_grid';
    }

    /**
     * Fetch column data custom fees values
     *
     * @param array $dataSource
     * @param array $config
     * @param string $namespace
     * @return void
     */
    private function fetchColumnData(array &$dataSource, array $config, string $namespace): void
    {
        $isCreditMemo = $this->isCreditMemoGrid($namespace);
        foreach ($dataSource['data']['items'] as &$item) {
            $columnName = $config[self::ATTRIBUTE_CODE];
            $entityId = $item[self::ENTITY_ID];
            $customFees = $isCreditMemo
                ? $this->customFeesRepository->getByCreditMemoId($entityId)
                : $this->customFeesRepository->getByInvoiceId($entityId);
            foreach ($customFees as $customFee) {
                if ($customFee->getCustomFeeCode() === $columnName) {
                    $value = $isCreditMemo
                        ? $customFee->getCustomFeeAmountRefunded()
                        : $customFee->getCustomFeeAmountInvoiced();

                    $item[$this->getData('name')] =
                        $this->priceCurrency->format($value, false, 2);
                }
            }
        }
    }
}
