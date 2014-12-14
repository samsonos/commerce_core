<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 13.09.14 at 18:56
 */
 namespace samsonos\commerce;

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

 public function updateStatus($status, $comment = '')
 {
  $this->satus = $status;
  $log = new PaymentLog(false);
  $log->PaymentId = $this->id;
  $log->Comment = $comment;
  $log->Status = $status;
  $log->save();

  $order = null;
  if (dbQuery('samsonos\commerce\Order')->OrderId($this->OrderID)->first($order)) {
   if ($status == self::STATUS_SUCCESS) {
    $order->updateSatatus();
   } elseif($status == self::STATUS_FAIL) {
    $order->updateSatatus();
   }

  }


 }
}
 