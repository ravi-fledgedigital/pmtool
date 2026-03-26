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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



declare(strict_types=1);

namespace Mirasvit\LandingPage\Service;

use Mirasvit\LandingPage\Block\RelatedLandingPages;
use Mirasvit\LandingPage\Model\Config\ConfigProvider;

class RelatedPagesLayoutService
{
    private $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @return string|null Layout XML or null if block should not be added.
     */
    public function getLayoutXml(string $fullActionName): ?string
    {
        if (!$this->configProvider->isRelatedPagesEnabled()) {
            return null;
        }

        $contextMap = [
            'catalog_product_view'  => ['context' => 'product',  'position' => $this->configProvider->getProductPosition()],
            'catalog_category_view' => ['context' => 'category', 'position' => $this->configProvider->getCategoryPosition()],
            'landing_landing_view'  => ['context' => 'landing',  'position' => $this->configProvider->getLandingPosition()],
        ];

        if (!isset($contextMap[$fullActionName])) {
            return null;
        }

        $config   = $contextMap[$fullActionName];
        $position = $config['position'];

        if ($position === '') {
            return null;
        }

        $parsed = $this->decodePosition($position);

        return $this->buildXml($config['context'], $parsed['container'], $parsed['before'], $parsed['after']);
    }

    /**
     * @return array{container: string, before: string, after: string}
     */
    public function decodePosition(string $position): array
    {
        $parts     = explode('/', $position);
        $container = $parts[0];
        $modifier  = isset($parts[1]) ? $parts[1] : '';

        if ($modifier === '-') {
            return ['container' => $container, 'before' => '-', 'after' => ''];
        }

        return ['container' => $container, 'before' => '', 'after' => '-'];
    }

    private function buildXml(string $context, string $container, string $before, string $after): string
    {
        $beforeAttr = $before !== '' ? sprintf('before="%s"', $before) : '';
        $afterAttr  = $after !== '' ? sprintf('after="%s"', $after) : '';

        return sprintf(
            '<body><referenceContainer name="%s">'
            . '<container name="mst.related.landing.pages.wrapper.%s" htmlTag="div" htmlClass="mst-landing-page-related" %s %s>'
            . '<block class="%s" name="mst.related.landing.pages.%s">'
            . '<arguments><argument name="context" xsi:type="string">%s</argument></arguments>'
            . '</block>'
            . '</container>'
            . '</referenceContainer></body>',
            $container,
            $context,
            $beforeAttr,
            $afterAttr,
            RelatedLandingPages::class,
            $context,
            $context
        );
    }
}
