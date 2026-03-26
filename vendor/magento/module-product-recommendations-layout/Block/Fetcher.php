<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductRecommendationsLayout\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Fetcher extends Template
{
    /**
     * Config Paths
     * @var string
     */
    const CONFIG_PATH_ALTERNATE_ENVIRONMENT_ENABLED = 'services_connector/product_recommendations/alternate_environment_enabled';
    const CONFIG_PATH_ALTERNATE_ENVIRONMENT_ID = 'services_connector/product_recommendations/alternate_environment_id';

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
    
    /**
     * Get alternate environment id to fetch recommendations
     *
     * @return string
     */
    public function getAlternateEnvironmentId(): string
    {
        $alternateEnvironmentId = "";
        if ($this->_scopeConfig->getValue(self::CONFIG_PATH_ALTERNATE_ENVIRONMENT_ENABLED)) {
            $alternateEnvironmentId = $this->_scopeConfig->getValue(self::CONFIG_PATH_ALTERNATE_ENVIRONMENT_ID);
        }
        return $alternateEnvironmentId;
    }
}
