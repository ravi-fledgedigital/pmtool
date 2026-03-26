<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\FrontendCaster;

use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface;
use Magento\Store\Model\StoreManagerInterface;

class File implements SpecificationProcessorInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * @param string[] $element
     * @param CheckoutAttributeInterface $attribute
     */
    public function processSpecificationByAttribute(array &$element, CheckoutAttributeInterface $attribute): void
    {
        $validateRules = $attribute->getValidateRules();
        if (!empty($validateRules['max_file_size'])) {
            $element['maxFileSize'] = (int)$validateRules['max_file_size'] * 1024 * 1024;
        }
        if (!empty($validateRules['file_extensions'])) {
            $element['allowedExtensions'] = str_replace(',', ' ', $validateRules['file_extensions']);
        }
        $element['uploaderConfig']['url'] = $this->storeManager->getStore()->getUrl('orderattribute/file/upload');
    }
}
