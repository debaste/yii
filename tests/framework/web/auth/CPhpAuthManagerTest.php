<?php

require_once(dirname(__FILE__).'/AuthManagerTestBase.php');

class CPhpAuthManagerTest extends AuthManagerTestBase
{
	protected function setUp(): void
	{
		$authFile=Yii::app()->getRuntimePath().'/CPhpAuthManagerTest_auth.php';
		@unlink($authFile);
		$this->auth=new CPhpAuthManager;
		$this->auth->authFile=$authFile;
		$this->auth->init();
		$this->prepareData();
	}

	protected function tearDown(): void
	{
		@unlink($this->auth->authFile);
	}

	public function testSaveLoad()
	{
		$this->auth->save();
		$this->auth->clearAll();
		$this->auth->load();
		$this->testCheckAccess();
	}
}
