<?php
/**
 * Fixture for test case in SoftDeletableBehavior.
 *
 * Go to the SoftDeletableBehavior page at Cake Syrup to learn more about it:
 *
 * http://cake-syrup.sourceforge.net/ingredients/soft-deletable-behavior/
 *
 * @filesource
 * @author Mariano Iglesias
 * @link http://cake-syrup.sourceforge.net/ingredients/soft-deletable-behavior/
 * @version	$Revision$
 * @license	http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package app.tests
 * @subpackage app.tests.fixtures
 */

/**
 * A fixture for a testing model
 *
 * @package app.tests
 * @subpackage app.tests.fixtures
 */
class DeletableCategoryFixture extends CakeTestFixture {
	public $name = 'DeletableCategory';
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false),
		'deletable_article_count' => array('type' => 'integer', 'default' => '0'),
		'created' => 'datetime',
		'updated' => 'datetime'
	);
	public $records = array(
		array ('id' => 1, 'name' => 'First Category', 'deletable_article_count' => 2, 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
		array ('id' => 2, 'name' => 'Second Category', 'deletable_article_count' => 1, 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
		array ('id' => 3, 'name' => 'Third Category', 'deletable_article_count' => 0, 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31')
	);
}

?>