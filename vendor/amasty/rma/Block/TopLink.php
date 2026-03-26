<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block;

use Magento\Customer\Block\Account\SortLinkInterface;
use Magento\Framework\View\Element\Html\Link;

class TopLink extends Link implements SortLinkInterface
{
    /**
     * @var \Amasty\Rma\Model\ConfigProvider
     */
    private $configProvider;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Amasty\Rma\Model\ConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
    }

    public function toHtml(): string
    {
        if (!$this->configProvider->isEnabled()) {
            return '';
        }

        return parent::toHtml();
    }

    public function getPath(): string
    {
        if (!$this->getData('path')) {
            $this->setData('path', $this->configProvider->getUrlPrefix() . '/account/history');
        }

        return $this->getData('path');
    }

    public function getLabel(): string
    {
        return (string)__('My Returns');
    }

    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }
}
