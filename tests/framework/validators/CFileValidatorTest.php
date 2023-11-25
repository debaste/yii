<?php
require_once('ValidatorTestModel.php');

class CFileValidatorTest extends CTestCase
{
	public function providerSizeToBytes()
	{
		return [
			['100M', 100*1024*1024],
			['100,5M', 100*1024*1024],
			['150m', 150*1024*1024],
			['150.5m', 150*1024*1024],
			['500K', 500*1024],
			['540.5K', 540*1024],
			['70k', 70*1024],
			['70,2k', 70*1024],
			['1G', 1*1024*1024*1024],
			['1.5g', 1*1024*1024*1024],
			['2g', 2*1024*1024*1024],
			['1.2G', 1*1024*1024*1024],
			['100500', 100500],
			['9000', 9000],
			[null, 0],
			['', 0],
		];
	}

	/**
	 * @dataProvider providerSizeToBytes
	 *
	 * @param string $sizeString
	 * @param integer $assertion
	 */
	public function testSizeToBytes($sizeString, $assertion)
	{
		$fileValidator=new CFileValidator();
		$this->assertSame($assertion, $fileValidator->sizeToBytes((string)$sizeString));
	}

	public function testValidate()
	{
		$model = new ValidatorTestModel(__CLASS__);
		$uploadedFile = new CUploadedFile('test.txt', __FILE__, 'text/plain', 40, UPLOAD_ERR_OK);
		$model->uploaded_file = $uploadedFile;
		$this->assertTrue($model->validate(), 'Valid file validation failed!');
	}

	public function testValidateNoFile()
	{
		$model = new ValidatorTestModel(__CLASS__);
		$uploadedFile = new CUploadedFile('test.txt', __FILE__, 'text/plain', 40, UPLOAD_ERR_NO_FILE);
		$model->uploaded_file = $uploadedFile;
		$this->assertFalse($model->validate(), 'File with error passed validation!');
	}
}
