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


namespace Mirasvit\LayeredNavigation\Block\Adminhtml\Config;


class ProductAttributeLinking extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    protected function _getHeaderCommentHtml($element) {
        $imageUrl = $this->getViewFileUrl('Mirasvit_LayeredNavigation::images/product-attribute-linking.png');
        $comment = "If enabled, for each filterable attribute&nbsp;value on the&nbsp;product view page
                    the&nbsp;extension will generate the&nbsp;link with the&nbsp;filter by that attribute";

        $html = '<div class="mst-nav_product-attribute-linking_comment">'
            . '<div class="mst-nav_product-attribute-linking_label">' . $comment . '</div>'
            . '<div class="mst-nav_product-attribute-linking_image"><img src="' . $imageUrl . '"/></div>'
            . '</div>';
        return $html;
    }
}
