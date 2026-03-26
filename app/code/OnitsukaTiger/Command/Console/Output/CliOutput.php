<?php
declare(strict_types=1);
namespace OnitsukaTiger\Command\Console\Output;

use Symfony\Component\Console\Output\OutputInterface;

class CliOutput
{
    /**
     * @param $debugData
     * @param OutputInterface|null $output
     */
    public function error($debugData, OutputInterface $output = null)
    {
        $output->writeln('<error>' . $debugData . '</error>');
    }

    public function success($debugData, OutputInterface $output = null)
    {
        $output->writeln('<info>' . $debugData . '</info>');
    }
}
