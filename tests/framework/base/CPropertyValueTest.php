<?php

class TextAlign extends CEnumerable
{
	const Left='Left';
	const Right='Right';
}

class CPropertyValueTest extends CTestCase
{
	public function testEnsureBoolean()
	{
		$entries=
		[
			[true,true],
			[false,false],
			[null,false],
			[0,false],
			[1,true],
			[-1,true],
			[2.1,true],
			['',false],
			['abc',false],
			['0',false],
			['1',true],
			['123',true],
			['false',false],
			['true',true],
			['tRue',true],
			[[],false],
			[[0],true],
			[[1],true],
		];
		foreach($entries as $index=>$entry)
			$this->assertTrue(CPropertyValue::ensureBoolean($entry[0])===$entry[1],
				"Comparison $index: {$this->varToString($entry[0])}=={$this->varToString($entry[1])}");
	}

	public function testEnsureString()
	{
		$entries=
		[
			['',''],
			['abc','abc'],
			[null,''],
			[0,'0'],
			[1,'1'],
			[-1.1,'-1.1'],
			[true,'true'],
			[false,'false'],
		];

		if(version_compare(PHP_VERSION, '5.4.0', '<'))
		{
			$entries = array_merge($entries, [
				[[],'Array'],
				[[0],'Array'],
			]);
		}

		foreach($entries as $index=>$entry)
			$this->assertTrue(CPropertyValue::ensureString($entry[0])===$entry[1],
				"Comparison $index: {$this->varToString($entry[0])}=={$this->varToString($entry[1])}");
	}

	public function testEnsureInteger()
	{
		$entries=
		[
			[123,123],
			[1.23,1],
			[null,0],
			['',0],
			['abc',0],
			['123',123],
			['1.23',1],
			[' 1.23',1],
			[' 1.23abc',1],
			['abc1.23abc',0],
			[true,1],
			[false,0],
			[[],0],
			[[0],1],
		];
		foreach($entries as $index=>$entry)
			$this->assertTrue(CPropertyValue::ensureInteger($entry[0])===$entry[1],
				"Comparison $index: {$this->varToString($entry[0])}=={$this->varToString($entry[1])}");
	}

	public function testEnsureFloat()
	{
		$entries=
		[
			[123,123.0],
			[1.23,1.23],
			[null,0.0],
			['',0.0],
			['abc',0.0],
			['123',123.0],
			['1.23',1.23],
			[' 1.23',1.23],
			[' 1.23abc',1.23],
			['abc1.23abc',0.0],
			[true,1.0],
			[false,0.0],
			[[],0.0],
			[[0],1.0],
		];
		foreach($entries as $index=>$entry)
			$this->assertTrue(CPropertyValue::ensureFloat($entry[0])===$entry[1],
				"Comparison $index: {$this->varToString($entry[0])}=={$this->varToString($entry[1])}");
	}

	public function testEnsureArray()
	{
		$entries=
		[
			[123,[123]],
			[null,[]],
			['',[]],
			['abc',['abc']],
			['(1,2)',[1,2]],
			['("key"=>"value",2=>3)',["key"=>"value",2=>3]],
			[true,[true]],
			[[],[]],
			[[0],[0]],
		];
		foreach($entries as $index=>$entry)
			$this->assertTrue(CPropertyValue::ensureArray($entry[0])===$entry[1],
				"Comparison $index: {$this->varToString($entry[0])}=={$this->varToString($entry[1])}");
	}

	private function varToString($var)
	{
		if(is_array($var))
			return 'Array';
		return (string)$var;
	}

	public function testEnsureObject()
	{
		$obj=new stdClass;
		$this->assertTrue(CPropertyValue::ensureObject($obj)===$obj);
	}

	public function testEnsureEnum()
	{
		$this->assertTrue(CPropertyValue::ensureEnum('Left','TextAlign')==='Left');
		$this->setExpectedException('CException');
		$this->assertTrue(CPropertyValue::ensureEnum('left','TextAlign')==='Left');
	}
}
