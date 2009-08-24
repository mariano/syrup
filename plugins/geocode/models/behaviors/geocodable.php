<?php
App::import('Core', array('HttpSocket', 'Security'));

class GeocodableBehavior extends ModelBehavior {
	/**
	 * Behavior settings
	 *
	 * @var array
	 * @access public
	 */
	public $settings;

	/**
	 * Default settings
	 *
	 * @var array
	 * @access public
	 */
	public $default = array(
		'service' => 'google',
		'key' => null,
		'fields' => array(
			'hash' => false,
			'address',
			'latitude',
			'longitude',
			'address1',
			'address2',
			'city',
			'state',
			'zip',
			'country'
		),
		'addressFields' => array(
			'address1' => array('addr', 'address_1'),
			'address2' => array('addr2', 'address_2'),
			'city',
			'state',
			'zip' => array('zipcode', 'zip_code', 'postal_code'),
			'country'
		)
	);

	/**
	 * Service information
	 *
	 * @var array
	 * @access protected
	 */
	protected $services = array(
		'google' => array(
			'url' => 'http://maps.google.com/maps/geo?q=${address}&output=csv&key=${key}',
			'format' => '${address1} ${address2}, ${city}, ${zip} ${state}, ${country}',
			'pattern' => '/200,[^,]+,([^,]+),([^,\s]+)/',
			'matches' => array(
				'latitude' => 1,
				'longitude' => 2
			)
		),
		'yahoo' => array(
			'url' => 'http://api.local.yahoo.com/MapsService/V1/geocode?appid=${key}&location=${address}',
			'format' => '${address1} ${address2}, ${city}, ${zip} ${state}, ${country}',
			'pattern' => '/<Latitude>(.*?)<\/Latitude><Longitude>(.*?)<\/Longitude>/U',
			'matches' => array(
				'latitude' => 1,
				'longitude' => 2
			)
		)
	);

	/**
	 * HttpSocket instance
	 *
	 * @var object
	 * @access protected
	 */
	protected $socket;

	/**
	 * Units relative to 1 kilometer.
	 * k: kilometers, m: miles, f: feet, i: inches, n: nautical miles
	 *
	 * @var array
	 * @access protected
	 */
	protected $units = array('k' => 1, 'm' => 0.621371192, 'f' => 3280.8399, 'i' => 39370.0787, 'n' => 0.539956803);

	/**
	 * Setup behavior
	 *
	 * @param object $model Model
	 * @param array $settings Settings
	 * @access public
	 */
	public function setup($model, $settings = array()) {
		if (!isset($this->settings[$model->alias])) {
			$configured = Configure::read('Geocode');
			if (!empty($configured)) {
				foreach($this->default as $key => $value) {
					if (isset($configured[$key])) {
						$this->default[$key] = $configured[$key];
					}
				}
			}
			$this->settings[$model->alias] = $this->default;
		}

		$settings = Set::merge($this->settings[$model->alias], $settings);

		if (empty($this->services[strtolower($settings['service'])])) {
			trigger_error(sprintf(__('Geocode service %s not implemented', true), $settings['service']), E_USER_WARNING);
			return false;
		}

		if (!isset($this->socket)) {
			$this->socket = new HttpSocket();
		}

		foreach(array('fields', 'addressFields') as $parameter) {
			$fields = array();
			foreach($settings[$parameter] as $i => $field) {
				$fields[is_numeric($i) ? $field : $i] = ($parameter != 'fields' || $model->hasField($field) ? $field : false);
			}
			$settings[$parameter] = $fields;
		}

		$this->settings[$model->alias] = $settings;
	}

	/**
	 * Before save callback
	 *
	 * @param object $model Model using this behavior
	 * @return bool true if the operation should continue, false if it should abort
	 * @access public
	 */
	public function beforeSave($model) {
		$latitudeField = $this->settings[$model->alias]['fields']['latitude'];
		$longitudeField = $this->settings[$model->alias]['fields']['longitude'];

		if (
			!empty($latitudeField) && !empty($longitudeField) &&
			!isset($model->data[$model->alias][$latitudeField]) && !isset($model->data[$model->alias][$longitudeField])
		) {
			$geocode = $this->geocode($model, $model->data[$model->alias], false);
			if (!empty($geocode)) {
				list(
					$model->data[$model->alias][$latitudeField],
					$model->data[$model->alias][$longitudeField]
				) = $geocode;

				$this->_addToWhitelist($model, array($latitudeField, $longitudeField));
			}
		}

		return parent::beforeSave($model);
	}

