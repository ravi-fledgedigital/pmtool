<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class RecurringData implements InstallDataInterface
{
    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Indexer\ActionProcessor
     */
    private $indexProcessor;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $config;

    public function __construct(
        \OnitsukaTiger\OrderAttribute\Model\Indexer\ActionProcessor $indexProcessor,
        \Magento\Eav\Model\Config $config
    ) {
        $this->indexProcessor = $indexProcessor;
        $this->config = $config;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     *
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (!$context->getVersion() || version_compare($context->getVersion(), '3.0.0', '<')) {
            $this->config->clear();
            $this->indexProcessor->reindexAll();
        }
    }
}
