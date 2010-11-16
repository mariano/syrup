<?php
/**
 * Test cases for SoftDeletable Behavior, which are basically testing methods to test several
 * aspects of slug functionality.
 *
 * Go to the SoftDeletable Behavior page at Cake Syrup to learn more about it:
 *
 * http://cake-syrup.sourceforge.net/ingredients/soft-deletable-behavior/
 *
 * @filesource
 * @author Mariano Iglesias
 * @link http://cake-syrup.sourceforge.net/ingredients/soft-deletable-behavior/
 * @version	$Revision: 924 $
 * @license	http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package app.tests
 * @subpackage app.tests.cases.behaviors
 */

App::import('Behavior', 'syrup.soft_deletable');
App::import('Core', 'ConnectionManager');

class SoftDeletableTestModel extends CakeTestModel {
	public $actsAs = array('Syrup.SoftDeletable');
}

class DeletableCategory extends SoftDeletableTestModel {
	public $name = 'DeletableCategory';
}

class Test1Behavior extends ModelBehavior {
	public function beforeSoftDeletable($model, $id) {
		$model->callbacks[] = array('before', $id, 'test1');
	}

	public function afterSoftDeletable($model, $id) {
		$model->callbacks[] = array('after', $id, 'test1');
	}
}

class Test2Behavior extends ModelBehavior {
}

class Test3Behavior extends ModelBehavior {
	public function afterSoftDeletable($model, $id) {
		$model->callbacks[] = array('after', $id, 'test3');
	}
}

class DeletableArticle extends SoftDeletableTestModel {
	public $name = 'DeletableArticle';
	public $belongsTo = array('DeletableCategory' => array('counterCache' => true));
	public $hasMany = array('DeletableComment' => array('dependent' => true));
	public $callbacks = array();
	public $abort = false;
	public function beforeSoftDeletable($id) {
		$this->callbacks[] = array('before', $id);
		return $this->abort === false;
	}
	public function afterSoftDeletable($id) {
		$this->callbacks[] = array('after', $id);
	}
}

class DeletableComment extends SoftDeletableTestModel {
	public $name = 'DeletableComment';
	public $belongsTo = array('DeletableArticle');
}

/**
 * Test case for SoftDeletable Behavior
 *
 * @package app.tests
 * @subpackage app.tests.cases.models
 */
class SoftDeletableTestCase extends CakeTestCase {
	public $fixtures = array(
		'plugin.syrup.deletable_article', 'plugin.syrup.deletable_category', 'plugin.syrup.deletable_comment'
	);

	public function startTest($method) {
		parent::startTest($method);
		$this->DeletableArticle = ClassRegistry::init('DeletableArticle');
	}

	public function endTest($method) {
		parent::endTest($method);
		unset($this->DeletableArticle);
		ClassRegistry::flush();
	}

