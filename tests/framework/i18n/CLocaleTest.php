<?php
class CLocaleTest extends CTestCase
{
	private $noPlurals = [
		'az', 'bm', 'fa', 'ig', 'hu', 'ja', 'kde', 'kea', 'ko', 'my', 'ses', 'sg',
		'to', 'tr', 'vi', 'wo', 'yo', 'zh', 'bo', 'dz', 'id', 'jv', 'ka', 'km',
		'kn', 'ms', 'th'
	];

	/**
	 * Codes of locales where official guide translation exists
	 */
	protected $criticalLocaleCodes = [
		'en',
		'bg',
		'bs',
		'cs',
		'de',
		'el',
		'es',
		'fr',
		'he',
		'hu',
		'id',
		'it',
		'ja',
		'lv',
		'nl',
		'no',
		'pl',
		'pt',
		'ro',
		'ru',
		'sk',
		'sr',
		'sr_yu',
		'sv',
		'ta_in',
		'th',
		'tr',
		'uk',
		'vi',
		'zh_cn',
		'zh_tw',
	];

	function setUp()
	{
		$config = [
			'language' => 'en',
		];

		new TestApplication($config);
		Yii::app()->configure($config);
	}

	function testRequiredDataExistence(){
		foreach($this->criticalLocaleCodes as $localeCode){
			$locale = Yii::app()->getLocale($localeCode);
			// AM/PM
			$this->assertNotNull($locale->getAMName(), "$localeCode: getAMName failed.");
			$this->assertNotNull($locale->getPMName(), "$localeCode: getPMName failed.");

			// currency
			$this->assertNotNull($locale->getCurrencySymbol("USD"), "$localeCode: getCurrencySymbol USD failed.");
			$this->assertNotNull($locale->getCurrencySymbol("EUR"), "$localeCode: getCurrencySymbol EUR failed.");

			// numbers
			$this->assertNotNull($locale->getNumberSymbol('decimal'), "$localeCode: getNumberSymbol failed.");
			$this->assertNotNull($locale->getDecimalFormat(), "$localeCode: getDecimalFormat failed.");
			$this->assertNotNull($locale->getCurrencyFormat(), "$localeCode: getCurrencyFormat failed.");
			$this->assertNotNull($locale->getPercentFormat(), "$localeCode: getPercentFormat failed.");
			$this->assertNotNull($locale->getScientificFormat(), "$localeCode: getScientificFormat failed.");

			// date and time formats
			$this->assertNotNull($locale->getMonthName(1), "$localeCode: getMonthName 1 failed.");
			$this->assertNotNull($locale->getMonthName(12, 'abbreviated'), "$localeCode: getMonthName 12 abbreviated failed.");
			$this->assertNotNull($locale->getMonthName(1, 'narrow', true), "$localeCode: getMonthName 1 narrow standalone failed.");
			$this->assertEquals(12, count($locale->getMonthNames()), "$localeCode: getMonthNames failed.");
			$this->assertNotNull($locale->getWeekDayName(0), "$localeCode: getWeekDayName failed.");
			$this->assertNotNull($locale->getWeekDayNames(), "$localeCode: getWeekDayNames failed.");
			$this->assertNotNull($locale->getEraName(1), "$localeCode: getEraName failed.");
			$this->assertNotNull($locale->getDateFormat(), "$localeCode: getDateFormat failed.");
			$this->assertNotNull($locale->getTimeFormat(), "$localeCode: getTimeFormat failed.");
			$this->assertNotNull($locale->getDateTimeFormat(), "$localeCode: getDateTimeFormat failed.");

			// ORIENTATION
			$this->assertTrue(in_array($locale->getOrientation(), ['ltr', 'rtl']), "$localeCode: getOrientation failed.");

			// plurals
			$l = explode('_', $localeCode);
			if(!in_array($l[0], $this->noPlurals)){
				$pluralRules = $locale->getPluralRules();
				$this->assertNotEmpty($pluralRules, $localeCode.": no plural rules");
			}
		}
	}

	public function providerGetLocaleDisplayName()
	{
		return [
			['de','en_US','amerikanisches englisch'],
			['de','en','englisch'],
			['de_DE','en_US','amerikanisches englisch'],
			['de_DE','en','englisch'],

			['es_MX',null,null],
			['es_ES',null,null],

			// https://github.com/yiisoft/yii/issues/2087
			['en_us','en','english'],
			['en_us','en_us','u.s. english'],
			['en_us','pt','portuguese'],
			['en_us','pt','portuguese'],
			['en_us','pt_br','brazilian portuguese'],
			['en_us','pt_pt','european portuguese'],
		];
	}

	/**
	 * @dataProvider providerGetLocaleDisplayName
	 */
	public function testGetLocaleDisplayName($ctorLocale,$methodLocale,$assertion)
	{
		$locale=CLocale::getInstance($ctorLocale);
		$this->assertEquals(mb_strtolower((string)$assertion),mb_strtolower((string)$locale->getLocaleDisplayName((string)$methodLocale)));
	}

