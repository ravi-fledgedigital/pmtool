<?php

namespace OnitsukaTiger\ProductFeed\Console\Command;

use Magento\Framework\Exception\RuntimeException;
use Magento\MagentoCloud\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use OnitsukaTiger\ProductFeed\Model\ProductFeed;
use Magento\Framework\App\State;

class AddGoogleImageShop extends Command
{
    const NAME     = 'asics_add_google_shop_image_link';
    const STORE_ID = 'store-id';
    const SKU      = 'sku';

    /**
     * @var ProductFeed
     */
    public $productFeed;

    /**
     * @var State
     */
    public $state;

    /**
     * @param ProductFeed $productFeed
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        ProductFeed $productFeed,
        State $state,
        string $name = null
    ) {
        $this->productFeed = $productFeed;
        $this->state       = $state;
        parent::__construct($name);
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName(self::NAME);
        $this->setDescription('Add Google Shop image Link')
            ->addOption(
                self::STORE_ID,
                'i',
                InputOption::VALUE_REQUIRED,
                'Store Id'
            )->addOption(
                self::SKU,
                's',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Update product by skus'
            );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->getAreaCode();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        }

        try {
            $this->productFeed->execute($input, $output);
        } catch (RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::FAILURE;
        }

        return Cli::SUCCESS;
    }
}
