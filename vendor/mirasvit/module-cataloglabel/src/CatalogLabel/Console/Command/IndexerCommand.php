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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */




namespace Mirasvit\CatalogLabel\Console\Command;


use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerRegistry;
use Mirasvit\CatalogLabel\Model\Indexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class IndexerCommand extends Command
{
    private $indexer;

    private $indexerRegistry;

    private $resource;

    public function __construct(
        Indexer $indexer,
        IndexerRegistry $indexerRegistry,
        ResourceConnection $resource
    ) {
        $this->indexer         = $indexer;
        $this->indexerRegistry = $indexerRegistry;
        $this->resource        = $resource;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('mirasvit:label:indexer')
            ->setDescription('Emulates product label cron execution');

        $this->addOption(
            'status',
            null,
            InputOption::VALUE_NONE,
            'Check CatalogLabel index status'
        );

        $this->addOption(
            'reindex-all',
            null,
            InputOption::VALUE_NONE,
            'Run full reindex'
        );

        $this->addOption(
            'reindex-new',
            null,
            InputOption::VALUE_NONE,
            'Run reindex for recently created/updated products'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $state = ObjectManager::getInstance()->get(\Magento\Framework\App\State::class);
            $state->setAreaCode('adminhtml');
        } catch (\Exception $e) {
        }

        $idxr = $this->indexerRegistry->get(Indexer::INDEXER_ID);

        if ($input->getOption('status')) {
            $output->writeln('<comment>Index</comment>      | ' . $idxr->getTitle());
            $output->writeln('<comment>Status</comment>     | ' . $idxr->getStatus());
            $output->writeln('<comment>Mode</comment>       | ' . strtoupper($idxr->isScheduled() ? 'udpate by schedule' : 'update on save'));
            $output->writeln('<comment>Updated At</comment> | ' . $idxr->getLatestUpdated());

            return 0;
        }

        if ($input->getOption('reindex-all')) {
            $start = microtime(true);
            $output->writeln($idxr->getTitle() . ': Running full reindex');

            $this->indexer->reindex();

            $time = round(microtime(true) - $start);

            $output->writeln('Reindex successfully finished in ' . gmdate('H:i:s', $time));

            return 0;
        }

        if ($input->getOption('reindex-new')) {
            $start = microtime(true);

            $connection = $this->resource->getConnection();

            $query = 'SELECT entity_id FROM '
                . $this->resource->getTableName('catalog_product_entity') .
                ' WHERE updated_at > "' . $idxr->getLatestUpdated() . '"';

            $result = $connection->query($query)->fetchAll();

            if (!count($result)) {
                $output->writeln($idxr->getTitle() . ': No recently created/updated products found.');

                return 0;
            }

            $output->writeln($idxr->getTitle() . ': Running reindex of recently updated/created products (' . count($result) . ')');

            $ids = [];

            foreach($result as $row) {
                $ids[] = $row['entity_id'];
            }

            $this->indexer->reindex(null, $ids);

            $idxr->getState()->setUpdated(time())->save();

            $time = round(microtime(true) - $start);

            $output->writeln('Reindex successfully finished in ' . gmdate('H:i:s', $time));

            return 0;
        }

        $help = new HelpCommand();

        $help->setCommand($this);
        $help->run($input, $output);

        return 0;
    }
}
