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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Block;


use Magento\Framework\View\Element\Template;
use Mirasvit\CatalogLabel\Model\ConfigProvider;

class Ajax extends Template
{
    private $configProvider;

    private $context;

    public function __construct(
        ConfigProvider $configProvider,
        Template\Context $context,
        array $data = []
    ) {
        $this->configProvider = $configProvider;
        $this->context        = $context;

        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        if (!$this->configProvider->isApplyForChild() || $this->isIgnoredPage()) {
            return '';
        }

        return parent::_toHtml();
    }

    private function isIgnoredPage(): bool
    {
        return $this->configProvider->isIgnoredPage(
            $this->context->getRequest()->getFullActionName(),
            $this->getCurrentUrl()
        );
    }

    private function getCurrentUrl(): string
    {
        $baseUrl    = $this->context->getUrlBuilder()->getBaseUrl();
        $currentUrl = $this->context->getUrlBuilder()->getCurrentUrl();

        return str_replace($baseUrl, '', $currentUrl);
    }

    public function getRequestUrl(): string
    {
        return $this->getUrl('cataloglabel/label/ajax');
    }
}
