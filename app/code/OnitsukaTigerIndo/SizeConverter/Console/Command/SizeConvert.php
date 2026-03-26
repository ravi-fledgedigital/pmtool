<?php
/** phpcs:ignoreFile */

namespace OnitsukaTigerIndo\SizeConverter\Console\Command;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SizeConvert extends Command
{
    const ARGUMENT_SKUS = 'skus';
    const OPTION_STORE = 'store';

    protected $productRepository;
    protected $resource;
    protected $storeManager;
    protected $state;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        State $state,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
                                   $name = null
    ) {
        $this->productRepository = $productRepository;
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->state = $state;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('size:convert')
            ->setDescription('Convert English size to Euro size')
            ->addArgument(
                self::ARGUMENT_SKUS,
                InputArgument::IS_ARRAY,
                'List of SKUs (space separated)'
            )
            ->addOption(
                self::OPTION_STORE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Store ID(s). Comma separated for multiple stores (example: --store=6,7)'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode('adminhtml');
        } catch (\Exception $e) {
            // Area already set
        }

        $skus = $input->getArgument(self::ARGUMENT_SKUS);
        $storeOption = $input->getOption(self::OPTION_STORE);

        if (empty($skus)) {
            $output->writeln('<error>No SKUs provided.</error>');
            return 1;
        }

        foreach ($skus as $sku) {

            try {

                // Determine store IDs
                if ($storeOption) {
                    $storeIds = array_map('trim', explode(',', $storeOption));
                } else {
                    $storeIds = $this->productRepository->get($sku)->getStoreIds();
                }

                foreach ($storeIds as $storeId) {

                    if (!$storeId || $storeId == 0) {
                        continue;
                    }

                    try {

                        $product = $this->productRepository->get($sku, false, $storeId, true);

                        $englishSize = $product->getCustomAttribute('qa_size')
                            ? $product->getCustomAttribute('qa_size')->getValue()
                            : null;

                        $gender = $product->getCustomAttribute('gender')
                            ? $product->getCustomAttribute('gender')->getValue()
                            : null;

                        if (!$englishSize) {
                            $output->writeln("<comment>SKU $sku (Store $storeId): English size not found.</comment>");
                            continue;
                        }

                        $euroSize = $this->getEuroSize($englishSize, $gender, $storeId);

                        if (!$euroSize) {
                            $output->writeln("<comment>SKU $sku (Store $storeId): No mapping found.</comment>");
                            continue;
                        }

                        /*$product->setStoreId($storeId);
                        $product->setCustomAttribute('size_for_display', $euroSize);
                        $this->productRepository->save($product);*/

                        $product = $this->productRepository->get($sku, true, $storeId, true);
                        $product->setStoreId($storeId);
                        $product->setData('size_for_display', $euroSize);
                        $product->getResource()->saveAttribute($product, 'size_for_display');

                        $output->writeln("<info>SKU $sku (Store $storeId) updated to Euro size: $euroSize</info>");

                    } catch (\Exception $e) {
                        $output->writeln("<error>SKU $sku (Store $storeId) error: {$e->getMessage()}</error>");
                    }
                }

            } catch (NoSuchEntityException $e) {
                $output->writeln("<error>Product with SKU $sku not found.</error>");
            } catch (\Exception $e) {
                $output->writeln("<error>Error processing SKU $sku: {$e->getMessage()}</error>");
            }
        }

        return 0;
    }

    private function getEuroSize($englishSize, $gender, $storeId)
    {
        $table = $this->resource->getTableName('onitsukatigerindo_sizeconverter');
        $select = $this->resource->getConnection()->select()
            ->from($table, ['euro_size','gender'])
            ->where('english_size = ?', $englishSize)
            ->where('gender = ?', $gender)
            ->where('FIND_IN_SET(?, store_ids)', $storeId);

        return $this->resource->getConnection()->fetchOne($select);
    }
}
