<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 13.09.14 at 18:35
 */
 namespace samsonos\commerce;

 use samson\activerecord\TableRelation;
 use samson\core\CompressableService;
 use samson\core\Config;
 use samson\core\Event;

 /**
 * Generics SamsonPHP commerce sytem core
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2014 SamsonOS
 */
class Core extends CompressableService
{
    public $id = 'commerce';

    public $sessionKay = '__samsonos_commerce_order_key';

    public $productClass = '\samson\cms\CMSMaterial';

    public $productCompanyField = 'CompanyId';

    public $productPriceField = 'Price';

	public $defaultCurrency = 'UAH';

    private $gates = array();

    /** Module connection handler */
    public function prepare()
    {
        // Create order table SQL
        $sqlOrders = 'CREATE TABLE IF NOT EXISTS `order` (
          `OrderId` int(11) NOT NULL AUTO_INCREMENT,
          `Key` VARCHAR( 32 ) NOT NULL,
          `CompanyId` int(11) NOT NULL,
          `ClientId` int(11) NOT NULL,
          `Total` float NOT NULL,
          `Currency` VARCHAR( 64 ) NOT NULL,
          `Status` int(11) NOT NULL,
          `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`OrderId`),
          KEY `ClientId` (`ClientId`),
          KEY `Key` (`Key`)
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
          `MaterialId` int(11) NOT NULL,
          `Price` float NOT NULL,
          `Count` int(11) NOT NULL DEFAULT 0,
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

	    new TableRelation('order', 'order_item', 'OrderId', TableRelation::T_ONE_TO_MANY);

        return parent::prepare();
    }

    public function init(array $params=array())
    {
        parent::init($params);

        Event::subscribe('commerce.gateinited',array($this, 'addGate'));
        Event::subscribe('commerce.update.status',array($this, 'updateStatus'));
        Event::subscribe('commerce.init.module.commerce.core',array($this, 'initCommerceCore'));
    }

    public function addGate($gate)
    {
        $this->gates[$gate->id] = & $gate;
    }

    public function updateStatus($class, $objectId, $status, $comment)
    {
        $obj = new $class($objectId);
        if (method_exists($obj, 'updateStatus')){
            $obj->updateStatus($status, $comment);
        }
    }

    public function initCommerceCore($module)
    {
	    $module->commerceCore = & $this;
    }

    public function createPayment(Order $order, $gate, $amount = null)
    {
        $payment = new Payment($order, $gate, $amount);
        Event::fire('commerce.payment.created', array( & $payment));
        return $payment;
    }

    public function addOrderItem($productId, $count = 1)
    {
        $product = null;
        if (dbQuery($this->productClass)->id($productId)->first($product)) {
            $order = null;
            if (!isset($_SESSION[$this->sessionKay][$product[$this->productCompanyField]])) {
                $order = new Order();
	            $order->Currency = $this->defaultCurrency;
                $order->CompanyId = $product[$this->productCompanyField];
                $_SESSION[$this->sessionKay][$product[$this->productCompanyField]] = $order->Key;
            } else {
	            Order::byURL($_SESSION[$this->sessionKay][$product[$this->productCompanyField]], $order);
            }
            if (isset($order)) {
                $orderItem = null;
                $orderItemQuery = dbQuery('\samsonos\commerce\OrderItem')->MaterialId($productId)->OrderId($order->id);
                if (!$orderItemQuery->first($orderItem)) {
                    $orderItem = new OrderItem(false);
                    $orderItem->OrderId = $order->id;
                    $orderItem->MaterialID = $productId;
                }
                $orderItem->Count += $count;
                $orderItem->Price = $product[$this->productPriceField];
                $orderItem->save();
                return $order;
            }
        }
        return false;
    }

    public function createForm($payment)
    {
        if (isset($this->gates[$payment->Gate])) {
            return $this->gates[$payment->Gate]->createForm($payment);
        }
        return false;
    }

    public  function clearOrders()
    {
        $_SESSION[$this->sessionKay] = array();
    }

	public function ordersList()
	{
		$orders = array();
		if (isset($_SESSION[$this->sessionKay]) && sizeof($_SESSION[$this->sessionKay])) {
			if (dbQuery('\samsonos\commerce\Order')->Key($_SESSION[$this->sessionKay])->join('order_item', '\samsonos\commerce\OrderItem')->exec($orders)) {
				foreach ($orders as & $order) {
					if (isset($order['onetomany']['_order_item'])) {
						$order->items = $order['onetomany']['_order_item'];
						unset($order['onetomany']['_order_item']);
						$productIdList = array();
						foreach($order->items as $item) {
							$productIdList[] = $item->MaterialId;
						}
						$productList = array();
						if (dbQuery($this->productClass)->id($productIdList)->exec($productList)) {
							foreach($order->items as & $item) {
								$item->Product = $productList[$item->MaterialId];
							}
						}
					}
				}
			}
		}
		return $orders;
	}

}
 