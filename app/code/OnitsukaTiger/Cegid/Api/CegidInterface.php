<?php

namespace OnitsukaTiger\Cegid\Api;

use OnitsukaTiger\Cegid\Api\Response\ProductEanCodeResponseInterface;

interface CegidInterface
{
    /**
     * API product sync EAN Code from Cegid
     * @return ProductEanCodeResponseInterface
     */
    public function productEanCode(): ProductEanCodeResponseInterface;

    /**
     * API Get Content Invoice Pdf from Cegid
     *
     * @return string
     */
    public function getInvoicePdf(): string;

    /**
     * API Get Content AWB Pdf from Cegid
     *
     * @return string
     */
    public function getAWBFilePdf():string;
}
