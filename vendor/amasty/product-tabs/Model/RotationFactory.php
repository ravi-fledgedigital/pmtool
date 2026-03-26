<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model;

class RotationFactory
{
    // @codingStandardsIgnoreLine
    public const NAME = 'Magento\TargetRule\Block\DataProviders\Rotation';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManagerInterface;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManagerInterface
    ) {
        $this->objectManagerInterface = $objectManagerInterface;
    }

    /**
     * @return null|mixed
     */
    public function get()
    {
        $result = null;
        if (class_exists(self::NAME)) {
            $result = $this->objectManagerInterface->get(self::NAME);
        }

        return $result;
    }
}
