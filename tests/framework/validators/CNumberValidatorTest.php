<?php
require_once('ValidatorTestModel.php');

class CNumberValidatorTest extends CTestCase
{
	public function providerIssue1669()
	{
		return [
			// boolean
			[false, ['number' => ['Number must be a number.']]],
			[true, ['number' => ['Number must be a number.']]],
			// integer
			[20, ['number' => ['Number is too big (maximum is 15).']]],
			[1, ['number' => ['Number is too small (minimum is 5).']]],
			// float
			[20.5, ['number' => ['Number must be an integer.','Number is too big (maximum is 15).']]],
			[1.5, ['number' => ['Number must be an integer.','Number is too small (minimum is 5).']]],
			// string
			['20', ['number' => ['Number is too big (maximum is 15).']]],
			['20.5', ['number' => ['Number must be an integer.','Number is too big (maximum is 15).']]],
			['1', ['number' => ['Number is too small (minimum is 5).']]],
			['1.5', ['number' => ['Number must be an integer.','Number is too small (minimum is 5).']]],
			['abc', ['number' => ['Number must be a number.']]],
			['a100', ['number' => ['Number must be a number.']]],
			// array
			[[1,2], ['number' => ['Number must be a number.']]],
			// object
			[(object)['a'=>1,'b'=>2], ['number' => ['Number must be a number.']]],
		];
	}

	/**
	 * https://github.com/yiisoft/yii/issues/1669
	 * @dataProvider providerIssue1669
	 */
	public function testIssue1669($value, $assertion)
	{
		$model = new ValidatorTestModel('CNumberValidatorTest');
		$model->number = $value;
		$model->validate(['number']);
		$this->assertSame($assertion, $model->getErrors());
	}
}
