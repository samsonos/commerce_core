<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 13.09.14 at 18:56
 */
 namespace samsonos\commerce;

 use samson\core\Event;

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

    public function __construct( $order = null, $gate = '', $amount = null )
    {
        if (is_object($order)) {
            if (get_class($order) === 'samsonos\commerce\Order') {
                parent::__construct(false);
                $this->OrderId = $order->id;
                $this->Gate = $gate;
                $this->Currency = $order->Currency;
                $this->Amount = (isset($amount))?$amount:$order->Total;
                $order = null;
            }
        }
        parent::__construct($order);
    }

    public function updateStatus($status, $comment = '')
    {
        $this->satus = $status;
        $log = new PaymentLog(false);
        $log->PaymentId = $this->id;
        $log->Comment = $comment;
        $log->Status = $status;
        $log->save();

        $order = null;
        if ($status == self::STATUS_SUCCESS) {
            Event::fire('commerce.update.status', array('Order', $this->OrderID, $status, $comment));
        } elseif($status == self::STATUS_FAIL) {
            Event::fire('commerce.update.status', array('Order', $this->OrderID, $status, $comment));
        }
    }
}
 