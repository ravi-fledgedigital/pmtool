<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2022 Adobe
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

namespace Magento\GiftCardProductDataExporter\Model\Provider\Product;

use Exception;
use Magento\CatalogDataExporter\Model\Provider\Product\ProductOptions\ProductOptionProviderInterface;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\GiftCardProductDataExporter\Model\Query\OptionQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;

/**
 * Provider class for gift card product options
 */
class Options implements ProductOptionProviderInterface
{
    /**
     * Giftcard amount option key for configuration
     */
    private const GIFTCARD_AMOUNT_KEY = 'giftcard_amount';

    /**
     * @var OptionQuery
     */
    private $productOptionQuery;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var OptionsUid
     */
    private $optionsUid;

    /**
     * @var array
     */
    private $options;

    /**
     * @param OptionQuery $productOptionQuery
     * @param ResourceConnection $resourceConnection
     * @param OptionsUid $optionsUid
     * @param LoggerInterface $logger
     * @param array $options
     */
    public function __construct(
        OptionQuery $productOptionQuery,
        ResourceConnection $resourceConnection,
        OptionsUid $optionsUid,
        LoggerInterface $logger,
        array $options = []
    ) {
        $this->productOptionQuery = $productOptionQuery;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
        $this->optionsUid = $optionsUid;
        $this->options = $options;
    }

    /**
     * Format provider data
     *
     * @param array $row
     * @param array $output
     * @return array
     */
    private function format(array $row, array $output): array
    {
        $key = $this->getOptionKey($row);
        $rowOutput = $output[$key] ?? [
                'productId' => $row['productId'],
                'storeViewCode' => $row['storeViewCode'],
                'optionsV2' => [
                    'id' => $row['attribute_id'],
                    'type' => Giftcard::TYPE_GIFTCARD,
                    'label' => $this->options[self::GIFTCARD_AMOUNT_KEY]['label'],
                    'renderType' => $this->options[self::GIFTCARD_AMOUNT_KEY]['renderType']
                ],
            ];
        $rowOutput['optionsV2']['values'][] = [
            'id' => $this->optionsUid->getOptionValueUid($row['value']),
            'price' => $row['value'],
        ];

        return $rowOutput;
    }

    /**
     * Generate option key by concatenating productId, storeViewCode
     *
     * @param array $row
     * @return string
     */
    private function getOptionKey(array $row): string
    {
        return $row['productId'] . $row['storeViewCode'];
    }

    /**
     * Get gift card product option provider data
     *
     * @param array $values
     * @return array
     * @throws UnableRetrieveData
     */
    public function get(array $values): array
    {
        $queryArguments = [];
        $connection = $this->resourceConnection->getConnection();
        try {
            $output = [];
            foreach ($values as $value) {
                $queryArguments['productId'][$value['productId']] = $value['productId'];
                $queryArguments['storeViewCode'][$value['storeViewCode']] = $value['storeViewCode'];
            }
            $select = $this->productOptionQuery->getQuery($queryArguments);
            $cursor = $connection->query($select);
            while ($row = $cursor->fetch()) {
                $key = $this->getOptionKey($row);
                $output[$key] = $this->format($row, $output);
            }
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            throw new UnableRetrieveData('Unable to retrieve gift card data');
        }
        return $output;
    }
}
