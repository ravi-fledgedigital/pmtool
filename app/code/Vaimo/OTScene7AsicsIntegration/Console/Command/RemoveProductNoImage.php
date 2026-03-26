<?php
namespace Vaimo\OTScene7AsicsIntegration\Console\Command;

use Magento\Framework\Exception\RuntimeException;
use Magento\MagentoCloud\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vaimo\OTScene7AsicsIntegration\Model\RemoveWebsitesProduct;

class RemoveProductNoImage extends Command
{
    private const NAME = 'asics:remove-websites:no-image';
    private const LOG = 'log';
    private RemoveWebsitesProduct $removeWebsitesProduct;

    /**
     * @param RemoveWebsitesProduct $removeWebsitesProduct
     * @param string|null $name
     */
    public function __construct(
        RemoveWebsitesProduct   $removeWebsitesProduct,
        string $name = null
    ) {
        $this->removeWebsitesProduct = $removeWebsitesProduct;
        parent::__construct($name);
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName(self::NAME);
        $this->setDescription('Removes all websites of a product when no image is found')
            ->addOption(
                self::LOG,
                'l',
                InputOption::VALUE_OPTIONAL,
                'Show Log'
            );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->removeWebsitesProduct->execute($input, $output);
        } catch (RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::FAILURE;
        }
        return Cli::SUCCESS;
    }
}
