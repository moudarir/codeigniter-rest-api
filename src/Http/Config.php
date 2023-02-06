<?php

namespace Moudarir\CodeigniterApi\Http;

class Config
{

    /**
     * XSS Filtering
     *
     * Set the default value of global xss filtering. Same approach as CodeIgniter 3
     */
    const XSS_FILTERING = true;

    /**
     * Default Output Format
     */
    const DEFAULT_OUTPUT_FORMAT = 'json';

    /**
     * List all supported formats, the first will be the default format.
     */
    const ALL_SUPPORTED_FORMATS = [
        'json' => 'application/json',
        'jsonp' => 'application/javascript',
        'xml' => 'application/xml',
    ];

    /**
     * Data Field Name
     *
     * The field name the data results
     */
    const DATA_FIELD_NAME = 'data';

    /**
     * Total Field Name
     *
     * The field name used in listing
     */
    const TOTAL_FIELD_NAME = 'total';

    /**
     * Total Field Name
     *
     * The field name used in listing
     */
    const NUM_PAGE_FIELD_NAME = 'page';

    /**
     * Error Field Name
     *
     * The field name for the status inside the response
     */
    const ERROR_FIELD_NAME = 'error';

    /**
     * Message Field Name
     *
     * The field name for the success message inside the response
     */
    const MESSAGE_FIELD_NAME = 'message';

    /**
     * Logging Request
     *
     * When set to TRUE, the REST API will log actions based on the column names 'key', 'date',
     * 'time' and 'ip_address'. This is a general rule that can be overridden in the
     * $this->method array for each controller
     */
    const ENABLE_LOGGING = false;

    /**
     * Enable Profiling
     */
    const ENABLE_PROFILING = false;

    /**
     * Enable Emulate Request
     *
     * Should we enable emulation of the request (e.g. used in Mootools request)
     */
    const ENABLE_EMULATE_REQUEST = true;

    /**
     * Enable authentication
     */
    const ENABLE_AUTHORIZATION = true;

    /**
     * Generate JWT TOKEN
     *
     * Useful for a login endpoint
     */
    const GENERATE_JWT_TOKEN = ['login'];

    /**
     * Enable use of API Key
     */
    const ENABLE_API_KEY = true;

    /**
     * API Key Length
     */
    const API_KEY_LENGTH = 40;

    /**
     * API Key Name
     *
     * Custom header to specify the API key
     *
     * Note: Custom headers with the X- prefix are deprecated as of
     * 2012/06/12. See RFC 6648 specification for more details
     */
    const API_KEY_NAME = 'X-API-KEY';

    /**
     * Enabling IP Whitelist
     *
     * Limit connections to your REST server to White-listed IP addresses
     *
     * Usage:
     * 1. Set to TRUE and select an auth option for extreme security (client's IP
     *    address must be in white-list and they must also log in)
     * 2. Set to TRUE with auth set to FALSE to allow White-listed IPs access with no login
     * 3. Set to FALSE but set '$auth_override_class_method' to 'white-list' to
     *    restrict certain methods to IPs in your white-list
     */
    const ENABLE_IP_WHITELIST = false;

    /**
     * IP Whitelist
     *
     * Limit connections to your REST server with a comma separated list of IP addresses
     *
     * e.g: '123.456.789.0, 987.654.32.1'
     *
     * 127.0.0.1, 0.0.0.0 and ::1 are allowed by default
     */
    const IP_WHITELIST = '';

    /**
     * Enabling IP Blacklist
     *
     * Prevent connections to the REST server from blacklisted IP addresses
     * Set to TRUE and add any IP address to 'IP_BLACKLIST'
     */
    const ENABLE_IP_BLACKLIST = false;

    /**
     * IP Blacklist
     *
     * Prevent connections from the following IP addresses
     * e.g: '123.456.789.0, 987.654.32.1'
     */
    const IP_BLACKLIST = '';

    /**
     * List of allowed HTTP methods.
     */
    const ALLOWED_HTTP_METHODS = ['get', 'delete', 'post', 'put', 'options', 'patch', 'head'];

    /**
     * Ignore HTTP Accept
     *
     * Set to TRUE to ignore the HTTP Accept and speed up each request a little.
     * Only do this if you are using the $this->rest_format or /format/xml in URLs
     */
    const IGNORE_HTTP_ACCEPT = false;

    /**
     * Allow AJAX Only
     *
     * Set to TRUE to allow AJAX requests only. Set to FALSE to accept HTTP requests
     *
     * Note: If set to TRUE and the request is not AJAX, a 505 response with the
     * error message 'Only AJAX requests are accepted.' will be returned.
     *
     * Hint: This is good for production environments
     */
    const ALLOW_AJAX_ONLY = false;

    /**
     * CORS Check
     *
     * Set to TRUE to enable Cross-Origin Resource Sharing (CORS). Useful if you
     * are hosting your API on a different domain from the application that
     * will access it through a browser
     */
    const CORS_CHECK = true;

    /**
     * CORS Allow Any Domain
     *
     * Set to TRUE to enable Cross-Origin Resource Sharing (CORS) from any
     */
    const CORS_ALLOW_ANY_DOMAIN = false;

    /**
     * CORS Allowable Headers
     *
     * If using CORS checks, set the allowable headers here
     */
    const CORS_ALLOWED_HEADERS = [
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
    const CORS_FORCED_HEADERS = [
        'Access-Control-Allow-Credentials' => 'true'
    ];

    /**
     * CORS Allowable Methods
     *
     * If using CORS checks, you can set the methods you want to be allowed
     */
    const CORS_ALLOWED_METHODS = ['GET', 'POST', 'OPTIONS', 'PUT', 'PATCH', 'DELETE', 'HEAD'];

    /**
     * CORS Allowable Domains
     *
     * Used if CORS_CHECK is set to true and CORS_ALLOW_ANY_DOMAIN is set to false.
     * Set all the allowable domains within the array
     */
    const CORS_ALLOWED_ORIGINS = [
        'https://leguideauto.am',
        'https://www.hck.moc',
    ];

    /**
     * Common HTTP status codes and their respective description.
     */
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_INTERNAL_ERROR = 500;
}
