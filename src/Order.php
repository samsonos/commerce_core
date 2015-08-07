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
    /** const for fully pay product */
    const TYPE_FULLY_PAY = 0;

    /** const for book product */
    const TYPE_BOOK_PAY = 1;
    
    /** @inheritdoc */
    public function filled()
    {
    	// Generate randomized order key
    	$this->Key = md5(time().rand(999999, 9999999));
    }

	/**
	 * Get order by Key
	 *
	 * @param string $key Order Key
	 * @param Order  $order Pointer where found order object will be returned
	 *
	 * @return bool True if order found
	 */
	public static function byKey($key, & $order = null)
	{
		// Perform DB request
		if (isset($key{0}) && dbQuery(get_called_class())->cond('Key', $key)->first($order)) {
			return true;
		}

		return false;
	}
}
 
