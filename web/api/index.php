<?php

// http://fezvrasta.github.io/bootstrap-material-design/bootstrap-elements.html
// http://fortawesome.github.io/Font-Awesome/icons/
session_start();


//GDPR HEADERS
header('Referrer-Policy: same-origin');
header('Strict-Transport-Security: max-age=31536000; preload');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

//set to neurtal timezone
date_default_timezone_set('UTC');


require_once('../inc/core.php');
includeManagement();

//block bad requests
$ids = new IDS;
$ids->blockBadRequest();

//loads language file into ram
loadLangFile();

//manages url, makes array and extracts options
$url = URLManagement();
//remove /api/ from the beginning
array_shift($url);

//do something else with the info

var_dump($url);