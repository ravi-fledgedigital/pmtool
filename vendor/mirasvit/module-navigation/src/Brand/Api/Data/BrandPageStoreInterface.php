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

namespace Mirasvit\Brand\Api\Data;

interface BrandPageStoreInterface
{
    const TABLE_NAME  = 'mst_brand_page_store';
    const TABLE_STORE = 'store';

    const ID                      = 'id';
    const BRAND_PAGE_ID           = 'brand_page_id';
    const STORE_ID                = 'store_id';
    const URL_KEY                 = 'url_key';
    const BRAND_TITLE             = 'brand_title';
    const BRAND_DESCRIPTION       = 'brand_description';
    const BRAND_SHORT_DESCRIPTION = 'brand_short_description';
    const BRAND_DISPLAY_MODE      = 'brand_display_mode';
    const BRAND_CMS_BLOCK         = 'brand_cms_block';
    
    const BRAND_META_TITLE        = 'meta_title';
    const BRAND_META_KEYWORDS     = 'meta_keyword';
    const BRAND_META_DESCRIPTION  = 'meta_description';
    const BRAND_SEO_DESCRIPTION   = 'seo_description';
    const BRAND_SEO_POSITION      = 'seo_position';
    const BRAND_CANONICAL_URL     = 'canonical';
    const BRAND_ROBOTS            = 'robots';

    const STORE                   = 'store';
    const STORES                  = 'stores_data';

    const STORE_FIELDS = [
        self::BRAND_DISPLAY_MODE,
        self::BRAND_CMS_BLOCK,
    ];

    /**
     * @return int
     */
    public function getId();

    /**
     * @return int
     */
    public function getBrandPageId();

    /**
     * @param string $value
     * @return $this
     */
    public function setBrandPageId($value);

    /**
     * @return int
     */
    public function getStoreId();

    /**
     * @param string $value
     * @return $this
     */
    public function setStoreId($value);
}
