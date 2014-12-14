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
        $sqlOrders = 'CREATE TABLE IF NOT EXISTS `order` (
          `OrderId` int(11) NOT NULL AUTO_INCREMENT,
          `CompanyId` int(11) NOT NULL,
          `ClientId` int(11) NOT NULL,
          `Total` float NOT NULL,
          `Currency` VARCHAR( 64 ) NOT NULL,
          `Status` int(11) NOT NULL,
          `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`OrderId`),
          KEY `ClientId` (`ClientId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

        // Create order table SQL
        $sqlOrderLog = 'CREATE TABLE IF NOT EXISTS `order_log` (
          `OrderLogId` int(11) NOT NULL AUTO_INCREMENT,
          `OrderId` int(11) NOT NULL,
          `UserId` int(11) NOT NULL,
          `Status` int(11) NOT NULL,
          `Comment` varchar(512) NOT NULL,
          `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`OrderLogId`),
          KEY `OrderId` (`OrderId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

        // Create payments table SQL
        $sqlPayment = 'CREATE TABLE IF NOT EXISTS `payment` (
          `PaymentId` int(11) NOT NULL AUTO_INCREMENT,
          `OrderId` int(11) NOT NULL,
          `Gate` varchar(50) NOT NULL,
          `Amount` float NOT NULL,
          `Currency` VARCHAR( 64 ) NOT NULL,
          `Status` tinyint(2) NOT NULL,
          `Response` varchar(512) NOT NULL,
          `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`PaymentId`),
          KEY `OrderId` (`OrderId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

        // Create payments table SQL
        $sqlPaymentLog = 'CREATE TABLE IF NOT EXISTS `payment_log` (
          `PaymentLogId` int(11) NOT NULL AUTO_INCREMENT,
          `PaymentID` int(11) NOT NULL,
          `Status` tinyint(2) NOT NULL,
          `Comment` varchar(512) NOT NULL,
          `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`PaymentLogId`),
          KEY `PaymentID` (`PaymentID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

        // Create payments table SQL
        $sqlOrderItem = 'CREATE TABLE IF NOT EXISTS `order_item` (
          `OrderItemId` int(11) NOT NULL AUTO_INCREMENT,
          `OrderId` int(11) NOT NULL,
          `MaterialID` int(11) NOT NULL,
          `Price` float NOT NULL,
          `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`OrderItemId`),
          KEY `MaterialID` (`MaterialID`),
          KEY `OrderId` (`OrderId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

        db()->simple_query($sqlOrders);
        db()->simple_query($sqlPayment);
        db()->simple_query($sqlOrderItem);
        db()->simple_query($sqlPaymentLog);
        db()->simple_query($sqlOrderLog);


        return parent::prepare();
    }
}
 