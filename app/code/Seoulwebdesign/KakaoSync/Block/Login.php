<?php
/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Block;

use Magento\Framework\View\Element\Template\Context;
use Seoulwebdesign\KakaoSync\Helper\ConfigHelper;

class Login extends \Magento\Framework\View\Element\Template
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        $this->configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get redirect auth link
     *
     * @return string
     */
    public function getRediectOAuthLink()
    {
        return $this->configHelper->getRedirectUrl();
    }

    /**
     * Get config helper
     *
     * @return ConfigHelper
     */
    public function getConfig()
    {
        return $this->configHelper;
    }
}
