<?php

namespace OnitsukaTiger\Catalog\Console\Command;

use Magento\Checkout\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class HideConfigurableProductNotSalable
 * @package OnitsukaTiger\Catalog\Console\Command
 */
class UpdateConfigurableProductVisible extends \OnitsukaTiger\Command\Console\Command
{
    const SKU = 'sku';

    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * @var LoggerInterface|\OnitsukaTiger\Logger\Logger
     */
    protected $logger;

    /**
     * @var \OnitsukaTiger\Catalog\Model\UpdateConfigurableProductVisible
     */
    private $updateConfigurableProductVisible;


    /**
     * HideConfigurableProductNotSalable constructor.
     * @param \OnitsukaTiger\Catalog\Model\UpdateConfigurableProductVisible $configurableProductNotSalable
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        \OnitsukaTiger\Catalog\Model\UpdateConfigurableProductVisible $updateConfigurableProductVisible,
        \OnitsukaTiger\Logger\Logger $logger,
        \Magento\Framework\App\State $state
    )
    {
        $this->state = $state;
        $this->logger = $logger;
        $this->updateConfigurableProductVisible = $updateConfigurableProductVisible;
        parent::__construct($logger,'configurable:product:update');
    }

    /**
     * Running Command Update Product Configurable
     */
    protected function configure()
    {
        $this->setName('configurable:product:update')
            ->setDescription('Update Configurable Product With All Simple Stock Information')
            ->addOption(
                self::SKU,
                's',
                InputOption::VALUE_OPTIONAL,
                'Sku'
            );
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);
            if ($sku = $input->getOption(self::SKU)) {
                $this->updateConfigurableProductVisible->updateConfigurableProductbySku($sku);
                $output->writeln('<info>Update Configurable Product ' . $sku . ' Success.</info>');
            } else {
                $this->updateConfigurableProductVisible->updateAllConfigurableProduct();
                $output->writeln('<info>Update All Configurable Product For All Website Success.</info>');
            }
        } catch (Exception $e) {
            $output->writeln('<info>' . $e->getMessage() . '</info>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
