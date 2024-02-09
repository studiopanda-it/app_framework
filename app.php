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

$_ACTION = REQUEST;
foreach(glob(ROOT."middlewares/*.php") as $_MIDDLEWARE) {
	$_ACTION = (require_once($_MIDDLEWARE))($_ACTION);
	if(is_array($_ACTION)) {
		header("Content-Type: application/json");
		echo json_encode($_ACTION, JSON_INVALID_UTF8_SUBSTITUTE);
		die;
	}
	if($_ACTION === false) {
		echo render_twig(false);
		die;
	}
}
define("ACTION", $_ACTION);
unset($_ACTION);

if(
	route(ACTION, CONTROLLERS_PATH, ".php")["main"] === false &&
	route(ACTION, VIEWS_PATH, ".twig")["main"] === false
) {
	echo render_twig(false);
	die;
}

define("REQUEST_PARAMS", route(ACTION, CONTROLLERS_PATH, ".php")["params"]);

foreach(array_merge(route(ACTION, CONTROLLERS_PATH, ".php")["before"], [route(ACTION, CONTROLLERS_PATH, ".php")["main"]]) as $_CONTROLLER) {
	if($_CONTROLLER === false) continue;
	$_CONTROLLER_RETURN_VALUE = include(CONTROLLERS_PATH.$_CONTROLLER);
	if(is_array($_CONTROLLER_RETURN_VALUE)) {
		header("Content-Type: application/json");
		echo json_encode($_CONTROLLER_RETURN_VALUE, JSON_INVALID_UTF8_SUBSTITUTE);
		die;
	}
	if($_CONTROLLER_RETURN_VALUE === false) {
		echo render_twig(false);
		die;
	}
	if(is_string($_CONTROLLER_RETURN_VALUE)) {
		echo render_twig($_CONTROLLER_RETURN_VALUE, get_defined_vars());
		die;
	}
}
unset($_CONTROLLER, $_CONTROLLER_RETURN_VALUE);

echo render_twig(substr(route(ACTION, ROOT."views/", ".twig")["main"], 0, -strlen(".twig")), get_defined_vars());
die;
