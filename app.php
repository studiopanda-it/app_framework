<?php
require_once __DIR__."/../../autoload.php";
if(defined("CONFIG")) return;

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

define("VIEWS_PATH", ROOT."views/");
define("CONTROLLERS_PATH", ROOT."controllers/");

foreach(array_merge(glob(__DIR__."/libs/*.php"), glob(ROOT."libs/*.php")) as $_LIBRARY) {
	require_once $_LIBRARY;
}
unset($_LIBRARY);

define("FULL_REQUEST", rawurldecode(explode("?", $_SERVER["REQUEST_URI"])[0]));

define("REQUEST", strpos(FULL_REQUEST, WEB_ROOT) === 0 ? substr(FULL_REQUEST, strlen(WEB_ROOT) - 1) : FULL_REQUEST);

$_REQUEST = REQUEST;
foreach(glob(ROOT."middlewares/*.php") as $_MIDDLEWARE) {
	$_EXPECTED_ACTION = \StudioPanda\route($_REQUEST, CONTROLLERS_PATH, ".php")["main"];
	if($_EXPECTED_ACTION === false) {
		$_EXPECTED_ACTION = \StudioPanda\route($_REQUEST, VIEWS_PATH, ".twig")["main"];
	}
	$_REQUEST = (require_once($_MIDDLEWARE))($_REQUEST, $_EXPECTED_ACTION);
	if(is_array($_REQUEST)) {
		header("Content-Type: application/json");
		echo json_encode($_REQUEST, JSON_INVALID_UTF8_SUBSTITUTE);
		die;
	}
	if($_REQUEST === false) {
		echo \StudioPanda\render_twig(false);
		die;
	}
}
define("ACTION", $_REQUEST);
unset($_REQUEST, $_EXPECTED_ACTION);

if(
	\StudioPanda\route(ACTION, CONTROLLERS_PATH, ".php")["main"] === false &&
	\StudioPanda\route(ACTION, VIEWS_PATH, ".twig")["main"] === false
) {
	echo \StudioPanda\render_twig(false);
	die;
}

define("REQUEST_PARAMS", \StudioPanda\route(ACTION, CONTROLLERS_PATH, ".php")["params"]);

foreach(array_merge(\StudioPanda\route(ACTION, CONTROLLERS_PATH, ".php")["before"], [\StudioPanda\route(ACTION, CONTROLLERS_PATH, ".php")["main"]]) as $_CONTROLLER) {
	if($_CONTROLLER === false) continue;
	$_CONTROLLER_RETURN_VALUE = include(CONTROLLERS_PATH.$_CONTROLLER);
	if(is_array($_CONTROLLER_RETURN_VALUE)) {
		header("Content-Type: application/json");
		echo json_encode($_CONTROLLER_RETURN_VALUE, JSON_INVALID_UTF8_SUBSTITUTE);
		die;
	}
	if($_CONTROLLER_RETURN_VALUE === false) {
		echo \StudioPanda\render_twig(false);
		die;
	}
	if(is_string($_CONTROLLER_RETURN_VALUE)) {
		echo \StudioPanda\render_twig($_CONTROLLER_RETURN_VALUE, get_defined_vars());
		die;
	}
}
unset($_CONTROLLER, $_CONTROLLER_RETURN_VALUE);

echo \StudioPanda\render_twig(substr(\StudioPanda\route(ACTION, ROOT."views/", ".twig")["main"], 0, -strlen(".twig")), get_defined_vars());
die;
