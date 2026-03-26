<?php

namespace OnitsukaTiger\NetSuite\Console;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NetsuiteEnableProduct extends Command
{
    const PRODUCT_IDS = 'productIds';

    /**
     * @var State
     */
    protected $appState;

    /**
     * @param State $appState
     * @param string|null $name
     */
    public function __construct(
        State $appState,
        private \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        private \OnitsukaTiger\Logger\Api\Logger $logger,
        private \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct $enable,
        string $name = null
    ) {
        parent::__construct($name);
        $this->appState = $appState;
    }

    /**
     * Method configure
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::PRODUCT_IDS,
                null,
                InputOption::VALUE_REQUIRED,
                'Store Code'
            )
        ];

        $this->setName('OT:ns-product-enabled');
        $this->setDescription('Netsuite product enabled');
        $this->setDefinition($options);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($productIds = $input->getOption(self::PRODUCT_IDS)) {
            $this->appState->setAreaCode('crontab');
            $productIds = explode(' ', $productIds);
            $output->writeln("------- Product syncing start -------");
            $this->syncedProductToNs($productIds);
            $output->writeln("------- Product syncing ended -------");
            return Command::SUCCESS;
        } else {
            $output->writeln("Product id is a required parameter.");
            return Command::SUCCESS;
        }
    }

    private function syncedProductToNs($productIds)
    {
        foreach ($productIds as $productId) {
            $product = $this->productRepository->getById($productId, false, null, true);
            $this->logger->info(sprintf('process SKU : %s', $product->getSku()));
            try {
                // merge with NetSuite value if value has null
                $product = $this->enable->mergeNetSuiteValue(
                    $product->getSku(),
                    1,
                    1,
                    1,
                    1
                );

                // update NetSuite setting
                $this->enable->updateItem($product);
            } catch (\Exception $e) {
                $this->logger->err(sprintf('retry failed : %s', $e->getMessage()));
                throw $e;
            }
        }
    }

}
