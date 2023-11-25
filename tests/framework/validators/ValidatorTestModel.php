<?php
class ValidatorTestModel extends CFormModel
{
	public $string1;
	public $string2;
	public $string3;

	public $email;

	public $url;

	public $number;

	public $username;
	public $address;

	public $uploaded_file;

	public function rules()
	{
		return [
			['string1', 'length', 'min'=>10, 'tooShort'=>'Too short message.', 'allowEmpty'=>false,
				'on'=>'CStringValidatorTest'],
			['string2', 'length', 'max'=>10, 'tooLong'=>'Too long message.', 'allowEmpty'=>false,
				'on'=>'CStringValidatorTest'],
			['string3', 'length', 'is'=>10, 'message'=>'Error message.', 'allowEmpty'=>false,
				'on'=>'CStringValidatorTest'],

			['email', 'email', 'allowEmpty'=>false, 'on'=>'CEmailValidatorTest'],

			['url', 'url', 'allowEmpty'=>false, 'on'=>'CUrlValidatorTest'],

			['number', 'numerical', 'min'=>5, 'max'=>15, 'integerOnly'=>true, 'on'=>'CNumberValidatorTest'],

			['username', 'required', 'trim' => false, 'on' => 'CRequiredValidatorTest'],
			['address', 'required', 'on' => 'CRequiredValidatorTest'],

			['string1', 'in', 'allowEmpty' => false, 'range' => [0,1,7,13], 'on' => 'CRangeValidatorTest'],
			['string2', 'in', 'allowEmpty' => false, 'range' => ['',1,7,13], 'on' => 'CRangeValidatorTest'],
			['string3', 'in', 'allowEmpty' => false, 'range' => [1], 'on' => 'CRangeValidatorTest', 'strict' => false],

			['uploaded_file', 'file', 'on' => 'CFileValidatorTest'],
		];
	}
}
