<?php

Yii::import('system.db.CDbConnection');
Yii::import('system.db.schema.mysql.CMysqlSchema');


class CMysqlTest extends CTestCase
{
	private $db;

	protected function setUp(): void
	{
		if(!extension_loaded('pdo') || !extension_loaded('pdo_mysql'))
			$this->markTestSkipped('PDO and MySQL extensions are required.');

		$this->db=new CDbConnection('mysql:host=127.0.0.1;dbname=yii','test','test');
		$this->db->charset='UTF8';
		try
		{
			$this->db->active=true;
		}
		catch(Exception $e)
		{
			$schemaFile=realpath(dirname(__FILE__).'/../data/mysql.sql');
			$this->markTestSkipped("Please read $schemaFile for details on setting up the test environment for MySQL test case.");
		}

		$tables=['comments','post_category','posts','categories','profiles','users','items','orders','types'];
		foreach($tables as $table)
			$this->db->createCommand("DROP TABLE IF EXISTS $table CASCADE")->execute();

		$sqls=file_get_contents(dirname(__FILE__).'/../data/mysql.sql');
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
		$this->assertEquals('`posts`',$schema->quoteTableName('posts'));
		$this->assertEquals('`id`',$schema->quoteColumnName('id'));
		$this->assertTrue($schema->getTable('posts') instanceof CDbTableSchema);
		$this->assertNull($schema->getTable('foo'));
	}

	public function testTable()
	{
		$table=$this->db->schema->getTable('posts');
		$this->assertTrue($table instanceof CDbTableSchema);
		$this->assertEquals('posts',$table->name);
		$this->assertEquals('`posts`',$table->rawName);
		$this->assertEquals('id',$table->primaryKey);
		$this->assertEquals(['author_id'=>['users','id']],$table->foreignKeys);
		$this->assertEquals('',$table->sequenceName);
		$this->assertEquals(5,count($table->columns));

		$this->assertTrue($table->getColumn('id') instanceof CDbColumnSchema);
		$this->assertTrue($table->getColumn('foo')===null);
		$this->assertEquals(['id','title','create_time','author_id','content'],$table->columnNames);

		$table=$this->db->schema->getTable('orders');
		$this->assertEquals(['key1','key2'],$table->primaryKey);

		$table=$this->db->schema->getTable('items');
		$this->assertEquals('id',$table->primaryKey);
		$this->assertEquals(['col1'=>['orders','key1'],'col2'=>['orders','key2']],$table->foreignKeys);

		$table=$this->db->schema->getTable('types');
		$this->assertTrue($table instanceof CDbTableSchema);
		$this->assertEquals('types',$table->name);
		$this->assertEquals('`types`',$table->rawName);
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
			'rawName'=>['`id`', '`title`', '`create_time`', '`author_id`', '`content`'],
			'defaultValue'=>[null, null, null, null, null],
			'size'=>[11, 128, null, 11, null],
			'precision'=>[11, 128, null, 11, null],
			'scale'=>[null, null, null, null, null],
			'dbType'=>['int(11)','varchar(128)','timestamp','int(11)','text'],
			'type'=>['integer','string','string','integer','string'],
			'isPrimaryKey'=>[true,false,false,false,false],
			'isForeignKey'=>[false,false,false,true,false],
		];
		$this->checkColumns('posts',$values);
		$values=
		[
			'name'=>['int_col', 'int_col2', 'char_col', 'char_col2', 'char_col3', 'float_col', 'float_col2', 'blob_col', 'numeric_col', 'time', 'bool_col', 'bool_col2', 'bit_col1', 'bit_col2'],
			'rawName'=>['`int_col`', '`int_col2`', '`char_col`', '`char_col2`', '`char_col3`', '`float_col`', '`float_col2`', '`blob_col`', '`numeric_col`', '`time`', '`bool_col`', '`bool_col2`', '`bit_col1`', '`bit_col2`'],
			'defaultValue'=>[null, 1, null, 'something', null, null, '1.23', null, '33.22', '2002-01-01 00:00:00', null, 1, null, 42],
			'size'=>[11, 11, 100, 100, null, 4, null, null, 5, null, 1, 1, 1, 32],
			'precision'=>[11, 11, 100, 100, null, 4, null, null, 5, null, 1, 1, 1, 32],
			'scale'=>[null, null, null, null, null, 3, null, null, 2, null, null, null, null, null],
			'dbType'=>['int(11)','int(11)','char(100)','varchar(100)','text','double(4,3)','double','blob','decimal(5,2)','timestamp','tinyint(1)','tinyint(1)','bit(1)','bit(32)'],
			'type'=>['integer','integer','string','string','string','double','double','string','string','string','integer','integer','integer','integer'],
			'isPrimaryKey'=>[false,false,false,false,false,false,false,false,false,false,false,false,false,false],
			'isForeignKey'=>[false,false,false,false,false,false,false,false,false,false,false,false,false,false],
		];
		$this->checkColumns('types',$values);
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
		$table=$schema->getTable('posts');

