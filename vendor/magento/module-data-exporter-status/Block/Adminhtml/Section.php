<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2025 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\DataExporterStatus\Block\Adminhtml;

use Magento\Backend\Block\Template;

/**
 * @api
 */
class Section extends Template
{
    /**
     * Gets the section title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->_getData('title');
    }

    /**
     * Gets the section content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->toHtml();
    }

    /**
     * Gets the tab ID
     *
     * @return string
     */
    public function getTabId(): string
    {
        return $this->getNameInLayout();
    }

    /**
     * Indicates whether the section is open
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->_getData('sectionOpen') ?? true;
    }
}
