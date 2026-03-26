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


use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Mirasvit\CatalogLabel\Model;

interface LabelInterface
{
    const TABLE_NAME = 'mst_productlabel_label';

    const ID                    = 'label_id';
    const TYPE                  = 'type';
    const ATTRIBUTE_ID          = 'attribute_id';
    const NAME                  = 'name';
    const ACTIVE_FROM           = 'active_from';
    const ACTIVE_TO             = 'active_to';
    const SORT_ORDER            = 'sort_order';
    const IS_ACTIVE             = 'is_active';
    const STORE_IDS             = 'store_ids';
    const CUSTOMER_GROUP_IDS    = 'customer_group_ids';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const CONDITIONS_SERIALIZED = 'conditions_serialized';
//    const ACTIONS_SERIALIZED    = 'actions_serialized';
//    const STOP_RULES_PROCESSING = 'stop_rules_processing';

    // Labels configurations and appearence modes
    const APPEARENCE      = 'appearence';
    const APPEARENCE_LIST = 'list'; // listing only
    const APPEARENCE_VIEW = 'view'; // view page only
    const APPEARENCE_BOTH = 'both'; // same label for listing and view
    // for sepparate labels in listing and view - list,view (default)

    const TYPE_ATTRIBUTE = 'attribute';
    const TYPE_RULE      = 'rule';
    const CACHE_TAG      = 'cataloglabel_label';

    public function getType(): string;

    public function setType(string $type): self;

    public function getAttributeId(): int;

    public function setAttributeId(int $attrId): self;

    public function getName(): string;

    public function setName(string $name): self;

    public function getActiveFrom(): ?string;

    public function setActiveFrom(string $from): self;

    public function getActiveTo(): ?string;

    public function setActiveTo(string $to): self;

    public function getIsActive(): bool;

    public function setIsActive(bool $isActive): self;

    public function getStoreIds(): array;

    public function setStoreIds(array $storeIds): self;

    public function getCustomerGroupIds(): array;

    public function setCustomerGroupIds(array $ids): self;

    public function getAppearence(): string;

    /**
     * @return Model\Label\Display[]
     */
    public function getDisplaysByProduct(ProductInterface $product): array;
}
