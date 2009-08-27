<?php

class RobotTask extends AppModel {
	/**
	 * belongsTo bindings
	 *
	 * @var array
	 * @access public
	 */
	public $belongsTo = array('Robot.RobotTaskAction');

	/**
	 * Fields to be compressed. Can be overriden with configure variable Robot.compress
	 *
	 * @var array
	 * @access private
	 */
	private $compress = array();

	/**
	 * Called before each save operation, after validation. Return a non-true result
	 * to halt the save.
	 *
	 * @return boolean True if the operation should continue, false if it should abort
	 * @access public
	 * @link http://book.cakephp.org/view/683/beforeSave
	 */
	public function beforeSave(){
		$fields = Configure::read('Robot.compress');
		if (is_null($fields)) {
			$fields = $this->compress;
		}

		$return = parent::beforeSave();
		if ($return === false || empty($fields)) {
			return $return;
		}

		foreach ((array) $fields as $field) {
			if (isset($this->data[$this->alias][$field])) {
				$this->data[$this->alias][$field] = $this->compress($this->data[$this->alias][$field]);
			}
		}

		return $return;
	}

	/**
	 * Called after each successful save operation.
	 *
	 * @param boolean $created True if this save created a new record
	 * @access public
	 * @link http://book.cakephp.org/view/684/afterSave
	 */
	public function afterSave($created){
		$fields = Configure::read('Robot.compress');
		if (is_null($fields)) {
			$fields = $this->compress;
		}

		$return = parent::afterSave($created);
		if (empty($fields)) {
			return $return;
		}

		foreach ((array) $fields as $field) {
			if (isset($this->data[$this->alias][$field])) {
				$this->data[$this->alias][$field] = $this->uncompress($this->data[$this->alias][$field]);
			}
		}

		return $return;
	}

	/**
	 * Called after each find operation. Can be used to modify any results returned by find().
	 * Return value should be the (modified) results.
	 *
	 * @param mixed $results The results of the find operation
	 * @param boolean $primary Whether this model is being queried directly (vs. being queried as an association)
	 * @return mixed Result of the find operation
	 * @access public
	 * @link http://book.cakephp.org/view/681/afterFind
	 */
	public function afterFind(&$results, $primary) {
		$fields = Configure::read('Robot.compress');
		if (is_null($fields)) {
			$fields = $this->compress;
		}

		$return = parent::afterFind($results, $primary);
		if (empty($fields)) {
			return $return;
		} else if (is_array($return)) {
			$results = $return;
		}

		foreach ((array) $fields as $field) {
			foreach ($results as $i => $record) {
				if (isset($record[$this->alias][$field])) {
					$results[$i][$this->alias][$field] = $this->uncompress($record[$this->alias][$field]);
				}
			}
		}
		return $results;
	}

	/**
	 * Schedule an action for execution.
	 *
	 * @param mixed $action Either a TaskAction ID, or the action (such as /emails/send)
	 * @param array $parameters Extra parameters for the action (received in Controller::$params['robot'])
	 * @param mixed $scheduled A string to pass to strtotime(), or the time value
	 * @return mixed Result of save() call
	 * @access public
	 */
	public function schedule($action, $parameters = array(), $scheduled = null) {
		if (empty($scheduled)) {
			$scheduled = 'now';
		}

		if (is_string($scheduled)) {
			$scheduled = strtotime($scheduled);
		}

		$taskAction = $this->RobotTaskAction->action($action);
		if (empty($taskAction)) {
			return false;
		}

		$task = array($this->alias => array(
			'robot_task_action_id' => $taskAction['RobotTaskAction']['id'],
			'status' => 'pending',
			'scheduled' => date('Y-m-d H:i:s', $scheduled),
			'parameters' => (!empty($parameters) ? serialize($parameters) : null)
		));

		$this->create();
		$result = $this->save($task);

		if (!empty($result) && !empty($result[$this->alias])) {
			$result[$this->alias][$this->primaryKey] = $this->id;
		}

		return $result;
	}

