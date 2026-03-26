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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\CatalogLabel\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;

/**
 * Emulates catalog label cron execution
 */
class EmulateCronCommand extends Command
{
    private $objectManagerFactory;

    public function __construct(
        ObjectManagerFactory $objectManagerFactory
    ) {
        $this->objectManagerFactory = $objectManagerFactory;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('mirasvit:label:emulate:cron')
            ->setDescription('Emulates product label cron execution');
        parent::configure();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $omParams = $_SERVER;
        $omParams[StoreManager::PARAM_RUN_CODE] = 'admin';
        $omParams[Store::CUSTOM_ENTRY_POINT_PARAM] = true;
        $objectManager = $this->objectManagerFactory->create($omParams);

        /** @var \Magento\Framework\App\State $state */
        $state = $objectManager->get('Magento\Framework\App\State');
        $state->setAreaCode('global');

        /** @var \Mirasvit\CatalogLabel\Model\Observer $cronObserver */
        $cronObserver = $objectManager->create('Mirasvit\CatalogLabel\Model\Observer');

        $cronObserver->apply(null, false, true);
        $output->writeln('<info>' . 'Ran cron jobs.' . '</info>');

        return 0;
    }
}
