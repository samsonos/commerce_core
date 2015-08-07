<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 13.09.14 at 18:35
 */
 namespace samsonos\commerce;

 use samson\activerecord\TableRelation;
 use samson\core\CompressableService;
 use samson\core\Config;
 use samsonphp\event\Event;

 /**
 * Generics SamsonPHP commerce system core
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2014 SamsonOS
 */
class Core extends CompressableService
{
    /** @var string module name */
    public $id = 'commerce';

    /** @var string session key uses for access to particular session name space */
    protected $sessionKey = '__samsonos_commerce_order_key';

    /** @var string is the main product class */
    protected $productClass = '\samson\cms\CMSMaterial';

    /** @var string name company field in order */
    protected $productCompanyField = 'CompanyId';

    /** @var string name price field in order */
    protected $productPriceField = 'Price';

    /** @var string default currency */
	protected $defaultCurrency = 'UAH';

    /** @var array store all gates which use for payment */
    private $gates = array();

    /**
     * Getter
     * @return string
     */
    public function getCurrency(){
        return $this->defaultCurrency;
    }

    /**
     * Prepare tables for working payment
     * @return bool
     */
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
          `Type` int(11),
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
          `OrderClass` VARCHAR (100),
          `Gate` varchar(20) NOT NULL,
          `GateCode` varchar(20) NOT NULL,
          `Amount` float NOT NULL,
          `Currency` VARCHAR( 64 ) NOT NULL,
          `Status` tinyint(2) NOT NULL,
          `Phone` varchar(32),
          `Response` text NOT NULL,
          `Description` varchar(512),
          `Created` DATETIME,
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
          `Quantity` int(11) NOT NULL DEFAULT 0,
          `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`OrderItemId`),
          KEY `MaterialID` (`MaterialID`),
          KEY `OrderId` (`OrderId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';

        //Create tables if they not exists
        db()->simple_query($sqlOrders);
        db()->simple_query($sqlPayment);
        db()->simple_query($sqlOrderItem);
        db()->simple_query($sqlPaymentLog);
        db()->simple_query($sqlOrderLog);

        //Create table relations
	    new TableRelation('order', 'order_item', 'OrderId', TableRelation::T_ONE_TO_MANY);

