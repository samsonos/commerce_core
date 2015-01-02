<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 13.09.14 at 18:56
 */
 namespace samsonos\commerce;

/**
 * Generic Orders SamsonPHP commerce system class
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @copyright 2014 SamsonOS
 */
class Order extends \samson\activerecord\order
{
    public function __construct( $id = NULL, $class_name = NULL )
    {
        if ($id !== false){
            if( ! isset( $id ) ) {
                $this->Key = md5(time().rand(999999, 9999999));
            }
        }
        parent::__construct($id, $class_name);
    }

	/**
	 * Get order by Key
	 *
	 * @param string    $key    Order Key
	 * @param Order      $order   Pointer where found order object will be returned
	 *
	 * @return bool True if tour order found
	 */
	public static function byURL($key, & $order = null)
	{
		// Perform DB request
		if (isset($key{0}) && dbQuery(get_called_class())->cond('Key', $key)->first($order)) {
			return true;
		}

		return false;
	}

    public function updateStatus($status, $comment='')
    {

    }
}
 