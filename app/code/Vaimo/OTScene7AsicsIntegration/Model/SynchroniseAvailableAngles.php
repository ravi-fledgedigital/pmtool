<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7AsicsIntegration\Model;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Vaimo\OTScene7AsicsIntegration\Model\Api\ImagesInfoProvider;

class SynchroniseAvailableAngles implements SynchroniseAvailableAnglesInterface
{
    private const API_PAGE_SIZE = 1000;
    private const NUMBER_OF_ATTEMPTS_IF_REQUEST_FAILED = 5;

    private ImagesInfoProvider $imagesInfoProvider;
    private ResourceConnection $resourceConnection;
    private UpdateAvailableAngles $updateAvailableAngles;
    private SerializerInterface $serializer;
    private EavConfig $eavConfig;
    private LoggerInterface $logger;
    /**
     * @var ProgressBarFactory
     */
    private $progressBarFactory;

    /**
     * @param ImagesInfoProvider $imagesInfoProvider
     * @param ResourceConnection $resourceConnection
     * @param UpdateAvailableAngles $updateAvailableAngles
     * @param SerializerInterface $serializer
     * @param EavConfig $eavConfig
     * @param LoggerInterface $logger
     * @param ProgressBarFactory $progressBarFactory
     */
    public function __construct(
        ImagesInfoProvider $imagesInfoProvider,
        ResourceConnection $resourceConnection,
        UpdateAvailableAngles $updateAvailableAngles,
        SerializerInterface $serializer,
        EavConfig $eavConfig,
        LoggerInterface $logger,
        ProgressBarFactory $progressBarFactory
    ) {
        $this->imagesInfoProvider = $imagesInfoProvider;
        $this->resourceConnection = $resourceConnection;
        $this->updateAvailableAngles = $updateAvailableAngles;
        $this->serializer = $serializer;
        $this->eavConfig = $eavConfig;
        $this->logger = $logger;
        $this->progressBarFactory = $progressBarFactory;
    }

    public function execute($lastTimeUpdate = "", string $item = "", $output = ""): void
    {
        $offset = 0;
        $totalCount = 1;
        $failedCount = 0;

        if ($output != "") {
            $response = $this->imagesInfoProvider->getImagesInfo($offset, $item, $lastTimeUpdate);
            $output->writeln('<info>Process starts.</info>');

            /** @var ProgressBar $progress */
            $progressBar = $this->progressBarFactory->create(
                [
                    'output' => $output,
                    'max' =>  ceil($response['total']/1000),
                ]
            );

            $progressBar->setFormat(
                '%current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s%'
            );

            $progressBar->start();
        }
        while ($offset < $totalCount) {
            try {
                $response = $this->imagesInfoProvider->getImagesInfo($offset, $item, $lastTimeUpdate);

                if (!empty($response['data'])) {
                    $this->updateAvailableAngles->execute($this->prepareData($response['data']));
                }
                $totalCount = $response['total'];
                $failedCount = 0;
                if ($output != "") {
                    $progressBar->advance();
                }
            } catch (\Throwable $e) {
                $this->logger->error($e->getMessage(), $e->getTrace());
                if (++$failedCount <= self::NUMBER_OF_ATTEMPTS_IF_REQUEST_FAILED) {
                    continue;
                }

                $this->logger->error(
                    'Getting product data from ASICS API failed more then ' .
                    self::NUMBER_OF_ATTEMPTS_IF_REQUEST_FAILED .
                    ' times'
                );

                break;
            }

            $offset += self::API_PAGE_SIZE;
        }
        if ($output != "") {
            $progressBar->finish();
            $output->write(PHP_EOL);
            $output->writeln('<info>Process finished.</info>');
        }
    }

    /**
     * Get images angles from API response, and prepare data for Magneto import
     *
     * @param mixed[] $data
     * @return string[][]
     */
    private function prepareData(array $data): array
    {
        $anglesData = [];
        foreach ($data as $product) {
            if (empty($product['assets'])) {
                continue;
            }

            $angles = [];
            foreach ($product['assets'] as $asset) {
                if (empty($asset['imageurl']) || empty($asset['angle']) || empty($asset['assettype'])) {
                    continue;
                }

                $angles[$asset['assettype'] . '_' . $asset['angle']] = $asset['imageurl'];
            }

            $anglesData[$product['material']] = $this->serializer->serialize($angles);
        }

        return $this->getAnglesForSkus($anglesData);
    }

    /**
     * Prepare sku=>angles data, by material=>angles data
     *
     * @param string[] $data
     * @return string[][]
     */
    private function getAnglesForSkus(array $data): array
    {
        $materials = \array_keys($data);
        $skus = $this->getAllSkusForMaterials($materials);

        $result = [];
        foreach ($skus as $sku => $item) {
            if (empty($data[$item['style_code']])) {
                continue;
            }

            $result[] = [(string) $sku, $data[$item['style_code']]];
        }

        return $result;
    }

    /**
     * Fetch all sku of products with given material codes
     *
     * @param string[] $materials
     * @return string[][]
     */
    private function getAllSkusForMaterials(array $materials): array
    {
        $styleAttribute = $this->eavConfig->getAttribute(Product::ENTITY, 'material_code');

        $styleCodeTable = $styleAttribute->getBackendTable();

        //phpcs:disable VCQP.Classes.ResourceModel.OutsideOfResourceModel
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()->from(
            ['style_code' => $styleCodeTable],
            [
                'sku' => 'catalog_product_entity.sku',
                'style_code' => 'style_code.value',
            ]
        )->joinLeft(
            ['catalog_product_entity' => $connection->getTableName('catalog_product_entity')],
            'style_code.row_id = catalog_product_entity.row_id',
            []
        )->where('value in (?)', $materials)
            ->where('attribute_id = ?', $styleAttribute->getId());

        return $connection->fetchAssoc($select);
    }
}