	public function testBeforeFind() {
		$Db = ConnectionManager::getDataSource($this->DeletableArticle->useDbConfig);
		$SoftDeletable = new SoftDeletableBehavior();
		$SoftDeletable->setup($this->DeletableArticle);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array());
		$expected = array('conditions' => array('DeletableArticle.deleted !=' => '1'));
		$this->assertEqual($result, $expected);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => array('DeletableArticle.deleted' => 0)));
		$expected = array('conditions' => array('DeletableArticle.deleted' => 0));
		$this->assertEqual($result, $expected);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => array('DeletableArticle.deleted' => array(0, 1))));
		$expected = array('conditions' => array('DeletableArticle.deleted' => array(0, 1)));
		$this->assertEqual($result, $expected);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => array('DeletableArticle.id' => '> 0', 'or' => array('DeletableArticle.title' => 'Title', 'DeletableArticle.id' => '5'))));
		$expected = array('conditions' => array('DeletableArticle.id' => '> 0', 'or' => array('DeletableArticle.title' => 'Title', 'DeletableArticle.id' => '5'), 'DeletableArticle.deleted !=' => '1'));
		$this->assertEqual($result, $expected);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => array('DeletableArticle.id' => '> 0', 'or' => array('DeletableArticle.title' => 'Title', 'DeletableArticle.id' => '5'), 'deleted' => 1)));
		$expected = array('conditions' => array('DeletableArticle.id' => '> 0', 'or' => array('DeletableArticle.title' => 'Title', 'DeletableArticle.id' => '5'), 'deleted' => 1));
		$this->assertEqual($result, $expected);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => 'id=1'));
		$this->assertPattern('/^' . preg_quote($Db->name('DeletableArticle') . '.' . $Db->name('deleted')) . '\s*!=\s*1\s+AND\s+id\s*=\s*1$/', $result['conditions']);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => '1=1 LEFT JOIN table ON (table.column=DeletableArticle.id)'));
		$this->assertPattern('/^' . preg_quote($Db->name('DeletableArticle') . '.' . $Db->name('deleted')) . '\s*!=\s*1\s+AND\s+1\s*=\s*1\s+LEFT JOIN table ON ' . preg_quote('(table.column=DeletableArticle.id)') . '$/', $result['conditions']);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => 'deleted=1'));
		$this->assertPattern('/^' . preg_quote('deleted') . '\s*=\s*1$/', $result['conditions']);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => 'deleted  = 1'));
		$this->assertPattern('/^' . preg_quote('deleted') . '\s*=\s*1$/', $result['conditions']);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => $Db->name('deleted') . '=1'));
		$this->assertPattern('/^' . preg_quote($Db->name('deleted')) . '\s*=\s*1$/', $result['conditions']);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => 'id > 0 AND deleted =1'));
		$this->assertPattern('/^id > 0 AND deleted\s*=\s*1$/', $result['conditions']);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => 'mydeleted=1'));
		$this->assertPattern('/^' . preg_quote($Db->name('DeletableArticle') . '.' . $Db->name('deleted')) . '\s*!=\s*1\s+AND\s+mydeleted\s*=\s*1$/', $result['conditions']);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => 'title = \'record is not deleted\''));
		$this->assertPattern('/^' . preg_quote($Db->name('DeletableArticle') . '.' . $Db->name('deleted')) . '\s*!=\s*1\s+AND\s+title\s*=\s*\'' . preg_quote('record is not deleted') . '\'$/', $result['conditions']);

		unset($SoftDeletable);
	}

	public function testFind() {
		$this->DeletableArticle->delete(2);
		$this->DeletableArticle->delete(3);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => array('DeletableArticle.deleted' => 0), 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => array('DeletableArticle.deleted' => 1), 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => array('DeletableArticle.deleted' => array(0, 1)), 'fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 1
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->Behaviors->disable('SoftDeletable');
		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 1
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->Behaviors->enable('SoftDeletable');
	}

	public function testFindStringConditions() {
		$Db = ConnectionManager::getDataSource($this->DeletableArticle->useDbConfig);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array(
			'conditions' => 'title LIKE ' . $Db->value('%Article%'),
			'fields' => array('id', 'title'),
			'recursive' => -1
		));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array(
			'conditions' => 'id > 0 AND title LIKE ' . $Db->value('%ir%'),
			'fields' => array('id', 'title'),
			'recursive' => -1
		));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->delete(2);
		$this->DeletableArticle->delete(3);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'title LIKE ' . $Db->value('%Article%'), 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'title LIKE ' . $Db->value('%Article%') . ' AND deleted=0', 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'DeletableArticle.deleted = 0 AND title LIKE ' . $Db->value('%Article%'), 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'title LIKE ' . $Db->value('%Article%') . ' AND deleted=1', 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'DeletableArticle.deleted = 1 AND title LIKE ' . $Db->value('%Article%'), 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'title LIKE ' . $Db->value('%Article%') . ' AND (deleted=0 OR deleted = 1)', 'fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 1
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'title LIKE ' . $Db->value('%ir%') . ' AND DeletableArticle.deleted IN (0,1)', 'fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 1
			))
		);
		$this->assertEqual($result, $expected);
	}

	public function testSoftDelete() {
		$this->DeletableArticle->delete(2);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->Behaviors->disable('SoftDeletable');
		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->Behaviors->enable('SoftDeletable');
	}

	public function testHardDelete() {
		$result = $this->DeletableArticle->hardDelete(2);
		$this->assertTrue($result);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->Behaviors->disable('SoftDeletable');
		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->Behaviors->enable('SoftDeletable');
	}

	public function testPurge() {
		$this->DeletableArticle->delete(2);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->Behaviors->disable('SoftDeletable');
		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->Behaviors->enable('SoftDeletable');

		$this->DeletableArticle->delete(3);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->Behaviors->disable('SoftDeletable');
		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 1
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->Behaviors->enable('SoftDeletable');

		$result = $this->DeletableArticle->purge();
		$this->assertTrue($result);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->Behaviors->disable('SoftDeletable');
		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->Behaviors->enable('SoftDeletable');
	}

	public function testUndelete() {
		$this->DeletableArticle->delete(2);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$result = $this->DeletableArticle->undelete(2);
		$this->assertTrue($result);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);
	}

	public function testRecursive() {
		$result = $this->DeletableArticle->DeletableComment->find('all', array('fields' => array('id', 'comment')));
		$expected = array(
			array('DeletableComment' => array(
				'id' => 1, 'comment' => 'First Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 2, 'comment' => 'Second Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 3, 'comment' => 'Third Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 4, 'comment' => 'Fourth Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 5, 'comment' => 'First Comment for Second Article'
			)),
			array('DeletableComment' => array(
				'id' => 6, 'comment' => 'Second Comment for Second Article'
			)),
			array('DeletableComment' => array(
				'id' => 7, 'comment' => 'First Comment for Third Article'
			)),
			array('DeletableComment' => array(
				'id' => 8, 'comment' => 'Second Comment for Third Article'
			)),
			array('DeletableComment' => array(
				'id' => 9, 'comment' => 'Third Comment for Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->delete(2);

		$result = $this->DeletableArticle->DeletableComment->find('all', array('fields' => array('id', 'comment')));
		$expected = array(
			array('DeletableComment' => array(
				'id' => 1, 'comment' => 'First Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 2, 'comment' => 'Second Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 3, 'comment' => 'Third Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 4, 'comment' => 'Fourth Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 7, 'comment' => 'First Comment for Third Article'
			)),
			array('DeletableComment' => array(
				'id' => 8, 'comment' => 'Second Comment for Third Article'
			)),
			array('DeletableComment' => array(
				'id' => 9, 'comment' => 'Third Comment for Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->DeletableComment->Behaviors->disable('SoftDeletable');
		$result = $this->DeletableArticle->DeletableComment->find('all', array('fields' => array('id', 'comment', 'deleted')));
		$expected = array(
			array('DeletableComment' => array(
				'id' => 1, 'comment' => 'First Comment for First Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 2, 'comment' => 'Second Comment for First Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 3, 'comment' => 'Third Comment for First Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 4, 'comment' => 'Fourth Comment for First Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 5, 'comment' => 'First Comment for Second Article', 'deleted' => 1
			)),
			array('DeletableComment' => array(
				'id' => 6, 'comment' => 'Second Comment for Second Article', 'deleted' => 1
			)),
			array('DeletableComment' => array(
				'id' => 7, 'comment' => 'First Comment for Third Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 8, 'comment' => 'Second Comment for Third Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 9, 'comment' => 'Third Comment for Third Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->DeletableComment->Behaviors->enable('SoftDeletable');
	}

	public function testCallbacks() {
		$result = $this->DeletableArticle->find('first', array(
			'conditions' => array('id' => 1),
			'fields' => array('id', 'title'),
			'recursive' => -1
		));
		$expected = array('DeletableArticle' => array(
			'id' => 1, 'title' => 'First Article'
		));
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->abort = true;
		$this->DeletableArticle->delete(1);
		$this->DeletableArticle->abort = false;

		$result = $this->DeletableArticle->callbacks;
		$expected = array(array('before', 1));
		$this->assertEqual($result, $expected);

		$result = $this->DeletableArticle->find('first', array(
			'conditions' => array('id' => 1),
			'fields' => array('id', 'title'),
			'recursive' => -1
		));
		$expected = array('DeletableArticle' => array(
			'id' => 1, 'title' => 'First Article'
		));
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->callbacks = array();
		$this->DeletableArticle->delete(1);

		$result = $this->DeletableArticle->callbacks;
		$expected = array(array('before', 1), array('after', 1));
		$this->assertEqual($result, $expected);
		$this->DeletableArticles->callbacks = array();

		$result = $this->DeletableArticle->find('first', array(
			'conditions' => array('id' => 1),
			'fields' => array('id', 'title'),
			'recursive' => -1
		));
		$expected = false;
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->Behaviors->attach('Test1');
		$this->DeletableArticle->Behaviors->attach('Test2');
		$this->DeletableArticle->Behaviors->attach('Test3');

		$this->DeletableArticle->callbacks = array();
		$this->DeletableArticle->delete(2);

		$result = $this->DeletableArticle->callbacks;
		$expected = array(
			array('before', 2, 'test1'),
			array('before', 2),
			array('after', 2, 'test1'),
			array('after', 2, 'test3'),
			array('after', 2)
		);
		$this->assertEqual($result, $expected);
	}

	public function testCounterCache() {
		$result = $this->DeletableArticle->DeletableCategory->find('all', array(
			'fields' => array('id', 'deletable_article_count'),
			'recursive' => -1
		));
		$this->assertTrue(!empty($result));
		$result = Set::combine($result, '/DeletableCategory/id', '/DeletableCategory/deletable_article_count');
		$expected = array(
			1 => 2,
			2 => 1,
			3 => 0
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->delete(1);

		$result = $this->DeletableArticle->find('first', array(
			'conditions' => array('id' => 1, 'deleted' => 1),
			'fields' => array('id', 'title'),
			'recursive' => -1
		));
		$expected = array('DeletableArticle' => array(
			'id' => 1, 'title' => 'First Article'
		));
		$this->assertEqual($result, $expected);

		$result = $this->DeletableArticle->DeletableCategory->find('all', array(
			'fields' => array('id', 'deletable_article_count'),
			'recursive' => -1
		));
		$this->assertTrue(!empty($result));
		$result = Set::combine($result, '/DeletableCategory/id', '/DeletableCategory/deletable_article_count');
		$expected = array(
			1 => 1,
			2 => 1,
			3 => 0
		);

		$this->DeletableArticle->create();
		$result = $this->DeletableArticle->save(array('DeletableArticle' => array(
			'deletable_category_id' => 1,
			'title' => 'New First Article',
			'body' => 'New First Article Body'
		)));
		$this->assertTrue(!empty($result));

		$this->DeletableArticle->create();
		$result = $this->DeletableArticle->save(array('DeletableArticle' => array(
			'deletable_category_id' => 3,
			'title' => 'New Second Article',
			'body' => 'New Second Article Body'
		)));
		$this->assertTrue(!empty($result));

		$result = $this->DeletableArticle->DeletableCategory->find('all', array(
			'fields' => array('id', 'deletable_article_count'),
			'recursive' => -1
		));
		$this->assertTrue(!empty($result));
		$result = Set::combine($result, '/DeletableCategory/id', '/DeletableCategory/deletable_article_count');
		$expected = array(
			1 => 2,
			2 => 1,
			3 => 1
		);

		$this->DeletableArticle->undelete(1);

		$result = $this->DeletableArticle->DeletableCategory->find('all', array(
			'fields' => array('id', 'deletable_article_count'),
			'recursive' => -1
		));
		$this->assertTrue(!empty($result));
		$result = Set::combine($result, '/DeletableCategory/id', '/DeletableCategory/deletable_article_count');
		$expected = array(
			1 => 3,
			2 => 1,
			3 => 1
		);
	}
}

?>