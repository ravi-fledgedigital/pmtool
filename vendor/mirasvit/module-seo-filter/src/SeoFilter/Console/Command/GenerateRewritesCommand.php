<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-seo-filter
 * @version   1.3.57
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



declare(strict_types=1);

namespace Mirasvit\SeoFilter\Console\Command;


use Magento\Catalog\Model\Layer\Category\FilterableAttributeList;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\SeoFilter\Api\Data\RewriteInterface;
use Mirasvit\SeoFilter\Repository\RewriteRepository;
use Mirasvit\SeoFilter\Service\RewriteService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateRewritesCommand extends Command
{
    private $objectManager;

    private $appState;

    private $storeManager;

    private $filterableAttributeList;

    private $rewriteService;

    private $rewriteRepository;

    public function __construct(
        ObjectManagerInterface $objectManager,
        State $appState,
        StoreManagerInterface $storeManager,
        FilterableAttributeList $filterableAttributeList,
        RewriteService $rewriteService,
        RewriteRepository $rewriteRepository
    ) {
        $this->objectManager           = $objectManager;
        $this->appState                = $appState;
        $this->storeManager            = $storeManager;
        $this->filterableAttributeList = $filterableAttributeList;
        $this->rewriteService          = $rewriteService;
        $this->rewriteRepository       = $rewriteRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('mirasvit:seo-filter:rewrites')
            ->setDescription('Generate SEO-friendly rewrites for filter options');

        $this->addOption('generate', null, null, 'Generate Rewrites');
        $this->addOption('remove', null, null, 'Remove All Rewrites');
        $this->addOption('actualize', null, null, 'Remove Unused Rewrites');

        parent::configure();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode('frontend');
        } catch (\Exception $e) {
        }

        if ($input->getOption('generate')) {
            $output->writeln('Generating filters rewrites for attributes:');

            foreach ($this->storeManager->getStores() as $store) {
                $this->storeManager->setCurrentStore($store);

                $attributes = $this->filterableAttributeList->getList();

                /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
                foreach ($attributes as $attribute) {
                    $output->writeln($attribute->getStoreLabel($store) . ' (' . $attribute->getAttributeCode() . ')...');

                    $attributeCode = $attribute->getAttributeCode();
                    $this->rewriteService->getAttributeRewrite($attributeCode, (int)$store->getId(), false);

                    if (!in_array($attribute->getFrontendInput(), ['select', 'multiselect', 'boolean'])) {
                        continue;
                    }

                    foreach ($attribute->getOptions() as $option) {
                        $optionValue = (string)$option->getValue();

                        if ($optionValue === '' || $optionValue === null) {
                            continue;
                        }

                        if (preg_match('/[;\'"()\\s]|--/', $optionValue)) {
                            $output->writeln(sprintf(
                                '<comment>Skipped potentially unsafe option value "%s" for attribute "%s"</comment>',
                                $optionValue,
                                $attributeCode
                            ));
                            continue;
                        }

                        $this->rewriteService->getOptionRewrite(
                            $attributeCode,
                            $optionValue,
                            (int)$store->getId(),
                            false
                        );
                    }
                }
            }

            $output->writeln('Done!');

            return 0;
        }

        if ($input->getOption('remove')) {
            $output->writeln('Removing existing filters rewrites...');
            $resource = $this->rewriteRepository->create()->getResource();
            $resource->getConnection()->query('TRUNCATE TABLE ' . $resource->getTable(RewriteInterface::TABLE_NAME));
            $output->writeln('Done!');

            return 0;
        }

        if ($input->getOption('actualize')) {
            $output->writeln('Removing filters rewrites for non-existent options');

            $resource = $this->rewriteRepository->create()->getResource();
            $connection = $resource->getConnection();

            // Remove aliases where associated option no longer exists in eav_attribute_option
            $select = $connection->select()->from(
                ['alias' => $resource->getTable(RewriteInterface::TABLE_NAME)],
                ['rewrite_id']
            )->joinLeft(
                ['opt' => $resource->getTable('eav_attribute_option')],
                'alias.option = opt.option_id',
                'option_id'
            )->where(
                'opt.option_id IS NULL'
            )->where('alias.option REGEXP ' . "'^[0-9]+$'");

            $rewriteIds = $connection->fetchCol($select);

            if (count($rewriteIds)) {
                $connection->delete(
                    $resource->getTable(RewriteInterface::TABLE_NAME),
                    ['rewrite_id IN (?)' => $rewriteIds]
                );
                $output->writeln(count($rewriteIds) . ' unused option aliases were deleted');
            } else {
                $output->writeln('No unused option aliases to remove');
            }

            // Remove aliases for attributes that are no longer marked as filterable (is_filterable = 0)
            $output->writeln('Removing filters rewrites for non-filterable attributes');

            $subSelect = $connection->select()
                ->from(
                    ['ea' => $resource->getTable('eav_attribute')],
                    ['attribute_code']
                )
                ->join(
                    ['cea' => $resource->getTable('catalog_eav_attribute')],
                    'cea.attribute_id = ea.attribute_id',
                    []
                )
                ->where('cea.is_filterable = ?', 0);

            $nonFilterableAttrCodes = $connection->fetchCol($subSelect);

            if (!empty($nonFilterableAttrCodes)) {
                $count = $connection->delete(
                    $resource->getTable(RewriteInterface::TABLE_NAME),
                    ['attribute_code IN (?)' => $nonFilterableAttrCodes]
                );

                $output->writeln($count . ' aliases for non-filterable attributes were deleted');
            } else {
                $output->writeln('No non-filterable attribute aliases to remove');
            }

            // Remove aliases for group options that no longer exist in mst_navigation_grouped_option
            $groupTableName = $resource->getTable('mst_navigation_grouped_option');

            if ($connection->isTableExists($groupTableName)) {
                $output->writeln('Removing filters rewrites for non-existent grouped options');

                $groupSelect = $connection->select()->from(
                    ['alias' => $resource->getTable(RewriteInterface::TABLE_NAME)],
                    ['rewrite_id']
                )->joinLeft(
                    ['group_table' => $groupTableName],
                    'alias.' . RewriteInterface::OPTION . ' = group_table.code',
                    ['group_id']
                )->where(
                    'group_table.group_id IS NULL'
                )->where('alias.' . RewriteInterface::OPTION . ' NOT REGEXP ' . "'^[0-9]+$'");

                $groupRewriteIds = $connection->fetchCol($groupSelect);

                if (count($groupRewriteIds)) {
                    $connection->delete(
                        $resource->getTable(RewriteInterface::TABLE_NAME),
                        ['rewrite_id IN (?)' => $groupRewriteIds]
                    );
                    $output->writeln(count($groupRewriteIds) . ' aliases for non-existent grouped options were deleted');
                } else {
                    $output->writeln('No non-existent grouped option aliases to remove');
                }
            }

            $output->writeln('Done!');
            return 0;
        }

        $help = new HelpCommand();
        $help->setCommand($this);

        $help->run($input, $output);

        return 0;
    }
}
