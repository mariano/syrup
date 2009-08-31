<?php
class StateFixture extends CakeTestFixture {
	public $name = 'State';
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'country_id' => array('type' => 'string', 'length' => 36, 'null' => true),
		'name' => array('type' => 'string', 'length' => 255),
	);
	public $records = array(
		array(
			'id' => '95147110-e770-102c-aa5d-00138fbbb402',
			'country_id' => '95147124-e770-102c-aa5d-00138fbbb402',
			'name' => 'California'
		)
	);
}
?>
