<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 13.09.14 at 18:35
 */
 namespace samsonos\commerce;

 use samson\core\CompressableService;
 use samson\core\Config;

 /**
 * Generics SamsonPHP commerce sytem core
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2014 SamsonOS
 */
class Core extends CompressableService
{
    public $id = 'commerce_core';

    /** Module connection handler */
    public function prepare()
    {
        // Create order table SQL
        $orders = 'CREATE TABLE IF NOT EXISTS `order` (
          `OrderId` int(11) NOT NULL AUTO_INCREMENT,
          `CompanyId` int(11) NOT NULL,
          `ClientId` int(11) NOT NULL,
          `sum` float NOT NULL,
          `status` int(11) NOT NULL,
          `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`order_id`),
          KEY `client_id` (`client_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

        // Create payments table SQL
        $payment = 'CREATE TABLE IF NOT EXISTS `payment` (
          `payment_id` int(11) NOT NULL AUTO_INCREMENT,
          `company_id` int(11) NOT NULL,
          `order_id` int(11) NOT NULL,
          `client_id` int(11) NOT NULL,
          `sum` float NOT NULL,
          `currency` VARCHAR( 64 ) NOT NULL AFTER  `sum`,
          `status` tinyint(2) NOT NULL,
          `response` varchar(512) NOT NULL,
          `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`payment_id`),
          KEY `client_id` (`client_id`),
          KEY `order_id` (`order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

        // Create payments table SQL
        $orderItem = 'CREATE TABLE IF NOT EXISTS `order_item` (
          `OrderItemId` int(11) NOT NULL AUTO_INCREMENT,
          `OrderId` int(11) NOT NULL,
          `MaterialID` int(11) NOT NULL,
          `Price` float NOT NULL,
          `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`OrderItemId`),
          KEY `MaterialID` (`MaterialID`),
          KEY `OrderId` (`OrderId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';


        return parent::prepare();
    }
}
 