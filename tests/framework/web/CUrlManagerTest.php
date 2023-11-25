<?php

Yii::import('system.web.CUrlManager');

class CUrlManagerTest extends CTestCase
{
	public function testParseUrlWithPathFormat()
	{
		$rules=[
			'article/<id:\d+>'=>'article/read',
			'article/<year:\d{4}>/<title>/*'=>'article/read',
			'a/<_a>/*'=>'article',
			'register/*'=>'user',
			'home/*'=>'',
			'ad/*'=>'admin/index/list',
			'<c:(post|comment)>/<id:\d+>/<a:(create|update|delete)>'=>'<c>/<a>',
			'<c:(post|comment)>/<id:\d+>'=>'<c>/view',
			'<c:(post|comment)>s/*'=>'<c>/list',
			'http://<user:\w+>.example.com/<lang:\w+>/profile'=>'user/profile',
			'currency/<c:\p{Sc}>'=>'currency/info',
			'url*with+special.symbols'=>'controller1/action',
			'<name:\w+>.<ext:\w+>'=>'controller2/action',
			'<name:\w+>*<ext:\w+>'=>'controller3/action',
		];
		$entries=[
			[
				'pathInfo'=>'article/123',
				'route'=>'article/read',
				'params'=>['id'=>'123'],
			],
			[
				'pathInfo'=>'article/123/name/value',
				'route'=>'article/123/name/value',
				'params'=>[],
			],
			[
				'pathInfo'=>'article/2000/title goes here',
				'route'=>'article/read',
				'params'=>['year'=>'2000','title'=>'title goes here'],
			],
			[
				'pathInfo'=>'article/2000/title goes here/name/value',
				'route'=>'article/read',
				'params'=>['year'=>'2000','title'=>'title goes here','name'=>'value'],
			],
			[
				'pathInfo'=>'register/username/admin',
				'route'=>'user',
				'params'=>['username'=>'admin'],
			],
			[
				'pathInfo'=>'home/name/value/name1/value1',
				'route'=>'',
				'params'=>['name'=>'value','name1'=>'value1'],
			],
			[
				'pathInfo'=>'home2/name/value/name1/value1',
				'route'=>'home2/name/value/name1/value1',
				'params'=>[],
			],
			[
				'pathInfo'=>'post',
				'route'=>'post',
				'params'=>[],
			],
			[
				'pathInfo'=>'post/read',
				'route'=>'post/read',
				'params'=>[],
			],
			[
				'pathInfo'=>'post/read/id/100',
				'route'=>'post/read/id/100',
				'params'=>[],
			],
			[
				'pathInfo'=>'',
				'route'=>'',
				'params'=>[],
			],
			[
				'pathInfo'=>'ad/name/value',
				'route'=>'admin/index/list',
				'params'=>['name'=>'value'],
			],
			[
				'pathInfo'=>'admin/name/value',
				'route'=>'admin/name/value',
				'params'=>[],
			],
			[
				'pathInfo'=>'posts',
				'route'=>'post/list',
				'params'=>[],
			],
			[
				'pathInfo'=>'posts/page/3',
				'route'=>'post/list',
				'params'=>['page'=>3],
			],
			[
				'pathInfo'=>'post/3',
				'route'=>'post/view',
				'params'=>['id'=>3],
			],
			[
				'pathInfo'=>'post/3/delete',
				'route'=>'post/delete',
				'params'=>['id'=>3],
			],
			[
				'pathInfo'=>'post/3/delete/a',
				'route'=>'post/3/delete/a',
				'params'=>[],
			],
			[
				'pathInfo'=>'en/profile',
				'route'=>'user/profile',
				'params'=>['user'=>'admin','lang'=>'en'],
			],
			[
				'pathInfo'=>'currency/＄',
				'route'=>'currency/info',
				'params'=>['c'=>'＄'],
			],
			[
				'pathInfo'=>'url*with+special.symbols',
				'route'=>'controller1/action',
				'params'=>[],
			],
			[
				'pathInfo'=>'picture.jpg',
				'route'=>'controller2/action',
				'params'=>['name'=>'picture','ext'=>'jpg'],
			],
			[
				'pathInfo'=>'urlwithoutadot',
				'route'=>'urlwithoutadot',
				'params'=>[],
			],
			[
				'pathInfo'=>'picture*jpg',
				'route'=>'controller3/action',
				'params'=>['name'=>'picture','ext'=>'jpg'],
			],
		];
		$config=[
			'basePath'=>dirname(__FILE__),
			'components'=>[
				'request'=>[
					'class'=>'TestHttpRequest',
					'scriptUrl'=>'/app/index.php',
				],
			],
		];
		$app=new TestApplication($config);
		$app->controllerPath=dirname(__FILE__).DIRECTORY_SEPARATOR.'controllers';
		$request=$app->request;
		$_SERVER['HTTP_HOST']='admin.example.com';
		$um=new CUrlManager;
		$um->urlSuffix='.html';
		$um->urlFormat='path';
		$um->rules=$rules;
		$um->init($app);
		foreach($entries as $entry)
		{
			$request->pathInfo=$entry['pathInfo'];
			$_GET=[];
			$route=$um->parseUrl($request);
			$this->assertEquals($entry['route'],$route);
			$this->assertEquals($entry['params'],$_GET);
			// test the .html version
			$request->pathInfo=$entry['pathInfo'].'.html';
			$_GET=[];
			$route=$um->parseUrl($request);
			$this->assertEquals($entry['route'],$route);
			$this->assertEquals($entry['params'],$_GET);
		}
	}