	/**
	 * Calculate geocode for given address, getting it from DB if already calculated
	 *
	 * @param object $model
	 * @param mixed $address Array with address info (address, city, etc.) or full address as string
	 * @param bool $save Set to true to save result in model, false otherwise
	 * @return mixed Array (latitude, longitude), or false if error
	 * @access public
	 */
	public function geocode($model, $address, $save = true) {
		$settings = $this->settings[$model->alias];
		$fullAddress = $this->_address($settings, $address);
		if (empty($fullAddress)) {
			return false;
		}

		$data = array($model->alias => array());
		$conditions = array();

		if (!empty($settings['fields']['hash'])) {
			$hash = Security::hash($fullAddress);
			$conditions[$model->alias . '.' . $settings['fields']['hash']] = $hash;
			$data[$model->alias][$settings['fields']['hash']] = $hash;
		} else if (!empty($settings['fields']['address'])) {
			$conditions[$model->alias . '.' . $settings['fields']['address']] = $fullAddress;
		}

		if (!empty($settings['fields']['address'])) {
			$data[$model->alias][$settings['fields']['address']] = $fullAddress;
		}

		if (is_array($address)) {
			foreach(array_intersect_key($address, $settings['fields']) as $field => $value) {
				if (empty($settings['fields']['hash']) && empty($settings['fields']['address'])) {
					$conditions[$model->alias . '.' . $field] = $value;
				}
				$data[$model->alias][$field] = $value;
			}
		}

		if (empty($settings['fields']['latitude']) || empty($settings['fields']['longitude'])) {
			$conditions = null;
			$data = null;
		}

		$coordinates = false;
		if (!empty($conditions)) {
			$coordinates = $model->find('first', array(
				'conditions' => $conditions,
				'recursive' => -1,
				'fields' => array($settings['fields']['latitude'], $settings['fields']['longitude'])
			));
			if (!empty($coordinates)) {
				$coordinates = array(
					$coordinates[$model->alias][$settings['fields']['latitude']],
					$coordinates[$model->alias][$settings['fields']['longitude']],
				);
			}
		}

		if (empty($coordinates) && empty($settings['key'])) {
			trigger_error(__('Address not found in model and no API key was provided', true), E_USER_WARNING);
			return false;
		}

		if (empty($coordinates)) {
			$coordinates = $this->_fetchCoordinates($settings, $fullAddress);
		}

		if (!empty($coordinates)) {
			foreach($coordinates as $i => $coordinate) {
				$coordinates[$i] = floatval($coordinate);
			}
		}

		if ($save && !empty($coordinates) && !empty($data)) {
			$data[$model->alias][$settings['fields']['latitude']] = $coordinates[0];
			$data[$model->alias][$settings['fields']['longitude']] = $coordinates[1];

			$model->create();
			$model->save($data);
		}

		return $coordinates;
	}

	/**
	 * Find points near given point for already saved records
	 *
	 * @param object $model Model
	 * @param string $type Find type (first / all / etc.)
	 * @param mixed $origin A point (latitude, longitude), a full address string, or an array of address data
	 * @param float $distance Set to a maximum distance if you wish to limit results
	 * @param string $unit Unit (k: kilometers, m: miles, f: feet, i: inches, n: nautical miles)
	 * @param array $query Query settings (as given to normal find operations) to override
	 * @return mixed Results
	 * @access public
	 */
	public function near($model, $type, $origin, $distance = null, $unit = 'k', $query = array()) {
		$settings = $this->settings[$model->alias];
		list($latitudeField, $longitudeField) = array(
			$settings['fields']['latitude'],
			$settings['fields']['longitude'],
		);

		if (!empty($query['fields'])) {
			$query['fields'] = array_merge($query['fields'], array(
				$latitudeField,
				$longitudeField
			));
		}

		$point = null;
		if (is_array($origin) && count($origin) == 2 && isset($origin[0]) && isset($origin[1]) && is_numeric($origin[0]) && is_numeric($origin[1])) {
			$point = $origin;
		} else {
			$point = $this->geocode($model, $origin);
		}

		if (empty($point)) {
			return false;
		}

		if (empty($query['order'])) {
			unset($query['order']);
		}

		$query = Set::merge(
			$this->distanceQuery($model, $point, $distance, $unit, !empty($query['direction']) ? $query['direction'] : 'ASC'),
			array_diff_key($query, array('direction'=>true))
		);

		if ($type == 'count' && !empty($query['order'])) {
			unset($query['order']);
		}
		$result = $model->find($type, $query);

		if (!empty($result) && $type != 'count') {
			$result = $this->_loadDistance($model, $result, $point, $unit, $model->alias, $latitudeField, $longitudeField);
		}

		return $result;
	}

