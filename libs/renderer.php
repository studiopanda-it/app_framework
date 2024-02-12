<?php
namespace StudioPanda;

function render_twig($view, $data = [], $main = true) {
	static $twig = null;
	if($twig === null) {
		$twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader(VIEWS_PATH));
		foreach(glob(ROOT."libs/renderer/*.php") as $function) {
			$fn = require($function);
			if($fn["type"] == "filter") {
				$twig->addFilter(new \Twig\TwigFilter($fn["name"], $fn["function"]), $fn["options"] ?? []);
			} else if($fn["type"] == "function") {
				$twig->addFunction(new \Twig\TwigFunction($fn["name"], $fn["function"]), $fn["options"] ?? []);
			}
		}
	}
	$constants = array_merge(get_defined_constants(true)["user"], ["_GET" => $_GET, "_POST" => $_POST, "_FILES" => $_FILES, "_COOKIE" => $_COOKIE, "_SESSION" => $_SESSION ?? [], "_SERVER" => $_SERVER]);
	if(file_exists(VIEWS_PATH."$view.twig")) {
		return $twig->render("$view.twig", array_merge($constants, $data));
	} else {
		if($main) {
			http_response_code(404);
			if(file_exists(VIEWS_PATH."_404.twig")) {
				return $twig->render("_404.twig", $constants);
			}
			return "404 page not found";
		} else {
			return "";
		}
	}
}

function output_twig(...$args) {
	echo render_twig(...$args);
	die;
}

function render_json($data = []) {
	return json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
}

function output_json(...$args) {
	header("Content-Type: application/json");
	echo render_json(...$args);
	die;
}
