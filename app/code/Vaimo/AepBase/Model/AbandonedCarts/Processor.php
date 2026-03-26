<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepBase\Model\AbandonedCarts;

use DateTime;
use Magento\Framework\Exception\LocalizedException;
use Vaimo\AepBase\Api\ConfigInterface;
use Vaimo\AepBase\Model\ResourceModel\Quote as ResourceModel;

class Processor
{
    private const MAX_CHARACTER_LENGTH = 255; // limited to varchar(255) column length
    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    private ResourceModel $resourceModel;

    public function __construct(
        ResourceModel $resourceModel
    ) {
        $this->resourceModel = $resourceModel;
    }

    /**
     * Returns updated customers ids for pickup by anything that would like to know about it
     * @return int[]
     * @throws LocalizedException
     */
    public function process(DateTime $dateFrom, DateTime $dateTo): array
    {
        $rawQuotesData = $this->resourceModel->getAbandonedQuotesData(
            $dateFrom->format(self::DATE_TIME_FORMAT),
            $dateTo->format(self::DATE_TIME_FORMAT),
        );

        $quoteData = $this->prepareQuoteData($rawQuotesData);
        $customersIds = \array_keys($quoteData);

        $currentAbandonedCartData = $this->resourceModel->getAbandonedCartSkus($customersIds);
        $newAbandonedCartData = $this->getNewAbandonedCartData($quoteData);

        $dataForUpdate = $this->getAbandonedCartDataForUpdate($currentAbandonedCartData, $newAbandonedCartData);

        if (empty($dataForUpdate)) {
            return [];
        }

        $this->resourceModel->updateAbandonedCartSkus($dataForUpdate);

        return \array_keys($dataForUpdate); // customers ids
    }

    /**
     * @param string[][] $currentAbandonedCartData
     * @param string[] $newAbandonedCartData
     * @return string[][]
     */
    private function getAbandonedCartDataForUpdate(array $currentAbandonedCartData, array $newAbandonedCartData): array
    {
        $result = [];

        foreach ($newAbandonedCartData as $customerId => $skus) {
            $currentSkus = $currentAbandonedCartData[$customerId]['skus'] ?? '';

            if ($currentSkus === $skus) {
                continue;
            }

            $result[$customerId] = $skus;
        }

        return $result;
    }

    /**
     * @param string[][] $quotesData
     * @return string[]
     */
    private function getNewAbandonedCartData(array $quotesData): array
    {
        $result = [];

        foreach ($quotesData as $customerId => $quoteData) {
            $skus = $quoteData['items'] ?? [];
            $result[$customerId] = \implode(ConfigInterface::SKU_DELIMITER, $this->getMostRecentSkus($skus));
        }

        return $result;
    }

    /**
     * @param string[] $skuList
     * @return string[]
     */
    private function getMostRecentSkus(array $skuList): array
    {
        $result = [];
        $totalCharacters = 0;

        foreach ($skuList as $sku) {
            $totalCharacters += \strlen($sku);

            if ($totalCharacters > self::MAX_CHARACTER_LENGTH) {
                break;
            }

            $totalCharacters++; // adding delimiter
            $result[] = $sku;
        }

        return $result;
    }

    /**
     * @param string[][] $rawQuotesData
     * @return string[][]
     */
    private function prepareQuoteData(array $rawQuotesData): array
    {
        $result = [];

        foreach ($rawQuotesData as $rawQuoteData) {
            $customerId = $rawQuoteData['customer_id'];
            $quoteId = $rawQuoteData['quote_id'];

            if (
                isset($result[$customerId]['quote_id'])
                && $result[$customerId]['quote_id'] !== $quoteId
            ) {
                continue; // only one quote per client allowed
            }

            $result[$customerId]['quote_id'] = $quoteId;
            $result[$customerId]['items'][] = \sprintf(
                '%s%s%s',
                $rawQuoteData['sku'],
                ConfigInterface::STORE_CODE_DELIMITER,
                $rawQuoteData['store_code']
            );
        }

        return $result;
    }
}
