<?php
require_once('ThumbGet.php');

$thumb = new ThumbGet();

if (isset($_POST['youtube'])) {//Is a POST request with correct form input
    $thumb->processRequest($_POST['youtube']);//Process and validate the request
} else {
    $thumb->show400Header();//Did not come from form so you get 400 bad request
}