<?php
require_once('ValidatorTestModel.php');

class CUrlValidatorTest extends CTestCase
{
	public function testEmpty()
	{
		$model = new ValidatorTestModel('CUrlValidatorTest');
		$model->validate(['url']);
		$this->assertArrayHasKey('url', $model->getErrors());
	}

	public function testArbitraryUrl()
	{
		$urlValidator = new CUrlValidator();
		$url = 'http://testing-arbitrary-domain.com/';
		$result = $urlValidator->validateValue($url);
		$this->assertEquals($url, $result);
	}

	public function providerIDNUrl()
	{
		return [
			// IDN validation enabled
			['http://президент.рф/', true, 'http://президент.рф/'],
			['http://bücher.de/?get=param', true, 'http://bücher.de/?get=param'],
			['http://检查域.cn/', true, 'http://检查域.cn/'],
			['http://mañana.com/', true, 'http://mañana.com/'],
			['http://☃-⌘.com/', true, 'http://☃-⌘.com/'],
			['http://google.com/', true, 'http://google.com/'],
			['https://www.yiiframework.com/forum/', true, 'https://www.yiiframework.com/forum/'],
			['https://www.yiiframework.com/extensions/', true, 'https://www.yiiframework.com/extensions/'],
			['ftp://www.yiiframework.com/', true, false],
			['www.yiiframework.com', true, false],

			// IDN validation disabled
			['http://президент.рф/', false, false],
			['http://bücher.de/?get=param', false, false],
			['http://检查域.cn/', false, false],
			['http://mañana.com/', false, false],
			['http://☃-⌘.com/', false, false],
			['http://google.com/', false, 'http://google.com/'],
			['https://www.yiiframework.com/forum/', false, 'https://www.yiiframework.com/forum/'],
			['https://www.yiiframework.com/extensions/', false, 'https://www.yiiframework.com/extensions/'],
			['ftp://www.yiiframework.com/', false, false],
			['www.yiiframework.com', false, false],
		];
	}

	/**
	 * @dataProvider providerIDNUrl
	 *
	 * @param string $url
	 * @param boolean $validateIDN
	 * @param string $assertion
	 */
	public function testIDNUrl($url, $validateIDN, $assertion)
	{
		$urlValidator = new CUrlValidator();
		$urlValidator->validateIDN = $validateIDN;
		$result = $urlValidator->validateValue($url);
		$this->assertEquals($assertion, $result);
	}

	public function providerValidSchemes()
	{
		return [
			['ftp://yiiframework.com/', ['ftp', 'http', 'https'], 'ftp://yiiframework.com/'],
			['ftp://yiiframework.com/', ['http', 'https'], false],
			['ftp://yiiframework.com/', ['ftp'], 'ftp://yiiframework.com/'],

			['that-s-not-an-url-at-all', ['ftp', 'http', 'https'], false],
			['that-s-not-an-url-at-all', [], false],
			['ftp://that-s-not-an-url-at-all', ['ftp'], false],

			['http://☹.com/', ['ftp'], false],
			['http://☹.com/', ['rsync'], false],
			['http://☹.com/', ['http', 'https'], false],

			['rsync://gentoo.org:873/distfiles/', ['rsync', 'http', 'https'], 'rsync://gentoo.org:873/distfiles/'],
			['rsync://gentoo.org:873/distfiles/', ['http', 'https'], false],
			['rsync://gentoo.org:873/distfiles/', ['rsync'], 'rsync://gentoo.org:873/distfiles/'],
		];
	}

	/**
	 * @dataProvider providerValidSchemes
	 *
	 * @param string $url
	 * @param array $validSchemes
	 * @param string $assertion
	 */
	public function testValidSchemes($url, $validSchemes, $assertion)
	{
		$urlValidator = new CUrlValidator();
		$urlValidator->validSchemes = $validSchemes;
		$result = $urlValidator->validateValue($url);
		$this->assertEquals($assertion, $result);
	}

	public function providerDefaultScheme()
	{
		return [
			['https://yiiframework.com/?get=param', null, 'https://yiiframework.com/?get=param'],
			['ftp://yiiframework.com/?get=param', null, false],
			['yiiframework.com/?get=param', null, false],
			['that-s-not-an-url-at-all', null, false],

			['http://yiiframework.com/?get=param', 'http', 'http://yiiframework.com/?get=param'],
			['ftp://yiiframework.com/?get=param', 'http', false],
			['yiiframework.com/?get=param', 'http', 'http://yiiframework.com/?get=param'],
			['that-s-not-an-url-at-all', 'http', false],

			['https://yiiframework.com/?get=param', 'ftp', 'https://yiiframework.com/?get=param'],
			['ftp://yiiframework.com/?get=param', 'ftp', false],
			['yiiframework.com/?get=param', 'ftp', false],
			['that-s-not-an-url-at-all', 'ftp', false],
		];
	}

	/**
	 * @dataProvider providerDefaultScheme
	 *
	 * @param string $url
	 * @param array $defaultScheme
	 * @param string $assertion
	 */
	public function testDefaultScheme($url, $defaultScheme, $assertion)
	{
		$urlValidator = new CUrlValidator();
		$urlValidator->defaultScheme = $defaultScheme;
		$result = $urlValidator->validateValue($url);
		$this->assertEquals($assertion, $result);
	}


	public function providerAllowEmpty()
	{
		return [
			['https://yiiframework.com/?get=param', false, 'https://yiiframework.com/?get=param'],
			['ftp://yiiframework.com/?get=param', false, false],
			['yiiframework.com/?get=param', false, false],
			['that-s-not-an-url-at-all', false, false],
			['http://☹.com/', false, false],
			['rsync://gentoo.org:873/distfiles/', false, false],
			['https://gentoo.org:8080/distfiles/', false, 'https://gentoo.org:8080/distfiles/'],
			[' ', false, false],
			['', false, false],

			['https://yiiframework.com/?get=param', true, 'https://yiiframework.com/?get=param'],
			['ftp://yiiframework.com/?get=param', true, false],
			['yiiframework.com/?get=param', true, false],
			['that-s-not-an-url-at-all', true, false],
			['http://☹.com/', true, false],
			['rsync://gentoo.org:873/distfiles/', true, false],
			['https://gentoo.org:8080/distfiles/', true, 'https://gentoo.org:8080/distfiles/'],
			[' ', true, false],
			['', true, ''],
		];
	}

	/**
	 * @dataProvider providerAllowEmpty
	 *
	 * @param string $url
	 * @param array $allowEmpty
	 * @param string $assertion
	 */
	public function testAllowEmpty($url, $allowEmpty, $assertion)
	{
		$urlValidator = new CUrlValidator();
		$urlValidator->allowEmpty = $allowEmpty;
		$result = $urlValidator->validateValue($url);
		$this->assertEquals($assertion, $result);
	}

	/**
	 * https://github.com/yiisoft/yii/issues/1955
	 */
	public function testArrayValue()
	{
		$model=new ValidatorTestModel('CUrlValidatorTest');
		$model->url=['https://yiiframework.com/'];
		$model->validate(['url']);
		$this->assertTrue($model->hasErrors('url'));
		$this->assertEquals(['Url is not a valid URL.'],$model->getErrors('url'));
	}
}
