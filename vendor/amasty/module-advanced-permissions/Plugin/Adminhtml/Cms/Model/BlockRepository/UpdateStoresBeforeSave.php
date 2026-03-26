<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Amasty\Rolepermissions\Plugin\Adminhtml\Cms\Model\BlockRepository;

use Amasty\Rolepermissions\Helper\Data;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Model\BlockRepository;

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
        BlockRepository $subject,
        BlockInterface $block
    ): void {
        $rule = $this->helper->currentRule();

        if ($rule && $rule->getScopeStoreviews()) {
            if (empty($block->getStoreId())) {
                $origStores = $block->getOrigData('store_id');
                $origStores = is_array($origStores)
                    ? $origStores
                    : array_filter(explode(',', (string)$origStores));

                $block->setStoreId(
                    array_diff($origStores, $rule->getScopeStoreviews())
                );
            }
        }
    }
}
