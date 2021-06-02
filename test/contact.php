<?php
require __DIR__ .'/../vendor/autoload.php';

use simpletemplate\Template;
Template::view('www/contact.html',
['title'=>'Contact Us']);
?>