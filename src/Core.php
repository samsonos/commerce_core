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


        return parent::prepare();
    }
}
 