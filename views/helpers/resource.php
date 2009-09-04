<?php

App::import('Helper', 'Html');

class ResourceHelper extends AppHelper {
	/**
	 * Helpers used by this helper
	 *
	 * @var array
	 */
	public $helpers = array('Html', 'Javascript');

	/**
	 * Included Javascripts
	 *
	 * @var array
	 */
	private static $__javascripts = array();

	/**
	 * Included Stylesheets
	 *
	 * @var array
	 */
	private static $__stylesheets = array();

	/**
	 * Before rendering a view check for automatic files
	 */
	public function beforeRender() {
		parent::beforeRender();

		if (!empty($this->params['controller']) && !empty($this->params['action'])) {
			$candidates = array(
				$this->params['controller']
				, $this->params['controller'] . '/' . $this->params['action']
			);

			foreach($candidates as $candidate) {
				if (is_readable(CSS . DS . str_replace('/', DS, $candidate) . '.css')) {
						self::$__stylesheets[] = $candidate;
				}
			}

			foreach($candidates as $candidate) {
				if (is_readable(JS . DS . str_replace('/', DS, $candidate) . '.js')) {
						self::$__javascripts[] = $candidate . '.js';
				}
			}
		}
	}

	/**
	 * Get tags for adding dependencies.
	 *
	 * @param string $type Type of dependency (css / javascript), or leave empty for all
	 * @param View if specified, use View::addScript to include elements
	 * @return string Added tags.
	 */
	public function resources($type = null, $view = null) {
		$out = '';

		if (empty($type)) {
			$out .= $this->resources('css');
			$out .= $this->resources('javascript');
			return $out;
		}

		$type = strtolower($type);

		$resources = null;
		switch($type) {
			case 'css':
				$resources = self::$__stylesheets;
				break;
			case 'js':
			case 'javascript':
				$resources = self::$__javascripts;
				break;
		}

		if (!empty($resources)) {
			return $this->resource($resources, $type, $view);
		}

		return $out;
	}

	/**
	 * Include specified set of javascripts to header.
	 *
	 * @return string Included javascripts
	 */
	public function javascript() {
		$javascripts = func_get_args();
		$top = false;

		foreach($javascripts as $index => $javascript) {
			if (is_array($javascript)) {
				if (!empty($javascript)) {
					$javascripts = array_merge($javascripts, $javascript);
				}

				unset($javascripts[$index]);
			} elseif (is_bool($javascript)) {
				$top = $javascript;
				unset($javascripts[$index]);
			}
		}

		if ($top) {
			$javascripts = array_merge($javascripts, self::$__javascripts);
		} else {
			$javascripts = array_merge(self::$__javascripts, $javascripts);
		}

		self::$__javascripts = array_unique($javascripts);
	}

	/**
	 * Include specified set of stylesheets to header.
	 *
	 * @return string Included stylesheets
	 */
	public function css() {
		$stylesheets = func_get_args();
		$top = false;

		foreach($stylesheets as $index => $stylesheet) {
			if (is_array($stylesheet)) {
				if (!empty($stylesheet)) {
					$stylesheets = array_merge($stylesheets, $stylesheet);
				}

				unset($stylesheets[$index]);
			} elseif (is_bool($stylesheet)) {
				$top = $stylesheet;
				unset($stylesheets[$index]);
			}
		}

		if ($top) {
			$stylesheets = array_merge($stylesheets, self::$__stylesheets);
		} else {
			$stylesheets = array_merge(self::$__stylesheets, $stylesheets);
		}

		self::$__stylesheets = array_unique($stylesheets);
	}

