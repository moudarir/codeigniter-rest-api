<?php

use Moudarir\CodeigniterApi\Routes\TestRoute;
use Moudarir\CodeigniterApi\Routes\UserRoute;

$route['default_controller'] = 'welcome';
$route['404_override'] = 'pageNotFound';
$route['translate_uri_dashes'] = false;

// TEST ROUTES
TestRoute::web($route);

// API ROUTES
UserRoute::api($route);
