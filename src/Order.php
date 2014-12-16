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
        // Запишем имя текущего класса
        if(!isset($this->class_name)) $this->class_name = get_class($this);

        if ($id !== false){
            if( ! isset( $id ) ) {
                $this->Key = md5(time().rand(999999, 9999999));
            } elseif( NULL !== ($db_record = db()->find_by_field( $this->class_name, 'Key', $id )))
            {
                // Пробежимся по переменным класса
                foreach( $db_record as $var => $value ) {
                    if ($var == 'OrderId') {
                        $this->id = $value;
                    }
                    $this->$var = $value;
                }

                // Установим флаг привязки к БД
                $this->attached = TRUE;

                // Зафиксируем данный класс в локальном кеше
                self::$instances[ $class_name ][ $this->id ] = $this;

                $id = false;
            }
        }
        parent::__construct($id, $this->class_name);
    }

    public function updateStatus($status, $comment='')
    {

    }
}
 