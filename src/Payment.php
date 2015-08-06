<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 13.09.14 at 18:56
 */
 namespace samsonos\commerce;

 use samsonphp\event\Event;

 /**
 * Generic Paymet SamsonPHP commerce system class
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2014 SamsonOS
 */
class Payment extends \samson\activerecord\payment
{
    // Payment statuses
    const STATUS_WAIT_PROCESSING = 113;
    const STATUS_WAIT_SECURE = 114;
    const STATUS_SUCCESS = 111;
    const STATUS_FAIL = 112;
    const STATUS_TEST_SUCCESSFUL = 115;

    /**
     * @param null $order Order
     * @param string $gate is the name of gate which uses
     * @param null $amount
     */
    public function __construct($order = null, $gate = '', $amount = null)
    {
        //If order not exists then create payment with init data
        if (is_object($order)){
            if (get_class($order) !== get_called_class()){
                parent::__construct(false);

                //Set data
                $this->OrderId = $order->id;
                $this->Gate = $gate;
                $this->Currency = $order->Currency;
                $this->OrderClass = get_class($order);

                //If amount not passed take property total in order
                $this->Amount = (isset($amount))?$amount:$order->Total;
                $order = null;
            }
        }
        parent::__construct($order);
    }
}
 