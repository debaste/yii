<?php

class CHtmlTest extends CTestCase
{
	public function setUp()
	{
		// clean up any possible garbage in global clientScript app component
		Yii::app()->clientScript->reset();

		// reset CHtml ID counter
		CHtml::$count=0;

		Yii::app()->request->baseUrl=null; // reset so that it can be determined based on scriptUrl
		Yii::app()->request->scriptUrl='/bootstrap.php';
	}

	public function tearDown()
	{
		// do not keep any garbage in global clientScript app component
		Yii::app()->clientScript->reset();
	}

	/* HTML characters encode/decode tests */

	public static function providerEncodeArray()
	{
		return [
				[ ['lessThanExpression'=>'4 < 9'], ['lessThanExpression'=>'4 &lt; 9'] ],
				[ [['lessThanExpression'=>'4 < 9']], [['lessThanExpression'=>'4 &lt; 9']] ],
				[ [['lessThanExpression'=>'4 < 9'], 'greaterThanExpression'=>'4 > 9'], [['lessThanExpression'=>'4 &lt; 9'], 'greaterThanExpression'=>'4 &gt; 9'] ]
			];
	}

	/**
	 * @dataProvider providerEncodeArray
	 *
	 * @param type $data
	 * @param type $assertion
	 */
	public function testEncodeArray($data, $assertion)
	{
		$this->assertEquals($assertion, CHtml::encodeArray($data));
	}

	/* Javascript generator tests */

	public static function providerAjax()
	{
		return [
				[["url" => "index"], "jQuery.ajax({'url':'index','cache':false});"],
				[["url" => "index", "success" => "function() { this.alert(\"HI\"); }"], "jQuery.ajax({'url':'index','success':function() { this.alert(\"HI\"); },'cache':false});"],
				[["async" => true, "success" => "function() { this.alert(\"HI\"); }"], "jQuery.ajax({'async':true,'success':function() { this.alert(\"HI\"); },'url':location.href,'cache':false});"],
				[["update" =>"#my-div", "success" => "function() { this.alert(\"HI\"); }"], "jQuery.ajax({'success':function() { this.alert(\"HI\"); },'url':location.href,'cache':false});"],
				[["update" =>"#my-div"], "jQuery.ajax({'url':location.href,'cache':false,'success':function(html){jQuery(\"#my-div\").html(html)}});"],
				[["replace" =>"#my-div", "success" => "function() { this.alert(\"HI\"); }"], "jQuery.ajax({'success':function() { this.alert(\"HI\"); },'url':location.href,'cache':false});"],
				[["replace" =>"#my-div"], "jQuery.ajax({'url':location.href,'cache':false,'success':function(html){jQuery(\"#my-div\").replaceWith(html)}});"]
			];
	}

	/**
	 * @dataProvider providerAjax
	 *
	 * @param type $options
	 * @param type $assertion
	 */
	public function testAjax($options, $assertion)
	{
		$this->assertEquals($assertion, CHtml::ajax($options));
	}

	/* DOM element generated from model attribute tests */

	public static function providerActiveDOMElements()
	{
		return [
				[new CHtmlTestModel(['attr1'=>true]), 'attr1', [], '<input id="ytCHtmlTestModel_attr1" type="hidden" value="0" name="CHtmlTestModel[attr1]" /><input name="CHtmlTestModel[attr1]" id="CHtmlTestModel_attr1" value="1" type="checkbox" />'],
				[new CHtmlTestModel(['attr1'=>false]), 'attr1', [], '<input id="ytCHtmlTestModel_attr1" type="hidden" value="0" name="CHtmlTestModel[attr1]" /><input name="CHtmlTestModel[attr1]" id="CHtmlTestModel_attr1" value="1" type="checkbox" />']
			];
	}

	/**
	 * @dataProvider providerActiveDOMElements
	 *
	 * @param string $action
	 * @param string $method
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testActiveCheckbox($model,$attribute,$htmlOptions, $assertion)
	{
		$this->assertEquals($assertion, CHtml::activeCheckBox($model,$attribute,$htmlOptions));
	}

	/* Static DOM element generator tests */

	public static function providerBeginForm()
	{
		return [
				["index", "get", [], '<form action="index" method="get">'],
				["index", "post", [], '<form action="index" method="post">'],
				["index?myFirstParam=3&mySecondParam=true#anchor", "get", [],
"<form action=\"index?myFirstParam=3&amp;mySecondParam=true#anchor\" method=\"get\">\n".
"<input type=\"hidden\" value=\"3\" name=\"myFirstParam\" />\n".
"<input type=\"hidden\" value=\"true\" name=\"mySecondParam\" />"],

			];
	}

