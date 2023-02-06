<?php

use Moudarir\CodeigniterApi\Routes\UserRoute;

$route['default_controller'] = 'welcome';
$route['404_override'] = 'pageNotFound';
$route['translate_uri_dashes'] = false;

// API ROUTES
UserRoute::api($route);
