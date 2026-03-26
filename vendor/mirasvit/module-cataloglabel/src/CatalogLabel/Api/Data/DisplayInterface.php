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




namespace Mirasvit\CatalogLabel\Api\Data;


interface DisplayInterface
{
    const TABLE_NAME = 'mst_productlabel_label_display';

    const ID             = 'display_id';
    const TYPE           = 'type';
    const LABEL_ID       = 'label_id';
    const PLACEHOLDER_ID = 'placeholder_id';
    const TEMPLATE_ID    = 'template_id';
    const ATTR_OPTION_ID = 'attribute_option_id';
    const TITLE          = 'title';
    const DESCRIPTION    = 'description';
    const IMAGE_PATH     = 'image_path';
    const URL            = 'url';
    const STYLE          = 'style';

    const TYPE_LIST = 'list';
    const TYPE_VIEW = 'view';

    public function getType(): string;

    public function setType(string $value): self;

    public function getLabelId(): int;

    public function setLabelId(int $value): self;

    public function getLabel(): LabelInterface;

    public function getPlaceholderId(): ?int;

    public function setPlaceholderId(int $value): self;

    public function getPlaceholder(): ?PlaceholderInterface;

    public function getTemplateId(): ?int;

    public function setTemplateId(int $value): self;

    public function getTemplate(): ?TemplateInterface;

    public function getTitle(): string;

    public function setTitle(string $value): self;

    public function getDescription(): string;

    public function setDescription(string $value): self;

    public function getImagePath(): ?string;

    public function setImagePath(string $value): self;

    public function getUrl(): ?string;

    public function setUrl(string $value): self;

    public function getStyle(): ?string;

    public function setStyle(string $value): self;

    public function getAttributeOptionId(): string;

    public function setAttributeOptionId(string $value): self;
}
