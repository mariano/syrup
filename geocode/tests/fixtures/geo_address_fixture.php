<?php
class GeoAddressFixture extends CakeTestFixture {
	public $name = 'GeoAddress';
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'address' => array('type' => 'string'),
		'address1' => array('type' => 'string', 'length' => 255),
		'address2' => array('type' => 'string', 'length' => 255, 'null' => true),
		'city' => array('type' => 'string', 'length' => 255, 'null' => true),
		'state' => array('type' => 'string', 'length' => 255, 'null' => true),
		'zip' => array('type' => 'string', 'length' => 10),
		'country' => array('type' => 'string', 'length' => 255, 'null' => true),
		'latitude' => array('type' => 'float'),
		'longitude' => array('type' => 'float')
	);
	public $records = array(
		array(
			'id' => '4a8f70ed-437c-45c5-ac04-0dc97f000101',
			'address' => '1209 La Brad Lane, Tampa, FL',
			'address1' => '1209 La Brad Lane',
			'city' => 'Tampa',
			'state' => 'FL',
			'zip' => null,
			'country' => null,
			'latitude' => 28.0792040,
			'longitude' => -82.4735510
		),
		array(
			'id' => '58325bdc-e08a-102c-b987-00138fbbb402',
			'address' => '14348 N Rome Ave, Tampa, 33613 FL',
			'address1' => '14348 N Rome Ave',
			'city' => 'Tampa',
			'state' => 'FL',
			'zip' => 33613,
			'country' => null,
			'latitude' => 28.0780514,
			'longitude' => -82.4758438
		),
		array(
			'id' => '2ea459ba-e08e-102c-b987-00138fbbb402',
			'address' => '1180 Magdalene Hill, Florida, US',
			'address1' => '1180 Magdalene Hill',
			'city' => null,
			'state' => 'Florida',
			'zip' => null,
			'country' => 'US',
			'latitude' => 28.075205,
			'longitude' => -82.475809
		),
		array(
			'id' => 'dfcf56d6-e08e-102c-b987-00138fbbb402',
			'address' => '13216 Forest Hills Dr, Tampa, FL',
			'address1' => '13216 Forest Hills Dr',
			'city' => 'Tampa',
			'state' => 'FL',
			'zip' => null,
			'country' => null,
			'latitude' => 28.06817,
			'longitude' => -82.473463
		),
		array(
			'id' => '7c92ca3e-e08f-102c-b987-00138fbbb402',
			'address' => '9106 El Portal Dr, Tampa, FL',
			'address1' => '9106 El Portal Dr',
			'city' => 'Tampa',
			'state' => 'FL',
			'zip' => null,
			'country' => null,
			'latitude' => 28.0315434,
			'longitude' => -82.4687346
		)
	);
}
?>
