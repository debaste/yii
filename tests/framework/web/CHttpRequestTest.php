<?php

class CHttpRequestTest extends CTestCase
{
	/**
	 * @covers CHttpRequest::parseAcceptHeader
	 * @dataProvider acceptHeaderDataProvider
	 */
	public function testParseAcceptHeader($header,$result,$errorString='Parse of header did not give expected result')
	{
		$this->assertEquals($result,CHttpRequest::parseAcceptHeader($header),$errorString);
	}

	/**
	 * @covers CHttpRequest::compareAcceptTypes
	 * @dataProvider acceptContentTypeArrayMapDataProvider
	 */
	public function testCompareAcceptTypes($a,$b,$result,$errorString='Compare of content type array maps did not give expected preference')
	{
		$this->assertEquals($result,CHttpRequest::compareAcceptTypes($a,$b),$errorString);
		// make sure that inverse comparison holds
		$this->assertEquals($result*-1,CHttpRequest::compareAcceptTypes($b,$a),'(Inverse) '.$errorString);
	}

	public function acceptHeaderDataProvider()
	{
		return [
			// null header
			[
				null,
				[],
				'Parsing null Accept header did not return empty array',
			],
			// empty header
			[
				'',
				[],
				'Parsing empty Accept header did not return empty array',
			],
			// nonsense header, containing no valid accept types (but containing the characters that the header is split on)
			[
				'gsf,\'yas\'erys"rt;,";s,y s;,',
				[],
				'Parsing completely invalid Accept header did not return empty array',
			],
			// valid header containing only content types
			[
				'application/xhtml+xml,text/html,*/json,image/png',
				[
					[
						'type'=>'application',
						'subType'=>'xhtml',
						'baseType'=>'xml',
						'params'=>[
							'q'=>1,
						],
					],
					[
						'type'=>'text',
						'subType'=>'html',
						'baseType'=>null,
						'params'=>[
							'q'=>1,
						],
					],
					[
						'type'=>'*',
						'subType'=>'json',
						'baseType'=>null,
						'params'=>[
							'q'=>1,
						],
					],
					[
						'type'=>'image',
						'subType'=>'png',
						'baseType'=>null,
						'params'=>[
							'q'=>1,
						],
					],
				],
				'Parsing valid Accept header containing only content types did not return correct result',
			],
			// valid header containing all details
			[
				'application/xhtml+xml;q=0.9,text/html,*/json;q=4;level=three,image/png;a=1;b=2;c=3',
				[
					[
						'type'=>'application',
						'subType'=>'xhtml',
						'baseType'=>'xml',
						'params'=>[
							'q'=>0.9,
						],
					],
					[
						'type'=>'text',
						'subType'=>'html',
						'baseType'=>null,
						'params'=>[
							'q'=>1,
						],
					],
					[
						'type'=>'*',
						'subType'=>'json',
						'baseType'=>null,
						'params'=>[
							'q'=>1,
							'level'=>'three',
						],
					],
					[
						'type'=>'image',
						'subType'=>'png',
						'baseType'=>null,
						'params'=>[
							'q'=>1,
							'a'=>1,
							'b'=>2,
							'c'=>3,
						],
					],
				],
				'Parsing valid Accept header containing all details did not return correct result',
			],
			// partially valid header containing all details (no , after */json)
			[
				'application/xhtml+xml;q=0.9,text/html,*/json;q=4;level=three image/png;a=1;b=2;c=3',
				[
					[
						'type'=>'application',
						'subType'=>'xhtml',
						'baseType'=>'xml',
						'params'=>[
							'q'=>0.9,
						],
					],
					[
						'type'=>'text',
						'subType'=>'html',
						'baseType'=>null,
						'params'=>[
							'q'=>1,
						],
					],
					[
						'type'=>'*',
						'subType'=>'json',
						'baseType'=>null,
						'params'=>[
							'q'=>1,
							'level'=>'three',
						],
					],
				],
				'Parsing partially valid Accept header containing all details did not return correct result',
			],
		];
	}

	public function acceptContentTypeArrayMapDataProvider()
	{
		return [
			[
				[
					'type'=>'application',
					'subType'=>'xhtml',
					'baseType'=>'xml',
					'params'=>[
						'q'=>0.99,
					],
				],
				[
					'type'=>'text',
					'subType'=>'html',
					'baseType'=>null,
					'params'=>[
						'q'=>(double)1,
					],
				],
				1,
				'Comparing different q did not assign correct preference',
			],
			[
				[
					'type'=>'application',
					'subType'=>'xhtml',
					'baseType'=>'xml',
					'params'=>[
						'q'=>0.5,
					],
				],
				[
					'type'=>'*',
					'subType'=>'html',
					'baseType'=>null,
					'params'=>[
						'q'=>0.5,
					],
				],
				-1,
				'Comparing type wildcard with specific type did not assign correct preference',
			],
			[
				[
					'type'=>'application',
					'subType'=>'*',
					'baseType'=>'xml',
					'params'=>[
						'q'=>0.5,
					],
				],
				[
					'type'=>'text',
					'subType'=>'html',
					'baseType'=>null,
					'params'=>[
						'q'=>0.5,
					],
				],
				1,
				'Comparing subType wildcard with specific subType did not assign correct preference',
			],
			[
				[
					'type'=>'*',
					'subType'=>'xhtml',
					'baseType'=>'xml',
					'params'=>[
						'q'=>0.9,
						'foo'=>'bar2',
					],
				],
				[
					'type'=>'*',
					'subType'=>'html',
					'baseType'=>null,
					'params'=>[
						'q'=>0.9,
						'foo'=>'bar',
						'test'=>'drive',
					],
				],
				1,
				'Comparing different number of params did not assign correct preference',
			],
			[
				[
					'type'=>'*',
					'subType'=>'xhtml',
					'baseType'=>'xml',
					'params'=>[
						'q'=>0.9,
						'foo'=>'bar',
					],
				],
				[
					'type'=>'*',
					'subType'=>'html',
					'baseType'=>null,
					'params'=>[
						'q'=>0.9,
						'foo'=>'bar',
					],
				],
				0,
				'Comparing equal type, subType, q and number of params did not return equality',
			],
		];
	}
}