	public function testcreateUrlWithPathFormat()
	{
		$rules=[
			'<name:\w+>.<ext:\w+>'=>'controller2/action',
			'article/<id:\d+>'=>'article/read',
			'article/<year:\d{4}>/<title>/*'=>'article/read',
			'a/<_a>/*'=>'article',
			'register/*'=>'user',
			'home/*'=>'',
			'<c:(post|comment)>/<id:\d+>/<a:(create|update|delete)>'=>'<c>/<a>',
			'<c:(post|comment)>/<id:\d+>'=>'<c>/view',
			'<c:(post|comment)>s/*'=>'<c>/list',
			'http://<user:\w+>.example.com/<lang:\w+>/profile'=>'user/profile',
			'currency/<c:\p{Sc}>'=>'currency/info',
			'url*with+special.symbols'=>'controller1/action',
			'<name:\w+>*<ext:\w+>'=>'controller3/action',
		];
		$config=[
			'basePath'=>dirname(__FILE__),
			'components'=>[
				'request'=>[
					'class'=>'TestHttpRequest',
				],
			],
		];
		$_SERVER['HTTP_HOST']='user.example.com';
		$app=new TestApplication($config);
		$entries=[
			[
				'scriptUrl'=>'/apps/index.php',
				'url'=>'/apps/index.php/post/123?name1=value1',
				'url2'=>'/apps/post/123?name1=value1',
				'url3'=>'/apps/post/123.html?name1=value1',
				'route'=>'post/view',
				'params'=>[
					'id'=>'123',
					'name1'=>'value1',
				],
			],
			[
				'scriptUrl'=>'/apps/index.php',
				'url'=>'/apps/index.php/post/123/update?name1=value1',
				'url2'=>'/apps/post/123/update?name1=value1',
				'url3'=>'/apps/post/123/update.html?name1=value1',
				'route'=>'post/update',
				'params'=>[
					'id'=>'123',
					'name1'=>'value1',
				],
			],
			[
				'scriptUrl'=>'/apps/index.php',
				'url'=>'/apps/index.php/posts/page/123',
				'url2'=>'/apps/posts/page/123',
				'url3'=>'/apps/posts/page/123.html',
				'route'=>'post/list',
				'params'=>[
					'page'=>'123',
				],
			],
			[
				'scriptUrl'=>'/apps/index.php',
				'url'=>'/apps/index.php/article/123?name1=value1',
				'url2'=>'/apps/article/123?name1=value1',
				'url3'=>'/apps/article/123.html?name1=value1',
				'route'=>'article/read',
				'params'=>[
					'id'=>'123',
					'name1'=>'value1',
				],
			],
			[
				'scriptUrl'=>'/index.php',
				'url'=>'/index.php/article/123?name1=value1',
				'url2'=>'/article/123?name1=value1',
				'url3'=>'/article/123.html?name1=value1',
				'route'=>'article/read',
				'params'=>[
					'id'=>'123',
					'name1'=>'value1',
				],
			],
			[
				'scriptUrl'=>'/apps/index.php',
				'url'=>'/apps/index.php/article/2000/the_title/name1/value1',
				'url2'=>'/apps/article/2000/the_title/name1/value1',
				'url3'=>'/apps/article/2000/the_title/name1/value1.html',
				'route'=>'article/read',
				'params'=>[
					'year'=>'2000',
					'title'=>'the_title',
					'name1'=>'value1',
				],
			],
			[
				'scriptUrl'=>'/index.php',
				'url'=>'/index.php/article/2000/the_title/name1/value1',
				'url2'=>'/article/2000/the_title/name1/value1',
				'url3'=>'/article/2000/the_title/name1/value1.html',
				'route'=>'article/read',
				'params'=>[
					'year'=>'2000',
					'title'=>'the_title',
					'name1'=>'value1',
				],
			],
			[
				'scriptUrl'=>'/apps/index.php',
				'url'=>'/apps/index.php/post/edit/id/123/name1/value1',
				'url2'=>'/apps/post/edit/id/123/name1/value1',
				'url3'=>'/apps/post/edit/id/123/name1/value1.html',
				'route'=>'post/edit',
				'params'=>[
					'id'=>'123',
					'name1'=>'value1',
				],
			],
			[
				'scriptUrl'=>'/index.php',
				'url'=>'http://admin.example.com/en/profile',
				'url2'=>'http://admin.example.com/en/profile',
				'url3'=>'http://admin.example.com/en/profile.html',
				'route'=>'user/profile',
				'params'=>[
					'user'=>'admin',
					'lang'=>'en',
				],
			],
			[
				'scriptUrl'=>'/index.php',
				'url'=>'/en/profile',
				'url2'=>'/en/profile',
				'url3'=>'/en/profile.html',
				'route'=>'user/profile',
				'params'=>[
					'user'=>'user',
					'lang'=>'en',
				],
			],
			[
				'scriptUrl'=>'/index.php',
				'url'=>'/index.php/currency/%EF%BC%84',
				'url2'=>'/currency/%EF%BC%84',
				'url3'=>'/currency/%EF%BC%84.html',
				'route'=>'currency/info',
				'params'=>[
					'c'=>'＄',
				],
			],
			[
				'scriptUrl'=>'/index.php',
				'route'=>'controller1/action',
				'params'=>[],
				'url'=>'/index.php/url*with+special.symbols',
				'url2'=>'/url*with+special.symbols',
				'url3'=>'/url*with+special.symbols.html',
			],
			[
				'scriptUrl'=>'/index.php',
				'route'=>'controller2/action',
				'params'=>['name'=>'picture','ext'=>'jpg'],
				'url'=>'/index.php/picture.jpg',
				'url2'=>'/picture.jpg',
				'url3'=>'/picture.jpg.html',
			],
			[
				'scriptUrl'=>'/index.php',
				'route'=>'controller3/action',
				'params'=>['name'=>'picture','ext'=>'jpg'],
				'url'=>'/index.php/picture*jpg',
				'url2'=>'/picture*jpg',
				'url3'=>'/picture*jpg.html',
			],
		];
		foreach($entries as $entry)
		{
			$app->request->baseUrl=null; // reset so that it can be determined based on scriptUrl
			$app->request->scriptUrl=$entry['scriptUrl'];
			for($matchValue=0;$matchValue<2;$matchValue++)
			{
				$um=new CUrlManager;
				$um->urlFormat='path';
				$um->rules=$rules;
				$um->matchValue=$matchValue!=0;
				$um->init($app);
				$url=$um->createUrl($entry['route'],$entry['params']);
				$this->assertEquals($entry['url'],$url,'matchValue='.($um->matchValue ? 'true' : 'false'));

				$um=new CUrlManager;
				$um->urlFormat='path';
				$um->rules=$rules;
				$um->matchValue=$matchValue!=0;
				$um->init($app);
				$um->showScriptName=false;
				$url=$um->createUrl($entry['route'],$entry['params']);
				$this->assertEquals($entry['url2'],$url,'matchValue='.($um->matchValue ? 'true' : 'false'));

				$um->urlSuffix='.html';
				$url=$um->createUrl($entry['route'],$entry['params']);
				$this->assertEquals($entry['url3'],$url,'matchValue='.($um->matchValue ? 'true' : 'false'));
			}
		}
	}