	public function providerGetLanguage()
	{
		return [
			['en','fr_FR','french'],
			['en','fr','french'],
			['en_US','fr_FR','french'],
			['en_US','fr','french'],
			['ru','de_DE','немецкий'],
			['ru','de','немецкий'],
			['ru_RU','de_DE','немецкий'],
			['ru_RU','de','немецкий'],
			['de','en_US','englisch'],
			['de','en','englisch'],
			['de','US',null],
			['de_DE','en_US','englisch'],
			['de_DE','en','englisch'],
			['de_DE','US',null],

			['es_MX',null,null],
			['es_ES',null,null],

			['ru_RU','zh-Hans-CN','китайский'],
			['en_US','zh-Hans-CN','chinese'],
			['ru_RU','zh-Hant-HK','китайский'],
			['en_US','zh-Hant-HK','chinese'],
			['ru','zh-Hant-HK','китайский'],
			['en','zh-Hant-HK','chinese'],
			['ru','CN',null],
			['en','CN',null],
			['ru','Hant',null],
			['en','Hant',null],

			// https://github.com/yiisoft/yii/issues/2087
			['en_us','en','English'],
			['en_us','en_us','English'],
			['en_us','us',null],
			['en_us','pt','Portuguese'],
			['en_us','pt','Portuguese'],
			['en_us','pt_br','Portuguese'],
			['en_us','br','Breton'],
			['en_us','pt_pt','Portuguese'],
		];
	}

	/**
	 * @dataProvider providerGetLanguage
	 */
	public function testGetLanguage($ctorLocale,$methodLocale,$assertion)
	{
		$locale=CLocale::getInstance($ctorLocale);
		$this->assertEquals(mb_strtolower((string)$assertion),mb_strtolower((string)$locale->getLanguage((string)$methodLocale)));
	}

	public function providerGetScript()
	{
		return [
			['en','fr_FR',null],
			['en','fr',null],
			['en_US','fr_FR',null],
			['en_US','fr',null],
			['ru','de_DE',null],
			['ru','de',null],
			['ru_RU','de_DE',null],
			['ru_RU','de',null],
			['de','en_US',null],
			['de','en',null],
			['de','US',null],
			['de_DE','en_US',null],
			['de_DE','en',null],
			['de_DE','US',null],

			['es_MX',null,null],
			['es_ES',null,null],

			['ru_RU','zh-Hans-CN','Упрощенный китайский'],
			['en_US','zh-Hans-CN','Simplified Han'],
			['ru_RU','zh-Hant-HK','Традиционный китайский'],
			['en_US','zh-Hant-HK','Traditional Han'],
			['ru','zh-Hant-HK','Традиционный китайский'],
			['en','zh-Hant-HK','Traditional Han'],
			['en','zh-CN',null],
			['en','zh-HK',null],
		];
	}

	/**
	 * @dataProvider providerGetScript
	 */
	public function testGetScript($ctorLocale,$methodLocale,$assertion)
	{
		$locale=CLocale::getInstance($ctorLocale);
		$this->assertEquals((string)$assertion,$locale->getScript((string)$methodLocale));
	}

	public function providerGetTerritory()
	{
		return [
			['en','fr_FR','France'],
			['en','fr','France'],
			['en_US','fr_FR','France'],
			['en_US','fr','France'],
			['ru','de_DE','Германия'],
			['ru','de','Германия'],
			['ru_RU','de_DE','Германия'],
			['ru_RU','de','Германия'],
			['de','en_US','Vereinigte Staaten'],
			['de','en',null],
			['de_DE','en_US','Vereinigte Staaten'],
			['de_DE','en',null],

			['es_MX',null,null],
			['es_ES',null,null],

			['ru_RU','zh-Hans-CN','Китай'],
			['en_US','zh-Hans-CN','China'],
			['ru_RU','zh-CN','Китай'],
			['en_US','zh-CN','China'],
			['ru_RU','Hans-CN','Китай'],
			['en_US','Hans-CN','China'],
			['ru_RU','CN','Китай'],
			['en_US','CN','China'],
			['ru_RU','zh',null],
			['en_US','zh',null],
			['ru_RU','Hans',null],
			['en_US','Hans',null],

			['fi_fi','se','Ruotsi'],
			['fi_fi','sv_se','Ruotsi'],
			['fi_fi','sv','El Salvador'],
		];
	}

	/**
	 * @dataProvider providerGetTerritory
	 */
	public function testGetTerritory($ctorLocale,$methodLocale,$assertion)
	{
		$locale=CLocale::getInstance($ctorLocale);
		$this->assertEquals((string)$assertion,$locale->getTerritory((string)$methodLocale));
	}
}