	/**
	 * Set a task as started
	 *
	 * @param string $id Task ID
	 * @return mixed Result of save() call
	 * @access public
	 */
	public function started($id = null) {
		if (empty($id)) {
			$id = $this->id;
		}

		$task = array($this->alias => array(
			'id' => $id,
			'status' => 'running',
			'started' => date('Y-m-d H:i:s')
		));

		return $this->save($task, true, array_keys($task[$this->alias]));
	}

	/**
	 * Set a task as finished
	 *
	 * @param string $id Task ID
	 * @return mixed Result of save() call
	 * @access public
	 */
	public function finished($id = null, $success = true) {
		if (empty($id)) {
			$id = $this->id;
		}

		$task = array($this->alias => array(
			'id' => $id,
			'status' => ($success ? 'completed' : 'failed'),
			'finished' => date('Y-m-d H:i:s')
		));

		return $this->save($task, true, array_keys($task[$this->alias]));
	}

	/**
	 * Returns a result set array.
	 *
	 * @param array $conditions SQL conditions array, or type of find operation (all / first / count / neighbors / list / threaded)
	 * @param mixed $fields Either a single string of a field name, or an array of field names, or options for matching
	 * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC")
	 * @param integer $recursive The number of levels deep to fetch associated records
	 * @return array Array of records
	 * @access public
	 */
	public function find($conditions = null, $fields = array(), $order = null, $recursive = null) {
		if (is_string($conditions) && $conditions == 'pending') {
			$cacheQueries = $this->cacheQueries;
			$this->cacheQueries = false;

			$limit = 1;
			if(!empty($fields['limit'])) {
				$limit = $fields['limit'];
				unset($fields['limit']);
			}

			$type = $conditions;
			$options = Set::merge(array(
				'recursive' => 0
				, 'conditions' => array(
					$this->alias . '.status' => 'pending',
					$this->alias . '.scheduled <=' => date('Y-m-d H:i:s')
				),
				'order' => array(
					'RobotTaskAction.weight' => 'asc',
					$this->alias . '.scheduled' => 'asc'
				),
				'limit' => $limit . ' FOR UPDATE'
			), (array) $fields);

			$this->begin();
			$tasks = parent::find('all', $options);
			if ($tasks === false) {
				$this->rollback();
			} else {
				foreach($tasks as $i => $task) {
					$task[$this->alias]['status'] = 'processing';

					$this->id = $task[$this->alias][$this->primaryKey];
					if ($this->saveField('status', 'processing')) {
						if (!empty($task[$this->alias]['parameters'])) {
							$task[$this->alias]['parameters'] = unserialize($task[$this->alias]['parameters']);
						}
						$tasks[$i] = $task;
					} else {
						unset($tasks[$i]);
					}
				}
				$this->commit();
			}

			$this->cacheQueries = $cacheQueries;

			if ($limit == 1 && !empty($tasks)) {
				reset($tasks);
				$tasks = current($tasks);
			}

			return $tasks;
		}
		return parent::find($conditions, $fields, $order, $recursive);
	}

	/**
	 * Compress data using zlib in a format compatible with MySQL's COMPRESS() function.
	 *
	 * @url http://dev.mysql.com/doc/refman/5.0/en/encryption-functions.html#function_compress
	 * @param $data Data to compress.
	 * @return string Compressed data
	 * @access private
	 */
	private function compress($data) {
		if (!empty($data)) {
			// MySQL requires the compressed data to start with a 32-bit little-endian integer of the original length of the data
			$data = pack('V', strlen($data)) . gzcompress($data, 9);
			// If the compressed data ends with a space, MySQL adds a period
			if (substr($data, -1) == ' ' ) {
				$data .= '.';
			}
		}
		return $data;
	}

	/**
	 * Uncompress data using zlib in from format compatible with MySQL's COMPRESS() function.
	 *
	 * @url http://dev.mysql.com/doc/refman/5.0/en/encryption-functions.html#function_compress
	 * @param $data Data to uncompress.
	 * @return string Uncompressed data.
	 * @access private
	 */
	private function uncompress($data) {
		if (!empty($data)) {
			// MySQL requires the compressed data to start with a 32-bit little-endian integer of the original length of the data
			$data = @gzuncompress(substr($data, 4));
		}
		return $data;
	}

}

?>
