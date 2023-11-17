<?php
if(!function_exists("route")) {
	function route($request, $root, $extension) {
		static $routes = [];

		$check_candidates = function($current_path, $exact_match = false) use ($root, $extension) {
			if(strlen($current_path)) {
				if($exact_match) {
					$candidates = ["", "+", "/_index", "/_index+"];
				} else {
					$candidates = ["+", "/_index+"];
				}
			} else {
				if($exact_match) {
					$candidates = ["/_index", "/_index+"];
				} else {
					$candidates = ["/_index+"];
				}
			}
			foreach($candidates as $candidate) {
				if(file_exists("$root$current_path$candidate$extension")) {
					return "$current_path$candidate$extension";
				}
			}
			return false;
		};

		$route_id = "$request\0$root\0$extension";

		if(!isset($routes[$route_id])) {
			$request = trim($request, "/");
			$request_parts = strlen($request) ? explode("/", $request) : [];
			foreach($request_parts as $request_part) {
				if(
					in_array($request_part, ["", ".", ".."]) ||
					strpos($request_part, "\0") !== false ||
					substr($request_part, 0, 1) === "_" ||
					substr($request_part, -1) === "+"
				) {
					echo render_twig(false);
					die;
				}
			}

			$main = $check_candidates(implode("/", $request_parts), true);
			$params = [];
			while(($main === false) && count($request_parts)) {
				array_unshift($params, array_pop($request_parts));
				$main = $check_candidates(implode("/", $request_parts));
			}

			$before = [];
			$current_path = "";
			if(file_exists($root.$current_path."_before$extension")) {
				$before[] = "_before$extension";
			}

			foreach($request_parts as $request_part) {
				$current_path .= "$request_part/";
				if(file_exists($root.$current_path."_before$extension")) {
					$before[] = $current_path."_before$extension";
				}
			}

			$routes[$route_id] = [
				"before" => $before,
				"main" => $main,
				"params" => $params
			];
		}
		return $routes[$route_id];
	}
}