	public function testParseUrlWithGetFormat()
	{
		$config=[
			'basePath'=>dirname(__FILE__),
			'components'=>[
				'request'=>[
					'class'=>'TestHttpRequest',
					'scriptUrl'=>'/app/index.php',
				],
			],
		];
		$entries=[
			[
				'route'=>'article/read',
				'name'=>'value',
			],
		];
		$app=new TestApplication($config);
		$request=$app->request;
		$um=new CUrlManager;
		$um->urlFormat='get';
		$um->routeVar='route';
		$um->init($app);
		foreach($entries as $entry)
		{
			$_GET=$entry;
			$route=$um->parseUrl($request);
			$this->assertEquals($entry['route'],$route);
			$this->assertEquals($_GET,$entry);
		}
	}

	public function testCreateUrlWithGetFormat()
	{
		$config=[
			'basePath'=>dirname(__FILE__),
			'components'=>[
				'request'=>[
					'class'=>'TestHttpRequest',
				],
			],
		];
		$app=new TestApplication($config);
		$entries=[
			[
				'scriptUrl'=>'/apps/index.php',
				'url'=>'/apps/index.php?route=article/read&name=value&name1=value1',
				'url2'=>'/apps/?route=article/read&name=value&name1=value1',
				'route'=>'article/read',
				'params'=>[
					'name'=>'value',
					'name1'=>'value1',
				],
			],
			[
				'scriptUrl'=>'/index.php',
				'url'=>'/index.php?route=article/read&name=value&name1=value1',
				'url2'=>'/?route=article/read&name=value&name1=value1',
				'route'=>'article/read',
				'params'=>[
					'name'=>'value',
					'name1'=>'value1',
				],
			],
		];
		foreach($entries as $entry)
		{
			$app->request->baseUrl=null;
			$app->request->scriptUrl=$entry['scriptUrl'];
			$um=new CUrlManager;
			$um->urlFormat='get';
			$um->routeVar='route';
			$um->init($app);
			$url=$um->createUrl($entry['route'],$entry['params'],'&');
			$this->assertEquals($url,$entry['url']);

			$um=new CUrlManager;
			$um->urlFormat='get';
			$um->routeVar='route';
			$um->showScriptName=false;
			$um->init($app);
			$url=$um->createUrl($entry['route'],$entry['params'],'&');
			$this->assertEquals($url,$entry['url2']);
		}
	}

