<?php

Yii::import('system.web.CArrayDataProvider');

class CArrayDataProviderTest extends CTestCase
{
	public function testGetData()
	{
		$simpleArray = ['zero', 'one'];
		$dataProvider = new CArrayDataProvider($simpleArray);
		$this->assertEquals($simpleArray, $dataProvider->getData());
	}

	public function testGetSortedData()
	{
		$simpleArray = [['sortField' => 1], ['sortField' => 0]];
		$dataProvider = new CArrayDataProvider(
			$simpleArray,
			[
				'sort' => [
					'attributes' => [
						'sort' => [
							'asc' => 'sortField ASC',
							'desc' => 'sortField DESC',
							'label' => 'Sorting',
							'default' => 'asc',
						],
					],
					'defaultOrder' => [
						'sort' => CSort::SORT_ASC,
					]
				],
			]
		);
		$sortedArray = [['sortField' => 0], ['sortField' => 1]];
		$this->assertEquals($sortedArray, $dataProvider->getData());
	}

	public function testGetSortedDataByInnerArrayField()
	{
		$simpleArray = [
			['innerArray' => ['sortField' => 1]],
			['innerArray' => ['sortField' => 0]]
		];
		$dataProvider = new CArrayDataProvider(
			$simpleArray,
			[
				'sort' => [
					'attributes' => [
						'sort' => [
							'asc' => 'innerArray.sortField ASC',
							'desc' => 'innerArray.sortField DESC',
							'label' => 'Sorting',
							'default' => 'asc',
						],
					],
					'defaultOrder' => [
						'sort' => CSort::SORT_ASC,
					]
				],
			]
		);
		$sortedArray = [
			['innerArray' => ['sortField' => 0]],
			['innerArray' => ['sortField' => 1]]
		];
		$this->assertEquals($sortedArray, $dataProvider->getData());
	}

	public function testCaseSensitiveSort()
	{
		// source data
		$unsortedProjects=[
			['title'=>'Zabbix', 'license'=>'GPL'],
			['title'=>'munin', 'license'=>'GPL'],
			['title'=>'Arch Linux', 'license'=>'GPL'],
			['title'=>'Nagios', 'license'=>'GPL'],
			['title'=>'zend framework', 'license'=>'BSD'],
			['title'=>'Zope', 'license'=>'ZPL'],
			['title'=>'active-record', 'license'=>false],
			['title'=>'ActiveState', 'license'=>false],
			['title'=>'mach', 'license'=>false],
			['title'=>'MySQL', 'license'=>'GPL'],
			['title'=>'mssql', 'license'=>'EULA'],
			['title'=>'Master-Master', 'license'=>false],
			['title'=>'Zend Engine', 'license'=>false],
			['title'=>'Mageia Linux', 'license'=>'GNU GPL'],
			['title'=>'nginx', 'license'=>'BSD'],
			['title'=>'Mozilla Firefox', 'license'=>'MPL'],
		];

		// expected data
		$sortedProjects=[
			// upper cased titles
			['title'=>'ActiveState', 'license'=>false],
			['title'=>'Arch Linux', 'license'=>'GPL'],
			['title'=>'Mageia Linux', 'license'=>'GNU GPL'],
			['title'=>'Master-Master', 'license'=>false],
			['title'=>'Mozilla Firefox', 'license'=>'MPL'],
			['title'=>'MySQL', 'license'=>'GPL'],
			['title'=>'Nagios', 'license'=>'GPL'],
			['title'=>'Zabbix', 'license'=>'GPL'],
			['title'=>'Zend Engine', 'license'=>false],
			['title'=>'Zope', 'license'=>'ZPL'],
			// lower cased titles
			['title'=>'active-record', 'license'=>false],
			['title'=>'mach', 'license'=>false],
			['title'=>'mssql', 'license'=>'EULA'],
			['title'=>'munin', 'license'=>'GPL'],
			['title'=>'nginx', 'license'=>'BSD'],
			['title'=>'zend framework', 'license'=>'BSD'],
		];

		$dataProvider=new CArrayDataProvider($unsortedProjects, [
			'sort'=>[
				'attributes'=>[
					'sort'=>[
						'asc'=>'title ASC',
						'desc'=>'title DESC',
						'label'=>'Title',
						'default'=>'desc',
					],
				],
				'defaultOrder'=>[
					'sort'=>CSort::SORT_ASC,
				]
			],
			'pagination'=>[
				'pageSize'=>100500,
			],
		]);

		// $dataProvider->caseSensitiveSort is true by default, so we do not touching it

		$this->assertEquals($sortedProjects, $dataProvider->getData());
	}

