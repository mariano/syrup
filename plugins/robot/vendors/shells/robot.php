<?php

App::import('Core', 'Dispatcher');

class RobotShell extends Shell {
	public $Dispatcher;
	public $actions = array(
		'run'
	);
	public $options = array(
		'daemon' => null,
		'debug' => false,
		'result' => false,
		'tasks' => null,
		'time' => null,
		'url' => null,
		'wait' => 1000
	);
	public $uses = array('Robot.RobotTask');

	public function main() {
		if (empty($this->args) || $this->args[0] == '?' || strtolower($this->args[0]) == 'help') {
			return $this->help();
		}

		if (!empty($this->params)) {
			$this->options = array_merge($this->options, array_intersect_key($this->params, $this->options));
			foreach(array('debug', 'result') as $booleanParameter) {
				$this->options[$booleanParameter] = (!empty($this->options[$booleanParameter]));
			}
		}

		foreach($this->actions as $i => $action) {
			if (!is_string($i)) {
				$method = '_' . $action;
				unset($this->actions[$i]);
				$this->actions[$action] = $method;
			} else {
				$method = $action;
				$action = $i;
			}
			if (!is_callable(array($this, $method))) {
				unset($this->actions[$action]);
			}
		}

		if (empty($this->actions)) {
			return $this->help('No callable actions found');
		}

		if (!in_array($this->args[0], array_keys($this->actions))) {
			return $this->help('Invalid action "' . $this->args[0] . '" specified');
		}

		if (empty($this->options['url'])) {
			$this->options['url'] = Configure::read('Robot.url');
			if (empty($this->options['url'])) {
				return $this->help('No URL specified (no -url parameter, nor config variable Robot.url)');
			}
		}
		if (stripos($this->options['url'], 'http') !== 0) {
			$this->options['url'] = 'http://' . $this->options['url'];
		}

		if (!empty($this->options['daemon'])) {
			if (empty($this->options['time']) && empty($this->options['tasks']) && strcasecmp($this->options['daemon'], 'force') != 0) {
				return $this->help('You should not enable the daemon without a task / time limit. If you still want to, set the option force for the daemon');
			} elseif (is_string($this->options['daemon'])) {
				$this->options['daemon'] = strtolower($this->options['daemon']);
			}
		}

		$this->Dispatcher = new Dispatcher();
		$action = array_shift($this->args);

		$this->_welcome($action);

		foreach(array('tasks', 'time') as $parameter) {
			if (!empty($this->options[$parameter])) {
				$this->__output(Inflector::humanize($parameter) . ' Limit: ' . $this->options[$parameter]);
			}
		}

		$this->__output();

		$startTime = microtime(true);

		$this->{$this->actions[$action]}($this->args);

		if ($this->options['debug']) {
			$endTime = microtime(true);
			$this->__output();
			$this->__output('TOTAL TIME: ' . number_format(($endTime - $startTime) * 1000, 2) . ' ms.');
		}
	}

	protected function _run($arguments = array()) {
		reset($arguments);
		$cakeAction = (!empty($arguments) ? current($arguments) : null);

		if (!empty($cakeAction)) {
			$this->__execute($cakeAction);
		} else {
			$tasks = 0;
			$startTime = microtime(true);

			do {
				$this->__output('Fetching next pending task... ', false);
				$task = $this->RobotTask->find('pending');
				$this->__output('DONE', true, false);

				if ($task === false) {
					$this->__error('Error fetching pending task');
				}

				if (empty($task)) {
					if (empty($this->options['daemon'])) {
						break;
					}

					$this->__output('Waiting ' . $this->options['wait'] . ' ms...', false);
					usleep($this->options['wait'] * 1000);
					$this->__output('DONE', true, false);
				} else {
					$this->__output('Processing task ' . $task['RobotTask']['id']);
					$this->RobotTask->started($task['RobotTask']['id']);

					$success = $this->__execute($task['RobotTaskAction']['action'], (!empty($task['RobotTask']['parameters']) ? $task['RobotTask']['parameters'] : array()));
					if (!$success) {
						$this->__error('Error running task ' . $task['RobotTask']['id'] . ' (action ' . $task['RobotTaskAction']['action'] . ')');
					}

					$this->__output('Setting task ' . $task['RobotTask']['id'] . ' as ' . ($success ? 'completed' : 'failed'));
					$this->RobotTask->finished($task['RobotTask']['id'], $success);

					$tasks++;
				}

				$ellapsed = microtime(true) - $startTime;

				if ((!empty($this->options['tasks']) && $tasks >= $this->options['tasks']) ||
					(!empty($this->options['time']) && $ellapsed >= $this->options['time'])) {
					$this->__output('Finishing task processing since limit has been reached (' . $tasks . ' tasks, ' . number_format($ellapsed * 1000, 2) . ' ms.)');
					break;
				}
			} while(true);
		}
	}