	/**
	 * @dataProvider providerBeginForm
	 *
	 * @param string $action
	 * @param string $method
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testBeginForm($action, $method, $htmlOptions, $assertion)
	{
		/* TODO - Steven Wexler - 3/5/11 - Mock out static methods in this function when CHtml leverages late static method binding
		 * because PHPUnit.  This is only possible Yii supports only >= PHP 5.3   - */
		$this->assertEquals($assertion, CHtml::beginForm($action, $method, $htmlOptions));
		$this->assertEquals($assertion, CHtml::form($action, $method, $htmlOptions));
	}

	public static function providerTextArea()
	{
		return [
				["textareaone", '', [], "<textarea name=\"textareaone\" id=\"textareaone\"></textarea>"],
				["textareaone", '', ["id"=>"MyAwesomeTextArea", "dog"=>"Lassie", "class"=>"colorful bright"], "<textarea id=\"MyAwesomeTextArea\" dog=\"Lassie\" class=\"colorful bright\" name=\"textareaone\"></textarea>"],
				["textareaone", '', ["id"=>false], "<textarea name=\"textareaone\"></textarea>"],
			];
	}

	/**
	 * @dataProvider providerTextArea
	 *
	 * @param string $name
	 * @param string $value
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testTextArea($name, $value, $htmlOptions, $assertion)
	{
		$this->assertEquals($assertion, CHtml::textArea($name, $value, $htmlOptions));
	}

	public function providerOpenTag()
	{
		return [
			['div', [], '<div>'],
			['h1', ['id'=>'title', 'class'=>'red bold'], '<h1 id="title" class="red bold">'],
			['ns:tag', ['attr1'=>'attr1value1 attr1value2'], '<ns:tag attr1="attr1value1 attr1value2">'],
			['option', ['checked'=>true, 'disabled'=>false, 'defer'=>true], '<option checked="checked" defer="defer">'],
			['another-tag', ['some-attr'=>'<>/\\<&', 'encode'=>true], '<another-tag some-attr="&lt;&gt;/\&lt;&amp;">'],
			['tag', ['attr-no-encode'=>'<&', 'encode'=>false], '<tag attr-no-encode="<&">'],
		];
	}

	/**
	 * @dataProvider providerOpenTag
	 *
	 * @param string $tag
	 * @param string $htmlOptions
	 * @param string $assertion
	 */
	public function testOpenTag($tag, $htmlOptions, $assertion)
	{
		$this->assertEquals($assertion, CHtml::openTag($tag, $htmlOptions));
	}

	public static function providerCloseTag()
	{
		return [
			['div', '</div>'],
			['h1', '</h1>'],
			['ns:tag', '</ns:tag>'],
			['minus-tag', '</minus-tag>'],
		];
	}

	/**
	 * @dataProvider providerCloseTag
	 *
	 * @param string $tag
	 * @param string $assertion
	 */
	public function testCloseTag($tag, $assertion)
	{
		$this->assertEquals($assertion, CHtml::closeTag($tag));
	}

	public static function providerCdata()
	{
		return [
			['cdata-content', '<![CDATA[cdata-content]]>'],
			['123321', '<![CDATA[123321]]>'],
		];
	}

	/**
	 * @dataProvider providerCdata
	 *
	 * @param string $data
	 * @param string $assertion
	 */
	public function testCdata($data, $assertion)
	{
		$this->assertEquals($assertion, CHtml::cdata($data));
	}

	public function providerMetaTag()
	{
		return [
			['simple-meta-tag', null, null, [],
				'<meta content="simple-meta-tag" />'],
			['test-name-attr', 'random-name', null, [],
				'<meta name="random-name" content="test-name-attr" />'],
			['text/html; charset=UTF-8', null, 'Content-Type', [],
				'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'],
			['test-attrs', null, null, ['xhtml-invalid-attr'=>'attr-value'],
				'<meta xhtml-invalid-attr="attr-value" content="test-attrs" />'],
			['complex-test', 'testing-name', 'Content-Type', ['attr1'=>'value2'],
				'<meta attr1="value2" name="testing-name" http-equiv="Content-Type" content="complex-test" />'],
		];
	}

	/**
	 * @dataProvider providerMetaTag
	 *
	 * @param string $content
	 * @param string $name
	 * @param string $httpEquiv
	 * @param array $options
	 * @param string $assertion
	 */
	public function testMetaTag($content, $name, $httpEquiv, $options, $assertion)
	{
		$this->assertEquals($assertion, CHtml::metaTag($content, $name, $httpEquiv, $options));
	}

	public function providerLinkTag()
	{
		return [
			[null, null, null, null, [], '<link />'],
			['stylesheet', null, null, null, [], '<link rel="stylesheet" />'],
			[null, 'text/css', null, null, [], '<link type="text/css" />'],
			[null, null, '/css/style.css', null, [], '<link href="/css/style.css" />'],
			[null, null, null, 'screen', [], '<link media="screen" />'],
			[null, null, null, null, ['attr'=>'value'], '<link attr="value" />'],
			['stylesheet', 'text/css', '/css/style.css', 'screen', ['attr'=>'value'],
				'<link attr="value" rel="stylesheet" type="text/css" href="/css/style.css" media="screen" />'],
		];
	}

	/**
	 * @dataProvider providerLinkTag
	 *
	 * @param string $relation
	 * @param string $type
	 * @param string $href
	 * @param string $media
	 * @param array $options
	 * @param string $assertion
	 */
	public function testLinkTag($relation, $type, $href, $media, $options, $assertion)
	{
		$this->assertEquals($assertion, CHtml::linkTag($relation, $type, $href, $media, $options));
	}

	public static function providerCssWithoutCdata()
	{
		return [
			['h1{font-size:20px;line-height:26px;}', '',
				"<style type=\"text/css\">\nh1{font-size:20px;line-height:26px;}\n</style>"],
			['h2{font-size:16px;line-height:22px;}', 'screen',
				"<style type=\"text/css\" media=\"screen\">\nh2{font-size:16px;line-height:22px;}\n</style>"],
		];
	}

	/**
	 * @dataProvider providerCssWithoutCdata
	 * @backupStaticAttributes enabled
	 *
	 * @param string $text
	 * @param string $media
	 * @param string $assertion
	 */
	public function testCssWithoutCdata($text, $media, $assertion)
	{
		CHtml::$cdataScriptAndStyleContents=false;
		$this->assertEquals($assertion, CHtml::css($text, $media));
	}
	
	public static function providerCss()
	{
		return [
			['h1{font-size:20px;line-height:26px;}', '',
				"<style type=\"text/css\">\n/*<![CDATA[*/\nh1{font-size:20px;line-height:26px;}\n/*]]>*/\n</style>"],
			['h2{font-size:16px;line-height:22px;}', 'screen',
				"<style type=\"text/css\" media=\"screen\">\n/*<![CDATA[*/\nh2{font-size:16px;line-height:22px;}\n/*]]>*/\n</style>"],
		];
	}

	/**
	 * @dataProvider providerCss
	 *
	 * @param string $text
	 * @param string $media
	 * @param string $assertion
	 */
	public function testCss($text, $media, $assertion)
	{
		$this->assertEquals($assertion, CHtml::css($text, $media));
	}

	public static function providerCssFile()
	{
		return [
			['/css/style.css?a=1&b=2', '', '<link rel="stylesheet" type="text/css" href="/css/style.css?a=1&amp;b=2" />'],
			['/css/style.css?c=3&d=4', 'screen', '<link rel="stylesheet" type="text/css" href="/css/style.css?c=3&amp;d=4" media="screen" />'],
		];
	}

	/**
	 * @dataProvider providerCssFile
	 *
	 * @param string $url
	 * @param string $media
	 * @param string $assertion
	 */
	public function testCssFile($url, $media, $assertion)
	{
		$this->assertEquals($assertion, CHtml::cssFile($url, $media));
	}

	public static function providerScript()
	{
		return [
			['var a = 10;', "<script type=\"text/javascript\">\n/*<![CDATA[*/\nvar a = 10;\n/*]]>*/\n</script>"],
			["\t(function() { var x = 100; })();\n\tvar y = 200;",
				"<script type=\"text/javascript\">\n/*<![CDATA[*/\n\t(function() { var x = 100; })();\n\tvar y = 200;\n/*]]>*/\n</script>"],
		];
	}

	/**
	 * @dataProvider providerScript
	 *
	 * @param string $text
	 * @param string $assertion
	 */
	public function testScript($text, $assertion)
	{
		$this->assertEquals($assertion, CHtml::script($text));
	}
	
	public static function providerScriptWithoutCdata()
	{
		return [
			['var a = 10;', "<script type=\"text/javascript\">\nvar a = 10;\n</script>"],
			["\t(function() { var x = 100; })();\n\tvar y = 200;",
				"<script type=\"text/javascript\">\n\t(function() { var x = 100; })();\n\tvar y = 200;\n</script>"],
		];
	}

	/**
	 * @dataProvider providerScriptWithoutCdata
	 * @backupStaticAttributes enabled
	 *
	 * @param string $text
	 * @param string $assertion
	 */
	public function testScriptWithoutCdata($text, $assertion)
	{
		CHtml::$cdataScriptAndStyleContents=false;
		$this->assertEquals($assertion, CHtml::script($text));
	}
	
	/**
	 * @dataProvider providerScript
	 * @backupStaticAttributes enabled
	 *
	 * @param string $text
	 * @param string $assertion
	 */
	public function testScriptHtml5($text, $assertion)
	{
		CHtml::$setScriptType=false;
		$assertion=str_replace(' type="text/javascript"', '', $assertion);
		$this->assertEquals($assertion, CHtml::script($text));
	}

	public static function providerScriptWithHtmlOptions()
	{
		return [
			[
				'var a = 10;',
				['defer'=>true],
				"<script type=\"text/javascript\" defer=\"defer\">\n/*<![CDATA[*/\nvar a = 10;\n/*]]>*/\n</script>"
			],
			[
				'var a = 10;',
				['async'=>true],
				"<script type=\"text/javascript\" async=\"async\">\n/*<![CDATA[*/\nvar a = 10;\n/*]]>*/\n</script>"
			],
			[
				'var a = 10;',
				['async'=>false],
				"<script type=\"text/javascript\" async=\"false\">\n/*<![CDATA[*/\nvar a = 10;\n/*]]>*/\n</script>"
			],
		];
	}

	/**
	 * @depends testScript
	 * @dataProvider providerScriptWithHtmlOptions
	 *
	 * @param string $text
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testScriptWithHtmlOptions($text, $htmlOptions, $assertion)
	{
		$this->assertEquals($assertion, CHtml::script($text,$htmlOptions));
	}

	public static function providerScriptFile()
	{
		return [
			['/js/main.js?a=2&b=4', '<script type="text/javascript" src="/js/main.js?a=2&amp;b=4"></script>'],
			['http://company.com/get-user-by-name?name=Василий&lang=ru',
				'<script type="text/javascript" src="http://company.com/get-user-by-name?name=Василий&amp;lang=ru"></script>'],
		];
	}

	/**
	 * @dataProvider providerScriptFile
	 *
	 * @param string $text
	 * @param string $assertion
	 */
	public function testScriptFile($text, $assertion)
	{
		$this->assertEquals($assertion, CHtml::scriptFile($text));
	}
	
	/**
	 * @dataProvider providerScriptFile
	 * @backupStaticAttributes enabled
	 *
	 * @param string $text
	 * @param string $assertion
	 */
	public function testScriptFileHtml5($text, $assertion)
	{
		CHtml::$setScriptType=false;
		$assertion=str_replace(' type="text/javascript"', '', $assertion);
		$this->assertEquals($assertion, CHtml::scriptFile($text));
	}

	public static function providerScriptFileWithHtmlOptions()
	{
		return [
			[
				'/js/main.js?a=2&b=4',
				['defer'=>true],
				'<script type="text/javascript" src="/js/main.js?a=2&amp;b=4" defer="defer"></script>'
			],
			[
				'/js/main.js?a=2&b=4',
				['async'=>true],
				'<script type="text/javascript" src="/js/main.js?a=2&amp;b=4" async="async"></script>'
			],
			[
				'/js/main.js?a=2&b=4',
				['onload'=>"some_js_function();"],
				'<script type="text/javascript" src="/js/main.js?a=2&amp;b=4" onload="some_js_function();"></script>'
			],
		];
	}

	/**
	 * @depends testScriptFile
	 * @dataProvider providerScriptFileWithHtmlOptions
	 *
	 * @param string $text
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testScriptFileWithHtmlOptions($text, $htmlOptions, $assertion)
	{
		$this->assertEquals($assertion, CHtml::scriptFile($text, $htmlOptions));
	}

	public function testEndForm()
	{
		$this->assertEquals('</form>', CHtml::endForm());
	}

	public function testActiveId()
	{
		$testModel=new CHtmlTestModel();
		$this->assertEquals('CHtmlTestModel_attr1', CHtml::activeId($testModel, 'attr1'));
		$this->assertEquals('CHtmlTestModel_attr2', CHtml::activeId($testModel, 'attr2'));
		$this->assertEquals('CHtmlTestModel_attr3', CHtml::activeId($testModel, 'attr3'));
		$this->assertEquals('CHtmlTestModel_attr4', CHtml::activeId($testModel, 'attr4'));
	}

	public function testActiveName()
	{
		$testModel=new CHtmlTestModel();
		$this->assertEquals('CHtmlTestModel[attr1]', CHtml::activeName($testModel, 'attr1'));
		$this->assertEquals('CHtmlTestModel[attr2]', CHtml::activeName($testModel, 'attr2'));
		$this->assertEquals('CHtmlTestModel[attr3]', CHtml::activeName($testModel, 'attr3'));
		$this->assertEquals('CHtmlTestModel[attr4]', CHtml::activeName($testModel, 'attr4'));
	}

	public static function providerGetIdByName()
	{
		return [
			['ContactForm[name]', 'ContactForm_name'],
			['Order[name][first]', 'Order_name_first'],
			['Order[name][last]', 'Order_name_last'],
			['Recipe[photo][]', 'Recipe_photo'],
			['Request title', 'Request_title'],
		];
	}

	/**
	 * @dataProvider providerGetIdByName
	 *
	 * @param string $text
	 * @param string $assertion
	 */
	public function testGetIdByName($text, $assertion)
	{
		$this->assertEquals($assertion, CHtml::getIdByName($text));
	}

	public function testResolveName()
	{
		$testModel=new CHtmlTestFormModel();

		$attrName='stringAttr';
		$this->assertEquals('CHtmlTestFormModel[stringAttr]', CHtml::resolveName($testModel, $attrName));
		$this->assertEquals('stringAttr', $attrName);

		$attrName='arrayAttr[k1]';
		$this->assertEquals('CHtmlTestFormModel[arrayAttr][k1]', CHtml::resolveName($testModel, $attrName));
		$this->assertEquals('arrayAttr[k1]', $attrName);

		$attrName='arrayAttr[k3][k5]';
		$this->assertEquals('CHtmlTestFormModel[arrayAttr][k3][k5]', CHtml::resolveName($testModel, $attrName));
		$this->assertEquals('arrayAttr[k3][k5]', $attrName);

		$attrName='[k3][k4]arrayAttr';
		$this->assertEquals('CHtmlTestFormModel[k3][k4][arrayAttr]', CHtml::resolveName($testModel, $attrName));
		$this->assertEquals('arrayAttr', $attrName);

		$attrName='[k3]arrayAttr[k4]';
		$this->assertEquals('CHtmlTestFormModel[k3][arrayAttr][k4]', CHtml::resolveName($testModel, $attrName));
		$this->assertEquals('arrayAttr[k4]', $attrName);

		// next two asserts gives 100% code coverage of the CHtml::resolveName() method
		// otherwise penultimate line (last closing curly bracket) of the CHtml::resolveName() will not be unit tested
		$attrName='[k3';
		$this->assertEquals('CHtmlTestFormModel[[k3]', CHtml::resolveName($testModel, $attrName));
		$this->assertEquals('[k3', $attrName);
	}

	public function testResolveValue()
	{
		$testModel=new CHtmlTestFormModel();

		$this->assertEquals('stringAttrValue', CHtml::resolveValue($testModel, 'stringAttr'));
		$this->assertEquals('v1', CHtml::resolveValue($testModel, 'arrayAttr[k1]'));
		$this->assertEquals('v2', CHtml::resolveValue($testModel, 'arrayAttr[k2]'));
		$this->assertEquals($testModel->arrayAttr['k3'], CHtml::resolveValue($testModel, 'arrayAttr[k3]'));
		$this->assertEquals('v4', CHtml::resolveValue($testModel, 'arrayAttr[k3][k4]'));
		$this->assertEquals('v5', CHtml::resolveValue($testModel, 'arrayAttr[k3][k5]'));
		$this->assertEquals('v6', CHtml::resolveValue($testModel, 'arrayAttr[k6]'));

		$this->assertNull(CHtml::resolveValue($testModel, 'arrayAttr[k7]'));
		$this->assertNull(CHtml::resolveValue($testModel, 'arrayAttr[k7][k8]'));

		$this->assertEquals($testModel->arrayAttr, CHtml::resolveValue($testModel, '[ignored-part]arrayAttr'));
		$this->assertEquals('v1', CHtml::resolveValue($testModel, '[ignored-part]arrayAttr[k1]'));
		$this->assertEquals('v4', CHtml::resolveValue($testModel, '[ignore-this]arrayAttr[k3][k4]'));
	}

	public function providerValue()
	{
		$result=[
			// $model is array
			[['k1'=>'v1','k2'=>'v2','v3','v4'],'k1',null,'v1'],
			[['k1'=>'v1','k2'=>'v2','v3','v4'],'k2',null,'v2'],
			[['k1'=>'v1','k2'=>'v2','v3','v4'],'k3',null,null],
			[['k1'=>'v1','k2'=>'v2','v3','v4'],'k3','defaultValue','defaultValue'],

			[['k1'=>'v1','k2'=>'v2','v3','v4'],0,null,'v3'],
			[['k1'=>'v1','k2'=>'v2','v3','v4'],1,null,'v4'],
			[['k1'=>'v1','k2'=>'v2','v3','v4'],2,null,null],
			[['k1'=>'v1','k2'=>'v2','v3','v4'],2,'defaultValue','defaultValue'],

			// $model is stdClass
			[(object)['k1'=>'v1','k2'=>'v2','v3','v4'],'k1',null,'v1'],
			[(object)['k1'=>'v1','k2'=>'v2','v3','v4'],'k2',null,'v2'],
			[(object)['k1'=>'v1','k2'=>'v2','v3','v4'],'k3',null,null],
			[(object)['k1'=>'v1','k2'=>'v2','v3','v4'],'k3','defaultValue','defaultValue'],

			[(object)['k1'=>'v1','k2'=>'v2','v3','v4'],0,null,null],
			[(object)['k1'=>'v1','k2'=>'v2','v3','v4'],1,null,null],
			[(object)['k1'=>'v1','k2'=>'v2','v3','v4'],2,null,null],
			[(object)['k1'=>'v1','k2'=>'v2','v3','v4'],2,'defaultValue','defaultValue'],

			// static method
			[['k1'=>'v1','k2'=>'v2','v3','v4'],['CHtmlTest','helperTestValue'],null,'v2'],
			[(object)['k1'=>'v1','k2'=>'v2','v3','v4'],['CHtmlTest','helperTestValue'],null,'v2'],

			// standard PHP functions should not be treated as callables
			[['array_filter'=>'array_filter','sort'=>'sort'],'sort',null,'sort'],
			[['array_filter'=>'array_filter','sort'=>'sort'],'array_map','defaultValue','defaultValue'],
			[(object)['array_filter'=>'array_filter','sort'=>'sort'],'sort',null,'sort'],
			[(object)['array_filter'=>'array_filter','sort'=>'sort'],'array_map','defaultValue','defaultValue'],

			// dot access, array
			[['k1'=>['k2'=>['k3'=>'v3']],['v1','k4'=>'v4']],'k1.k2.k3',null,'v3'],
			[['k1'=>['k2'=>['k3'=>'v3']],['v1','k4'=>'v4']],'0.0',null,'v1'],
			[['k1'=>['k2'=>['k3'=>'v3']],['v1','k4'=>'v4']],'0.k4',null,'v4'],
			[['k1'=>['k2'=>['k3'=>'v3']],['v1','k4'=>'v4']],'0.1',null,null],

			// dot access, object
			[(object)['k1'=>['k2'=>['k3'=>'v3']],['v1','k4'=>'v4']],'k1.k2.k3',null,'v3'],
			[(object)['k1'=>['k2'=>['k3'=>'v3']],['v1','k4'=>'v4']],'0.0',null,null],
			[(object)['k1'=>['k2'=>['k3'=>'v3']],['v1','k4'=>'v4']],'0.k4',null,null],
			[(object)['k1'=>['k2'=>['k3'=>'v3']],['v1','k4'=>'v4']],'0.1',null,null],

			// $attribute parameter is:
			// 1. null or empty string
			// 2. not "0" string, 0 integer or 0.0 double/float
			// 3. empty array doesn't make sense
			[['v1'],null,'defaultValue','defaultValue'],
			[['v1'],"",'defaultValue','defaultValue'],
			[['v1'],"0",'defaultValue','v1'],
			[['v1'],0,'defaultValue','v1'],
			[['v1'],0.0,'defaultValue','v1'],

			// Test $model as an array, with null as a key, see: https://github.com/yiisoft/yii/pull/4503#discussion_r1054516859
			[[null=>'v1','k2'=>'v2'],null,'defaultValue','v1'],
			[[null=>'v1','k2'=>'v2'],'','defaultValue','v1'],
			[[''=>'v1','k2'=>'v2'],null,'defaultValue','v1'],
			[[''=>'v1','k2'=>'v2'],'','defaultValue','v1'],
			[[null=>'v1','k2'=>'v2'],'k2','defaultValue','v2'],
		];

		// create_function is not supported by CHtml::value(), we're just testing this feature/property
		if(version_compare(PHP_VERSION,'8.0','<')) {
			$result=array_merge($result, [
				[['k1' => 'v1', 'k2' => 'v2', 'v3', 'v4'], create_function('$model', 'return $model["k2"];'), null, null],
				[(object)['k1' => 'v1', 'k2' => 'v2', 'v3', 'v4'], create_function('$model', 'return $model->k2;'), null, null],
			]);
		}

		if(class_exists('Closure',false))
		{
			// anonymous function
			$result=array_merge($result,require(dirname(__FILE__).'/CHtml/providerValue.php'));
		}
		return $result;
	}

	/**
	 * @dataProvider providerValue
	 *
	 * @param array|stdClass $model
	 * @param integer|double|string $attribute
	 * @param mixed $defaultValue
	 * @param string $assertion
	 */
	public function testValue($model, $attribute, $defaultValue, $assertion)
	{
		$this->assertEquals($assertion, CHtml::value($model, $attribute, (string)$defaultValue));
	}

	/**
	 * Helper method for {@link testValue()} and {@link providerValue()} methods.
	 */
	public static function helperTestValue($model)
	{
		return is_array($model) ? $model['k2'] : $model->k2;
	}

	public static function providerPageStateField()
	{
		return [
			['testing-value', '<input type="hidden" name="'.CController::STATE_INPUT_NAME.'" value="testing-value" />'],
			['another-testing&value', '<input type="hidden" name="'.CController::STATE_INPUT_NAME.'" value="another-testing&value" />'],
		];
	}

	/**
	 * @dataProvider providerPageStateField
	 *
	 * @param string $value
	 * @param string $assertion
	 */
	public function testPageStateField($value, $assertion)
	{
		$this->assertEquals($assertion, CHtml::pageStateField($value));
	}

	public static function providerEncodeDecode()
	{
		return [
			[
				'<h1 class="header" attr=\'value\'>Text header</h1>',
				'&lt;h1 class=&quot;header&quot; attr=&#039;value&#039;&gt;Text header&lt;/h1&gt;',
			],
			[
				'<p>testing & text</p>',
				'&lt;p&gt;testing &amp; text&lt;/p&gt;',
			],
		];
	}

	/**
	 * @dataProvider providerEncodeDecode
	 *
	 * @param string $text
	 * @param string $assertion
	 */
	public function testEncode($text, $assertion)
	{
		$this->assertEquals($assertion, CHtml::encode($text));
	}

	/**
	 * @dataProvider providerEncodeDecode
	 *
	 * @param string $assertion
	 * @param string $text
	 */
	public function testDecode($assertion, $text)
	{
		$this->assertEquals($assertion, CHtml::decode($text));
	}

	public static function providerRefresh()
	{
		return [
			[
				10,
				'https://yiiframework.com/',
				'<meta http-equiv="refresh" content="10;url=https://yiiframework.com/" />'."\n",
			],
			[
				15,
				['site/index'],
				'<meta http-equiv="refresh" content="15;url=/bootstrap.php?r=site/index" />'."\n",
			],
		];
	}

	/**
	 * @dataProvider providerRefresh
	 *
	 * @param $seconds
	 * @param $url
	 * @param $assertion
	 */
	public function testRefresh($seconds, $url, $assertion)
	{
		// this adds element to the CClientScript::$metaTags
		CHtml::refresh($seconds, $url);

		// now render html head with registered meta tags
		$output='';
		Yii::app()->clientScript->renderHead($output);

		// and test it now
		$this->assertEquals($assertion, $output);
	}

	public static function providerStatefulForm()
	{
		// we should keep in mind that CHtml::statefulForm() calls CHtml::beginForm() internally
		// so we can make expected assertion value more readable by using CHtml::beginForm() because
		// we are testing stateful feature of the CHtml::statefulForm(), not <form> tag generation
		// same true for CHtml::pageStateField - it is already tested in another method
		return [
			[
				['site/index'],
				'post',
				[],
				CHtml::form(['site/index'], 'post', [])."\n".'<div style="display:none">'.CHtml::pageStateField('').'</div>'
			],
			[
				'/some-static/url',
				'get',
				['test-attr'=>'test-value'],
				CHtml::form('/some-static/url', 'get', ['test-attr'=>'test-value'])."\n".'<div style="display:none">'.CHtml::pageStateField('').'</div>'
			],
		];
	}

	/**
	 * @dataProvider providerStatefulForm
	 *
	 * @param string $action
	 * @param string $method
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testStatefulForm($action, $method, $htmlOptions, $assertion)
	{
		$this->assertEquals($assertion, CHtml::statefulForm($action, $method, $htmlOptions));
	}

	public static function providerMailto()
	{
		return [
			[
				'Drop me a line! ;-)',
				'admin@example.com',
				['class'=>'mail-to-admin'],
				'<a class="mail-to-admin" href="mailto:admin@example.com">Drop me a line! ;-)</a>',
			],
			[
				'Contact me',
				'foo@bar.baz',
				[],
				'<a href="mailto:foo@bar.baz">Contact me</a>',
			],
			[
				'boss@acme.com',
				'',
				[],
				'<a href="mailto:boss@acme.com">boss@acme.com</a>',
			],
		];
	}

	/**
	 * @dataProvider providerMailto
	 *
	 * @param string $text
	 * @param string $email
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testMailto($text, $email, $htmlOptions, $assertion)
	{
		$this->assertEquals($assertion, CHtml::mailto($text, $email, $htmlOptions));
	}

	public static function providerImage()
	{
		return [
			['/images/logo.png', 'YiiSoft, LLC', [], '<img src="/images/logo.png" alt="YiiSoft, LLC" />'],
			['/img/test.jpg', '', ['class'=>'test-img'], '<img class="test-img" src="/img/test.jpg" alt="" />'],
		];
	}

	/**
	 * @dataProvider providerImage
	 *
	 * @param string $src
	 * @param string $alt
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testImage($src, $alt, $htmlOptions, $assertion)
	{
		$this->assertEquals($assertion, CHtml::image($src, $alt, $htmlOptions));
	}

	public function providerActiveLabel()
	{
		return [
			[false, 'userName', [], '<label for="CHtmlTestActiveModel_userName">User Name</label>'],
			[false, 'userName', ['for'=>'someTestingInput'], '<label for="someTestingInput">User Name</label>'],
			[false, 'userName', ['label'=>'Custom Label'], '<label for="CHtmlTestActiveModel_userName">Custom Label</label>'],
			[false, 'userName', ['label'=>false], ''],
			[true, 'userName', [], '<label class="error" for="CHtmlTestActiveModel_userName">User Name</label>'],
			[true, 'userName', ['for'=>'someTestingInput'], '<label class="error" for="someTestingInput">User Name</label>'],
			[true, 'firstName', ['label'=>'Custom Label'], '<label for="CHtmlTestActiveModel_firstName">Custom Label</label>'],
			[true, 'userName', ['label'=>false], ''],
			[false, '[1]userName', ['for'=>'customFor'], '<label for="customFor">User Name</label>'],
		];
	}

	/**
	 * @dataProvider providerActiveLabel
	 *
	 * @param boolean $validate
	 * @param string $attribute
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testActiveLabel($validate, $attribute, $htmlOptions, $assertion)
	{
		$model=new CHtmlTestActiveModel();
		if($validate)
			$model->validate();
		$this->assertEquals($assertion, CHtml::activeLabel($model, $attribute, $htmlOptions));
	}

	public function providerActiveLabelEx()
	{
		return [
			[false, 'firstName', [], '<label for="CHtmlTestActiveModel_firstName">First Name</label>'],
			[false, 'firstName', ['for'=>'someTestingInput'], '<label for="someTestingInput">First Name</label>'],
			[false, 'userName', ['label'=>'Custom Label'], '<label for="CHtmlTestActiveModel_userName" class="required">Custom Label <span class="required">*</span></label>'],
			[false, 'userName', ['label'=>false], ''],
			[true, 'userName', [], '<label class="error required" for="CHtmlTestActiveModel_userName">User Name <span class="required">*</span></label>'],
			[true, 'userName', ['for'=>'someTestingInput'], '<label class="error required" for="someTestingInput">User Name <span class="required">*</span></label>'],
			[true, 'firstName', ['label'=>'Custom Label'], '<label for="CHtmlTestActiveModel_firstName">Custom Label</label>'],
			[true, 'firstName', ['label'=>false], ''],
		];
	}

	/**
	 * @dataProvider providerActiveLabelEx
	 *
	 * @param boolean $addErrors
	 * @param string $attribute
	 * @param array $htmlOptions
	 * @param string $validate
	 */
	public function testActiveLabelEx($validate, $attribute, $htmlOptions, $assertion)
	{
		$model=new CHtmlTestActiveModel();
		if($validate)
			$model->validate();
		$this->assertEquals($assertion, CHtml::activeLabelEx($model, $attribute, $htmlOptions));
	}

	public function providerActiveTextField()
	{
		return [
			[false, 'userName', ['class'=>'user-name-field'],
				'<input class="user-name-field" name="CHtmlTestActiveModel[userName]" id="CHtmlTestActiveModel_userName" type="text" />'],
			[true, 'userName', ['class'=>'user-name-field'],
				'<input class="user-name-field error" name="CHtmlTestActiveModel[userName]" id="CHtmlTestActiveModel_userName" type="text" />'],
			[false, 'firstName', ['class'=>'first-name-field'],
				'<input class="first-name-field" name="CHtmlTestActiveModel[firstName]" id="CHtmlTestActiveModel_firstName" type="text" />'],
			[true, 'firstName', ['class'=>'first-name-field'],
				'<input class="first-name-field" name="CHtmlTestActiveModel[firstName]" id="CHtmlTestActiveModel_firstName" type="text" />'],
		];
	}

	/**
	 * @dataProvider providerActiveTextField
	 *
	 * @param boolean $validate
	 * @param string $attribute
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testActiveTextField($validate, $attribute, $htmlOptions, $assertion)
	{
		$model=new CHtmlTestActiveModel();
		if($validate)
			$model->validate();
		$this->assertEquals($assertion, CHtml::activeTextField($model, $attribute, $htmlOptions));
	}

	public function providerActiveUrlField()
	{
		return [
			[false, 'userName', ['class'=>'test-class-attr'],
				'<input class="test-class-attr" name="CHtmlTestActiveModel[userName]" id="CHtmlTestActiveModel_userName" type="url" />'],
			[true, 'userName', ['another-attr'=>'another-attr-value', 'id'=>'changed-id'],
				'<input another-attr="another-attr-value" id="changed-id" name="CHtmlTestActiveModel[userName]" type="url" class="error" />'],
			[false, 'firstName', [],
				'<input name="CHtmlTestActiveModel[firstName]" id="CHtmlTestActiveModel_firstName" type="url" />'],
			[true, 'firstName', ['disabled'=>true, 'name'=>'changed-name'],
				'<input disabled="disabled" name="changed-name" id="changed-name" type="url" />'],
		];
	}

	/**
	 * @dataProvider providerActiveUrlField
	 *
	 * @param boolean $validate
	 * @param string $attribute
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testActiveUrlField($validate, $attribute, $htmlOptions, $assertion)
	{
		$model=new CHtmlTestActiveModel();
		if($validate)
			$model->validate();
		$this->assertEquals($assertion, CHtml::activeUrlField($model, $attribute, $htmlOptions));
	}

	public function providerButton()
	{
		return [
			['button1', ['name'=>null, 'class'=>'class1'], '<input class="class1" type="button" value="button1" />'],
			['button2', ['name'=>'custom-name', 'class'=>'class2'], '<input name="custom-name" class="class2" type="button" value="button2" />'],
			['button3', ['type'=>'submit'], '<input type="submit" name="yt0" value="button3" />'],
			['button4', ['value'=>'button-value'], '<input value="button-value" name="yt0" type="button" />'],
			['button5', [], '<input name="yt0" type="button" value="button5" />'],
		];
	}

	/**
	 * @dataProvider providerButton
	 *
	 * @param string $label
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testButton($label, $htmlOptions, $assertion)
	{
		$this->assertEquals($assertion, CHtml::button($label, $htmlOptions));
	}

	public function providerHtmlButton()
	{
		return [
			['button1', ['name'=>null, 'class'=>'class1'], '<button name="yt0" class="class1" type="button">button1</button>'],
			['button2', ['name'=>'custom-name', 'class'=>'class2'], '<button name="custom-name" class="class2" type="button">button2</button>'],
			['button3', ['type'=>'submit'], '<button type="submit" name="yt0">button3</button>'],
			['button4', ['value'=>'button-value'], '<button value="button-value" name="yt0" type="button">button4</button>'],
			['button5', [], '<button name="yt0" type="button">button5</button>'],
		];
	}

	/**
	 * @dataProvider providerHtmlButton
	 *
	 * @param string $label
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testHtmlButton($label, $htmlOptions, $assertion)
	{
		$this->assertEquals($assertion, CHtml::htmlButton($label, $htmlOptions));
	}

	public function providerSubmitButton()
	{
		return [
			['submit', [], '<input type="submit" name="yt0" value="submit" />'],
			['submit1', ['type'=>'button'], '<input type="submit" name="yt0" value="submit1" />'],
		];
	}

	/**
	 * @dataProvider providerSubmitButton
	 *
	 * @param string $label
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testSubmitButton($label, $htmlOptions, $assertion)
	{
		$this->assertEquals($assertion, CHtml::submitButton($label, $htmlOptions));
	}

	public function providerResetButton()
	{
		return [
			['reset', [], '<input type="reset" name="yt0" value="reset" />'],
			['reset1', ['type'=>'button'], '<input type="reset" name="yt0" value="reset1" />'],
		];
	}

	/**
	 * @dataProvider providerResetButton
	 *
	 * @param string $label
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testResetButton($label, $htmlOptions, $assertion)
	{
		$this->assertEquals($assertion, CHtml::resetButton($label, $htmlOptions));
	}

	public function providerImageButton()
	{
		return [
			['/images/test-image.png', ['src'=>'ignored-src'], '<input src="/images/test-image.png" type="image" name="yt0" />'],
			['/images/test-image.jpg', ['type'=>'button'], '<input type="image" src="/images/test-image.jpg" name="yt0" />'],
			['/images/test-image.gif', ['value'=>'image'], '<input value="image" src="/images/test-image.gif" type="image" name="yt0" />'],
		];
	}

	/**
	 * @dataProvider providerImageButton
	 *
	 * @param string $src
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testImageButton($label, $htmlOptions, $assertion)
	{
		$this->assertEquals($assertion, CHtml::imageButton($label, $htmlOptions));
	}

	public function providerLinkButton()
	{
		return [
			['submit', [], '<a href="#" id="yt0">submit</a>',
				"jQuery('body').on('click','#yt0',function(){jQuery.yii.submitForm(this,'',{});return false;});"],
			['link-button', [], '<a href="#" id="yt0">link-button</a>',
				"jQuery('body').on('click','#yt0',function(){jQuery.yii.submitForm(this,'',{});return false;});"],
			['link-button', ['href'=>'https://yiiframework.com/'], '<a href="#" id="yt0">link-button</a>',
				"jQuery('body').on('click','#yt0',function(){jQuery.yii.submitForm(this,'https\\x3A\\x2F\\x2Fyiiframework.com\\x2F',{});return false;});"],
		];
	}

	/**
	 * @dataProvider providerLinkButton
	 *
	 * @param string $label
	 * @param array $htmlOptions
	 * @param string $assertion
	 * @param string $clientScriptOutput
	 */
	public function testLinkButton($label, $htmlOptions, $assertion, $clientScriptOutput)
	{
		$this->assertEquals($assertion, CHtml::linkButton($label, $htmlOptions));

		$output='';
		Yii::app()->getClientScript()->renderBodyEnd($output);
		$this->assertContains($clientScriptOutput, $output);
	}

	public function testAjaxCallbacks()
	{
		$out=CHtml::ajax([
			'success'=>'js:function() { /* callback */ }',
		]);
		$this->assertTrue(mb_strpos($out,"'success':function() { /* callback */ }", 0, Yii::app()->charset)!==false, "Unexpected JavaScript: ".$out);

		$out=CHtml::ajax([
			'success'=>'function() { /* callback */ }',
		]);
		$this->assertTrue(mb_strpos($out,"'success':function() { /* callback */ }", 0, Yii::app()->charset)!==false, "Unexpected JavaScript: ".$out);

		$out=CHtml::ajax([
			'success'=>new CJavaScriptExpression('function() { /* callback */ }'),
		]);
		$this->assertTrue(mb_strpos($out,"'success':function() { /* callback */ }", 0, Yii::app()->charset)!==false, "Unexpected JavaScript: ".$out);
	}
}

