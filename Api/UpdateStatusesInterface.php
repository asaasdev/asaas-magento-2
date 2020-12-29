<?php
namespace Asaas\Magento2\Api;
//use Asaas\Magento2\Api\Data\PointInterface;
/**
 * @api
 */
interface UpdateStatusesInterface
{
   /**
     * Post Company.
     *
     * @api
     * @param  mixed $event
     * @param  mixed $payment
     * @return  mixed
     */
    public function doUpdate($event,$payment);
}