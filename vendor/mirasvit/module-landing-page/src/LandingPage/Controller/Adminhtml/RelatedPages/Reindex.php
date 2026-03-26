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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Controller\Adminhtml\RelatedPages;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Mirasvit\LandingPage\Model\Indexer\LandingPageProduct;

class Reindex extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Mirasvit_LandingPage::config_landing_page';

    private $indexerRegistry;

    public function __construct(
        IndexerRegistry $indexerRegistry,
        Context         $context
    ) {
        parent::__construct($context);

        $this->indexerRegistry = $indexerRegistry;
    }

    public function execute()
    {
        try {
            $indexer = $this->indexerRegistry->get(LandingPageProduct::INDEXER_ID);
            $indexer->reindexAll();

            $this->messageManager->addSuccessMessage(__('Landing Page Products index has been rebuilt.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Reindex failed: %1', $e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('adminhtml/system_config/edit', [
            'section' => 'mst_landing_page',
        ]);
    }
}