        return parent::prepare();
    }

    /**
     * Init module
     * @param array $params
     */
    public function init(array $params=array())
    {

        //Call parent handler
        parent::init($params);

        $this->prepare();

        //Subscribe on event for catch when some gate will be init
        Event::subscribe('commerce.gateinited',array($this, 'addGate'));

        //Subscribe on event for catch when need init commerce core
        Event::subscribe('commerce.init.module.commerce.core',array($this, 'initCommerceCore'));
    }

    /**
     * Save new gate in object
     * @param $gate
     */
    public function addGate($gate)
    {
        $this->gates[$gate->id] = & $gate;
    }

    /**
     * Save link by himself in module object
     * @param $module
     */
    public function initCommerceCore($module)
    {
	    $module->commerceCore = & $this;
    }

    /**
     * Create new payment
     * @param $order \samsonos\commerce\Order
     * @param $gate is the gate which will be do main work with payment
     * @param null $amount of product
     * @return Payment
     */
    //TODO payment created only for getting form and init with not full data change it
    public function createPayment($order, $gate, $amount = null)
    {
        //Get payment
        $payment = new Payment($order, $gate, $amount);

        //Trigger event
        Event::fire('commerce.payment.created', array(&$payment));

        return $payment;
    }

    /**
     * Add product into OrderItem and if need create new Order
     * @param $productId int is id of product
     * @param int $count count of particular items
     * @param array $orderProp custom field for changing order values
     * @param array $orderItemProp custom field for changing order item values
     * @return bool|Order return or false in error case or product which was created
     */
    //TODO divide it on two methods which will create single order and add item order to them
    public function addOrderItem($productId, $count = 1, $orderProp = array(), $orderItemProp = array())
    {
        if(!m('social')->user()){
            return false;
        }

        $product = null;

        //Find and get product by id
        if(dbQuery($this->productClass)->id($productId)->first($product)){

            //If product don't have field companyId then set it manually
            if(!isset($product[$this->productCompanyField])){
                $product[$this->productCompanyField] = 0;
            }

            $order = null;

            //If data on session not exists then create new order manually and save it in db
            if (!isset($_SESSION[$this->sessionKey][$product[$this->productCompanyField]])){
                $order = $this->getOrderIfExists($_SESSION[$this->sessionKey][$product[$this->productCompanyField]], $orderProp);
            }else{
                $order = $this->getOrderIfExists($_SESSION[$this->sessionKey][$product[$this->productCompanyField]], $orderProp);
            }

            //If order exists add new item into OrderItem
            if (isset($order)){

                $orderItem = null;
                $orderItemQuery = dbQuery('\samsonos\commerce\OrderItem')->MaterialId($productId)->OrderId($order->id);

                //In OrderItem row not exists create item else add count
                if (!$orderItemQuery->first($orderItem)) {
                    $orderItem = new OrderItem(false);
                    $orderItem->OrderId = isset($orderItemProp['OrderId']) ? $orderItemProp['OrderId'] : $order->id;
                    $orderItem->MaterialId = isset($orderItemProp['MaterialId']) ? $orderItemProp['MaterialId'] : $productId;
                }

                //Increase quantity
                $orderItem->Quantity += $count;

                if(isset($orderProp['Quantity'])){
                    $orderItem->Quantity = $orderProp['Quantity'];
                }

                //Get price from product
                $orderItem->Price = isset($orderItemProp['Price']) ? $orderItemProp['Price'] : $product[$this->productPriceField];
                $orderItem->save();

                return $order;
            }
        }
        return false;
    }

    /**
     * If order exists in session get it
     * @param $sessionKey
     * @param int $companyId is the column on product
     * @return bool|null|Order Order
     */
    public function getOrderIfExists(&$sessionKey, $orderProp, $companyId = 0){

        $order = null;

        //If data on session not exists then create new order manually and save it in db
        if(!isset($sessionKey)){

            $order = new Order();

            //If custom data for order was passed then set it or use default values
            $order->Currency = isset($orderProp['Currency']) ? $orderProp['Currency'] : $this->defaultCurrency;
            $order->ClientId = isset($orderProp['ClientId']) ? $orderProp['ClientId'] : m('social')->user()->ClientID;
            $order->Status = isset($orderProp['Status']) ? $orderProp['Status'] : Payment::STATUS_WAIT_PROCESSING;
            $order->Type = isset($orderProp['Type']) ? $orderProp['Type'] : Order::TYPE_FULLY_PAY;
            $order->Total = isset($orderProp['Total']) ? $orderProp['Total'] : 0;
            $order->CompanyId = isset($orderProp['CompanyId']) ? $orderProp['CompanyId'] : ($companyId == 0 ? 0 : $companyId);
            $sessionKey = $order->Key;
            $order->save();

        }else{

            //Data in session is already exists then get order from session key
            Order::byKey($sessionKey, $order);
        }

        return $order;
    }

    /**
     * Create form with need data for sending to payment service
     * @param $payment Payment
     * @return bool
     */
    public function createForm($payment)
    {
        if (isset($this->gates[$payment->Gate])) {
            return $this->gates[$payment->Gate]->createForm($payment);
        }
        return false;
    }

    /**
     * Clear session
     */
    public  function clearOrders()
    {
        $_SESSION[$this->sessionKey] = array();
    }

    /**
     * Get order list by session or by orderId
     * @var $ordersSelector int|\samson\activerecord\dbQuery|null is selector which point on where get query
     *      if passed dbQuery it must by instance of Order class!
     * @return array
     */
	public function ordersList($ordersSelector = null){
		$orders = array();

        //If dbQuery passed use it
        if(isset($ordersSelector)&&is_object($ordersSelector)){
            $ordersSelector->join('order_item', '\samsonos\commerce\OrderItem')->exec($orders);
        //If passed orderId get by orderId else get list by session
        }else if(isset($ordersSelector)&&is_int($ordersSelector)){
            trace('int',true);
            //Get orders
            dbQuery('\samsonos\commerce\Order')->id($ordersSelector)->join('order_item', '\samsonos\commerce\OrderItem')->exec($orders);
        //Check session
        }else if(isset($_SESSION[$this->sessionKey]) && sizeof($_SESSION[$this->sessionKey])){
            trace('session',true);
            //Get orders
			dbQuery('\samsonos\commerce\Order')->Key($_SESSION[$this->sessionKey])->join('order_item', '\samsonos\commerce\OrderItem')->exec($orders);
		}

        foreach($orders as &$order){
            if(isset($order['onetomany']['_order_item'])){

                //Save nested items by order
                $order->items = $order['onetomany']['_order_item'];
                $productIdList = array();

                //Save all products id
                foreach($order->items as $item) {
                    $productIdList[] = $item->MaterialId;
                }

                $productList = array();

                //Get product
                if (dbQuery($this->productClass)->id($productIdList)->exec($productList)) {
                    foreach($order->items as & $item) {
                        $item->Product = $productList[$item->MaterialId];
                    }
                }
            }
        }

		return $orders;
	}
}
 
