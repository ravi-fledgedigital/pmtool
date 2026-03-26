<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;

/**
 * Template db resource
 *
 * @api
 */
class Template extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public const MAIN_TABLE = 'amasty_pdf_template';

    /**
     * Initialize email template resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, 'template_id');
    }

    /**
     * Check usage of template code in other templates
     *
     * @param \Amasty\PDFCustom\Model\Template $template
     * @return bool
     */
    public function checkCodeUsage(\Amasty\PDFCustom\Model\Template $template)
    {
        if ($template->getTemplateActual() != 0 || $template->getTemplateActual() === null) {
            $select = $this->getConnection()->select()->from(
                $this->getMainTable(),
                'COUNT(*)'
            )->where(
                'template_code = :template_code'
            );
            $bind = ['template_code' => $template->getTemplateCode()];

            $templateId = $template->getId();
            if ($templateId) {
                $select->where('template_id != :template_id');
                $bind['template_id'] = $templateId;
            }

            $result = $this->getConnection()->fetchOne($select, $bind);
            if ($result) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param int $place
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTemplatesDataByPlace($place)
    {
        $bind = [
            'place_id' => $place
        ];
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            ['template_id', 'store_ids', 'customer_group_ids']
        )->where(
            'place_for_use = :place_id'
        )->order('template_id ASC');

        return $this->getConnection()->fetchAll($select, $bind);
    }
}
