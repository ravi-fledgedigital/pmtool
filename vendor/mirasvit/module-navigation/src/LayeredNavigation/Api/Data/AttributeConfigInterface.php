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

namespace Mirasvit\LayeredNavigation\Api\Data;

use Mirasvit\LayeredNavigation\Model\AttributeConfig\OptionConfig;

interface AttributeConfigInterface
{
    const TABLE_NAME = 'mst_navigation_attribute_config';

    const DISPLAY_MODE_LABEL          = 'label';
    const DISPLAY_MODE_SLIDER         = 'slider';
    const DISPLAY_MODE_FROM_TO        = 'from-to';
    const DISPLAY_MODE_SLIDER_FROM_TO = self::DISPLAY_MODE_SLIDER . '+' . self::DISPLAY_MODE_FROM_TO;
    const DISPLAY_MODE_RANGE          = 'range';
    const DISPLAY_MODE_DROPDOWN       = 'dropdown';

    const SLIDER_STEP = 'slider_step';

    const POSITION_HORIZONTAL = 'horizontal';
    const POSITION_SIDEBAR    = 'sidebar';
    const POSITION_BOTH       = 'both';

    const POSITION            = 'filter_position';
    const HORIZONTAL_POSITION = 'horizontal_position';

    const TOOLTIP = 'tooltip';

    const CATEGORY_VISIBILITY_MODE_ALL              = 'all';
    const CATEGORY_VISIBILITY_MODE_SHOW_IN_SELECTED = 'show_in_selected';
    const CATEGORY_VISIBILITY_MODE_HIDE_IN_SELECTED = 'hide_in_selected';

    const OPTION_SORT_BY_POSITION      = 'position';
    const OPTION_SORT_BY_LABEL         = 'label';
    const OPTION_SORT_BY_COUNT         = 'count';
    const OPTION_SORT_BY_NATURAL_LABEL = 'natural_label';

    const ID             = 'config_id';
    const ATTRIBUTE_ID   = 'attribute_id';
    const ATTRIBUTE_CODE = 'attribute_code';
    const CONFIG         = 'config';

    const OPTIONS_CONFIG = 'options';

    const OPTIONS_SORT_BY    = 'options_sort_by';
    const ALPHABETICAL_INDEX = 'alphabetical_index';
    const DISPLAY_MODE       = 'display_mode';
    const VALUE_TEMPLATE     = 'value_template';
    const IS_SHOW_SEARCH_BOX = 'is_show_search_box';

    const SEARCH_VISIBILITY_MODE   = 'search_visibility_mode';
    const CATEGORY_VISIBILITY_MODE = 'category_visibility_mode';
    const CATEGORY_VISIBILITY_IDS  = 'category_visibility_ids';

    const ENABLE_MULTISELECT = 'enable_multiselect';
    const MULTISELECT_LOGIC  = 'multiselect_logic';

    const MULTISELECT_LOGIC_OR  = 0;
    const MULTISELECT_LOGIC_AND = 1;

    public function getId(): ?int;

    public function getAttributeId(): int;

    public function setAttributeId(int $value): self;

    public function getAttributeCode(): string;

    public function setAttributeCode(string $value): self;

    public function getConfig(): array;

    public function setConfig(array $value): self;

    /** @return OptionConfig[] */
    public function getOptionsConfig(): array;

    /** @param OptionConfig[] $value */
    public function setOptionsConfig(array $value): self;

    public function getSearchVisibilityMode(): string;

    public function setSearchVisibilityMode(string $value): self;

    public function getCategoryVisibilityMode(): string;

    public function setCategoryVisibilityMode(string $value): self;

    public function getCategoryVisibilityIds(): array;

    public function setCategoryVisibilityIds(array $value): self;

    public function getOptionsSortBy(): string;

    public function setOptionsSortBy(string $value): self;

    public function getDisplayMode(): string;

    public function setDisplayMode(string $value): self;

    public function getValueTemplate(): string;

    public function setValueTemplate(string $value): self;

    public function getSliderStep(): int;

    public function setSliderStep(int $value): self;

    public function isShowSearchBox(): bool;

    public function setIsShowSearchBox(bool $value): self;

    public function isMultiselectEnabled(): ?bool;

    public function setIsMultiselectEnabled(int $value): self;

    public function getMultiselectLogic(): int;

    public function setMultiselectLogic(int $value): self;

    public function getTooltip(): string;

    public function setTooltip(string $value): self;

    public function getPosition(): string;

    public function setPosition(string $value): self;

    public function getHorizontalPosition(): int;

    public function setHorizontalPosition(int $value): self;
}
