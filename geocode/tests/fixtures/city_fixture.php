<?php
class CityFixture extends CakeTestFixture {
	public $name = 'City';
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'state_id' => array('type' => 'string', 'length' => 36, 'null' => true),
		'name' => array('type' => 'string', 'length' => 255),
	);
	public $records = array(
		array(
			'id' => '951470f2-e770-102c-aa5d-00138fbbb402',
			'state_id' => '95147110-e770-102c-aa5d-00138fbbb402',
			'name' => 'Mountan View'
		)
	);
}
?>
