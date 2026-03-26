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

namespace Mirasvit\LayeredNavigation\Plugin\Frontend\Swatches;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Swatches\Block\Product\Renderer\Listing\Configurable;
use Mirasvit\LayeredNavigation\Service\GroupedOptionResolver;

/**
 * Adds resolved grouped option IDs to swatch config for proper preselection.
 *
 * @see \Magento\Swatches\Block\Product\Renderer\Listing\Configurable::getJsonConfig()
 */
class AddResolvedGroupOptionsPlugin
{
    private $resolver;

    private $jsonEncoder;

    private $jsonDecoder;

    public function __construct(
        GroupedOptionResolver $resolver,
        EncoderInterface $jsonEncoder,
        DecoderInterface $jsonDecoder
    ) {
        $this->resolver    = $resolver;
        $this->jsonEncoder = $jsonEncoder;
        $this->jsonDecoder = $jsonDecoder;
    }

    public function afterGetJsonConfig(Configurable $subject, string $result): string
    {
        $product = $subject->getProduct();

        if (!$product || $product->getTypeId() !== ConfigurableType::TYPE_CODE) {
            return $result;
        }

        $resolvedOptions = $this->resolver->resolve($product);

        if (empty($resolvedOptions)) {
            return $result;
        }

        $config = $this->jsonDecoder->decode($result);
        $config['resolvedGroupOptions'] = $resolvedOptions;

        return $this->jsonEncoder->encode($config);
    }
}
