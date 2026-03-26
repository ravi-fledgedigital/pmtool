<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Reindex\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;
use Magento\Framework\Xml\Parser;
use OnitsukaTiger\Logger\Api\Logger as OnitsukaTigerLogger;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Filesystem;
use OnitsukaTiger\Reindex\Helper\Data;

/**
 * Reindex
 */
class Reindex extends Command
{
    const SKU = 'sku';

    /**
     * list of indexers
     */
    const LIST_INDEXERS = [
        'catalog_category_product',
        'catalog_product_category',
        'catalog_product_attribute',
        'cataloginventory_stock',
        'inventory',
        'catalogsearch_fulltext',
        'catalog_product_price',
        'catalogrule_product',
        'catalogrule_rule'
    ];

    /**
     * @var State
     */
    protected $state;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var OnitsukaTigerLogger
     */
    protected $logger;

    /**
     * @var IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @param State $state
     * @param Parser $parser
     * @param OnitsukaTigerLogger $logger
     * @param IndexerRegistry $indexerRegistry
     * @param Filesystem $filesystem
     * @param Data $dataHelper
     * @param string|null $name
     */
    public function __construct(
        State $state,
        Parser $parser,
        OnitsukaTigerLogger $logger,
        IndexerRegistry $indexerRegistry,
        Filesystem $filesystem,
        Data $dataHelper,
        string $name = null
    ){
        $this->state = $state;
        $this->parser = $parser;
        $this->logger = $logger;
        $this->indexerRegistry = $indexerRegistry;
        $this->_filesystem = $filesystem;
        $this->dataHelper = $dataHelper;
        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        try {
            $this->state->setAreaCode('global');
            $skuString = $input->getOption('sku');
            $listSku = [];
            if ($skuString && isset($skuString[0])) {
                $listSku = explode("\n",$skuString[0]);
            }
            $listSku = array_unique($listSku);

            $productIds = $this->dataHelper->getProductIds($listSku);
            if (count($productIds) > 0) {
                $count = count($productIds);
                foreach (self::LIST_INDEXERS as $indexList) {
                    $indexer = $this->indexerRegistry->get($indexList);
                    $indexer->reindexList(array_unique($productIds));
                }
                $output->writeln("<info>Reindex Done $count product</info>");
            }else {
                $output->writeln("<info>There are no products in sku\'s list</info>");
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), []);
            $output->writeln("<error>{$e->getMessage()}</error>");
        }

        return Command::SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("catalog:product:reindex_product_by_skus");
        $this->setDescription("Reindex product by skus")
        ->addOption(
        self::SKU,
        's', \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED | \Symfony\Component\Console\Input\InputOption::VALUE_IS_ARRAY,
        'Sku');
        parent::configure();
    }
}

