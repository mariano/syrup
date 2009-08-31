<?php
class CountryFixture extends CakeTestFixture {
	public $name = 'Country';
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => 255),
	);
	public $records = array(
		array(
			'id' => '95147124-e770-102c-aa5d-00138fbbb402',
			'name' => 'United States of America'
		)
	);
}
?>
