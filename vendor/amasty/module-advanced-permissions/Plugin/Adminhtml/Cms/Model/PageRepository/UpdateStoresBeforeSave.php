<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Amasty\Rolepermissions\Plugin\Adminhtml\Cms\Model\PageRepository;

use Amasty\Rolepermissions\Helper\Data;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Model\PageRepository;

class UpdateStoresBeforeSave
{
    public function __construct(
        private readonly Data $helper
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        PageRepository $subject,
        PageInterface $page
    ): void {
        $rule = $this->helper->currentRule();

        if ($rule && $rule->getScopeStoreviews()) {
            if (empty($page->getStoreId())) {
                $origStores = $page->getOrigData('store_id');
                $origStores = is_array($origStores)
                    ? $origStores
                    : array_filter(explode(',', (string)$origStores));

                $page->setStoreId(
                    array_diff($origStores, $rule->getScopeStoreviews())
                );
            }
        }
    }
}