	/**
	 * Add resources (CSS / JS) to current layout, caching and embedding all of them
	 * in one file.
	 *
	 * @param array $dependencies Set of dependencies (file locations)
	 * @param string $type Type of dependency being added (css / javascript)
	 * @param View if specified, use View::addScript to include elements
	 * @param array $settings Settings to use
	 * @return string Set of tags to add resources
	 */
	public function resource($dependencies, $type = 'css', $view = null, $settings = array()) {
		if (strtolower($type) == 'javascript') {
			$type = 'js';
		}

		if (Configure::read('Cache.resources') !== null) {
			$settings = array_merge(Configure::read('Cache.resources'), $settings);
		}

		$defaults = array(
			'cache' => false,
			'force' => false,
			'cachePath' => CACHE . 'views',
			'cacheDir' => '/',
			'cacheFileAppend' => '-203-20070814-1415.cache',
			'minimize' => true
		);

		switch($type) {
			case 'css':
				$defaults['path'] = CSS;
				break;
			case 'js':
				$defaults['path'] = JS;
				break;
		}

		$settings = array_merge($defaults, $settings);

		// Not using cache and unifier

		if (!$settings['cache'] || !is_writable($settings['cachePath'])) {
			$out = '';

			foreach($dependencies as $dependency) {
				$tag = $this->__dependencyTag($type, $dependency);
				if (is_object($view)) {
					$view->addScript($tag);
				}
				$out .= $tag;
			}

			return $out;
		}

		// Calculate signature and name cache file

		$cacheName = Security::hash(serialize($dependencies)) . $settings['cacheFileAppend'] . '.' . $type;
		$cacheFile = $settings['cachePath'] . DS . $cacheName;

		// Look for cached version of signature

		if (!file_exists($cacheFile) || $settings['force']) {
			// Build full body

			$body = '';

			foreach($dependencies as $dependency) {
				$dependency = str_replace('/', DS, $dependency);
				$file = ($dependency[0] != DS ? str_replace('/', DS, $settings['path']) : substr(WWW_ROOT, 0, -1)) . $dependency;

				if (!preg_match('/\.' . $type . '$/i', $file)) {
					$file .= '.' . $type;
				}

				// Get the contents

				if (!file_exists($file)) {
					continue;
				}

				$contents = file_get_contents($file);

				// If we have relative paths on stylesheet, make sure we set them right

				if ($type == 'css') {
					// Calculate indirections from this dependency to root

					$relativeFile = str_replace(($dependency[0] != DS ? $settings['path'] : substr(WWW_ROOT, 0, -1)), '', $file);
					$indirections = 0;

					for ($i=0, $limiti=strlen($relativeFile); $i < $limiti; $i++) {
						$indirections += ($relativeFile[$i] == DS ? 1 : 0);
					}

					$relativePath = '';
					if ($indirections > 0) {
						$relativePath = str_replace(DS . basename($relativeFile), '', $relativeFile);
					}

					$rootIndirections = 0;

					for ($i=0, $limiti=strlen($settings['cacheDir']); $i < $limiti && $limiti > 1; $i++) {
						$rootIndirections += ($settings['cacheDir'] == '/' ? 1 : 0);
					}

					// Now look for relative paths

					if (preg_match_all('/url\(("|\')?([^\)]+)\)/i', $contents, $matches)) {
						$replaces = array();

						foreach($matches[0] as $index => $original) {
							$startQuote = $matches[1][$index];
							$endQuote = $startQuote;
							$currentPath = $matches[2][$index];

							if (!empty($startQuote) && $currentPath[strlen($currentPath) - 1] == $startQuote) {
								$currentPath = substr($currentPath, 0, -1);
							}

							// Look for non absolute path

							if (stripos($currentPath, 'http:') === false && stripos($currentPath, 'https:') === false) {
								$newPath = $currentPath;

								if ($currentPath[0] == '/') {
									// Refers to root, so just add indirection (from cache to root)

									$newPath = str_repeat('../', $rootIndirections) . substr($currentPath, 1);
								} else {
									// Refers to relative path, so calculate path from root

									$convertPath = str_replace(DS, '/', $relativePath . '/' . $currentPath);

									// Remove mutually exclusive relative URLs

									$convertPath = preg_replace('/[^\/]+\/\.\.\//', '', $convertPath);

									// Get real path from relative path

									$convertPath = explode('/', $convertPath);
									$elements = array();

									for ($i=0, $limiti=count($convertPath); $i < $limiti; $i++) {
										if ($convertPath[$i] == '' || $convertPath[$i] == '.') {
											continue;
										}

										if ($convertPath[$i] == '..' && $i > 0 && isset($elements[$limiti - 1]) && $elements[$limiti - 1] != '..') {
											array_pop($elements);
											continue;
										}

										array_push($elements, $convertPath[$i]);
									}

									$newPath = str_repeat('../', $rootIndirections) . implode('/', $elements);
									if ($dependency[0] == DS) {
										$newPath = '/' . $newPath;
									}
								}

								if ($newPath != $currentPath) {
									$replaces[] = array(
										'original' => $original
										, 'replace' => 'url(' . $startQuote . $newPath . $endQuote . ')'
									);
								}
							}
						}

						if (!empty($replaces)) {
							$contents = str_replace(
								Set::extract($replaces, '{n}.original')
								, Set::extract($replaces, '{n}.replace')
								, $contents
							);
						}
					}
				}

				$body .= (!empty($body) ? "\n\n" : '') . $contents;
			}

			if ($type == 'js') {
				// Add a global variable to indicate which JS file is being used

				$body = '__PackagedJavaScriptFile = \'' . $settings['cacheDir'] . $cacheName . '\';' . "\n\n" . $body;

				if ($settings['minimize'] !== false) {
					// Normalize new lines

					$body = str_replace("\\\r\n", "\\n", $body);
					$body = str_replace("\\\n", "\\n", $body);
					$body = str_replace("\\\r", "\\n", $body);

					// Remove extra spaces

					$body = preg_replace("/\\n\s*\\n*/s", "\n", $body);
				}
			} elseif ($type == 'css') {
				// Make sure all import tags are at the beggining

				$imports = array();

				if (preg_match_all('/@import\s+url\(([^\)]+)\)\s*;?/i', $body, $matches)) {
					foreach($matches[0] as $match) {
						$imports[] = $match;
					}
				}

				if (!empty($imports)) {
					$body = implode("\n", $imports) . "\n\n" . str_replace($imports, '', $body);
				}
			}

			// Create cache file

			file_put_contents($cacheFile, $body);
		}

		// Strip out extension from cached file

		$cacheName = preg_replace('/\.' . $type . '$/i', '', $cacheName);

		// Add cached file as a view dependency

		$out = '';
		$tag = $this->__dependencyTag($type, ($settings['cacheDir'] != '/' ? $settings['cacheDir'] : '') . $cacheName);
		if (is_object($view)) {
			$view->addScript($tag);
		}
		$out .= $tag;

		return $out;
	}

	/**
	 * Build dependency tag
	 *
	 * @param string $type Type of dependency (css / js)
	 * @param string $dependency Dependency location
	 * @return string Tag
	 */
	private function __dependencyTag($type, $dependency) {
		$line = null;
		switch($type) {
			case 'css':
				$line = $this->Html->css($dependency);
				break;
			case 'js':
			case 'javascript':
				$line = $this->Javascript->link($dependency);
				break;
		}
		return $line;
	}
}

?>
