<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPCustomerFileExport\Console\Command;

use Magento\MagentoCloud\Cli;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends BaseCommand
{
    private const NAME = 'aep:file-export:adobe-campaign-customers';
    private ProxyWrapper $export;

    public function __construct(
        ProxyWrapper $export,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->export = $export;
    }

    protected function configure()
    {
        $this->setName(self::NAME);
        $this->setDescription('Export customers to Adobe Campaign');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->export->execute();

        return Cli::SUCCESS;
    }
}