	public function testDefaultParams()
	{
		$config=[
			'basePath'=>dirname(__FILE__),
			'components'=>[
				'request'=>[
					'class'=>'TestHttpRequest',
				],
			],
		];
		$app=new TestApplication($config);

		$app->request->baseUrl=null; // reset so that it can be determined based on scriptUrl
		$app->request->scriptUrl='/apps/index.php';
		$um=new CUrlManager;
		$um->urlFormat='path';
		$um->rules=[
			''=>['site/page', 'defaultParams'=>['view'=>'about']],
			'posts'=>['post/index', 'defaultParams'=>['page'=>1]],
			'<slug:[0-9a-z-]+>' => ['news/list', 'defaultParams' => ['page' => 1]],
		];
		$um->init($app);

		$url=$um->createUrl('site/page',['view'=>'about']);
		$this->assertEquals('/apps/index.php/',$url);
		$app->request->pathInfo='';
		$_GET=[];
		$route=$um->parseUrl($app->request);
		$this->assertEquals('site/page',$route);
		$this->assertEquals(['view'=>'about'],$_GET);

		$url=$um->createUrl('post/index',['page'=>1]);
		$this->assertEquals('/apps/index.php/posts',$url);
		$app->request->pathInfo='posts';
		$_GET=[];
		$route=$um->parseUrl($app->request);
		$this->assertEquals('post/index',$route);
		$this->assertEquals(['page'=>'1'],$_GET);

		$url=$um->createUrl('news/list', ['slug' => 'example', 'page' => 1]);
		$this->assertEquals('/apps/index.php/example',$url);
		$app->request->pathInfo='example';
		$_GET=[];
		$route=$um->parseUrl($app->request);
		$this->assertEquals('news/list',$route);
		$this->assertEquals(['slug'=>'example', 'page'=>'1'],$_GET);
	}

