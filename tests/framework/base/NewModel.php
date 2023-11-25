<?php

class NewModel extends CModel
{
	public $attr1;
	public $attr2;
	public $attr3;
	public $attr4;
	public $departement_name;
	public $firstName;
	public $LastName;

	public function rules()
	{
		return [
			['attr2,attr1','numerical','max'=>5],
			['attr1','required'],
			['attr3', 'unsafe'],
		];
	}

	public function attributeNames()
	{
		return ['attr1','attr2'];
	}

}

class InvalidModel extends CModel
{
	public $username;

	public function rules()
	{
		return [
			['username'],
		];
	}

	public function attributeNames()
	{
		return ['username'];
	}
}
