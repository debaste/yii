<?php
require_once('ValidatorTestModel.php');

class CEmailValidatorTest extends CTestCase
{
	public function testEmpty()
	{
		$emailValidator = new CEmailValidator();
		$emailValidator->allowEmpty = true;
		$this->assertTrue($emailValidator->validateValue('test@example.com'));
		$this->assertFalse($emailValidator->validateValue(''));
		
		$emailValidator->allowEmpty = false;
		$this->assertTrue($emailValidator->validateValue('test@example.com'));
		$this->assertFalse($emailValidator->validateValue(''));
	}

	public function testNumericEmail()
	{
		$emailValidator = new CEmailValidator();
		$result = $emailValidator->validateValue("5011@gmail.com");
		$this->assertTrue($result);
	}

	public function providerIDNEmail()
	{
		return [
			// IDN validation enabled
			['test@президент.рф', true, true],
			['test@bücher.de', true, true],
			['test@检查域.cn', true, true],
			['☃-⌘@mañana.com', true, false],
			['test@google.com', true, true],
			['test@yiiframework.com', true, true],
			['bad-email', true, false],
			['without@tld', true, false],
			['without.at-mark.com', true, false],
			['检查域', true, false],

			// IDN validation disabled
			['test@президент.рф', false, false],
			['test@bücher.de', false, false],
			['test@检查域.cn', false, false],
			['☃-⌘@mañana.com', false, false],
			['test@google.com', false, true],
			['test@yiiframework.com', false, true],
			['bad-email', false, false],
			['without@tld', false, false],
			['without.at-mark.com', false, false],
			['检查域', false, false],
		];
	}

	/**
	 * @dataProvider providerIDNEmail
	 *
	 * @param string $email
	 * @param boolean $validateIDN
	 * @param string $assertion
	 */
	public function testIDNUrl($email, $validateIDN, $assertion)
	{
		$emailValidator = new CEmailValidator();
		$emailValidator->validateIDN = $validateIDN;
		$result = $emailValidator->validateValue($email);
		$this->assertEquals($assertion, $result);
	}

	/**
	 * https://github.com/yiisoft/yii/issues/1955
	 */
	public function testArrayValue()
	{
		$model=new ValidatorTestModel('CEmailValidatorTest');
		$model->email=['user@domain.tld'];
		$model->validate(['email']);
		$this->assertTrue($model->hasErrors('email'));
		$this->assertEquals(['Email is not a valid email address.'],$model->getErrors('email'));
	}

	public function testMxPortDomainWithNoMXRecord()
	{
		if (getenv('TRAVIS')==='true' || getenv('GITHUB_ACTIONS')==='true')
			$this->markTestSkipped('MX connections are disabled in travis.');

		$emailValidator = new CEmailValidator();
		$emailValidator->checkPort = true;
		$result = $emailValidator->validateValue('user@example.com');
		$this->assertFalse($result);
	}

	public function testMxPortDomainWithMXRecord()
	{
		if (getenv('TRAVIS')==='true')
			$this->markTestSkipped('MX connections are disabled in travis.');

		$emailValidator = new CEmailValidator();
		$emailValidator->checkPort = true;
		$result = $emailValidator->validateValue('user@hotmail.com');
		$this->assertTrue($result);
	}
}
