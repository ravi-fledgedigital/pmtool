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

namespace Mirasvit\LandingPage\Observer;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Mirasvit\LandingPage\Model\Config\ConfigProvider;

class NotifyReindexOnEnableObserver implements ObserverInterface
{
    private $configProvider;

    private $messageManager;

    private $urlBuilder;

    public function __construct(
        ConfigProvider   $configProvider,
        ManagerInterface $messageManager,
        UrlInterface     $urlBuilder
    ) {
        $this->configProvider = $configProvider;
        $this->messageManager = $messageManager;
        $this->urlBuilder     = $urlBuilder;
    }

    public function execute(Observer $observer): void
    {
        $changedPaths = (array)$observer->getEvent()->getData('changed_paths');

        if (!in_array('mst_landing_page/related_pages/enabled', $changedPaths, true)) {
            return;
        }

        if (!$this->configProvider->isRelatedPagesEnabled()) {
            return;
        }

        $reindexUrl = $this->urlBuilder->getUrl('mst_landing/relatedPages/reindex');

        $this->messageManager->addComplexNoticeMessage(
            'mstLandingPageReindexMessage',
            ['url' => $reindexUrl]
        );
    }
}
