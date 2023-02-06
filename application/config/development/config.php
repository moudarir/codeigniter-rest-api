<?php

$config['base_url'] = 'https://rest.api/';

$config['index_page'] = '';
$config['uri_protocol'] = 'REQUEST_URI';
$config['url_suffix'] = '';
$config['language'] = 'french';
$config['time_reference'] = 'Africa/Casablanca';
$config['charset'] = 'UTF-8';

$config['enable_hooks'] = true;
$config['subclass_prefix'] = 'Core';

$config['composer_autoload'] = SITEPATH.'vendor/autoload.php';
$config['permitted_uri_chars'] = 'A-Z a-z 0-9~%.,:_\-+=';

$config['enable_query_strings'] = false;
$config['controller_trigger'] = 'c';
$config['function_trigger'] = 'm';
$config['directory_trigger'] = 'd';

$config['allow_get_array'] = true;

$config['log_threshold'] = 1;
$config['log_path'] = '';
$config['log_file_extension'] = '';
$config['log_file_permissions'] = 0644;
$config['log_date_format'] = 'Y-m-d H:i:s';

$config['error_views_path'] = '';

$config['cache_path'] = '';
$config['cache_query_string'] = false;

$config['encryption_key'] = 'XSDlIiK5pvu1Vtluq6iqqFo60H6HjIQj';

$config['sess_driver'] = 'database';
$config['sess_cookie_name'] = 'app_session';
$config['sess_expiration'] = 60 * 60 * 24 * 365;
$config['sess_save_path'] = 'app_sessions';
$config['sess_match_ip'] = false;
$config['sess_time_to_update'] = 60 * 60 * 24 * 365;
$config['sess_regenerate_destroy'] = true;
$config['sess_samesite'] = 'Lax';

$config['cookie_prefix'] = 'app_';
$config['cookie_domain'] = '';
$config['cookie_path'] = '/';
$config['cookie_secure'] = true;
$config['cookie_httponly'] = false;
$config['cookie_samesite'] = 'Lax';

$config['standardize_newlines'] = false;
$config['global_xss_filtering'] = false;

$config['csrf_protection'] = false;
$config['csrf_token_name'] = 'app_csrf_token_name';
$config['csrf_cookie_name'] = 'app_csrf_cookie_name';
$config['csrf_expire'] = 7200;
$config['csrf_regenerate'] = true;
$config['csrf_exclude_uris'] = [];

$config['compress_output'] = false;
$config['rewrite_short_tags'] = false;
$config['proxy_ips'] = '';
