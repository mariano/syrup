<?php
class CompressibleBehavior extends ModelBehavior {
	/**
	 * Setup this behavior with the specified configuration settings.
	 *
	 * @param object $model Model using this behavior
	 * @param array $config Configuration settings for $model
	 */
	public function setup($model, $settings = array()) {
		$this->settings[$model->alias] = $settings;
	}

	/**
	 * Before save callback
	 *
	 * @param object $model Model using this behavior
	 * @return boolean True if the operation should continue, false if it should abort
	 */
	public function beforeSave($model){
		$return = parent::beforeSave($model);
		if ($return === false) {
			return $return;
		}

		foreach ($this->settings[$model->alias] as $field) {
			if (isset($model->data[$model->alias][$field])) {
				$model->data[$model->alias][$field] = $this->compress($model, $model->data[$model->alias][$field]);
			}
		}

		return $return;
	}

	/**
	 * After save callback
	 *
	 * @param object $model Model using this behavior
	 * @param boolean $created True if this save created a new record
	 */
	public function afterSave($model, $created){
		$return = parent::afterSave($model, $created);

		foreach ($this->settings[$model->alias] as $field) {
			if (isset($model->data[$model->alias][$field])) {
				$model->data[$model->alias][$field] = $this->uncompress($model, $model->data[$model->alias][$field]);
			}
		}

		return $return;
	}

	/**
	 * After find callback. Can be used to modify any results returned by find and findAll.
	 *
	 * @param object $model Model using this behavior
	 * @param mixed $results The results of the find operation
	 * @param boolean $primary Whether this model is being queried directly (vs. being queried as an association)
	 * @return mixed Result of the find operation
	 */
	public function afterFind($model, $results, $primary) {
		$return = parent::afterFind($model, $results, $primary);
		if (is_array($return)) {
			$results = $return;
		}

		foreach ($this->settings[$model->alias] as $field) {
			foreach ($results as $i => $record) {
				if (isset($record[$model->alias][$field])) {
					$results[$i][$model->alias][$field] = $this->uncompress($model, $record[$model->alias][$field]);
				}
			}
		}
		return $results;
	}

	/**
	 * Compress data using zlib in a format compatible with MySQL's COMPRESS() function.
	 *
	 * @url http://dev.mysql.com/doc/refman/5.0/en/encryption-functions.html#function_compress
	 * @param object $model Model using this behavior
	 * @param $data Data to compress.
	 * @return string Compressed data
	 */
	public function compress($model, $data) {
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
	 * @param object $model Model using this behavior
	 * @param $data Data to uncompress.
	 * @return string Uncompressed data.
	 */
	public function uncompress($model, $data) {
		if (!empty($data)) {
			// MySQL requires the compressed data to start with a 32-bit little-endian integer of the original length of the data
			$data = @gzuncompress(substr($data, 4));
		}
		return $data;
	}
}
?>
