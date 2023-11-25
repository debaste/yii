<?php

class CDbStatePersisterTest extends CTestCase
{
	protected function setUp(): void
	{
		if(!extension_loaded('pdo') || !extension_loaded('pdo_sqlite'))
			$this->markTestSkipped('PDO and SQLite extensions are required.');
		// clean up runtime directory
		$app=new TestApplication;
		$app->reset();
	}

	public function testLoadSave()
	{
		$app=new TestApplication([
			'components'=>[
				'db'=>[
					'class' => 'CDbConnection',
					'connectionString' => 'mysql:host=127.0.0.1;port=3306;dbname=yii',
					'username' => 'test',
					'password' => 'test',
					'emulatePrepare' => true,
					'charset' => 'utf8',
					'enableParamLogging' => true,
				],
				'statePersister' => [
					'class' => 'CDbStatePersister'
				]
			]
		]);
		$sp=$app->statePersister;
		$data=['123','456','a'=>443];
		$sp->save($data);
		$this->assertEquals($sp->load(),$data);
	}
}
