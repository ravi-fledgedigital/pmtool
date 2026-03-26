<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Observer\Frontend;

use Amasty\Rma\Model\Chat\AdminEmailProcessor;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class NotifyAdminAboutNewMessage implements ObserverInterface
{
    /**
     * @var AdminEmailProcessor
     */
    private $adminEmailProcessor;

    public function __construct(
        AdminEmailProcessor $adminEmailProcessor
    ) {
        $this->adminEmailProcessor = $adminEmailProcessor;
    }

    public function execute(Observer $observer)
    {
        $request = $observer->getRequest();
        $message = $observer->getMessage();

        if ($request && $message) {
            $this->adminEmailProcessor->process($request, $message);
        }
    }
}
