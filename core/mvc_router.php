<?php

class MvcRouter {
	
	public $routes = array();

	public function public_url($options=array()) {
		$routes = self::get_public_routes();
		$controller = $options['controller'];
		$action = empty($options['action']) ? 'index' : $options['action'];
		$matched_route = null;
		if (!empty($options['object']) && is_object($options['object'])) {
			$model_name = MvcInflector::camelize(MvcInflector::singularize($controller));
			$model = MvcModelRegistry::get_model($model_name);
			if (!empty($model) && method_exists($model, 'to_url')) {
				$url = site_url('/');
				$url .= $model->to_url($options['object']);
				return $url;
			}
			if (empty($options['id']) && !empty($options['object']->__id)) {
				$options['id'] = $options['object']->__id;
			}
		}
		foreach($routes as $route) {
			$route_path = $route[0];
			$route_defaults = $route[1];
			if (!empty($route_defaults['controller']) && $route_defaults['controller'] == $controller) {
				if (!empty($route_defaults['action']) && $route_defaults['action'] == $action) {
					$matched_route = $route;
				}
			}
		}
		$url = site_url('/');
		if ($matched_route) {
			$path_pattern = $matched_route[0];
			preg_match_all('/{:([\w]+).*?}/', $path_pattern, $matches, PREG_SET_ORDER);
			$path = $path_pattern;
			foreach($matches as $match) {
				$pattern = $match[0];
				$option_key = $match[1];
				if (isset($options[$option_key])) {
					$value = $options[$option_key];
					$path = preg_replace('/'.preg_quote($pattern).'/', $value, $path, 1);
				}
			}
			$path = rtrim($path, '/').'/';
			$url .= $path;
		} else {
			$url .= $options['controller'].'/';
			if (!empty($options['action']) && $options['action'] != 'show') {
				$url .= $options['action'].'/';
			}
			if (!empty($options['id'])) {
				$url .= $options['id'].'/';
			}
		}
		return $url;
	}

	public function admin_url($options=array()) {
		if (!empty($options['object']) && is_object($options['object'])) {
			if (empty($options['id']) && !empty($options['object']->__id)) {
				$options['id'] = $options['object']->__id;
			}
		}
		$url = get_admin_url().'admin.php';
		$params = http_build_query(self::admin_url_params($options));
		if ($params) {
			$url .= '?'.$params;
		}
		return $url;
	}
	
	public function admin_url_params($options=array()) {
		$params = array();
		if (!empty($options['controller'])) {
			$controller = preg_replace('/^admin_/', '', $options['controller']);
			$params['page'] = 'mvc_'.$controller;
			if (!empty($options['action']) && $options['action'] != 'index') {
				$params['page'] .= '-'.$options['action'];
			}
		}
		if (!empty($options['id'])) {
			$params['id'] = $options['id'];
		}
		return $params;
	}
	
	public function admin_page_param($options=array()) {
		if (is_string($options)) {
			$options = array('model' => $options);
		}
		if (!empty($options['model'])) {
			return 'mvc_'.MvcInflector::tableize($options['model']);
		}
		return false;
	}

	public function public_connect($route, $defaults=array()) {
		$_this =& MvcRouter::get_instance();
		$_this->add_public_route($route, $defaults);
	}
	
	public function admin_ajax_connect($route) {
		$_this =& MvcRouter::get_instance();
		$_this->add_admin_ajax_route($route);
	}

	private function &get_instance() {
		static $instance = array();
		if (!$instance) {
			$instance[0] =& new MvcRouter();
			$instance[0]->routes = array(
				'public' => array(),
				'admin_ajax' => array()
			);
		}
		return $instance[0];
	}

	public function &get_public_routes() {
		$_this =& self::get_instance();
		$return =& $_this->routes['public'];
		return $return;
	}

	public function &get_admin_ajax_routes() {
		$_this =& self::get_instance();
		$return =& $_this->routes['admin_ajax'];
		return $return;
	}
	
	public function add_public_route($route, $defaults) {
		$_this =& self::get_instance();
		$_this->routes['public'][] = array($route, $defaults);
	}
	
	public function add_admin_ajax_route($route) {
		$_this =& self::get_instance();
		if (empty($route['wp_action'])) {
			$route['wp_action'] = $route['controller'].'_'.$route['action'];
		}
		$_this->routes['admin_ajax'][] = $route;
	}

}

?>