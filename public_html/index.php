<?php
define('ENVIRONMENT', $_SERVER['CI_ENV'] ?? 'development');

const MIN_ALLOWED_PHP_VERSION = '7.4';

/**
 * ERROR REPORTING
 */
switch (ENVIRONMENT):
	case 'testing':
	case 'development':
        error_reporting(-1);
        ini_set('display_errors', 1);
	break;
	case 'production':
		ini_set('display_errors', 0);
		if (version_compare(PHP_VERSION, MIN_ALLOWED_PHP_VERSION, '>=')):
			error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
		else:
			error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
		endif;
	break;
	default:
		header('HTTP/1.1 503 Service Unavailable.', true, 503);
		echo 'The application environment is not set correctly.';
		exit(1); // EXIT_ERROR
endswitch;

/**
 * SYSTEM DIRECTORY NAME
 */
$system_path = '../system';

/**
 * APPLICATION DIRECTORY NAME
 */
$application_folder = '../application';

/**
 * VIEW DIRECTORY NAME
 */
$view_folder = '';

// ---------------------------------------------------------------
// END OF USER CONFIGURABLE SETTINGS.  DO NOT EDIT BELOW THIS LINE
// ---------------------------------------------------------------

/**
 *  Resolve the system path for increased reliability
 */
// Set the current directory correctly for CLI requests
if (defined('STDIN')):
	chdir(dirname(__FILE__));
endif;

if (($_temp = realpath($system_path)) !== false):
	$system_path = $_temp.DIRECTORY_SEPARATOR;
else:
	// Ensure there's a trailing slash
	$system_path = strtr(
		rtrim($system_path, '/\\'),
		'/\\',
		DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
	).DIRECTORY_SEPARATOR;
endif;

// Is the system path correct?
if (!is_dir($system_path)):
	header('HTTP/1.1 503 Service Unavailable.', true, 503);
	echo 'Your system folder path does not appear to be set correctly. Please open the following file and correct this: '.pathinfo(__FILE__, PATHINFO_BASENAME);
	exit(3); // EXIT_CONFIG
endif;

/**
 *  Now that we know the path, set the main path constants
 */
// The name of THIS file
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

// Path to the system directory
define('BASEPATH', $system_path);

// Path to the front controller (this file) directory
define('FCPATH', dirname(__FILE__).DIRECTORY_SEPARATOR);

// Path to the site home directory
define('SITEPATH', dirname(FCPATH).DIRECTORY_SEPARATOR);

// Name of the "system" directory
define('SYSDIR', basename(BASEPATH));

// Used in Email and Form_validation
if (!defined('INTL_IDNA_VARIANT_UTS46')):
    define('INTL_IDNA_VARIANT_UTS46', 1);
endif;

// The path to the "application" directory
if (is_dir($application_folder)):
	if (($_temp = realpath($application_folder)) !== false):
		$application_folder = $_temp;
	else:
		$application_folder = strtr(
			rtrim($application_folder, '/\\'),
			'/\\',
			DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
		);
	endif;
elseif (is_dir(BASEPATH.$application_folder.DIRECTORY_SEPARATOR)):
	$application_folder = BASEPATH.strtr(
		trim($application_folder, '/\\'),
		'/\\',
		DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
	);
else:
	header('HTTP/1.1 503 Service Unavailable.', true, 503);
	echo 'Your application folder path does not appear to be set correctly. Please open the following file and correct this: '.SELF;
	exit(3); // EXIT_CONFIG
endif;

define('APPPATH', $application_folder.DIRECTORY_SEPARATOR);

// The path to the "views" directory
if (!isset($view_folder[0]) && is_dir(APPPATH.'views'.DIRECTORY_SEPARATOR)):
	$view_folder = APPPATH.'views';
elseif (is_dir($view_folder)):
	if (($_temp = realpath($view_folder)) !== false):
		$view_folder = $_temp;
	else:
		$view_folder = strtr(
			rtrim($view_folder, '/\\'),
			'/\\',
			DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
		);
	endif;
elseif (is_dir(APPPATH.$view_folder.DIRECTORY_SEPARATOR)):
	$view_folder = APPPATH.strtr(
		trim($view_folder, '/\\'),
		'/\\',
		DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
	);
else:
	header('HTTP/1.1 503 Service Unavailable.', true, 503);
	echo 'Your view folder path does not appear to be set correctly. Please open the following file and correct this: '.SELF;
	exit(3); // EXIT_CONFIG
endif;

define('VIEWPATH', $view_folder.DIRECTORY_SEPARATOR);

/**
 * LOAD THE BOOTSTRAP FILE
 */
require_once BASEPATH.'core/CodeIgniter.php';