	public function testCaseInsensitiveSort()
	{
		// source data
		$unsortedProjects=[
			['title'=>'Zabbix', 'license'=>'GPL'],
			['title'=>'munin', 'license'=>'GPL'],
			['title'=>'Arch Linux', 'license'=>'GPL'],
			['title'=>'Nagios', 'license'=>'GPL'],
			['title'=>'zend framework', 'license'=>'BSD'],
			['title'=>'Zope', 'license'=>'ZPL'],
			['title'=>'active-record', 'license'=>false],
			['title'=>'ActiveState', 'license'=>false],
			['title'=>'mach', 'license'=>false],
			['title'=>'MySQL', 'license'=>'GPL'],
			['title'=>'mssql', 'license'=>'EULA'],
			['title'=>'Master-Master', 'license'=>false],
			['title'=>'Zend Engine', 'license'=>false],
			['title'=>'Mageia Linux', 'license'=>'GNU GPL'],
			['title'=>'nginx', 'license'=>'BSD'],
			['title'=>'Mozilla Firefox', 'license'=>'MPL'],
		];

		// expected data
		$sortedProjects=[
			// case is not taken into account
			['title'=>'active-record', 'license'=>false],
			['title'=>'ActiveState', 'license'=>false],
			['title'=>'Arch Linux', 'license'=>'GPL'],
			['title'=>'mach', 'license'=>false],
			['title'=>'Mageia Linux', 'license'=>'GNU GPL'],
			['title'=>'Master-Master', 'license'=>false],
			['title'=>'Mozilla Firefox', 'license'=>'MPL'],
			['title'=>'mssql', 'license'=>'EULA'],
			['title'=>'munin', 'license'=>'GPL'],
			['title'=>'MySQL', 'license'=>'GPL'],
			['title'=>'Nagios', 'license'=>'GPL'],
			['title'=>'nginx', 'license'=>'BSD'],
			['title'=>'Zabbix', 'license'=>'GPL'],
			['title'=>'Zend Engine', 'license'=>false],
			['title'=>'zend framework', 'license'=>'BSD'],
			['title'=>'Zope', 'license'=>'ZPL'],
		];

		$dataProvider=new CArrayDataProvider($unsortedProjects, [
			'sort'=>[
				'attributes'=>[
					'sort'=>[
						'asc'=>'title ASC',
						'desc'=>'title DESC',
						'label'=>'Title',
						'default'=>'desc',
					],
				],
				'defaultOrder'=>[
					'sort'=>CSort::SORT_ASC,
				]
			],
			'pagination'=>[
				'pageSize'=>100500,
			],
		]);

		// we're explicitly setting case insensitive sort
		$dataProvider->caseSensitiveSort = false;

		$this->assertEquals($sortedProjects, $dataProvider->getData());
	}

	public function testNestedObjectsSort()
	{
		$obj1 = new \stdClass();
		$obj1->type = "def";
		$obj1->owner = $obj1;
		$obj2 = new \stdClass();
		$obj2->type = "abc";
		$obj2->owner = $obj2;
		$obj3 = new \stdClass();
		$obj3->type = "abc";
		$obj3->owner = $obj3;
		$models = [$obj1, $obj2, $obj3];

		$this->assertEquals($obj2, $obj3);
		$dataProvider = new CArrayDataProvider($models, [
			'sort'=>[
				'attributes'=>[
					'sort'=>[
						'asc'=>'type ASC',
						'desc'=>'type DESC',
						'label'=>'Type',
						'default'=>'asc',
					],
				],
				'defaultOrder'=>[
					'sort'=>CSort::SORT_ASC,
				]
			],
		]);
		$sortedArray = [$obj2, $obj3, $obj1];
		$this->assertEquals($sortedArray, $dataProvider->getData());
	}
}
