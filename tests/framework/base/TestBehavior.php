<?php

/**
 * Used in CBehaviorTest
 */
class TestBehavior extends CBehavior
 {
 	public function events()
 	{
 		return [
 			'onTestEvent' => 'handleTest',
 		];
 	}

 	public function handleTest($event)
 	{
 		if (!($event instanceof CEvent)) {
 			throw new CException('event has to be instance of CEvent');
 		}
 		$this->owner->behaviorEventHandled++;
 	}
 }