/* Helper classes */

class CHtmlTestModel extends CModel
{
	private static $_names=[];

	/**
	 * @property mixed $attr1
	 */
	public $attr1;

	/**
	 * @property mixed $attr2
	 */
	public $attr2;

	/**
	 * @property mixed $attr3
	 */
	public $attr3;

	/**
	 * @property mixed $attr4
	 */
	public $attr4;

	/**
	 * Returns the list of attribute names.
	 * @return array list of attribute names. Defaults to all public properties of the class.
	 */
	public function attributeNames()
	{
		$className=get_class($this);
		if(!isset(self::$_names[$className]))
		{
			$class=new ReflectionClass(get_class($this));
			$names=[];
			foreach($class->getProperties() as $property)
			{
				$name=$property->getName();
				if($property->isPublic() && !$property->isStatic())
					$names[]=$name;
			}
			return self::$_names[$className]=$names;
		}
		else
			return self::$_names[$className];
	}

}

class CHtmlTestFormModel extends CFormModel
{
	public $stringAttr;
	public $arrayAttr;

	public function afterConstruct()
	{
		$this->stringAttr='stringAttrValue';
		$this->arrayAttr=[
			'k1'=>'v1',
			'k2'=>'v2',
			'k3'=>[
				'k4'=>'v4',
				'k5'=>'v5',
			],
			'k6'=>'v6',
		];
	}
}

class CHtmlTestActiveModel extends CFormModel
{
	public $userName;
	public $firstName;

	public function rules()
	{
		return [
			['userName', 'required'],
			['firstName', 'safe'],
		];
	}
}