	protected function __execute($action, $parameters = array()) {
		$this->__output('Running ' . $action . '... ', false);
		$startTime = microtime(true);

		Configure::write('App.baseUrl', $this->options['url']);
		$_SERVER['HTTP_HOST'] = preg_replace('/^(https?:\/\/)?([^\/]+)/i', '\\2', $this->options['url']);

		$result = $this->Dispatcher->dispatch($action, array('robot' => $parameters, 'bare' => true, 'return' => true));

		$endTime = microtime(true);
		$this->__output(($result !== false ? 'DONE' : 'FAILED') . ' (' . number_format(($endTime - $startTime) * 1000, 2) . ' ms.)', true, false);

		if ($this->options['result']) {
			if (is_string($result)) {
				$lines = split("\n", $result);
				foreach($lines as $i => $line) {
					$lines[$i] = "\t" . $line;
				}
				$output = implode("\n", $lines);
			} else {
				$output = var_export($result, true);
			}
			$this->__output("\t" . 'Result: (' . $output . ')');
		}

		return ($result !== false);
	}

	private function __hr($time = true, $force = false) {
		$this->__output(str_repeat('-', 64), true, $time, $force);
	}

	private function __error($message) {
		$this->__output($message, true, true, true);
	}

	private function __output($message = '', $newLine = true, $time = true, $force = false) {
		if ($this->options['debug'] || $force) {
			$this->out(($time ? '[' . date('m/d/Y H:i:s') . '] ' : '') . $message, $newLine);
		}
	}

	public function _welcome($action = null, $force = false, $time = true) {
		if ($this->options['debug'] || $force) {
			$this->__hr($time, $force);
			$this->__output('Robot Runner v1.3 - A CakePHP shell based task runner', true, $time, $force);
			$this->__hr($time, $force);
			if (!empty($action)) {
				$this->__output('Action: ' . $action, true, $time, $force);
			}
		}
	}

	public function help($error = null) {
		$help = array(
			'Usage: ./cake robot <run [/cake/action]> [-daemon [force] | -debug | -result | -tasks N | -time N | -url value | -wait N]',
			'',
			'where:',
			'-daemon [force]		If no tasks, keep waiting for tasks. Set force to force daemon (if no -tasks or -time specified)',
			'-debug			Show debug info',
			'-result			Show resulting values when calling CakePHP actions (usable only if -debug set)',
			'-tasks N		Do not process more than N tasks',
			'-time N			Stop if processing is over N seconds',
			'-url			Base URL for application (if not specified, use configure Robot.url)',
			'-wait N			Wait N miliseconds before trying to fetch next task (usable only with -daemon)',
			'run			If no Cake URL specified, look and run from pending tasks, otherwise run specified action'
		);

		$this->_welcome(null, true, false);

		if (!empty($error)) {
			$this->err($error);
			$this->err('');
		}

		foreach((array) $help as $line) {
			if (!empty($error)) {
				$this->err($line);
			} else {
				$this->out($line);
			}
		}
	}
}

?>
