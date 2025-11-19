<?php

namespace Asaas\Magento2\Api;

/**
 * @api
 */
interface UpdateStatusesInterface
{
   /**
     * Update Order Status.
     *
     * @api
     * @param  mixed $event
     * @param  mixed $payment
     * @return  mixed
     */
    public function doUpdate($event,$payment);
}