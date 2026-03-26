<?php
namespace Vaimo\OTScene7AsicsIntegration\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\RuntimeException;
use Magento\MagentoCloud\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vaimo\OTScene7AsicsIntegration\Model\ReAddWebsiteProduct;

class ReAddWebsiteProductWithImage extends Command
{
    private const NAME = 'asics:re-add-website-product';
    private const LOG = 'log';
    /**
     * @var ReAddWebsiteProduct
     */
    private $reAddWebsiteProduct;
    /**
     * @var State
     */
    private $state;

    /**
     * @param ReAddWebsiteProduct $reAddWebsiteProduct
     * @param State $state
     * @param string|null $name
     */
    public function __construct(
        ReAddWebsiteProduct   $reAddWebsiteProduct,
        State                 $state,
        string $name = null
    ) {
        parent::__construct($name);
        $this->reAddWebsiteProduct = $reAddWebsiteProduct;
        $this->state = $state;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName(self::NAME);
        $this->setDescription('Add all websites if image is found, skip if products have been assigned to any website ')
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
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
            $this->reAddWebsiteProduct->execute($input, $output);
        } catch (RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::FAILURE;
        }
        return Cli::SUCCESS;
    }
}
