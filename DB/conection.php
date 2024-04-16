<?php
require 'vendor/autoload.php';
function conection(){
        $f3 = \Base::instance();
        $f3 -> config('DB/dbconf.cfg');
        $db=new DB\SQL(
        'mysql:host=localhost;port='.$f3->get('port').';dbname='.$f3->get('dbname'),
        $f3->get('user'),
        $f3->get('password')
    );
        return $db;
}
?>
