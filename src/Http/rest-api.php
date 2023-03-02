<?php

/**
 * XSS Filtering
 *
 * Set the default value of global xss filtering. Same approach as CodeIgniter 3
 */
$config['xss_filtering'] = true;

/**
 * Logging Request / Response
 *
 * When set to TRUE, the REST API will log actions based on the column
 * names 'key', 'date', 'time' and 'ip_address'.
 */
$config['enable_logging'] = true;

/**
 * Enable Profiling
 */
$config['enable_profiling'] = false;

/**
 * Enable Emulate Request
 *
 * Should we enable emulation of the request (e.g. used in Mootools request)
 */
$config['enable_emulate_request'] = true;

/**
 * Enable authentication
 */
$config['enable_authentication'] = true;

/**
 * Enable use of API Key
 */
$config['enable_api_key'] = true;

/**
 * Enable Request Limits
 *
 * Used only if Authentication is enabled
 */
$config['enable_limits'] = false;

/**
 * IP Whitelist
 *
 * Limit connections to your REST server with a comma separated list of IP addresses
 *
 * e.g: '123.456.789.0, 987.654.32.1'
 *
 * 127.0.0.1 and ::1 are allowed by default
 */
$config['enable_ip_whitelist'] = false;

/**
 * Enabling IP Blacklist
 *
 * Prevent connections to the REST server from blacklisted IP addresses
 * Set to TRUE and add any IP address to 'IP_BLACKLIST'
 */
$config['enable_ip_blacklist'] = false;

/**
 * Ignore HTTP Accept
 *
 * Set to TRUE to ignore the HTTP Accept and speed up each request a little.
 * Only do this if you are using the /format/xml in URLs
 */
$config['ignore_http_accept'] = false;

/**
 * Allow AJAX Only
 *
 * Set to TRUE to allow AJAX requests only. Set to FALSE to accept HTTP requests
 *
 * Note: If set to TRUE and the request is not AJAX, a 406 response with the
 * error message 'Only AJAX requests are accepted.' will be returned.
 */
$config['allow_ajax_only'] = false;

/**
 * CORS Check
 *
 * Set to TRUE to enable Cross-Origin Resource Sharing (CORS). Useful if you
 * are hosting your API on a different domain from the application that
 * will access it through a browser
 */
$config['enable_cors_check'] = true;

/**
 * CORS Allow Any Domain
 *
 * Set to TRUE to enable Cross-Origin Resource Sharing (CORS) from any
 */
$config['cors_allow_any_domain'] = false;

/**
 * Default Output Format
 */
$config['default_output_format'] = 'json';

/**
 * List all supported formats, the first will be the default format.
 */
$config['supported_formats'] = [
    'json' => 'application/json',
    'jsonp' => 'application/javascript',
    'xml' => 'application/xml',
];

/**
 * Data Field Name
 *
 * The field name for data results
 */
$config['data_field_name'] = 'data';

/**
 * Total Field Name
 *
 * The field name is used in listing
 */
$config['total_field_name'] = 'total';

/**
 * Page Field Name
 *
 * The field name is used for pagination
 */
$config['page_field_name'] = 'page';

/**
 * Error Field Name
 *
 * The field name for the status inside the response
 */
$config['error_field_name'] = 'error';

/**
 * Message Field Name
 *
 * The field name for the message inside the response
 */
$config['message_field_name'] = 'message';

/**
 * Reasons Field Name
 *
 * The field name used for expected errors
 * Used only if errors was sent  as an array
 */
$config['reasons_field_name'] = 'reasons';

/**
 * API Key Name
 *
 * Custom header to specify the API key
 *
 * Note: Custom headers with the X- prefix are deprecated as of
 * 2012/06/12. See RFC 6648 specification for more details
 */
$config['api_key_name'] = 'X-API-KEY';

/**
 * API Limits type
 *
 * Specify the type used to limit the API calls
 *
 * Available types are:
 * IP_ADDRESS    Check a limit per ip address
 * API_KEY       Check a limit per api key
 * METHOD_NAME   Check a limit on method calls
 * ROUTED_URL    Check a limit on the routed URL. Default
 */
$config['limits_type'] = 'ROUTED_URL';

/**
 * API Limits Timeout
 *
 * Default 1 Hour
 */
$config['default_limits_timeout'] = 60 * 60;

/**
 * Basic Auth class name
 *
 * Used in basic Authorization Type
 */
$config['api_key_auth_basic_class'] = \Moudarir\CodeigniterApi\Models\ApiKey::class;

/**
 * Basic Auth method name
 *
 * Used in basic Authorization Type
 */
$config['api_key_auth_basic_method'] = 'verify';

/**
 * JWT secret password
 *
 * If Authentication is enabled and Authorization type is "Bearer"
 * then the environment variable getenv('JWT_SECRET') is used
 * to sign the token sent. If the env variable not set then this
 * param is used.
 */
$config['jwt_secret'] = '';

/**
 * IP Whitelist
 *
 * Limit connections to your REST server with a comma separated list of IP addresses
 *
 * e.g: '123.456.789.0, 987.654.32.1'
 *
 * 127.0.0.1 ::1 are allowed by default
 */
$config['ip_whitelist'] = '';

/**
 * IP Blacklist
 *
 * Prevent connections from the following IP addresses
 * e.g: '123.456.789.0, 987.654.32.1'
 */
$config['ip_blacklist'] = '';

/**
 * List of allowed HTTP methods.
 */
$config['allowed_http_methods'] = ['get', 'post', 'put', 'delete', 'options', 'patch', 'head'];

/**
 * CORS Allowable Methods
 *
 * If using CORS checks, you can set the methods you want to be allowed
 */
$config['cors_allowed_methods'] = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH', 'HEAD'];

/**
 * CORS Allowable Headers
 *
 * If using CORS checks, set the allowable headers here
 */
$config['cors_allowed_headers'] = [
    'Origin',
    'X-Requested-With',
    'Content-Type',
    'Accept',
    'Access-Control-Request-Method',
    'Authorization',
    'X-Api-key',
];

/**
 * CORS Forced Headers
 *
 * If using CORS checks, always include the headers and values specified
 * here in the OPTIONS client preflight.
 */
$config['cors_forced_headers'] = ['Access-Control-Allow-Credentials' => 'true'];

/**
 * CORS Allowable Domains
 *
 * Used if "enable_cors_check" is set to true and "cors_allow_any_domain" is set to false.
 * Set all the allowable domains within the array
 */
$config['cors_allowed_origins'] = [
    'https://rest.api',
    'https://api.hck.moc',
];
