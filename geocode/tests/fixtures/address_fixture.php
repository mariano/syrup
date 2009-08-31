<?php
class AddressFixture extends CakeTestFixture {
	public $name = 'Address';
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'address' => array('type' => 'text'),
		'address_1' => array('type' => 'string', 'length' => 255),
		'address_2' => array('type' => 'string', 'length' => 255, 'null' => true),
		'city_id' => array('type' => 'string', 'length' => 36, 'null' => true),
		'state_id' => array('type' => 'string', 'length' => 36),
		'zip' => array('type' => 'string', 'length' => 10),
		'latitude' => array('type' => 'float'),
		'longitude' => array('type' => 'float')
	);
}
?>
