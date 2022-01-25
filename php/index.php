<?php

//namespace App;

//use AmoCRM;


require_once('classes\AmoCRM.php');
$keys = new AmoCRM();
$keysfunc = 'revoke_keys';
echo $keys->$keysfunc();
//echo revoke_keys();
//echo $sex;


?>