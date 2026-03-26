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

namespace Mirasvit\Brand\Model\Brand\PostData;


use Mirasvit\Brand\Api\Data\BrandPageInterface;
use Mirasvit\Brand\Api\Data\PostData\ProcessorInterface;
use Mirasvit\Brand\Repository\BrandRepository;

class StoresProcessor implements ProcessorInterface
{
    private $brandRepository;

    public function __construct(BrandRepository $brandRepository)
    {
        $this->brandRepository = $brandRepository;
    }

    public function preparePostData(array $data): array
    {
        if (isset($data[BrandPageInterface::STORE_IDS]) && is_array($data[BrandPageInterface::STORE_IDS])) {
            $data[BrandPageInterface::STORE_IDS] = implode(',', $data[BrandPageInterface::STORE_IDS]);
        } 

        return $data;
    }
}
