<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Amasty\Rolepermissions\Plugin\Adminhtml\Catalog\Ui\DataProvider\Product\ProductDataProvider;

use Amasty\Rolepermissions\Helper\Data;
use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider;

class RestrictNameAttribute
{
    public const NAME_FIELD = 'name';

    public function __construct(
        private readonly Data $helper
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetData(
        ProductDataProvider $subject,
        array $result
    ): array {
        $allowedAttributeCodes = $this->helper->getAllowedAttributeCodes();

        if (is_array($allowedAttributeCodes)
            && !in_array(self::NAME_FIELD, $allowedAttributeCodes)
            && isset($result['items'])
        ) {
            foreach ($result['items'] as &$item) {
                if (!isset($item[self::NAME_FIELD])) {
                    $item[self::NAME_FIELD] = '';
                }
            }
            unset($item);
        }

        return $result;
    }
}
