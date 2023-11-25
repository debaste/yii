<?php
require_once('ValidatorTestModel.php');

class CRequiredValidatorTest extends CTestCase
{
	public function testEmpty()
	{
		$model = new ValidatorTestModel('CRequiredValidatorTest');
		$model->validate(['username']);
		$this->assertArrayHasKey('username', $model->getErrors());
	}

	public function testSpaces()
	{
		$model = new ValidatorTestModel('CRequiredValidatorTest');
		$model->username = ' ';
		$model->validate(['username']);
		$this->assertArrayNotHasKey('username', $model->getErrors());
	}

	public function testEmptyWithSpaces()
	{
		$model = new ValidatorTestModel('CRequiredValidatorTest');
		$model->address = ' ';
		$model->validate(['address']);
		$this->assertArrayHasKey('address', $model->getErrors());
	}

}
