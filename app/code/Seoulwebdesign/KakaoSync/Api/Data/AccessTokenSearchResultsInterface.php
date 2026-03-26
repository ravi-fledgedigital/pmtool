<?php
/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Api\Data;

interface AccessTokenSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get access_token list.
     *
     * @return \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface[]
     */
    public function getItems();

    /**
     * Set token_type list.
     *
     * @param \Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
