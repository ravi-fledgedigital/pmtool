<?php

namespace OnitsukaTiger\Relation\Console\Command;

use Magento\Checkout\Exception;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use OnitsukaTiger\Logger\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateConfigurableProductRelation extends Command
{

    public const SKU = 'sku';

    /**
     * @var State
     */
    private State $state;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var \OnitsukaTiger\Relation\Model\UpdateConfigurableProductRelationFactory
     */
    private \OnitsukaTiger\Relation\Model\UpdateConfigurableProductRelationFactory $relationModel;

    /**
     * @param \OnitsukaTiger\Relation\Model\UpdateConfigurableProductRelationFactory $relationModel
     * @param Logger $logger
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        \OnitsukaTiger\Relation\Model\UpdateConfigurableProductRelationFactory $relationModel,
        Logger                                                                 $logger,
        State                                                                  $state,
        string                                                                 $name = null
    ) {
        $this->relationModel = $relationModel;
        $this->state = $state;
        $this->logger = $logger;
        parent::__construct($name);
    }

    /**
     * Config Command Name
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('update:product:relations');
        $this->setDescription('Update Product Relations With Attributes');
        $this->addOption(
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
     * @return int|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);
        $updateRelation = $this->relationModel->create();
        try {
            $sku = $input->getOption(self::SKU);
            if ($sku) {
                $updateRelation->updateConfigurableProductBySku($sku, $output);
                $output->writeln('<info>Update Configurable Product ' . $sku . ' For All Website Success.</info>');
            } else {
                $updateRelation->updateAllConfigurableProduct($output);
                $output->writeln('<info>Update All Configurable Product For All Website Success.</info>');
            }
            return Command::SUCCESS;
            exit;
        } catch (Exception $e) {
            $output->writeln('<info>' . $e->getMessage() . '</info>');
            return Command::FAILURE;
        }
    }
}
