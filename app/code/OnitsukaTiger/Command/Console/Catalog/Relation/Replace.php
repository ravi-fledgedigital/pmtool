<?php
namespace OnitsukaTiger\Command\Console\Catalog\Relation;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use OnitsukaTiger\Command\Console\Command;
use OnitsukaTiger\Catalog\Model\ConfigurableProduct\Filter;
use OnitsukaTiger\Catalog\Model\ConfigurableProduct\LinkManagement;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Replace
 * @package OnitsukaTiger\Command\Console\Catalog\Relation
 */
class Replace extends Command
{
    const SKU = 'sku';

    /**
     * @var State
     */
    protected $state;

    /**
     * @var Filter
     */
    protected $productFilter;

    /**
     * @var LinkManagement
     */
    protected $linkManagement;

    /**
     * Add constructor.
     * @param \OnitsukaTiger\Logger\Logger $logger
     * @param State $state
     * @param Filter $productFilter
     * @param LinkManagement $linkManagement
     * @param string|null $name
     */
    public function __construct(
        \OnitsukaTiger\Logger\Logger $logger,
        State $state,
        Filter $productFilter,
        LinkManagement $linkManagement,
        string $name = null
    ) {
        $this->state = $state;
        $this->productFilter = $productFilter;
        $this->linkManagement = $linkManagement;
        parent::__construct($logger, $name);
    }

    /**
     * Configures the current command.
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('catalog:relation:update')
            ->setDescription('Update (remove all and assign) assignment simple products to a configurable product.')
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
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        $this->state->setAreaCode(Area::AREA_CRONTAB);
        if ($configurableSku = $input->getOption(self::SKU)) {
            $configurableSku = explode("\n", $configurableSku);
            foreach ($configurableSku as $item) {
                $this->linkManagement->removeAllChild($item);
                $products = $this->productFilter->getChildProductsToAssign($item);
                foreach ($products as $product) {
                    /** @var Product $product */
                    $this->linkManagement->setColorCodeAttributeToProductChild($product);
                    $this->linkManagement->addChild($item, $product->getSku());
                }
                $output->writeln('<info>Replace Associated Simple Products To Configurable Product With SKU: ' . $item . ' Success.</info>');
            }
        }
        $timeElapsedSecs = microtime(true) - $start;
        $output->writeln($timeElapsedSecs);

        return Command::SUCCESS;
    }
}
