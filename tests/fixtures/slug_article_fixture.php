<?php
class SlugArticleFixture extends CakeTestFixture {
	public $name = 'SlugArticle';
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary', 'extra'=> 'auto_increment'),
		'slug' => array('type' => 'string', 'null' => false),
		'title' => array('type' => 'string', 'null' => false),
		'subtitle' => array('type' => 'string', 'null' => true),
		'body' => 'text',
		'created' => 'datetime',
		'updated' => 'datetime'
	);
	public $records = array(
		array ('id' => 1, 'slug' => 'first-article', 'title' => 'First Article', 'subtitle' => '', 'body' => 'First Article Body', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'),
		array ('id' => 2, 'slug' => 'second-article', 'title' => 'Second Article', 'subtitle' => '', 'body' => 'Second Article Body', 'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'),
		array ('id' => 3, 'slug' => 'third-article', 'title' => 'Third Article', 'subtitle' => '', 'body' => 'Third Article Body', 'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31')
	);
}

?>
