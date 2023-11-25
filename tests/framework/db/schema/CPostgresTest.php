<?php

Yii::import('system.db.CDbConnection');

class CPostgresTest extends CTestCase
{
	private $db;

	protected function setUp(): void
	{
		if(!extension_loaded('pdo') || !extension_loaded('pdo_pgsql'))
			$this->markTestSkipped('PDO and PostgreSQL extensions are required.');

		$this->db=new CDbConnection('pgsql:host=127.0.0.1;dbname=yii','test','test');
		$this->db->charset='UTF8';
		try
		{
			$this->db->active=true;
		}
		catch(Exception $e)
		{
			$schemaFile=realpath(dirname(__FILE__).'/../data/postgres.sql');
			$this->markTestSkipped("Please read $schemaFile for details on setting up the test environment for PostgreSQL test case.");
		}

		try	{ $this->db->createCommand('DROP SCHEMA test CASCADE')->execute(); } catch(Exception $e) { }
		try	{ $this->db->createCommand('DROP TABLE yii_types CASCADE')->execute(); } catch(Exception $e) { }

		$sqls=file_get_contents(dirname(__FILE__).'/../data/postgres.sql');
		foreach(explode(';',$sqls) as $sql)
		{
			if(trim($sql)!=='')
				$this->db->createCommand($sql)->execute();
		}
	}

	protected function tearDown(): void
	{
		$this->db->active=false;
	}

	public function testSchema()
	{
		$schema=$this->db->schema;
		$this->assertTrue($schema instanceof CDbSchema);
		$this->assertEquals($schema->dbConnection,$this->db);
		$this->assertTrue($schema->commandBuilder instanceof CDbCommandBuilder);
		$this->assertEquals('"posts"',$schema->quoteTableName('posts'));
		$this->assertEquals('"id"',$schema->quoteColumnName('id'));
		$this->assertTrue($schema->getTable('test.posts') instanceof CDbTableSchema);
		$this->assertTrue($schema->getTable('foo')===null);
	}

	public function testTable()
	{
		$table=$this->db->schema->getTable('test.posts');
		$this->assertTrue($table instanceof CDbTableSchema);
		$this->assertEquals('posts',$table->name);
		$this->assertEquals('"test"."posts"',$table->rawName);
		$this->assertEquals('id',$table->primaryKey);
		$this->assertEquals(['author_id'=>['users','id']],$table->foreignKeys);
		$this->assertEquals('test.posts_id_seq',$table->sequenceName);
		$this->assertEquals(5,count($table->columns));

		$this->assertTrue($table->getColumn('id') instanceof CDbColumnSchema);
		$this->assertTrue($table->getColumn('foo')===null);
		$this->assertEquals(['id','title','create_time','author_id','content'],$table->columnNames);

		$table=$this->db->schema->getTable('test.orders');
		$this->assertEquals(['key1','key2'],$table->primaryKey);

		$table=$this->db->schema->getTable('test.items');
		$this->assertEquals('id',$table->primaryKey);
		$this->assertEquals(['col1'=>['orders','key1'],'col2'=>['orders','key2']],$table->foreignKeys);

		$table=$this->db->schema->getTable('yii_types');
		$this->assertTrue($table instanceof CDbTableSchema);
		$this->assertEquals('yii_types',$table->name);
		$this->assertEquals('"yii_types"',$table->rawName);
		$this->assertTrue($table->primaryKey===null);
		$this->assertTrue($table->foreignKeys===[]);
		$this->assertTrue($table->sequenceName===null);

		$table=$this->db->schema->getTable('invalid');
		$this->assertNull($table);
	}

