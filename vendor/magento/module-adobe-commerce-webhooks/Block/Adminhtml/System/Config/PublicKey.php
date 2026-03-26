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
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * The public key field
 */
class PublicKey extends Field
{
    /**
     * Path to template file
     *
     * @var string
     */
    protected $_template = 'Magento_AdobeCommerceWebhooks::system/config/public_key.phtml';

    /**
     * @param Context $context
     * @param Config $config
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        private Config $config,
        private EncryptorInterface $encryptor
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
     * Returns the public key
     *
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->encryptor->decrypt($this->config->getDigitalSignaturePublicKey());
    }
}
