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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Service;

use Magento\Cms\Model\Template\FilterProvider as CmsFilterProvider;
use Magento\Email\Model\TemplateFactory as EmailTemplateFactory;
use Magento\Framework\DataObject;
use Mirasvit\Core\Helper\ParseVariables;

class ContentService
{
    private $emailTemplateFactory;

    private $filterProvider;

    /** @var array */
    private $vars = [];

    public function __construct(
        EmailTemplateFactory $emailTemplateFactory,
        CmsFilterProvider $filterProvider
    ) {
        $this->emailTemplateFactory = $emailTemplateFactory;
        $this->filterProvider       = $filterProvider;
    }

    public function processHtmlContent(string $html, array $vars = [])
    {
        $template = $this->emailTemplateFactory->create();

        $this->prepareVariables($vars);

        $html = $this->prepareContent($html);

        $template->setTemplateText($html)
            ->setData('is_plain', false);

        try {
            $processed = $template->getProcessedTemplate($this->vars);
        } catch (\Exception $e) {
            $processed = '';
        }

        return htmlspecialchars_decode($processed);
    }

    public function prepareVariables(array $vars): void
    {
        $this->vars = array_merge($this->vars, $vars);
    }

    private function prepareContent(string $html): string
    {
        $html = preg_replace_callback('/\{\{([^\}]*)\}\}/s', function ($match) {
            if (!trim($match[1])) {
                return ''; // replace empty variables like {{ }}
            }

            // trim variable body to prevent warnings Undefined array key "directiveName"
            return str_replace($match[1], trim($match[1]), $match[0]);
        }, $html);

        return $html;
    }
}
