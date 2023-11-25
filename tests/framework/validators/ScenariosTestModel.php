<?php

class ScenariosTestModel extends CFormModel
{
	public $title;
	public $firstName;
	public $lastName;
	public $patronymic;
	public $nickName;

	public $login;
	public $password;

	public $birthday;

	public function rules()
	{
		return [
			// scenario1
			['title', 'required', 'on'=>'scenario1'],

			// scenario1 and scenario2
			['firstName', 'required', 'except'=>'scenario3, scenario4'],

			// scenario1, scenario2 and scenario3
			['lastName', 'required', 'on'=>['scenario1', 'scenario2', 'scenario3']],

			// scenario1, scenario2 and scenario3
			['patronymic', 'required', 'except'=>['scenario4']],

			// scenario1 and scenario3
			['nickName', 'required', 'on'=>['scenario1', 'scenario2', 'scenario3'], 'except'=>'scenario2'],

			// scenario1, scenario2, scenario3 and scenario4
			['login', 'required'],

			// useless rule
			['password', 'required', 'on'=>'scenario1,scenario2,scenario3,scenario4',
				'except'=>['scenario1', 'scenario2', 'scenario3', 'scenario4']],

			// scenario2
			['birthday', 'required', 'on'=>'scenario2', 'except'=>'scenario3'],
		];
	}
}
