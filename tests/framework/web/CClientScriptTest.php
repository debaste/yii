<?php

Yii::import('system.web.CClientScript');

/**
 *  @group web
 */
class CClientScriptTest extends CTestCase
{
	/**
	 * @var CClientScript
	 */
	private $_clientScript;
	
	protected function setUp(): void
	{
		$this->_clientScript = new CClientScript();
		$this->_clientScript->setCoreScriptUrl("assets/12345");
		$this->_clientScript->registerCoreScript('jquery');
		$this->_clientScript->registerCoreScript('yii');
	}
	
	/* Test Script Getters */
	
	public function testGetCoreScriptUrl()
	{
		$this->assertEquals('assets/12345', $this->_clientScript->getCoreScriptUrl());
	}
	
	
	public function providerGetPackageBaseUrl()
	{
		return [
			['jquery', 'assets/12345'],
			['yii', 'assets/12345']
		];
	}	
	
	/**
	 * @dataProvider providerGetPackageBaseUrl
	 * 
	 * @param string $name
	 * @param string $assertion 
	 */
	public function testGetPackageBaseUrl($name, $assertion)
	{
		$this->assertEquals($assertion,$this->_clientScript->getPackageBaseUrl($name));
	}
	
	/* Test Script Registers */
	
	public function providerCoreScripts()
	{
		return [
			['jquery', ['js'=>['jquery.js']]],
			['yiitab', ['js'=>['jquery.yiitab.js'], 'depends'=>['jquery']]],
			['yiiactiveform', ['js'=>['jquery.yiiactiveform.js'], 'depends'=>['jquery']]]

		];
	}
	/**
	 * @dataProvider providerCoreScripts
	 * 
	 * @param string $name
	 * @param array $assertion 
	 */
	public function testRegisterCoreScript($name, $assertion)
	{
		$returnedClientScript = $this->_clientScript->registerCoreScript($name);
		$this->assertEquals($assertion, $returnedClientScript->corePackages[$name]);
	}
	
	/**
	 * @dataProvider providerCoreScripts
	 * 
	 * @param string $name
	 * @param array $assertion 
	 */
	public function testRegisterPackage($name, $assertion)
	{
		$returnedClientScript = $this->_clientScript->registerPackage($name);
		$this->assertEquals($assertion, $returnedClientScript->corePackages[$name]);
	}

	public function providerScriptFiles()
	{
		return [
			['/some/script.js', CClientScript::POS_HEAD, '/some/script.js'],
			['http://some/script.js', CClientScript::POS_BEGIN, 'http://some/script.js'],
			['/some/script.js', CClientScript::POS_END, '/some/script.js'],
		];
	}

	public function testHasPackage()
    {
        $this->assertTrue($this->_clientScript->hasPackage('jquery'));
        $this->assertFalse($this->_clientScript->hasPackage('nonexisting'));
    }

	/**
	 * @dataProvider providerScriptFiles
	 *
	 * @param string $url
	 * @param integer $position
	 * @param string $assertion
	 */
	public function testRegisterScriptFile($url, $position, $assertion)
	{
		$returnedClientScript = $this->_clientScript->registerScriptFile($url, $position);
		$scriptFiles = $this->readAttribute($returnedClientScript, 'scriptFiles');
		$this->assertEquals($assertion, $scriptFiles[$position][$url]);
	}

	public function providerScriptFilesWithHtmlOptions()
	{
		return [
			[
				'/some/script.js',
				CClientScript::POS_HEAD,
				['defer'=>true],
				[
					'src'=>'/some/script.js',
					'defer'=>true
				]
			],
		];
	}

	/**
	 * @dataProvider providerScriptFilesWithHtmlOptions
	 *
	 * @param string $url
	 * @param integer $position
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testRegisterScriptFileWithHtmlOptions($url, $position, $htmlOptions, $assertion)
	{
		$returnedClientScript = $this->_clientScript->registerScriptFile($url, $position, $htmlOptions);
		$scriptFiles = $this->readAttribute($returnedClientScript, 'scriptFiles');
		$this->assertEquals($assertion, $scriptFiles[$position][$url]);
	}

	public function providerScripts()
	{
		return [
			['jsId', "function() {alert('alert')}", CClientScript::POS_HEAD, "function() {alert('alert')}"],
			['jsId', "function() {alert('alert')}", CClientScript::POS_BEGIN, "function() {alert('alert')}"],
		];
	}

	/**
	 * @dataProvider providerScripts
	 *
	 * @param string $id
	 * @param string $script
	 * @param integer $position
	 * @param string $assertion
	 */
	public function testRegisterScript($id, $script, $position, $assertion) {
		$returnedClientScript = $this->_clientScript->registerScript($id, $script, $position);
		$this->assertEquals($assertion, $returnedClientScript->scripts[$position][$id]);
	}