		$c=$builder->createInsertCommand($table,['title'=>'test post','create_time'=>'2000-01-01','author_id'=>1,'content'=>'test content']);
		$this->assertEquals('INSERT INTO `posts` (`title`, `create_time`, `author_id`, `content`) VALUES (:yp0, :yp1, :yp2, :yp3)',$c->text);
		$c->execute();
		$this->assertEquals(6,$builder->getLastInsertId($table));

		$c=$builder->createCountCommand($table,new CDbCriteria);
		$this->assertEquals('SELECT COUNT(*) FROM `posts` `t`',$c->text);
		$this->assertEquals(6,$c->queryScalar());

		$c=$builder->createDeleteCommand($table,new CDbCriteria([
			'condition'=>'id=:id',
			'params'=>['id'=>6]]));
		$this->assertEquals('DELETE FROM `posts` WHERE id=:id',$c->text);
		$c->execute();
		$c=$builder->createCountCommand($table,new CDbCriteria);
		$this->assertEquals(5,$c->queryScalar());
 
		// test for delete with joins
		$c=$builder->createInsertCommand($table,['title'=>'new post delete','create_time'=>'2000-01-01','author_id'=>1,'content'=>'test content']);
		$c->execute();
		$c=$builder->createDeleteCommand($table,new CDbCriteria([
				'condition'=>'u.`username`=:username and `posts`.`title`=:title',
				'join'=>'JOIN `users` u ON `author_id`=u.`id`',
				'params'=>[':username'=>'user1', ':title'=>'new post delete']]));
        $this->assertEquals('DELETE `posts` FROM `posts` JOIN `users` u ON `author_id`=u.`id` WHERE u.`username`=:username and `posts`.`title`=:title',$c->text);
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
		$this->assertEquals('SELECT id, title FROM `posts` `t` WHERE id=:id ORDER BY title LIMIT 2',$c->text);
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
		
		$c=$builder->createSqlCommand('SELECT title FROM posts WHERE id=:id',[':id'=>3]);
		$this->assertEquals('post 3',$c->queryScalar());

		$c=$builder->createUpdateCounterCommand($table,['author_id'=>-1],new CDbCriteria(['condition'=>'id=5']));
		$this->assertEquals('UPDATE `posts` SET `author_id`=`author_id`-1 WHERE id=5',$c->text);
		$c->execute();
		$c=$builder->createSqlCommand('SELECT author_id FROM posts WHERE id=5');
		$this->assertEquals(2,$c->queryScalar());

		// test for updates with joins
		$c=$builder->createUpdateCommand($table,['title'=>'new post 1'],new CDbCriteria([
				'condition'=>'u.`username`=:username',
				'join'=>'JOIN `users` u ON `author_id`=u.`id`',
				'params'=>[':username'=>'user1']]));
		$c->execute();
		$c=$builder->createFindCommand($table,new CDbCriteria([
				'select'=>'title',
				'condition'=>'id=:id',
				'params'=>['id'=>1]]));
		$this->assertEquals('new post 1',$c->queryScalar());
		
