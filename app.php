<?php
if(defined("CONFIG")) return;

$_ROOT = realpath(__DIR__."/../../..")."/";

define("CONFIG", array_merge(
	file_exists($_ROOT."config.json") ? json_decode(file_get_contents($_ROOT."config.json"), true) : [],
	file_exists($_ROOT.".env") ? parse_ini_file($_ROOT.".env", false, INI_SCANNER_TYPED) : []
));

define("ROOT", CONFIG["ROOT"] ?? $_ROOT);
unset($_ROOT);

define("HOST", CONFIG["HOST"] ?? $_SERVER["HTTP_HOST"]);

define("DOCUMENT_ROOT", CONFIG["DOCUMENT_ROOT"] ?? realpath($_SERVER["DOCUMENT_ROOT"]));

define("WEB_ROOT", CONFIG["WEB_ROOT"] ?? ((substr(ROOT, 0, strlen(DOCUMENT_ROOT)) === DOCUMENT_ROOT) ? substr(ROOT, strlen(DOCUMENT_ROOT)) : "/"));

define("REQUEST", strpos($_SERVER["REQUEST_URI"], "?") === false ? $_SERVER["REQUEST_URI"] : strstr($_SERVER["REQUEST_URI"], "?", true));

date_default_timezone_set(CONFIG["TIMEZONE"] ?? "UTC");

foreach(get_defined_vars() as $_VAR => $_VALUE) {
	unset($$_VAR);
}
unset($_VAR, $_VALUE);

foreach(glob(__DIR__."/libs/*.php") as $_LIBRARY) {
	require_once $_LIBRARY;
}
foreach(glob(ROOT."libs/*.php") as $_LIBRARY) {
	require_once $_LIBRARY;
}
unset($_LIBRARY);

define("CONTROLLERS_PATH", ROOT."controllers/");

if(
	route(REQUEST, CONTROLLERS_PATH, ".php")["main"] === false &&
	route(REQUEST, VIEWS_PATH, ".twig")["main"] === false
) {
	echo render_twig(false);
	die;
}

define("REQUEST_PARAMS", route(REQUEST, CONTROLLERS_PATH, ".php")["params"]);

foreach(array_merge(route(REQUEST, CONTROLLERS_PATH, ".php")["before"], [route(REQUEST, CONTROLLERS_PATH, ".php")["main"]]) as $_CONTROLLER) {
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

echo render_twig(substr(route(REQUEST, ROOT."views/", ".twig")["main"], 0, -strlen(".twig")), get_defined_vars());
die;