	public function providerScriptsWithHtmlOptions()
	{
		return [
			[
				'jsId',
				"function() {alert('alert')}",
				CClientScript::POS_HEAD,
				['defer'=>true],
				[
					'content'=>"function() {alert('alert')}",
					'defer'=>true,
				]
			],
		];
	}

	/**
	 * @dataProvider providerScriptsWithHtmlOptions
	 *
	 * @param string $id
	 * @param string $script
	 * @param integer $position
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testRegisterScriptWithHtmlOptions($id, $script, $position, $htmlOptions, $assertion) {
		$returnedClientScript = $this->_clientScript->registerScript($id, $script, $position, $htmlOptions);
		$this->assertEquals($assertion, $returnedClientScript->scripts[$position][$id]);
	}
	
	public function providerRegisterCss()
	{
		return [
			['myCssDiv', 'float:right;', '', ['myCssDiv'=>['float:right;', '']]],
			['myCssDiv', 'float:right;', 'screen', ['myCssDiv'=>['float:right;', 'screen']]]
		];
	}
	
	/**
	 * @dataProvider providerRegisterCss
	 * 
	 * @param string $id
	 * @param string $css
	 * @param string $media
	 * @param array $assertion 
	 */
	public function testRegisterCss($id, $css, $media, $assertion)
	{
		$returnedClientScript = $this->_clientScript->registerCss($id, $css, $media);
		$this->assertAttributeEquals($assertion, 'css', $returnedClientScript);
	}

	public function providerRegisterMetaTag()
	{
		$data = [];

		// Simple:
		$metaTagData = [
			'name'=>'testMetaTagName',
			'http-equiv'=>false,
			'content'=>'testMetaTagContent',
		];
		$assertion = [
			$metaTagData
		];
		$data[] = [$metaTagData['content'],$metaTagData['name'],$metaTagData['http-equiv'],[],$assertion];

		// Http Equiv:
		$metaTagData = [
			'name'=>'testMetaTagHttpEquiv',
			'http-equiv'=>true,
			'content'=>'testMetaTagHttpEquivContent',
		];
		$assertion = [
			$metaTagData
		];
		$data[] = [$metaTagData['content'],$metaTagData['name'],$metaTagData['http-equiv'],[],$assertion];

		return $data;
	}

	/**
	 * @dataProvider providerRegisterMetaTag
	 *
	 * @param string $content
	 * @param string $name
	 * @param boolean $httpEquiv
	 * @param array $options
	 * @param array $assertion
	 */
	public function testRegisterMetaTag($content,$name,$httpEquiv,$options,$assertion)
	{
		$returnedClientScript = $this->_clientScript->registerMetaTag($content,$name,$httpEquiv,$options);
		$this->assertAttributeEquals($assertion, 'metaTags', $returnedClientScript);
	}

	/**
	 * @depends testRegisterMetaTag
	 */
	public function testRegisterDuplicatingMetaTag() {
		$content='Test meta tag content';
		$name='test_meta_tag_name';
		$this->_clientScript->registerMetaTag($content,$name);
		$this->_clientScript->registerMetaTag($content,$name);

		$metaTagData=[
			'name'=>$name,
			'content'=>$content,
		];
		$assertion=[
			$metaTagData,
			$metaTagData
		];
		$this->assertAttributeEquals($assertion, 'metaTags', $this->_clientScript);
	}

	/* Test Script Renderers */
	
	public function providerRenderScriptFiles()
	{
		return [
			[
				'/some/script.js',
				CClientScript::POS_HEAD,
				[],
				'<script type="text/javascript" src="/some/script.js"></script>'
			],
			[
				'/some/script.js',
				CClientScript::POS_BEGIN,
				[],
				'<script type="text/javascript" src="/some/script.js"></script>'
			],
			[
				'/some/script.js',
				CClientScript::POS_END,
				[],
				'<script type="text/javascript" src="/some/script.js"></script>'
			],
			[
				'/options/script.js',
				CClientScript::POS_HEAD,
				['defer'=>true],
				'<script type="text/javascript" src="/options/script.js" defer="defer"></script>'
			],
			[
				'/options/script.js',
				CClientScript::POS_BEGIN,
				['defer'=>true],
				'<script type="text/javascript" src="/options/script.js" defer="defer"></script>'
			],
			[
				'/options/script.js',
				CClientScript::POS_END,
				['defer'=>true],
				'<script type="text/javascript" src="/options/script.js" defer="defer"></script>'
			],
		];
	}

