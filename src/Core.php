<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 13.09.14 at 18:35
 */
 namespace samsonos\commerce;
 use samson\core\ExternalModule;

 /**
 * Generics SamsonPHP commerce sytem core
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2014 SamsonOS
 */
class Core extends ExternalModule
{
    public $id = 'commerce_core';

    /** Module connection handler */
    public function prepare()
    {
        // Create order table SQL
        $orders = 'CREATE TABLE IF NOT EXISTS `order` (
          `order_id` int(11) NOT NULL AUTO_INCREMENT,
          `client_id` int(11) NOT NULL,
          `sum` float NOT NULL,
          `status` int(11) NOT NULL,
          `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`order_id`),
          KEY `client_id` (`client_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

        // Create payments table SQL
        $payment = 'CREATE TABLE IF NOT EXISTS `payment` (
          `payment_id` int(11) NOT NULL AUTO_INCREMENT,
          `order_id` int(11) NOT NULL,
          `client_id` int(11) NOT NULL,
          `sum` float NOT NULL,
          `status` tinyint(2) NOT NULL,
          `response` varchar(512) NOT NULL,
          `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`payment_id`),
          KEY `client_id` (`client_id`),
          KEY `order_id` (`order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

        return parent::prepare();
    }
}
 