<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Block\Adminhtml\System\Config;

use Magento\AdobeCommerceWebhooks\Model\Config\System\Config;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

/**
 * Button to generate keys pair
 */
class GenerateKeysButton extends Field
{
    /**
     * Path to template file
     *
     * @var string
     */
    protected $_template = 'Magento_AdobeCommerceWebhooks::system/config/generate_keys.phtml';

    /**
     * @param Context $context
     * @param Config $config
     */
    public function __construct(
        Context $context,
        private Config $config,
    ) {
        parent::__construct($context);
    }

    /**
     * Returns element html
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * Generates button html
     *
     * @return string
     * @throws LocalizedException
     */
    public function getButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock(
            Button::class
        )->setData(
            [
                'id' => 'generate_keys',
                'label' => $this->isKeyPairGenerated() ? __('Regenerate key pair') : __('Generate key pair')
            ]
        );

        return $button->toHtml();
    }

    /**
     * Returns url to generate keys
     *
     * @return string
     */
    public function getGenerateKeysUrl(): string
    {
        return $this->getUrl('webhooks/keys/generate');
    }

    /**
     * Checks if key pair is generated
     *
     * @return bool
     */
    public function isKeyPairGenerated(): bool
    {
        return !empty($this->config->getDigitalSignaturePublicKey());
    }
}
