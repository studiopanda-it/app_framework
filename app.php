<?php
if(defined("CONFIG")) return;

require_once __DIR__."/../../autoload.php";

$_ROOT = realpath(__DIR__."/../../..")."/";

define("CONFIG", array_merge(
	file_exists($_ROOT."config.json") ? json_decode(file_get_contents($_ROOT."config.json"), true) : [],
	file_exists($_ROOT.".env") ? parse_ini_file($_ROOT.".env", false, INI_SCANNER_TYPED) : []
));

define("ROOT", CONFIG["ROOT"] ?? $_ROOT);
unset($_ROOT);

define("HOST", CONFIG["HOST"] ?? $_SERVER["HTTP_HOST"]);

$_DOCUMENT_ROOT = CONFIG["DOCUMENT_ROOT"] ?? realpath($_SERVER["DOCUMENT_ROOT"]);
define("WEB_ROOT", CONFIG["WEB_ROOT"] ?? ((substr(ROOT, 0, strlen($_DOCUMENT_ROOT)) === $_DOCUMENT_ROOT) ? substr(ROOT, strlen($_DOCUMENT_ROOT)) : "/"));
unset($_DOCUMENT_ROOT);

date_default_timezone_set(CONFIG["TIMEZONE"] ?? "UTC");

$_VIEWS_PATH = ROOT."views/";
$_CONTROLLERS_PATH = ROOT."controllers/";

foreach(array_merge(glob(__DIR__."/libs/*.php"), glob(ROOT."libs/*.php")) as $_LIBRARY) {
	require_once $_LIBRARY;
}
unset($_LIBRARY);

define("FULL_REQUEST", rawurldecode(explode("?", $_SERVER["REQUEST_URI"])[0]));

define("REQUEST", strpos(FULL_REQUEST, WEB_ROOT) === 0 ? substr(FULL_REQUEST, strlen(WEB_ROOT) - 1) : FULL_REQUEST);

$_REQUEST = REQUEST;
foreach(glob(ROOT."middlewares/*.php") as $_MIDDLEWARE) {
	$_REQUEST = (require_once($_MIDDLEWARE))(
		$_REQUEST,
		\StudioPanda\route($_REQUEST, $_CONTROLLERS_PATH, ".php", true),
		\StudioPanda\route($_REQUEST, $_VIEWS_PATH, ".twig", true),
		$_CONTROLLERS_PATH,
		$_VIEWS_PATH
	);
	if(is_array($_REQUEST)) {
		\StudioPanda\output_json($_REQUEST);
	}
	if($_REQUEST === false) {
		$_REQUEST = false;
		break;
	}
}
define("ACTION", $_REQUEST);
define("VIEWS_PATH", $_VIEWS_PATH);
define("CONTROLLERS_PATH", $_CONTROLLERS_PATH);
unset($_REQUEST, $_EXPECTED_ACTION, $_VIEWS_PATH, $_CONTROLLERS_PATH);

define("CONTROLLER", \StudioPanda\route(ACTION, CONTROLLERS_PATH, ".php"));
define("VIEW", \StudioPanda\route(ACTION, VIEWS_PATH, ".twig", true));

if(CONTROLLER["MAIN"] === false && VIEW === false) {
	\StudioPanda\output_twig(false);
}

foreach([...CONTROLLER["BEFORE"], CONTROLLER["MAIN"]] as $_CONTROLLER) {
	if($_CONTROLLER === false) continue;
	$_CONTROLLER_RETURN_VALUE = include(CONTROLLERS_PATH.$_CONTROLLER.".php");
	if(is_array($_CONTROLLER_RETURN_VALUE)) {
		\StudioPanda\output_json($_CONTROLLER_RETURN_VALUE);
	}
	if($_CONTROLLER_RETURN_VALUE === false) {
		\StudioPanda\output_twig(false);
	}
	if(is_string($_CONTROLLER_RETURN_VALUE)) {
		\StudioPanda\output_twig($_CONTROLLER_RETURN_VALUE, get_defined_vars());
	}
}
unset($_CONTROLLER, $_CONTROLLER_RETURN_VALUE);

\StudioPanda\output_twig(VIEW, get_defined_vars());
