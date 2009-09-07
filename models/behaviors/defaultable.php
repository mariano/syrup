<?php
class DefaultableBehavior extends ModelBehavior {
	/**
	 * Setup behavior
	 *
	 * @param object $model Model
	 * @param array $settings Settings
	 */
	public function setup($model, $settings = array()) {
		if (!isset($this->settings[$model->alias])) {
			$this->settings[$model->alias] = array();
		}

		if (!empty($settings)) {
			if (empty($settings['find']) && empty($settings['save'])) {
				$settings = array('find' => $settings, 'save' => $settings);
			}

			$default = array('find'=>null, 'save'=>null);
			$settings = array_intersect_key(array_merge($default, $settings), $default);
		}

		$this->settings[$model->alias] = array_merge($this->settings[$model->alias], (array) $settings);
	}

	/**
	 * Before find callback
	 *
	 * @param object $model Model using this behavior
	 * @param array $query Data used to execute this query, i.e. conditions, order, etc.
	 * @return bool True if the operation should continue, false if it should abort
	 */
	public function beforeFind($model, $query) {
		$result = parent::beforeFind($model, $query);
		if (empty($this->settings[$model->alias]['find']) || $result === false) {
			return $result;
		} else if (is_array($result)) {
			$query = $result;
		}

		$Db = ConnectionManager::getDataSource($model->useDbConfig);
		$defaultConditions = array();
		foreach($this->settings[$model->alias]['find'] as $field => $fieldValue) {
			$fields = array(
				$Db->name($model->alias) . '.' . $Db->name($field),
				$Db->name($field),
				$model->alias . '.' . $field,
				$field
			);
			$include = true;
			if (!empty($query['conditions'])) {
				foreach(Set::flatten((array) $query['conditions']) as $key => $value) {
					$condition = is_numeric($key) ? $value : $key;
					foreach($fields as $field) {
						if (
							preg_match('/^((not|or)\.)?' . preg_quote($field) . '/i', $condition) ||
							preg_match('/^((not|or)\.)?' . preg_quote($model->alias . '.' . $field) . '/i', $condition) ||
							preg_match('/[^A-Z0-9_]+' . preg_quote($field) . '[^A-Z0-9_]+/i', $condition) ||
							preg_match('/[^A-Z0-9_]+' . preg_quote($model->alias . '.' . $field) . '[^A-Z0-9_]+/i', $condition)
						) {
							$include = false;
							break;
						}
					}
					if (!$include) {
						break;
					}
				}
			}

			if ($include) {
				$defaultConditions[$field] = $fieldValue;
			}
		}

		if (!empty($defaultConditions)) {
			if (empty($query['conditions'])) {
				$query['conditions'] = array();
			} else if (!is_array($query['conditions'])) {
				$query['conditions'] = (array) $query['conditions'];
			}

			$query['conditions'] = array_merge($query['conditions'], $defaultConditions);
			$result = $query;
		}

		return $result;
	}

	/**
	 * Before save callback
	 *
	 * @param object $model Model using this behavior
	 * @return bool True if the operation should continue, false if it should abort
	 */
	public function beforeSave($model) {
		$result = parent::beforeSave($model);
		if (empty($this->settings[$model->alias]['save']) || $result === false || $model->exists()) {
			return $result;
		}

		$defaults = array();
		foreach($model->schema() as $field => $properties) {
			$defaults[$field] = $properties['default'];
		}

		$data = array();
		foreach($this->settings[$model->alias]['save'] as $field => $fieldValue) {
			if (!isset($model->data[$model->alias][$field]) || $model->data[$model->alias][$field] == $defaults[$field]) {
				$data[$field] = $fieldValue;
			}
		}

		if (!empty($data)) {
			$this->_addToWhitelist($model, array_keys($data));
			$model->data[$model->alias] = array_merge($model->data[$model->alias], $data);
		}

		return $result;
	}
}
?>
