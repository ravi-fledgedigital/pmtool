<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\Filter\DirectiveProcessor;

use Magento\Framework\Filter\DirectiveProcessor\Filter\FilterApplier;
use Magento\Framework\Filter\DirectiveProcessorInterface;
use Magento\Framework\Filter\Template;
use Magento\Framework\Filter\VariableResolverInterface;

/**
 * @see \Magento\Framework\Filter\DirectiveProcessor\VarDirective
 */
class InlineCssDirective implements DirectiveProcessorInterface
{
    /**
     * @var VariableResolverInterface
     */
    private $variableResolver;

    /**
     * @var FilterApplier
     */
    private $filterApplier;

    public function __construct(
        VariableResolverInterface $variableResolver,
        FilterApplier $filterApplier
    ) {
        $this->variableResolver = $variableResolver;
        $this->filterApplier = $filterApplier;
    }

    public function process(array $construction, Template $filter, array $templateVariables): string
    {
        if (empty($construction[2])) {
            return $construction[0];
        }

        $result = (string) $this->variableResolver->resolve($construction[2], $filter, $templateVariables);

        if (isset($construction['filters']) && strpos($construction['filters'], '|') !== false) {
            $result = $this->filterApplier->applyFromRawParam($construction['filters'], $result);
        }

        return $result;
    }

    public function getRegularExpression(): string
    {
        return '/{{(var)(\s+(?:template_styles)*?)(?P<filters>(?:\|[a-z0-9:_-]+)+)?}}/si';
    }
}
