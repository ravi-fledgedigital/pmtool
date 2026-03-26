<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Amasty\Rolepermissions\Observer\Admin\Product;

use Amasty\Rolepermissions\Helper\Data;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class EditPostdispatchObserver implements ObserverInterface
{
    public const BUTTONS_TO_UNSET = [
        'save_and_new',
        'save_and_duplicate'
    ];

    public function __construct(
        private readonly ViewInterface $view,
        private readonly Data $helper
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer): void
    {
        if ($this->helper->restrictAttributeSets()) {
            $toolbar = $this->view->getLayout()->getBlock('page.actions.toolbar');
            $toolbar->unsetChild('addButton');

            $saveButtonBlock = $toolbar->getChildBlock('save');
            $saveButton = $saveButtonBlock ? $saveButtonBlock->getButtonItem() : false;
            $saveButtonOptions = $saveButton ? $saveButton->getOptions() : false;
            if ($saveButtonOptions) {
                foreach ($saveButtonOptions as $key => $option) {
                    if (in_array($option['id_hard'], self::BUTTONS_TO_UNSET)) {
                        unset($saveButtonOptions[$key]);
                    }
                }

                $saveButton->setOptions(array_values($saveButtonOptions));
            }
        }
    }
}
