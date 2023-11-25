<?php

Yii::import('system.collections.CStack');

class CStackTest extends CTestCase
{
	public function testConstruct()
	{
		$stack = new CStack();
		$this->assertEquals([], $stack->toArray());
		$stack = new CStack([1, 2, 3]);
		$this->assertEquals([1, 2, 3], $stack->toArray());
	}

	public function testToArray()
	{
		$stack = new CStack([1, 2, 3]);
		$this->assertEquals([1, 2, 3], $stack->toArray());
	}

	public function testCopyFrom()
	{
		$stack = new CStack([1, 2, 3]);
		$data = [4, 5, 6];
		$stack->copyFrom($data);
		$this->assertEquals([4, 5, 6], $stack->toArray());
	}

	public function testCanNotCopyFromNonTraversableTypes()
	{
		$stack = new CStack();
		$data = new stdClass();
		$this->setExpectedException('CException');
		$stack->copyFrom($data);
	}

	public function testClear()
	{
		$stack = new CStack([1, 2, 3]);
		$stack->clear();
		$this->assertEquals([], $stack->toArray());
	}

	public function testContains()
	{
		$stack = new CStack([1, 2, 3]);
		$this->assertTrue($stack->contains(2));
		$this->assertFalse($stack->contains(4));
	}

	public function testPeek()
	{
		$stack = new CStack([1]);
		$this->assertEquals(1, $stack->peek());
	}

	public function testCanNotPeekAnEmptyStack()
	{
		$stack = new CStack();
		$this->setExpectedException('CException');
		$item = $stack->peek();
	}

	public function testPop()
	{
		$stack = new CStack([1, 2, 3]);
		$last = $stack->pop();
		$this->assertEquals(3, $last);
		$this->assertEquals([1, 2], $stack->toArray());
	}

	public function testCanNotPopAnEmptyStack()
	{
		$stack = new CStack();
		$this->setExpectedException('CException');
		$item = $stack->pop();
	}

	public function testPush()
	{
		$stack = new CStack();
		$stack->push(1);
		$this->assertEquals([1], $stack->toArray());
	}

 	public function testGetIterator()
 	{
		$stack = new CStack([1, 2]);
		$this->assertInstanceOf('CStackIterator', $stack->getIterator());
		$n = 0;
		$found = 0;
		foreach($stack as $index => $item)
		{
			foreach($stack as $a => $b); // test of iterator
			$n++;
			if($index === 0 && $item === 1)
				$found++;
			if($index === 1 && $item === 2)
				$found++;
		}
		$this->assertTrue($n == 2 && $found == 2);
	}

	public function testGetCount()
	{
		$stack = new CStack();
		$this->assertEquals(0, $stack->getCount());
		$stack = new CStack([1, 2, 3]);
		$this->assertEquals(3, $stack->getCount());
	}

	public function testCount()
	{
		$stack = new CStack();
		$this->assertEquals(0, count($stack));
		$stack = new CStack([1, 2, 3]);
		$this->assertEquals(3, count($stack));
	}
}
