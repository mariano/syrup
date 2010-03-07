<?php
App::import('Behavior', 'Syrup.Sluggable');

class TestSluggableBehavior extends SluggableBehavior {
	public function run($method) {
		$args = array_slice(func_get_args(), 1);
		return call_user_method_array($method, $this, $args);
	}
}

class SluggableTestModel extends CakeTestModel {
	public $actsAs = array('Syrup.Sluggable');
}

class SlugArticle extends SluggableTestModel {
}

class SluggableTestCase extends CakeTestCase {
	public $fixtures = array('plugin.syrup.slug_article');

	public function startTest($action) {
		parent::startTest($action);
		$this->SlugArticle = ClassRegistry::init('SlugArticle');
	}

	public function endTest($action) {
		parent::endTest($action);
		unset($this->SlugArticle);
		ClassRegistry::flush();
	}

	public function testGeneration() {
		$Sluggable = new TestSluggableBehavior();

		$result = $Sluggable->run('_slug', 'title', array('separator' => '-', 'length' => 100));
		$expected = 'title';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'my title', array('separator' => '-', 'length' => 100));
		$expected = 'my-title';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'MY TITle', array('separator' => '-', 'length' => 100));
		$expected = 'my-title';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'my  long title with  some extra spaces  ', array('separator' => '-', 'length' => 100));
		$expected = 'my-long-title-with-some-extra-spaces';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'my  long title! with@  "some" extra spaces & weird chars ', array('separator' => '-', 'length' => 100));
		$expected = 'my-long-title-with-some-extra-spaces-weird-chars';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', '-my - long title! with@  "some" extra spaces & weird chars ', array('separator' => '-', 'length' => 100));
		$expected = 'my-long-title-with-some-extra-spaces-weird-chars';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'my  long title! with@  "some" extra spaces & weird chars ', array('separator' => '-', 'length' => 10));
		$expected = 'my-long-ti';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'my  long title! with@  "some" extra spaces & weird chars ', array('separator' => '-', 'length' => 18));
		$expected = 'my-long-title-with';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'my  long title! with@  "some" extra spaces & weird chars ', array('separator' => '_', 'length' => 18));
		$expected = 'my_long_title_with';
		$this->assertEqual($expected, $result);
	}

	public function testGenerationWithTranslation() {
		$Sluggable = new TestSluggableBehavior();

		// Predefined: UTF-8

		$result = $Sluggable->run('_slug', 'normal string for slug', array('separator' => '-', 'length' => 100, 'translation' => 'utf-8'));
		$expected = 'normal-string-for-slug';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', '-my - long title! with@  "some" extra spaces & weird chars ', array('separator' => '-', 'length' => 100, 'translation' => 'utf-8'));
		$expected = 'my-long-title-with-some-extra-spaces-weird-chars';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'H' . chr(196).chr(146) . 're C' . chr(195).chr(182) . 'mes', array('separator' => '-', 'length' => 100, 'translation' => 'utf-8'));
		$expected = 'here-comes';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'H' . chr(196).chr(155) . 're C' . chr(195).chr(182) . 'mes ' . chr(196).chr(129) . ' mix ' . chr(197).chr(165).chr(196).chr(164) . 'under', array('separator' => '-', 'length' => 100, 'translation' => 'utf-8'));
		$expected = 'here-comes-a-mix-thunder';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'H' . chr(196).chr(155) . 're C' . chr(195).chr(182) . 'mes ' . chr(196).chr(129) . ' mix ' . chr(197).chr(165).chr(196).chr(164) . 'under with ' . chr(208).chr(160) . 'u' . chr(209).chr(129) . 'sian flavor', array('separator' => '-', 'length' => 100, 'translation' => 'utf-8'));
		$expected = 'here-comes-a-mix-thunder-with-russian-flavor';
		$this->assertEqual($expected, $result);

		// Predefined: ISO-8859-1

		$result = $Sluggable->run('_slug', 'normal string for slug', array('separator' => '-', 'length' => 100, 'translation' => 'iso-8859-1'));
		$expected = 'normal-string-for-slug';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', '-my - long title! with@  "some" extra spaces & weird chars ', array('separator' => '-', 'length' => 100, 'translation' => 'iso-8859-1'));
		$expected = 'my-long-title-with-some-extra-spaces-weird-chars';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'H' . chr(128) . 're C' . chr(245) . 'mes', array('separator' => '-', 'length' => 100, 'translation' => 'iso-8859-1'));
		$expected = 'here-comes';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'H' . chr(128) . 're C' . chr(245) . 'mes ' . chr(226) . ' mix ' . chr(254) . 'under', array('separator' => '-', 'length' => 100, 'translation' => 'iso-8859-1'));
		$expected = 'here-comes-a-mix-thunder';
		$this->assertEqual($expected, $result);

		// UTF-8

		$settings = array('separator' => '-', 'length' => 100, 'translation' => array(
			array(
				// Decompositions for Latin-1 Supplement
				chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
				chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
				chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
				chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
				chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
				chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
				chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
				chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
				chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
				chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
				chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
				chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
				chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
				chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
				chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
				chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
				chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
				chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
				chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
				chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
				chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
				chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
				chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
				chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
				chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
				chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
				chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
				chr(195).chr(191) => 'y',
				// Decompositions for Latin Extended-A
				chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
				chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
				chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
				chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
				chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
				chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
				chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
				chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
				chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
				chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
				chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
				chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
				chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
				chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
				chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
				chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
				chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
				chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
				chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
				chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
				chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
				chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
				chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
				chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
				chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
				chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
				chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
				chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
				chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
				chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
				chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
				chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
				chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
				chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
				chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
				chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
				chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
				chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
				chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
				chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
				chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
				chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
				chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
				chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
				chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
				chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
				chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
				chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
				chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
				chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
				chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
				chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
				chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
				chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
				chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
				chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
				chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
				chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
				chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
				chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
				chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
				chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
				chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
				chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
				// Euro Sign
				chr(226).chr(130).chr(172) => 'E'
			)
		));

		$result = $Sluggable->run('_slug', 'normal string for slug', $settings);
		$expected = 'normal-string-for-slug';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', '-my - long title! with@  "some" extra spaces & weird chars ', $settings);
		$expected = 'my-long-title-with-some-extra-spaces-weird-chars';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'H' . chr(196).chr(146) . 're C' . chr(195).chr(182) . 'mes', $settings);
		$expected = 'here-comes';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'H' . chr(196).chr(155) . 're C' . chr(195).chr(182) . 'mes ' . chr(196).chr(129) . ' mix ' . chr(197).chr(165).chr(196).chr(164) . 'under', $settings);
		$expected = 'here-comes-a-mix-thunder';
		$this->assertEqual($expected, $result);

		// ISO-8859-1 translation table

		$settings = array('separator' => '-', 'length' => 100, 'translation' => array(
			chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
			.chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
			.chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
			.chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
			.chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
			.chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
			.chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
			.chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
			.chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
			.chr(252).chr(253).chr(255),
			'EfSZsz' . 'YcYuAAA' . 'AAACEEE' . 'EIIIINO' . 'OOOOOUU' . 'UUYaaaa' . 'aaceeee' . 'iiiinoo' . 'oooouuu' . 'uyy',
			array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254)),
			array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th')
		));

		$result = $Sluggable->run('_slug', 'normal string for slug', $settings);
		$expected = 'normal-string-for-slug';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', '-my - long title! with@  "some" extra spaces & weird chars ', $settings);
		$expected = 'my-long-title-with-some-extra-spaces-weird-chars';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'H' . chr(128) . 're C' . chr(245) . 'mes', $settings);
		$expected = 'here-comes';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'H' . chr(128) . 're C' . chr(245) . 'mes ' . chr(226) . ' mix ' . chr(254) . 'under', $settings);
		$expected = 'here-comes-a-mix-thunder';
		$this->assertEqual($expected, $result);
	}

	public function testGenerationWithIgnore() {
		$Sluggable = new TestSluggableBehavior();

		// Predefined: UTF-8

		$result = $Sluggable->run('_slug', 'normal string for slug', array(
			'separator' => '-', 'length' => 100, 'translation' => 'utf-8',
			'ignore' => array('for')
		));
		$expected = 'normal-string-slug';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'for normal string for slug', array(
			'separator' => '-', 'length' => 100, 'translation' => 'utf-8',
			'ignore' => array('for')
		));
		$expected = 'normal-string-slug';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'for normal string for slug for', array(
			'separator' => '-', 'length' => 100, 'translation' => 'utf-8',
			'ignore' => array('for')
		));
		$expected = 'normal-string-slug';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'this is my string for slug generation', array(
			'separator' => '-', 'length' => 100, 'translation' => 'utf-8',
			'ignore' => array('for', 'is', 'this')
		));
		$expected = 'my-string-slug-generation';
		$this->assertEqual($expected, $result);

		$result = $Sluggable->run('_slug', 'i saw and grabbed an apple from the tree. light should be off or on?', array(
			'separator' => '-', 'length' => 100, 'translation' => 'utf-8',
			'ignore' => array('a', 'an', 'and', 'i', 'of', 'on', 'or', 'the')
		));
		$expected = 'saw-grabbed-apple-from-tree-light-should-be-off';
		$this->assertEqual($expected, $result);
	}

	public function testBeforeSave() {
		$Sluggable = new TestSluggableBehavior();

		$Sluggable->setup($this->SlugArticle, array('separator' => '-', 'length' => 100));

		$this->SlugArticle->data = array('SlugArticle' => array('title' => 'My test title'));
		$result = $Sluggable->beforeSave($this->SlugArticle);
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->data;
		$expected = array('SlugArticle' => array('title' => 'My test title', 'slug' => 'my-test-title'));
		$this->assertEqual($expected, $result);

		$this->SlugArticle->data = array('SlugArticle' => array('title' => 'First Article'));
		$result = $Sluggable->beforeSave($this->SlugArticle);
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->data;
		$expected = array('SlugArticle' => array('title' => 'First Article', 'slug' => 'first-article-1'));
		$this->assertEqual($expected, $result);

		$this->SlugArticle->data = array('SlugArticle' => array('title' => 'First Article Unique'));
		$result = $Sluggable->beforeSave($this->SlugArticle);
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->data;
		$expected = array('SlugArticle' => array('title' => 'First Article Unique', 'slug' => 'first-article-unique'));
		$this->assertEqual($expected, $result);

		$this->SlugArticle->data = array('SlugArticle' => array('body' => 'Just Body'));
		$result = $Sluggable->beforeSave($this->SlugArticle);
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->data;
		$expected = array('SlugArticle' => array('body' => 'Just Body'));
		$this->assertEqual($expected, $result);

		$Sluggable->setup($this->SlugArticle, array('label' => array('title', 'subtitle'), 'separator' => '-', 'length' => 100));

		$this->SlugArticle->data = array('SlugArticle' => array('title' => 'My test title'));
		$result = $Sluggable->beforeSave($this->SlugArticle);
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->data;
		$expected = array('SlugArticle' => array('title' => 'My test title', 'slug' => 'my-test-title'));
		$this->assertEqual($expected, $result);

		$this->SlugArticle->data = array('SlugArticle' => array('title' => 'My test title', 'subtitle' => ''));
		$result = $Sluggable->beforeSave($this->SlugArticle);
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->data;
		$expected = array('SlugArticle' => array('title' => 'My test title', 'subtitle' => '', 'slug' => 'my-test-title'));
		$this->assertEqual($expected, $result);

		$this->SlugArticle->data = array('SlugArticle' => array('title' => 'My test title', 'subtitle' => 'My subtitle'));
		$result = $Sluggable->beforeSave($this->SlugArticle);
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->data;
		$expected = array('SlugArticle' => array('title' => 'My test title', 'subtitle' => 'My subtitle', 'slug' => 'my-test-title-my-subtitle'));
		$this->assertEqual($expected, $result);

		$Sluggable->setup($this->SlugArticle, array('overwrite' => false));

		$this->SlugArticle->id = 1;
		$this->SlugArticle->data = array('SlugArticle' => array('title' => 'New First Article'));
		$result = $Sluggable->beforeSave($this->SlugArticle);
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->data;
		$expected = array('SlugArticle' => array('title' => 'New First Article'));
		$this->assertEqual($expected, $result);
		$this->SlugArticle->id = null;

		$Sluggable->setup($this->SlugArticle, array('overwrite' => true));

		$this->SlugArticle->id = 1;
		$this->SlugArticle->data = array('SlugArticle' => array('title' => 'New First Article'));
		$result = $Sluggable->beforeSave($this->SlugArticle);
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->data;
		$expected = array('SlugArticle' => array('title' => 'New First Article', 'slug' => 'new-first-article'));
		$this->assertEqual($expected, $result);
		$this->SlugArticle->id = null;
	}

	public function testSave() {
		$data = array('SlugArticle' => array('title' => 'New Article', 'subtitle' => '', 'body' => 'New Body 1'));
		$result = $this->SlugArticle->create();
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->save($data);
		$this->assertTrue($result !== false);

		$result = $this->SlugArticle->read(array('slug', 'title'), 4);
		$expected = array('SlugArticle' => array('slug' => 'new-article', 'title' => 'New Article'));

		$data = array('SlugArticle' => array('title' => 'New Article', 'subtitle' => 'Second Version', 'body' => 'New Body 2'));
		$result = $this->SlugArticle->create();
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->save($data);
		$this->assertTrue($result !== false);

		$result = $this->SlugArticle->read(array('slug', 'title'), 5);
		$expected = array('SlugArticle' => array('slug' => 'new-article-1', 'title' => 'New Article'));

		$data = array('SlugArticle' => array('title' => 'New Article', 'subtitle' => 'Third Version', 'body' => 'New Body 3'));
		$result = $this->SlugArticle->create();
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->save($data);
		$this->assertTrue($result !== false);

		$result = $this->SlugArticle->read(array('slug', 'title'), 6);
		$expected = array('SlugArticle' => array('slug' => 'new-article-2', 'title' => 'New Article'));

		$data = array('SlugArticle' => array('title' => 'Brand New Article', 'subtitle' => '', 'body' => 'Brand New Body'));
		$result = $this->SlugArticle->create();
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->save($data);
		$this->assertTrue($result !== false);

		$result = $this->SlugArticle->read(array('slug', 'title'), 7);
		$expected = array('SlugArticle' => array('slug' => 'brand-new-article', 'title' => 'Brand New Article'));

		$data = array('SlugArticle' => array('id' => 2, 'title' => 'New Title for Second Article'));
		$result = $this->SlugArticle->create();
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->save($data);
		$this->assertTrue($result !== false);

		$result = $this->SlugArticle->read(array('slug', 'title'), 2);
		$expected = array('SlugArticle' => array('slug' => 'second-article', 'title' => 'New Title for Second Article'));

		$data = array('SlugArticle' => array('title' => 'Article with whitelist', 'body' => 'Brand New Body'));
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->create();
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->save($data, true, array('title', 'body'));
		$this->assertTrue($result !== false);

		$result = $this->SlugArticle->read(array('slug', 'title'), 8);
		$expected = array('SlugArticle' => array('slug' => 'article-with-whitelist', 'title' => 'Article with whitelist'));
	}

	public function testSaveField() {
		$expected = 'New body for first article';
		$this->SlugArticle->id = 1;
		$result = $this->SlugArticle->saveField('body', $expected);
		$this->assertTrue($result);
		$result = $this->SlugArticle->field('body');
		$this->assertEqual($expected, $result);
		$result = $this->SlugArticle->field('title');
		$expected = 'First Article';
		$this->assertEqual($expected, $result);
		$result = $this->SlugArticle->field('slug');
		$expected = 'first-article';
		$this->assertEqual($expected, $result);

		$expected = 'New title for first article';
		$this->SlugArticle->id = 1;
		$result = $this->SlugArticle->saveField('title', $expected);
		$this->assertTrue($result);
		$result = $this->SlugArticle->field('title');
		$this->assertEqual($expected, $result);
		$result = $this->SlugArticle->field('slug');
		$expected = 'first-article';
		$this->assertEqual($expected, $result);
	}

	public function testSaveCollisions() {
		$data = array('SlugArticle' => array('title' => 'New Article', 'subtitle' => '', 'body' => 'New Body 1'));
		$result = $this->SlugArticle->create();
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->save($data);
		$this->assertTrue($result !== false);

		$result = $this->SlugArticle->field('slug');
		$expected = 'new-article';
		$this->assertEqual($expected, $result);

		$data = array('SlugArticle' => array('title' => 'New Article', 'subtitle' => '', 'body' => 'New Body 1'));
		$result = $this->SlugArticle->create();
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->save($data);
		$this->assertTrue($result !== false);

		$result = $this->SlugArticle->field('slug');
		$expected = 'new-article-1';
		$this->assertEqual($expected, $result);

		$data = array('SlugArticle' => array('title' => 'New Article', 'subtitle' => '', 'body' => 'New Body 1'));
		$result = $this->SlugArticle->create();
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->save($data);
		$this->assertTrue($result !== false);

		$result = $this->SlugArticle->field('slug');
		$expected = 'new-article-2';
		$this->assertEqual($expected, $result);


		$data = array('SlugArticle' => array('title' => 'New Article', 'subtitle' => '', 'body' => 'New Body 1'));
		$result = $this->SlugArticle->create();
		$this->assertTrue($result !== false);
		$result = $this->SlugArticle->save($data);
		$this->assertTrue($result !== false);

		$result = $this->SlugArticle->field('slug');
		$expected = 'new-article-3';
		$this->assertEqual($expected, $result);
	}
}

?>
