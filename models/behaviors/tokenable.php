<?php
class TokenableBehavior extends ModelBehavior {
	/**
	 * Default settings
	 *
	 * @var array
	 */
	protected $default = array(
		'field' => 'token',
		'length' => 8,
		'chars' => '0123456789abcdefghijklmnopqrstuvwxyz'
	);

	/**
	 * Setup behavior
	 *
	 * @param object $model Model
	 * @param array $settings Settings
	 */
	public function setup($model, $settings = array()) {
		if (!isset($this->settings[$model->alias])) {
			$this->settings[$model->alias] = $this->default;
		}

		$this->settings[$model->alias] = array_merge($this->settings[$model->alias], (array) $settings);
	}

	/**
	 * Create and save a token for given record
	 *
	 * @param object $model Model
	 * @param mixed $id Record ID
	 * @param string $field Use this field instead of the configured one
	 * @param int $length Use this length instead of the configured one
	 * @param string $chars Use this chars instead of the configured ones
	 * @return string Token
	 */
	public function token($model, $id = null, $field = null, $length = null, $chars = null) {
		foreach(array_keys($this->default) as $var) {
			if (empty($$var)) {
				$$var = $this->settings[$model->alias][$var];
			}
		}

		$token = false;
		$charCount = strlen($chars);

		while (empty($token) || $model->find('count', array('conditions' => array($model->alias . '.' . $field => $token), 'recursive' => -1)) > 0) {
			$token = '';
			for ($i=0; $i < $length; $i++) {
				$token .= $chars[mt_rand(0, $charCount - 1)];
			}
		}

		if (!empty($token)) {
			if (empty($id)) {
				$id = $model->id;
			}
			if (empty($id)) {
				$token = false;
			} else {
				$model->id = $id;
				$model->saveField($field, $token);
			}
		}

		return $token;
	}

	/**
	 * Clear token
	 *
	 * @param object $model Model
	 * @param mixed $id Record ID
	 * @param string $field Use this field instead of the configured one
	 */
	public function clearToken($model, $id = null, $field = null) {
		if (empty($field)) {
			$field = $this->settings[$model->alias]['field'];
		}
		if (empty($id)) {
			$id = $model->id;
		}

		$model->id = $id;
		return $model->saveField($field, null);
	}
}
?>
