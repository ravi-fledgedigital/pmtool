<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPProductFileExport\Console\Command\ExportAdobeCampaignProducts;

use Magento\MagentoCloud\Cli;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    private const NAME = 'aep:file-export:adobe-campaign-products';
    private ProxyWrapper $productExport;

    public function __construct(
        ProxyWrapper $productExport,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->productExport = $productExport;
    }

    protected function configure()
    {
        $this->setName(self::NAME);
        $this->setDescription('Export products to Adobe Campaign');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->productExport->execute();

        return Cli::SUCCESS;
    }
}
