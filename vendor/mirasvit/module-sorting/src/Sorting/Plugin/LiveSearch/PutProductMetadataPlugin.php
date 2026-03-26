<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\Sorting\Plugin\LiveSearch;

use Magento\DataExporter\Model\Indexer\FeedIndexMetadata;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Mirasvit\Sorting\Api\Data\CriterionInterface;
use Mirasvit\Sorting\Api\Data\RankingFactorInterface;
use Mirasvit\Sorting\Repository\CriterionRepository;
use Mirasvit\Sorting\Repository\RankingFactorRepository;
use Mirasvit\Sorting\Service\ScoreFetcherService;
use Mirasvit\Sorting\Model\Indexer;
use Magento\Framework\Filesystem\DirectoryList;
use Mirasvit\Sorting\Model\ConfigProvider;
use Magento\SaaSCommon\Model\ExportFeed;
use Magento\Framework\Module\Manager;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PutProductMetadataPlugin
{

    private $storeManager;

    private $rankingFactorRepository;

    private $scoreFetcherService;

    private $criterionRepository;

    private $directoryList;

    private $configProvider;

    private $moduleManager;
    
    public function __construct(
        StoreManagerInterface   $storeManager,
        CriterionRepository     $criterionRepository,
        RankingFactorRepository $rankingFactorRepository,
        ScoreFetcherService     $scoreCalculationService,
        DirectoryList           $directoryList,
        ConfigProvider          $configProvider,
        Manager                 $moduleManager
    ) {
        $this->storeManager            = $storeManager;  
        $this->criterionRepository     = $criterionRepository;
        $this->rankingFactorRepository = $rankingFactorRepository;
        $this->scoreFetcherService     = $scoreCalculationService;
        $this->directoryList           = $directoryList;
        $this->configProvider          = $configProvider;
        $this->moduleManager           = $moduleManager;
    }

    /**
     * @param ExportFeed $subject
     * @param array $data
     * @param FeedIndexMetadata $metadata
     * @return array
     */
    public function beforeExport(ExportFeed $subject, array $data, FeedIndexMetadata $metadata)
    {  
        
        if (!$this->moduleManager->isEnabled('Magento_LiveSearch') || $metadata->getFeedName() != 'products') {
            return [$data, $metadata];
        }

        $data = $this->addScoresToProductMetadata($data);

        if ($this->configProvider->isDebugProductMetadataEnabled()) {
            $logFileName = $this->directoryList->getRoot() . '/var/log/product-metadata.txt';
            file_put_contents($logFileName, print_r($data, true).PHP_EOL);
        }

        return [$data, $metadata];
    }

    private function addScoresToProductMetadata(array $data): array
    {
        foreach ($this->storeManager->getStores() as $store) {
            if ($store->getId() == 0) {
                continue;
            }
            $data = $this->getScoresPerStore($data, $store);

        }
        return $data;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getScoresPerStore(array $data, StoreInterface $store): array
    {
        $productIds = array_column($data, 'productId');

        $scoreList  = $this->scoreFetcherService->getProductsScoreList($productIds, intval($store->getId()));
        $collection = $this->rankingFactorRepository->getCollection()
            ->addFieldToFilter(RankingFactorInterface::IS_ACTIVE, true);

        foreach ($collection as $factor) {
            foreach ($data as &$product) {
                if ($product['storeViewCode'] == $store->getCode()) {
                    $score = $this->scoreFetcherService->getScore(
                        $scoreList,
                        (int)$product['productId'],
                        Indexer::getScoreColumn($factor),
                        true
                    );

                    $product['attributes'][] = [
                        'attributeCode' => 'sorting_factor_' . $factor->getId(),
                        'type' => "decimal",
                        'value' => [$score],
                        'valueId' => null
                    ];
                }
            }
        }

        $globalFactors = $this->rankingFactorRepository->getCollection();
        $globalFactors->addFieldToFilter(RankingFactorInterface::IS_ACTIVE, true)
            ->addFieldToFilter(RankingFactorInterface::IS_GLOBAL, true);

        foreach ($data as &$product) {
            if ($product['storeViewCode'] == $store->getCode()) {
                $globalScore = 0;
                foreach ($globalFactors as $factor) {
                    $score = $this->scoreFetcherService->getScore(
                        $scoreList,
                        (int)$product['productId'],
                        Indexer::getScoreColumn($factor),
                        true
                    );
    
                    $globalScore += $score * $factor->getWeight();
                }

                $product['attributes'][] = [
                    'attributeCode' => 'sorting_global',
                    'type' => "decimal",
                    'value' => [floatval($globalScore)],
                    'valueId' => null
                ];
            }
        }

        foreach ($this->criterionRepository->getCollection() as $criterion) {
            foreach ($data as &$product) {
                if ($product['storeViewCode'] == $store->getCode()) {
                    $frameScores = $this->scoreFetcherService->getFrameScores($criterion, $scoreList, $product['productId'], true);
                    foreach ($frameScores as $frameIdx => $score) {
                        $product['attributes'][] = [
                            'attributeCode' => 'sorting_criterion_' . $criterion->getId() . '_frame_' . $frameIdx,
                            'type' => "decimal",
                            'value' => [floatval($score)],
                            'valueId' => null
                        ];
                    }
                }
            }
        }

        return $data;
    }
}