	/**
	 * @depends testRegisterScriptFile
	 * @depends testRegisterScriptFileWithHtmlOptions
	 * 
	 * @dataProvider providerRenderScriptFiles
	 *
	 * @param string $url
	 * @param integer $position
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testRenderScriptFiles($url, $position, $htmlOptions, $assertion)
	{
		$returnedClientScript = $this->_clientScript->registerScriptFile($url, $position, $htmlOptions);
		$output = '<head></head>';
		$returnedClientScript->render($output);
		$this->assertContains($assertion, $output);
	}

	public function providerRenderScripts()
	{
		return [
			[
				'some_js_id',
				"function() {alert('script')}",
				CClientScript::POS_HEAD,
				[],
				CHtml::script("function() {alert('script')}")
			],
			[
				'some_js_id',
				"function() {alert('script')}",
				CClientScript::POS_BEGIN,
				[],
				CHtml::script("function() {alert('script')}")
			],
			[
				'some_js_id',
				"function() {alert('script')}",
				CClientScript::POS_END,
				[],
				CHtml::script("function() {alert('script')}")
			],
			[
				'some_js_id',
				"function() {alert('script')}",
				CClientScript::POS_LOAD,
				[],
				CHtml::script("function() {alert('script')}")
			],
			[
				'some_js_id',
				"function() {alert('script')}",
				CClientScript::POS_READY,
				[],
				CHtml::script("function() {alert('script')}")
			],
			// With HTML options
			[
				'option_js_id',
				"function() {alert('script')}",
				CClientScript::POS_HEAD,
				['defer'=>true],
				CHtml::script("function() {alert('script')}",['defer'=>true])
			],
			[
				'option_js_id',
				"function() {alert('script')}",
				CClientScript::POS_BEGIN,
				['defer'=>true],
				CHtml::script("function() {alert('script')}",['defer'=>true])
			],
			[
				'option_js_id',
				"function() {alert('script')}",
				CClientScript::POS_END,
				['defer'=>true],
				CHtml::script("function() {alert('script')}",['defer'=>true])
			],
		];
	}

	/**
	 * @depends testRegisterScript
	 *
	 * @dataProvider providerRenderScripts
	 *
	 * @param string $id
	 * @param string $script
	 * @param integer $position
	 * @param array $htmlOptions
	 * @param string $assertion
	 */
	public function testRenderScripts($id, $script, $position, $htmlOptions, $assertion)
	{
		$returnedClientScript = $this->_clientScript->registerScript($id, $script, $position, $htmlOptions);
		$output = '<head></head>';
		$returnedClientScript->render($output);
		$this->assertContains($assertion, $output);
	}

	public function providerRenderScriptsBatch()
	{
		return [
			[
				[
					[
						'id' => 'js_id_1',
						'script' => "function() {alert('script1')}",
						'position' => CClientScript::POS_HEAD,
						'htmlOptions' => [],
					],
					[
						'id' => 'js_id_2',
						'script' => "function() {alert('script2')}",
						'position' => CClientScript::POS_HEAD,
						'htmlOptions' => [],
					],
				],
				1
			],
			[
				[
					[
						'id' => 'js_id_1',
						'script' => "function() {alert('script1')}",
						'position' => CClientScript::POS_HEAD,
						'htmlOptions' => [],
					],
					[
						'id' => 'js_id_2',
						'script' => "function() {alert('script2')}",
						'position' => CClientScript::POS_HEAD,
						'htmlOptions' => [
							'defer' => true
						],
					],
				],
				2
			],
		];
	}

	/**
	 * @depends testRenderScripts
	 *
	 * @dataProvider providerRenderScriptsBatch
	 *
	 * @param array $scriptBatch
	 * @param integer $expectedScriptTagCount
	 *
	 * @see https://github.com/yiisoft/yii/issues/2770
	 */
	public function testRenderScriptsBatch(array $scriptBatch, $expectedScriptTagCount)
	{
		$this->_clientScript->reset();
		foreach($scriptBatch as $scriptParams)
			$this->_clientScript->registerScript($scriptParams['id'], $scriptParams['script'], $scriptParams['position'], $scriptParams['htmlOptions']);
		$output = '<head></head>';
		$this->_clientScript->render($output);
		$this->assertEquals($expectedScriptTagCount, substr_count($output, '<script'));
	}
}