		$c=$builder->createUpdateCounterCommand($table,['author_id'=>-1],new CDbCriteria([
				'condition'=>'u.`username`="user2"',
				'join'=>'JOIN `users` u ON `author_id`=u.`id`']));
		$this->assertEquals('UPDATE `posts` JOIN `users` u ON `author_id`=u.`id` SET `author_id`=`author_id`-1 WHERE u.`username`="user2"',$c->text);
		$c->execute();
		$c=$builder->createSqlCommand('SELECT author_id FROM posts WHERE id=2');
		$this->assertEquals(1,$c->queryScalar());
		
		// test bind by position
		$c=$builder->createFindCommand($table,new CDbCriteria([
			'select'=>'title',
			'condition'=>'id=?',
			'params'=>[4]]));
		$this->assertEquals('SELECT title FROM `posts` `t` WHERE id=?',$c->text);
		$this->assertEquals('post 4',$c->queryScalar());

		// another bind by position
		$c=$builder->createUpdateCommand($table,['title'=>'new post 4'],new CDbCriteria([
			'condition'=>'id=?',
			'params'=>[4]]));
		$c->execute();
		$c=$builder->createSqlCommand('SELECT title FROM posts WHERE id=4');
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
		$this->assertEquals('`posts`.`id`=1 AND (author_id>1)',$c->condition);

		$c=$builder->createPkCriteria($table,[1,2]);
		$this->assertEquals('`posts`.`id` IN (1, 2)',$c->condition);

		$c=$builder->createPkCriteria($table,[]);
		$this->assertEquals('0=1',$c->condition);

		$table2=$schema->getTable('orders');
		$c=$builder->createPkCriteria($table2,['key1'=>1,'key2'=>2],'name=``');
		$this->assertEquals('`orders`.`key1`=1 AND `orders`.`key2`=2 AND (name=``)',$c->condition);

		$c=$builder->createPkCriteria($table2,[['key1'=>1,'key2'=>2],['key1'=>3,'key2'=>4]]);
		$this->assertEquals('(`orders`.`key1`, `orders`.`key2`) IN ((1, 2), (3, 4))',$c->condition);

		// createColumnCriteria
		$c=$builder->createColumnCriteria($table,['id'=>1,'author_id'=>2],'title=``');
		$this->assertEquals('`posts`.`id`=:yp0 AND `posts`.`author_id`=:yp1 AND (title=``)',$c->condition);

		$c=$builder->createPkCriteria($table2,[]);
		$this->assertEquals('0=1',$c->condition);
	}

	public function testResetSequence()
	{
		$max=$this->db->createCommand("SELECT MAX(id) FROM users")->queryScalar();
		$this->db->createCommand("DELETE FROM users")->execute();
		$this->db->createCommand("INSERT INTO users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
		$max2=$this->db->createCommand("SELECT MAX(id) FROM users")->queryScalar();
		$this->assertEquals($max+1,$max2);

		$userTable=$this->db->schema->getTable('users');

		$this->db->createCommand("DELETE FROM users")->execute();
		$this->db->schema->resetSequence($userTable);return;
		$this->db->createCommand("INSERT INTO users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
		$max=$this->db->createCommand("SELECT MAX(id) FROM users")->queryScalar();
		$this->assertEquals(1,$max);
		$this->db->createCommand("INSERT INTO users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
		$max=$this->db->createCommand("SELECT MAX(id) FROM users")->queryScalar();
		$this->assertEquals(2,$max);

		$this->db->createCommand("DELETE FROM users")->execute();
		$this->db->schema->resetSequence($userTable,10);
		$this->db->createCommand("INSERT INTO users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
		$max=$this->db->createCommand("SELECT MAX(id) FROM users")->queryScalar();
		$this->assertEquals(10,$max);
		$this->db->createCommand("INSERT INTO users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
		$max=$this->db->createCommand("SELECT MAX(id) FROM users")->queryScalar();
		$this->assertEquals(11,$max);
	}

	public function testColumnComments()
	{
		$usersColumns=$this->db->schema->tables['users']->columns;

		$this->assertEquals('',$usersColumns['id']->comment);
		$this->assertEquals('Name of the user',$usersColumns['username']->comment);
		$this->assertEquals('Hashed password',$usersColumns['password']->comment);
		$this->assertEquals('',$usersColumns['email']->comment);
	}
}