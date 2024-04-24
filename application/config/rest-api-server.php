<?php

// Routing params
$config['route_default_controller'] = 'welcome';
$config['route_404_override'] = 'pageNotFound@index';
$config['route_translate_uri_dashes'] = false;
$config['route_fake_default_controller'] = 'DefaultController';

$config['xss_filtering'] = true;
$config['enable_logging'] = false;
$config['enable_profiling'] = false;
$config['enable_emulate_request'] = true;

$config['default_output_format'] = 'json'; // json or xml

// Response Fields
$config['data_field_name'] = 'data';
$config['total_field_name'] = 'total';
$config['page_field_name'] = 'page';
$config['limit_field_name'] = 'limit';
$config['error_field_name'] = 'error';
$config['message_field_name'] = 'message';
$config['reasons_field_name'] = 'reasons';

$config['enable_api_key'] = true;
$config['api_key_name'] = 'X-API-KEY';

$config['enable_authentication'] = true;
$config['api_key_auth_basic_class'] = \Moudarir\CodeigniterApi\Models\ApiKey::class;
$config['api_key_auth_basic_method'] = 'verify';
$config['jwt_secret'] = 'tkharbi9initialD';
$config['jwt_algorithm'] = 'HS256';

$config['enable_limits'] = false;
$config['limits_type'] = 'ROUTED_URL';
$config['default_limits_timeout'] = 60 * 60;

$config['enable_ip_whitelist'] = false;
$config['enable_ip_blacklist'] = false;
$config['ip_whitelist'] = ['127.0.0.1', '::1'];
$config['ip_blacklist'] = [];

$config['allow_ajax_only'] = false;
$config['allowed_http_methods'] = ['get', 'post', 'put', 'delete', 'options', 'patch', 'head'];

$config['enable_cors_check'] = true;
$config['cors_allow_any_domain'] = false;
$config['cors_allowed_methods'] = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH', 'HEAD'];
$config['cors_allowed_headers'] = [
    'Origin', 'X-Requested-With', 'Content-Type', 'Accept',
    'Access-Control-Request-Method', 'Authorization', 'X-Api-Key',
];
$config['cors_forced_headers'] = ['Access-Control-Allow-Credentials' => 'true'];
$config['cors_allowed_origins'] = [
    'https://rest.api',
];