	public function testColumn()
	{
		$values=
		[
			'name'=>['id', 'title', 'create_time', 'author_id', 'content'],
			'rawName'=>['"id"', '"title"', '"create_time"', '"author_id"', '"content"'],
			'defaultValue'=>[null, null, null, null, null],
			'size'=>[null, 128, null, null, null],
			'precision'=>[null, 128, null, null, null],
			'scale'=>[null, null, null, null, null],
			'type'=>['integer','string','string','integer','string'],
			'isPrimaryKey'=>[true,false,false,false,false],
			'isForeignKey'=>[false,false,false,true,false],
		];
		$this->checkColumns('test.posts',$values);
		$values=
		[
			'name'=>['int_col', 'int_col2', 'char_col', 'char_col2', 'char_col3', 'numeric_col', 'real_col', 'blob_col', 'time', 'time2', 'bool_col', 'bool_col2'],
			'rawName'=>['"int_col"', '"int_col2"', '"char_col"', '"char_col2"', '"char_col3"', '"numeric_col"', '"real_col"', '"blob_col"', '"time"', '"time2"', '"bool_col"', '"bool_col2"'],
			'defaultValue'=>[null, 1, null, 'something', null, null, '1.23', null, null, null, null, true],
			'size'=>[null, null, 100, 100, null, 4, null, null, null, null, null, null],
			'precision'=>[null, null, 100, 100, null, 4, null, null, null, 4, null, null],
			'scale'=>[null, null, null, null, null, 3, null, null, null, null, null, null],
			'type'=>['integer','integer','string','string','string','string','double','string','string','string','boolean','boolean'],
			'isPrimaryKey'=>[false,false,false,false,false,false,false,false,false,false,false,false],
			'isForeignKey'=>[false,false,false,false,false,false,false,false,false,false,false,false],
		];
		$this->checkColumns('yii_types',$values);
	}

	protected function checkColumns($tableName,$values)
	{
		$table=$this->db->schema->getTable($tableName);
		foreach($values as $name=>$value)
		{
			foreach(array_values($table->columns) as $i=>$column)
			{
				$type1=gettype($column->$name);
				$type2=gettype($value[$i]);
				$this->assertTrue($column->$name===$value[$i], "$tableName.{$column->name}.$name is {$column->$name} ($type1), different from the expected {$value[$i]} ($type2).");
			}
		}
	}

	public function testCommandBuilder()
	{
		$schema=$this->db->schema;
		$builder=$schema->commandBuilder;
		$this->assertTrue($builder instanceof CDbCommandBuilder);
		$table=$schema->getTable('test.posts');

		$c=$builder->createInsertCommand($table,['title'=>'test post','create_time'=>'2004-10-19 10:23:54','author_id'=>1,'content'=>'test content']);
		$this->assertEquals('INSERT INTO "test"."posts" ("title", "create_time", "author_id", "content") VALUES (:yp0, :yp1, :yp2, :yp3)',$c->text);
		$c->execute();
		$this->assertEquals(6,$builder->getLastInsertId($table));

		$c=$builder->createCountCommand($table,new CDbCriteria);
		$this->assertEquals('SELECT COUNT(*) FROM "test"."posts" "t"',$c->text);
		$this->assertEquals(6,$c->queryScalar());

		$c=$builder->createDeleteCommand($table,new CDbCriteria([
			'condition'=>'id=:id',
			'params'=>['id'=>6]]));
		$this->assertEquals('DELETE FROM "test"."posts" WHERE id=:id',$c->text);
		$c->execute();
		$c=$builder->createCountCommand($table,new CDbCriteria);
		$this->assertEquals(5,$c->queryScalar());

		$c=$builder->createFindCommand($table,new CDbCriteria([
			'select'=>'id, title',
			'condition'=>'id=:id',
			'params'=>['id'=>5],
			'order'=>'title',
			'limit'=>2,
			'offset'=>0]));
		$this->assertEquals('SELECT id, title FROM "test"."posts" "t" WHERE id=:id ORDER BY title LIMIT 2',$c->text);
		$rows=$c->query()->readAll();
		$this->assertEquals(1,count($rows));
		$this->assertEquals('post 5',$rows[0]['title']);

		$c=$builder->createUpdateCommand($table,['title'=>'new post 5'],new CDbCriteria([
			'condition'=>'id=:id',
			'params'=>['id'=>5]]));
		$c->execute();
		$c=$builder->createFindCommand($table,new CDbCriteria([
			'select'=>'title',
			'condition'=>'id=:id',
			'params'=>['id'=>5]]));
		$this->assertEquals('new post 5',$c->queryScalar());

		$c=$builder->createSqlCommand('SELECT title FROM test.posts WHERE id=:id',[':id'=>3]);
		$this->assertEquals('post 3',$c->queryScalar());

		$c=$builder->createUpdateCounterCommand($table,['author_id'=>-2],new CDbCriteria(['condition'=>'id=5']));
		$this->assertEquals('UPDATE "test"."posts" SET "author_id"="author_id"-2 WHERE id=5',$c->text);
		$c->execute();
		$c=$builder->createSqlCommand('SELECT author_id FROM posts WHERE id=5');
		$this->assertEquals(1,$c->queryScalar());

		// test bind by position
		$c=$builder->createFindCommand($table,new CDbCriteria([
			'select'=>'title',
			'condition'=>'id=?',
			'params'=>[4]]));
		$this->assertEquals('SELECT title FROM "test"."posts" "t" WHERE id=?',$c->text);
		$this->assertEquals('post 4',$c->queryScalar());

		// another bind by position
		$c=$builder->createUpdateCommand($table,['title'=>'new post 4'],new CDbCriteria([
			'condition'=>'id=?',
			'params'=>[4]]));
		$c->execute();
		$c=$builder->createSqlCommand('SELECT title FROM test.posts WHERE id=4');
		$this->assertEquals('new post 4',$c->queryScalar());

		// testCreateCriteria
		$c=$builder->createCriteria('column=:value',[':value'=>'value']);
		$this->assertEquals('column=:value',$c->condition);
		$this->assertEquals([':value'=>'value'],$c->params);

		$c=$builder->createCriteria(['condition'=>'column=:value','params'=>[':value'=>'value']]);
		$this->assertEquals('column=:value',$c->condition);
		$this->assertEquals([':value'=>'value'],$c->params);

		$c2=$builder->createCriteria($c);
		$this->assertTrue($c2!==$c);
		$this->assertEquals('column=:value',$c2->condition);
		$this->assertEquals([':value'=>'value'],$c2->params);

		// testCreatePkCriteria
		$c=$builder->createPkCriteria($table,1,'author_id>1');
		$this->assertEquals('"test"."posts"."id"=1 AND (author_id>1)',$c->condition);

		$c=$builder->createPkCriteria($table,[1,2]);
		$this->assertEquals('"test"."posts"."id" IN (1, 2)',$c->condition);

		$table2=$schema->getTable('test.orders');
		$c=$builder->createPkCriteria($table2,['key1'=>1,'key2'=>2],'name=\'\'');
		$this->assertEquals('"test"."orders"."key1"=1 AND "test"."orders"."key2"=2 AND (name=\'\')',$c->condition);

		$c=$builder->createPkCriteria($table2,[['key1'=>1,'key2'=>2],['key1'=>3,'key2'=>4]]);
		$this->assertEquals('("test"."orders"."key1", "test"."orders"."key2") IN ((1, 2), (3, 4))',$c->condition);

		// createColumnCriteria
		$c=$builder->createColumnCriteria($table,['id'=>1,'author_id'=>2],'title=\'\'');
		$this->assertEquals('"test"."posts"."id"=:yp0 AND "test"."posts"."author_id"=:yp1 AND (title=\'\')',$c->condition);
	}

