<?php

Yii::import('system.web.CUrlManager');

class CUrlRuleTest extends CTestCase
{
	private $app;

	protected function setUp(): void
	{
		$config=[
			'basePath'=>dirname(__FILE__),
			'components'=>[
				'request'=>[
					'class'=>'TestHttpRequest',
				],
			],
		];
		$_SERVER['HTTP_HOST']='user.example.com';
		$this->app=new TestApplication($config);
	}

	public function testParseUrlMatchValue()
	{
		$rules=[
			[
				'route'=>'article/read',
				'pattern'=>'article/<id:\d+>',
				'scriptUrl'=>'/apps/index.php',
				'entries'=>[
					[
						'route'=>'article/read',
						'params'=>[
							'id'=>'123',
							'name1'=>'value1',
						],
						'url'=>'article/123?name1=value1',
					],
					[
						'route'=>'article/read',
						'params'=>[
							'id'=>'abc',
							'name1'=>'value1',
						],
						'url'=>false,
					],
					[
						'route'=>'article/read',
						'params'=>[
							'id'=>"123\n",
							'name1'=>'value1',
						],
						'url'=>false,
					],
					[
						'route'=>'article/read',
						'params'=>[
							'id'=>'0x1',
							'name1'=>'value1',
						],
						'url'=>false,
					],
				],
			],
		];
		$um=new CUrlManager;
		foreach($rules as $rule)
		{
			$this->app->request->baseUrl=null; // reset so that it can be determined based on scriptUrl
			$this->app->request->scriptUrl=$rule['scriptUrl'];
			$ur=new CUrlRule($rule['route'],$rule['pattern']);
			$ur->matchValue=true;
			foreach($rule['entries'] as $entry)
			{
				$url=$ur->createUrl($um,$entry['route'],$entry['params'],'&');
				$this->assertEquals($entry['url'],$url);
			}
		}
	}
}
