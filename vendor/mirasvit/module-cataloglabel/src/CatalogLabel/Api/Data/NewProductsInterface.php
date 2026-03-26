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

namespace Mirasvit\CatalogLabel\Api\Data;

interface NewProductsInterface
{
    const TABLE_NAME = 'mst_productlabel_new_product';

    const ID         = 'id';
    const PRODUCT_ID = 'product_id';

    public function getId(): ?int;

    public function getProductId(): int;

    public function setProductId(int $value): self;
}