	public function testVerb()
	{
		$config=[
			'basePath'=>dirname(__FILE__),
			'components'=>[
				'request'=>[
					'class'=>'TestHttpRequest',
				],
			],
		];
		$rules=[
			'article/<id:\d+>'=>['article/read', 'verb'=>'GET'],
			'article/update/<id:\d+>'=>['article/update', 'verb'=>'POST'],
			'article/update/*'=>'article/admin',
		];

		$entries=[
			[
				'scriptUrl'=>'/apps/index.php',
				'url'=>'article/123',
				'verb'=>'GET',
				'route'=>'article/read',
			],
			[
				'scriptUrl'=>'/apps/index.php',
				'url'=>'article/update/123',
				'verb'=>'POST',
				'route'=>'article/update',
			],
			[
				'scriptUrl'=>'/apps/index.php',
				'url'=>'article/update/123',
				'verb'=>'GET',
				'route'=>'article/admin',
			],
		];

		foreach($entries as $entry)
		{
			$_SERVER['REQUEST_METHOD']=$entry['verb'];
			$app=new TestApplication($config);
			$app->request->baseUrl=null; // reset so that it can be determined based on scriptUrl
			$app->request->scriptUrl=$entry['scriptUrl'];
			$app->request->pathInfo=$entry['url'];
			$um=new CUrlManager;
			$um->urlFormat='path';
			$um->rules=$rules;
			$um->init($app);
			$route=$um->parseUrl($app->request);
			$this->assertEquals($entry['route'],$route);
		}
	}

	public function testParsingOnly()
	{
		$config=[
			'basePath'=>dirname(__FILE__),
			'components'=>[
				'request'=>[
					'class'=>'TestHttpRequest',
				],
			],
		];
		$rules=[
			'(articles|article)/<id:\d+>'=>['article/read', 'parsingOnly'=>true],
			'article/<id:\d+>'=>['article/read', 'verb'=>'GET'],
		];

		$_SERVER['REQUEST_METHOD']='GET';
		$app=new TestApplication($config);
		$app->request->baseUrl=null; // reset so that it can be determined based on scriptUrl
		$app->request->scriptUrl='/apps/index.php';
		$app->request->pathInfo='articles/123';
		$um=new CUrlManager;
		$um->urlFormat='path';
		$um->rules=$rules;
		$um->init($app);

		$route=$um->parseUrl($app->request);
		$this->assertEquals('article/read',$route);

		$url=$um->createUrl('article/read', ['id'=>345]);
		$this->assertEquals('/apps/index.php/article/345',$url);
	}
}
