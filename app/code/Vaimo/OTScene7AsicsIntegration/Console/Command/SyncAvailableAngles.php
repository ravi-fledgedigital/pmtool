<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7AsicsIntegration\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\MagentoCloud\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vaimo\OTScene7AsicsIntegration\Model\SynchroniseAvailableAnglesInterface;

class SyncAvailableAngles extends Command
{
    const SKU = 'sku';
    const AFTER_DATE = 'afterdate';

    private const NAME = 'asics:images:sync-angles';
    private SynchroniseAvailableAnglesInterface $synchroniseAvailableAngles;
    private State $state;


    /**
     * @param SynchroniseAvailableAnglesInterface $synchroniseAvailableAngles
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        SynchroniseAvailableAnglesInterface $synchroniseAvailableAngles,
        State $state,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->synchroniseAvailableAngles = $synchroniseAvailableAngles;
        $this->state = $state;
    }

    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Synchronize image angles with Asics API')
            ->addOption(
                self::SKU,
                's',
                InputOption::VALUE_OPTIONAL,
                'Sku'
            )
            ->addOption(
                self::AFTER_DATE,
                'd',
                InputOption::VALUE_OPTIONAL,
                'after date'
            )

        ;

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);
        $timestamp = $input->getOption('afterdate') ?? '';
        $configurableSku = $input->getOption(self::SKU) ?? '';
        $configurableSku = explode("\n", $configurableSku);
        foreach ($configurableSku as $item) {
            $this->synchroniseAvailableAngles->execute($timestamp, $item, $output);
        }
        return Cli::SUCCESS;
    }
}