	public function testResetSequence()
	{
		$max=$this->db->createCommand("SELECT MAX(id) FROM test.users")->queryScalar();
		$this->db->createCommand("DELETE FROM test.users")->execute();
		$this->db->createCommand("INSERT INTO test.users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
		$max2=$this->db->createCommand("SELECT MAX(id) FROM test.users")->queryScalar();
		$this->assertEquals($max+1,$max2);

		$userTable=$this->db->schema->getTable('test.users');

		$this->db->createCommand("DELETE FROM test.users")->execute();
		$this->db->schema->resetSequence($userTable);
		$this->db->createCommand("INSERT INTO test.users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
		$max=$this->db->createCommand("SELECT MAX(id) FROM test.users")->queryScalar();
		$this->assertEquals(1,$max);
		$this->db->createCommand("INSERT INTO test.users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
		$max=$this->db->createCommand("SELECT MAX(id) FROM test.users")->queryScalar();
		$this->assertEquals(2,$max);

		$this->db->createCommand("DELETE FROM test.users")->execute();
		$this->db->schema->resetSequence($userTable,10);
		$this->db->createCommand("INSERT INTO test.users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
		$max=$this->db->createCommand("SELECT MAX(id) FROM test.users")->queryScalar();
		$this->assertEquals(10,$max);
		$this->db->createCommand("INSERT INTO test.users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
		$max=$this->db->createCommand("SELECT MAX(id) FROM test.users")->queryScalar();
		$this->assertEquals(11,$max);
	}

	public function testColumnComments()
	{
		$usersColumns=$this->db->schema->getTable('test.users')->columns;

		$this->assertEquals('',$usersColumns['id']->comment);
		$this->assertEquals('Name of the user',$usersColumns['username']->comment);
		$this->assertEquals('Hashed password',$usersColumns['password']->comment);
		$this->assertEquals('',$usersColumns['email']->comment);
	}
}
