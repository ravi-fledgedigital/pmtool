<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\Source;

class PlaceForUse extends \Magento\Framework\DataObject implements \Magento\Framework\Option\ArrayInterface
{
    public const TYPE_ORDER = 1;
    public const TYPE_INVOICE = 2;
    public const TYPE_SHIPPING = 3;
    public const TYPE_CREDIT_MEMO = 4;

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => ''],
            ['value' => self::TYPE_ORDER, 'label' => __('Order')],
            ['value' => self::TYPE_INVOICE, 'label' => __('Invoice')],
            ['value' => self::TYPE_SHIPPING, 'label' => __('Shipping')],
            ['value' => self::TYPE_CREDIT_MEMO, 'label' => __('Credit Memo')],
        ];
    }
}
