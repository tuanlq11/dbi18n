<?php
namespace tuanlq11\dbi18n;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as Schema;
use Illuminate\Database\Schema\Grammars\Grammar;

/**
 * Created by PhpStorm.
 * User: arch
 * Date: 5/4/16
 * Time: 7:20 AM
 */
class I18NBlueprint extends Blueprint
{
    public function build(Connection $connection, Grammar $grammar)
    {
        print_r($this->commands); exit;
//        parent::build($connection, $grammar);
    }


    public function i18n()
    {

    }
}