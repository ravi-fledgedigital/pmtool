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
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Brand\Block\Brand;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\Brand\Model\Config\BrandPageConfig;
use Mirasvit\Brand\Registry;
use Magento\Cms\Model\Template\FilterProvider;

class SeoDescription extends Template
{
    private $position = null;

    private $registry;

    private $filterProvider;

    /**
     * Description constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FilterProvider $filterProvider,
        array $data = []
    ) {
        $this->registry  = $registry;
        $this->filterProvider = $filterProvider;
        $this->position  = isset($data['position']) ? $data['position'] : '';

        parent::__construct($context, $data);
    }

    /**
     * @return bool|string
     */
    public function getDescription()
    {
        $brandPage = $this->registry->getBrandPage();

        $description = $this->filterProvider->getPageFilter()->filter($brandPage->getSeoDescription());

        if (!$description) {
            return false;
        }

        if ($this->position == 'bottom'
            && $brandPage->getSeoPosition() == BrandPageConfig::BOTTOM_SEO_POSITION) {
            return $description;
        }

        if ($this->position == 'content'
            && $brandPage->getSeoPosition() == BrandPageConfig::PRODUCT_LIST_SEO_POSITION) {
            return $description;
        }

        return false;
    }
}