	/**
	 * Calculate distance (in given unit) between two given points, each of them
	 * expressed as latitude, longitude. Uses the haversine formula.
	 *
	 * @param object $model Model
	 * @param mixed $origin Starting point (latitude, longitude), expressed in numeric degrees, a full address string, or array with address data
	 * @param mixed $destination Ending point (latitude, longitude), expressed in numeric degrees, a full address string, or array with address data
	 * @param string $unit Unit (k: kilometers, m: miles, f: feet, i: inches, n: nautical miles)
	 * @return float Distance expressed in given unit
	 * @access public
	 */
	public function distance($model, $origin, $destination, $unit = 'k') {
		$unit = (!empty($unit) && array_key_exists(strtolower($unit), $this->units) ? $unit : 'k');
		$point1 = null;
		$point2 = null;

		foreach(array('point1'=>'origin', 'point2'=>'destination') as $var => $parameter) {
			$data = $$parameter;
			if (is_array($data) && count($data) == 2 && isset($data[0]) && isset($data[1]) && is_numeric($data[0]) && is_numeric($data[1])) {
				$$var = $data;
			} else {
				$$var = $this->geocode($model, $data);
			}
		}

		if (empty($point1) || empty($point2)) {
			return false;
		}

		$line = array(
			deg2rad($point2[0] - $point1[0]),
			deg2rad($point2[1] - $point1[1])
		);
		$angle = sin($line[0]/2) * sin($line[0]/2) + sin($line[1]/2) * sin($line[1]/2) * cos(deg2rad($point1[0])) * cos(deg2rad($point2[0]));
		$earthRadiusKm = 6371;
		return ($earthRadiusKm * 2 * atan2(sqrt($angle), sqrt(1 - $angle))) * $this->units[strtolower($unit)];
	}

