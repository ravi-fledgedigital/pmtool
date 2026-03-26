<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Block\Adminhtml\Variable;

use Magento\Backend\Block\Template;
use Magento\Framework\App\ProductMetadataInterface;

class Editor extends Template
{
    protected $_template = 'Amasty_CustomTabs::variable/attributes.phtml';

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    public function __construct(
        ProductMetadataInterface $productMetadata,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->productMetadata = $productMetadata;
    }

    /**
     * @return string
     */
    public function getSrc()
    {
        return 'Amasty_CustomTabs/js/variable/attributes';
    }
}
