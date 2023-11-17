<?php
if(!defined("VIEWS_PATH")) {
	define("VIEWS_PATH", ROOT."views/");
}

if(!function_exists("render_twig")) {
	function render_twig($view, $data = [], $main = true) {
		static $twig = null;
		if($twig === null) {
			$twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader(VIEWS_PATH));
			foreach(glob(ROOT."libs/renderer/*.function.php") as $function) {
				$twig->addFunction(new \Twig\TwigFunction(basename($function, ".function.php"), require($function)));
			}
			foreach(glob(ROOT."libs/renderer/*.filter.php") as $filter) {
				$twig->addFilter(new \Twig\TwigFilter(basename($filter, ".filter.php"), require($filter)));
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
}
