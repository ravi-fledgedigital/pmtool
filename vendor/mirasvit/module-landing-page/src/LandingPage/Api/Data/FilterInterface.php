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

namespace Mirasvit\LandingPage\Api\Data;

interface FilterInterface
{
    const MAIN_TABLE = 'mst_landing_page_filter';

    const PAGE_ID        = 'page_id';
    const ID             = 'filter_id';
    const ATTRIBUTE_ID   = 'attribute_id';
    const ATTRIBUTE_CODE = 'attribute_code';
    const OPTION_IDS     = 'option_ids';


    public function getPageId(): int;

    public function setPageId(int $value): self;

    public function getAttributeId(): int;

    public function setAttributeId(int $value): self;

    public function getAttributeCode(): string;

    public function setAttributeCode(string $value): self;

    public function getOptionIds(): string;

    public function setOptionIds(string $value): self;

}
