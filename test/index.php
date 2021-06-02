<?php
require __DIR__ .'/../vendor/autoload.php';

use simpletemplate\Template;
Template::view('www/home.html',[
    'title' => "Welcome",
    'colors' => ['Red', 'Blue', 'Green']
]);
?>