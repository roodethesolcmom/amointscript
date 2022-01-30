<?php

//namespace App;

//use AmoCRM;


require_once('classes\AmoCRM.php');
$keys = new AmoCRM();

//$get_contact = 'get_contact';
//$get_c = $keys->get_contact('79776056820');
//$get_c = $keys->get_contact('79213108898');
$get_c = $keys->user_worker('John Dhpe', '76574567457', 'lol@kek.ru');

if ($get_c){
    echo $get_c;
} else {
    echo 'net kontakta';
}


?>