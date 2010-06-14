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
class DeletableArticleFixture extends CakeTestFixture {
	public $name = 'DeletableArticle';
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'deletable_category_id' => array('type' => 'integer', 'null' => false),
		'title' => array('type' => 'string', 'null' => false),
		'body' => 'text',
		'published' => array('type' => 'integer', 'default' => '0', 'null' => false),
		'deleted' => array('type' => 'integer', 'default' => '0'),
		'deleted_date' => 'datetime',
		'created' => 'datetime',
		'updated' => 'datetime'
	);
	public $records = array(
		array ('id' => 1, 'deletable_category_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body', 'published' => '1', 'deleted' => '0', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
		array ('id' => 2, 'deletable_category_id' => 1, 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => '1', 'deleted' => '0', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
		array ('id' => 3, 'deletable_category_id' => 2, 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => '1', 'deleted' => '0', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31')
	);
}

?>