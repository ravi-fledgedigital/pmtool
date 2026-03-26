<?php


namespace OnitsukaTiger\NetSuite\Api\Response;


interface ShippedResponseInterface extends ResponseInterface
{
    /**
     * @return string
     */
    public function getShippingId();

    /**
     * @param string $shippingId
     * @return void
     */
    public function setShippingId(string $shippingId);

    /**
     * @return string
     */
    public function getInvoiceNo();

    /**
     * @param string $invoiceNo
     * @return void
     */
    public function setInvoiceNo(string $invoiceNo);

    /**
     * @return string
     */
    public function getAwb();

    /**
     * @param string $awb
     * @return void
     */
    public function setAwb(string $awb);
}