	/**
	 * Give back needed condition / ordering clause to find points near given point
	 *
	 * @param object $model Model
	 * @param array $point Point (latitude, longitude), expressed in numeric degrees
	 * @param float $distance If specified, add condition to only match points within given distance
	 * @param string $unit Unit (k: kilometers, m: miles, f: feet, i: inches, n: nautical miles)
	 * @param string $direction Sorting direction (ASC / DESC)
	 * @return array Query parameters (conditions, order)
	 * @access public
	 */
	public function distanceQuery($model, $point, $distance = null, $unit = 'k', $direction = 'ASC') {
		$unit = (!empty($unit) && array_key_exists(strtolower($unit), $this->units) ? $unit : 'k');
		$settings = $this->settings[$model->alias];
		foreach($point as $k => $v) {
			$point[$k] = floatval($v);
		}
		list($latitude, $longitude) = $point;
		list($latitudeField, $longitudeField) = array(
			$model->escapeField($settings['fields']['latitude']),
			$model->escapeField($settings['fields']['longitude']),
		);
		$earthRadiusKm = 6371;

		$expression = '(' . $earthRadiusKm . ' * 2 * ATAN2(
			SQRT(
				SIN(RADIANS(' . $latitude . ' - ' . $latitudeField . ')/2) * SIN(RADIANS(' . $latitude . ' - ' . $latitudeField . ')/2) +
				SIN(RADIANS(' . $longitude . ' - ' . $longitudeField . ')/2) * SIN(RADIANS(' . $longitude . ' - ' . $longitudeField . ')/2) *
				COS(RADIANS(' . $latitude . ')) * COS(RADIANS(' . $longitude . '))
			),
			SQRT(1 - (
				SIN(RADIANS(' . $latitude . ' - ' . $latitudeField . ')/2) * SIN(RADIANS(' . $latitude . ' - ' . $latitudeField . ')/2) +
				SIN(RADIANS(' . $longitude . ' - ' . $longitudeField . ')/2) * SIN(RADIANS(' . $longitude . ' - ' . $longitudeField . ')/2) *
				COS(RADIANS(' . $latitude . ')) * COS(RADIANS(' . $longitude . '))
			))
		) * ' . $this->units[strtolower($unit)] . ')';

		$expression = str_replace("\n", ' ', $expression);
		$query = array(
			'order' => $expression . ' ' . $direction,
			'conditions' => array(
				'ROUND(' . $latitudeField . ', 4) !=' => round($latitude, 4),
				'ROUND(' . $longitudeField . ', 4) !=' => round($longitude, 4)
			)
		);

		if (!empty($distance)) {
			$query['conditions'][] = $expression . ' <= ' . $distance;
		}

		return $query;
	}

	/**
	 * Navigate result rows and calculate distance
	 *
	 * @param object $model Model
	 * @param array $result Result rows
	 * @param array $point Point (latitude, longitude), expressed in numeric degrees
	 * @param string $unit Unit (k: kilometers, m: miles, f: feet, i: inches, n: nautical miles)
	 * $param string $alias Location model alias
	 * @param string $latitudeField Name of latitude field
	 * @param string $longitudeField Name of longitude field
	 * @return array Modified results
	 * @access protected
	 */
	protected function _loadDistance($model, $result, $point, $unit, $alias, $latitudeField, $longitudeField) {
		if (!is_array($result)) {
			return $result;
		} else if (!empty($result[$alias])) {
			$result[$alias]['distance'] = $this->distance($model,
				array($result[$alias][$latitudeField], $result[$alias][$longitudeField]),
				$point,
				$unit
			);
		} else {
			foreach($result as $i => $row) {
				$result[$i] = $this->_loadDistance($model, $row, $point, $unit, $alias, $latitudeField, $longitudeField);
			}
		}

		return $result;
	}

	/**
	 * Query a service to get coordinates for given address
	 *
	 * @param array $settings Settings
	 * @param string $address Full address
	 * @return array Coordinates (latitude, longitude), expressed in numeric degrees
	 * @access protected
	 */
	protected function _fetchCoordinates($settings, $address) {
		$vars = array(
			'${key}' => $settings['key'],
			'${address}' => $address
		);
		$service = $this->services[$settings['service']];

		foreach($vars as $var => $value) {
			$vars[$var] = urlencode($value);
		}

		$url = str_replace(array_keys($vars), $vars, $service['url']);
		$result = $this->socket->get($url);

		if (empty($result) || !preg_match($service['pattern'], $result, $matches)) {
			return false;
		}

		$coordinates = array(
			$matches[$service['matches']['latitude']],
			$matches[$service['matches']['longitude']]
		);

		return $coordinates;
	}

	/**
	 * Build full address from given address
	 *
	 * @param array $settings Settings
	 * @param mixed $address If array, will look for normal address parameters (address, city, etc.)
	 * @return string Full address
	 * @access protected
	 */
	protected function _address($settings, $address) {
		if (is_array($address)) {
			$elements = array();
			foreach($settings['addressFields'] as $type => $fields) {
				$fields = array_merge(array($type => $type), (array) $fields);
				$elements['${' . $type . '}'] = str_replace(',', ' ', trim(current(array_intersect_key($address, array_flip($fields)))));
			}
			$nonEmpty = array_filter($elements);
			if (empty($nonEmpty)) {
				return null;
			}

			$address = trim(str_replace(array_keys($elements), $elements, $this->services[$settings['service']]['format']));
			$replacements = array(
				'/(\s)\s+/' => '\\1',
				'/\s+,(.+)/' => ',\\1',
				'/\s*,\s*,/' => ',',
				'/,\s*$/' => ''

			);
			foreach($replacements as $pattern => $replacement) {
				$address = preg_replace($pattern, $replacement, $address);
			}
			$address = preg_replace('/,\s*$/', '', $address);
		}

		return $address;
	}
}

?>
