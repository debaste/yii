<?php

Yii::import('system.db.CDbConnection');
Yii::import('system.db.ar.CActiveRecord');

require_once(dirname(__FILE__).'/../data/models.php');
require_once(dirname(__FILE__).'/../data/models2.php');

class CActiveRecordTest extends CTestCase
{
	protected $backupStaticAttributes = true;

	private $_connection;

	protected function setUp(): void
	{
		if(!extension_loaded('pdo') || !extension_loaded('pdo_sqlite'))
			$this->markTestSkipped('PDO and SQLite extensions are required.');

		$this->_connection=new CDbConnection('sqlite::memory:');
		$this->_connection->active=true;
		$this->_connection->pdoInstance->exec(file_get_contents(dirname(__FILE__).'/../data/sqlite.sql'));
		CActiveRecord::$db=$this->_connection;
	}

	protected function tearDown(): void
	{
		$this->_connection->active=false;
	}

	public function testModel()
	{
		$model=Post::model();
		$this->assertTrue($model instanceof Post);
		$this->assertTrue($model->dbConnection===$this->_connection);
		$this->assertTrue($model->dbConnection->active);
		$this->assertEquals('posts',$model->tableName());
		$this->assertEquals('id',$model->tableSchema->primaryKey);
		$this->assertTrue($model->tableSchema->sequenceName==='');
		$this->assertEquals([],$model->attributeLabels());
		$this->assertEquals('Id',$model->getAttributeLabel('id'));
		$this->assertEquals('Author Id',$model->getAttributeLabel('author_id'));
		$this->assertTrue($model->getActiveRelation('author') instanceof CBelongsToRelation);
		$this->assertTrue($model->tableSchema instanceof CDbTableSchema);
		$this->assertTrue($model->commandBuilder instanceof CDbCommandBuilder);
		$this->assertTrue($model->hasAttribute('id'));
		$this->assertFalse($model->hasAttribute('comments'));
		$this->assertFalse($model->hasAttribute('foo'));
		$this->assertEquals(['id'=>null,'title'=>null,'create_time'=>null,'author_id'=>null,'content'=>null],$model->attributes);

		$post=new Post;
		$this->assertNull($post->id);
		$this->assertNull($post->title);
		$post->setAttributes(['id'=>3,'title'=>'test title']);
		$this->assertNull($post->id);
		$this->assertEquals('test title',$post->title);
	}

	public function testFind()
	{
		// test find() with various parameters
		$post=Post::model()->find();
		$this->assertTrue($post instanceof Post);
		$this->assertEquals(1,$post->id);

		$post=Post::model()->find('id=5');
		$this->assertTrue($post instanceof Post);
		$this->assertEquals(5,$post->id);

		$post=Post::model()->find('id=:id',[':id'=>2]);
		$this->assertTrue($post instanceof Post);
		$this->assertEquals(2,$post->id);

		$post=Post::model()->find(['condition'=>'id=:id','params'=>[':id'=>3]]);
		$this->assertTrue($post instanceof Post);
		$this->assertEquals(3,$post->id);

		// test find() without result
		$post=Post::model()->find('id=6');
		$this->assertNull($post);

		// test findAll() with various parameters
		$posts=Post::model()->findAll();
		$this->assertEquals(5,count($posts));
		$this->assertTrue($posts[3] instanceof Post);
		$this->assertEquals(4,$posts[3]->id);

		$posts=Post::model()->findAll(new CDbCriteria(['limit'=>3,'offset'=>1]));
		$this->assertEquals(3,count($posts));
		$this->assertTrue($posts[2] instanceof Post);
		$this->assertEquals(4,$posts[2]->id);

		// test findAll() without result
		$posts=Post::model()->findAll('id=6');
		$this->assertTrue($posts===[]);

		// test findByPk
		$post=Post::model()->findByPk(2);
		$this->assertEquals(2,$post->id);

		$post=Post::model()->findByPk([3,2]);
		$this->assertEquals(2,$post->id);

		$post=Post::model()->findByPk([]);
		$this->assertNull($post);

		$post=Post::model()->findByPk(null);
		$this->assertNull($post);

		$post=Post::model()->findByPk(6);
		$this->assertNull($post);

		// test findAllByPk
		$posts=Post::model()->findAllByPk(2);
		$this->assertEquals(1,count($posts));
		$this->assertEquals(2,$posts[0]->id);

		$posts=Post::model()->findAllByPk([4,3,2],'id<4');
		$this->assertEquals(2,count($posts));
		$this->assertEquals(2,$posts[0]->id);
		$this->assertEquals(3,$posts[1]->id);

		$posts=Post::model()->findAllByPk([]);
		$this->assertTrue($posts===[]);

		// test findByAttributes
		$post=Post::model()->findByAttributes(['author_id'=>2],['order'=>'id DESC']);
		$this->assertEquals(4,$post->id);

		// test findAllByAttributes
		$posts=Post::model()->findAllByAttributes(['author_id'=>2]);
		$this->assertEquals(3,count($posts));

		// test findBySql
		$post=Post::model()->findBySql('select * from posts where id=:id',[':id'=>2]);
		$this->assertEquals(2,$post->id);

		// test findAllBySql
		$posts=Post::model()->findAllBySql('select * from posts where id>:id',[':id'=>2]);
		$this->assertEquals(3,count($posts));

		// test count
		$this->assertEquals(5,Post::model()->count());
		$this->assertEquals(3,Post::model()->count(['condition'=>'id>2']));

		// test countBySql
		$this->assertEquals(1,Post::model()->countBySql('select id from posts limit 1'));

		// test exists
		$this->assertTrue(Post::model()->exists('id=:id',[':id'=>1]));
		$this->assertFalse(Post::model()->exists('id=:id',[':id'=>6]));
	}

	public function testInsert()
	{
		$post=new Post;
		$this->assertEquals(['id'=>null,'title'=>null,'create_time'=>null,'author_id'=>null,'content'=>null],$post->attributes);
		$post->title='test post 1';
		$post->create_time=time();
		$post->author_id=1;
		$post->content='test post content 1';
		$this->assertTrue($post->isNewRecord);
		$this->assertNull($post->id);
		$this->assertTrue($post->save());
		$this->assertEquals([
			'id'=>6,
			'title'=>'test post 1',
			'create_time'=>$post->create_time,
			'author_id'=>1,
			'content'=>'test post content 1'],$post->attributes);
		$this->assertFalse($post->isNewRecord);
		$this->assertEquals($post->attributes,Post::model()->findByPk($post->id)->attributes);
	}

	public function testUpdate()
	{
		// test save
		$post=Post::model()->findByPk(1);
		$this->assertFalse($post->isNewRecord);
		$this->assertEquals('post 1',$post->title);
		$post->title='test post 1';
		$this->assertTrue($post->save());
		$this->assertFalse($post->isNewRecord);
		$this->assertEquals('test post 1',$post->title);
		$this->assertEquals('test post 1',Post::model()->findByPk(1)->title);

		// test updateByPk
		$this->assertEquals(2,Post::model()->updateByPk([4,5],['title'=>'test post']));
		$this->assertEquals('post 2',Post::model()->findByPk(2)->title);
		$this->assertEquals('test post',Post::model()->findByPk(4)->title);
		$this->assertEquals('test post',Post::model()->findByPk(5)->title);

		// test updateAll
		$this->assertEquals(1,Post::model()->updateAll(['title'=>'test post'],'id=1'));
		$this->assertEquals('test post',Post::model()->findByPk(1)->title);

		// test updateCounters
		$this->assertEquals(2,Post::model()->findByPk(2)->author_id);
		$this->assertEquals(2,Post::model()->findByPk(3)->author_id);
		$this->assertEquals(2,Post::model()->findByPk(4)->author_id);
		$this->assertEquals(3,Post::model()->updateCounters(['author_id'=>-1],'id>2'));
		$this->assertEquals(2,Post::model()->findByPk(2)->author_id);
		$this->assertEquals(1,Post::model()->findByPk(3)->author_id);
	}

	public function testSaveCounters()
	{
		$post=Post::model()->findByPk(2);
		$this->assertEquals(2, $post->author_id);
		$result=$post->saveCounters(['author_id'=>-1]);
		$this->assertTrue($result);
		$this->assertEquals(1, $post->author_id);
		$this->assertEquals(1, Post::model()->findByPk(2)->author_id);
		$this->assertEquals(2, Post::model()->findByPk(3)->author_id);
	}

	public function testDelete()
	{
		$post=Post::model()->findByPk(1);
		$this->assertTrue($post->delete());
		$this->assertNull(Post::model()->findByPk(1));

		$this->assertTrue(Post::model()->findByPk(2) instanceof Post);
		$this->assertTrue(Post::model()->findByPk(3) instanceof Post);
		$this->assertEquals(2,Post::model()->deleteByPk([2,3]));
		$this->assertNull(Post::model()->findByPk(2));
		$this->assertNull(Post::model()->findByPk(3));

		$this->assertTrue(Post::model()->findByPk(5) instanceof Post);
		$this->assertEquals(1,Post::model()->deleteAll('id=5'));
		$this->assertNull(Post::model()->findByPk(5));
	}

	public function testRefresh()
	{
		$post=Post::model()->findByPk(1);
		$post2=Post::model()->findByPk(1);
		$post2->title='new post';
		$post2->save();
		$this->assertEquals('post 1',$post->title);
		$this->assertTrue($post->refresh());
		$this->assertEquals('new post',$post->title);

		$post = new Post();
		$this->assertFalse($post->refresh());
		$post->id = 1;
		$this->assertTrue($post->refresh());
		$this->assertEquals('new post',$post->title);
	}

	public function testEquals()
	{
		$post=Post::model()->findByPk(1);
		$post2=Post::model()->findByPk(1);
		$post3=Post::model()->findByPk(3);
		$this->assertEquals(1,$post->primaryKey);
		$this->assertTrue($post->equals($post2));
		$this->assertTrue($post2->equals($post));
		$this->assertFalse($post->equals($post3));
		$this->assertFalse($post3->equals($post));
	}

	public function testValidation()
	{
		$user=new User;
		$user->password='passtest';
		$this->assertFalse($user->hasErrors());
		$this->assertEquals([],$user->errors);
		$this->assertEquals([],$user->getErrors('username'));
		$this->assertFalse($user->save());
		$this->assertNull($user->id);
		$this->assertTrue($user->isNewRecord);
		$this->assertTrue($user->hasErrors());
		$this->assertTrue($user->hasErrors('username'));
		$this->assertTrue($user->hasErrors('email'));
		$this->assertFalse($user->hasErrors('password'));
		$this->assertEquals(1,count($user->getErrors('username')));
		$this->assertEquals(1,count($user->getErrors('email')));
		$this->assertEquals(2,count($user->errors));

		$user->clearErrors();
		$this->assertFalse($user->hasErrors());
		$this->assertEquals([],$user->errors);
	}

	public function testCompositeKey()
	{
		$order=new Order;
		$this->assertEquals(['key1','key2'],$order->tableSchema->primaryKey);
		$order=Order::model()->findByPk(['key1'=>2,'key2'=>1]);
		$this->assertEquals('order 21',$order->name);
		$orders=Order::model()->findAllByPk([['key1'=>2,'key2'=>1],['key1'=>1,'key2'=>3]]);
		$this->assertEquals('order 13',$orders[0]->name);
		$this->assertEquals('order 21',$orders[1]->name);
	}

	public function testDefault()
	{
		$type=new ComplexType;
		$this->assertEquals(1,$type->int_col2);
		$this->assertEquals('something',$type->char_col2);
		$this->assertEquals(1.23,$type->float_col2);
		$this->assertEquals(33.22,$type->numeric_col);
		$this->assertEquals(123,$type->time);
		$this->assertNull($type->bool_col);
		$this->assertTrue($type->bool_col2);
	}

	public function testPublicAttribute()
	{
		$post=new PostExt;
		$this->assertEquals(['id'=>null,'title'=>'default title','create_time'=>null,'author_id'=>null,'content'=>null],$post->attributes);
		$post=Post::model()->findByPk(1);
		$this->assertEquals([
			'id'=>1,
			'title'=>'post 1',
			'create_time'=>100000,
			'author_id'=>1,
			'content'=>'content 1'],$post->attributes);

		$post=new PostExt;
		$post->title='test post';
		$post->create_time=1000000;
		$post->author_id=1;
		$post->content='test';
		$post->save();
		$this->assertEquals([
			'id'=>6,
			'title'=>'test post',
			'create_time'=>1000000,
			'author_id'=>1,
			'content'=>'test'],$post->attributes);
	}

	public function testLazyRelation()
	{
		// test belongsTo
		$post=Post::model()->findByPk(2);
		$this->assertTrue($post->author instanceof User);
		$this->assertEquals([
			'id'=>2,
			'username'=>'user2',
			'password'=>'pass2',
			'email'=>'email2'],$post->author->attributes);

		// test hasOne
		$post=Post::model()->findByPk(2);
		$this->assertTrue($post->firstComment instanceof Comment);
		$this->assertEquals([
			'id'=>4,
			'content'=>'comment 4',
			'post_id'=>2,
			'author_id'=>2],$post->firstComment->attributes);
		$post=Post::model()->findByPk(4);
		$this->assertNull($post->firstComment);

		// test hasMany
		$post=Post::model()->findByPk(2);
		$this->assertEquals(2,count($post->comments));
		$this->assertEquals([
			'id'=>5,
			'content'=>'comment 5',
			'post_id'=>2,
			'author_id'=>2],$post->comments[0]->attributes);
		$this->assertEquals([
			'id'=>4,
			'content'=>'comment 4',
			'post_id'=>2,
			'author_id'=>2],$post->comments[1]->attributes);
		$post=Post::model()->findByPk(4);
		$this->assertEquals([],$post->comments);

		// test manyMany
		$post=Post::model()->findByPk(2);
		$this->assertEquals(2,count($post->categories));
		$this->assertEquals([
			'id'=>4,
			'name'=>'cat 4',
			'parent_id'=>1],$post->categories[0]->attributes);
		$this->assertEquals([
			'id'=>1,
			'name'=>'cat 1',
			'parent_id'=>null],$post->categories[1]->attributes);
		$post=Post::model()->findByPk(4);
		$this->assertEquals([],$post->categories);

		// test self join
		$category=Category::model()->findByPk(5);
		$this->assertEquals([],$category->posts);
		$this->assertEquals(2,count($category->children));
		$this->assertEquals([
			'id'=>6,
			'name'=>'cat 6',
			'parent_id'=>5],$category->children[0]->attributes);
		$this->assertEquals([
			'id'=>7,
			'name'=>'cat 7',
			'parent_id'=>5],$category->children[1]->attributes);
		$this->assertTrue($category->parent instanceof Category);
		$this->assertEquals([
			'id'=>1,
			'name'=>'cat 1',
			'parent_id'=>null],$category->parent->attributes);

		$category=Category::model()->findByPk(2);
		$this->assertEquals(1,count($category->posts));
		$this->assertEquals([],$category->children);
		$this->assertNull($category->parent);

		// test composite key
		$order=Order::model()->findByPk(['key1'=>1,'key2'=>2]);
		$this->assertEquals(2,count($order->items));
		$order=Order::model()->findByPk(['key1'=>2,'key2'=>1]);
		$this->assertEquals(0,count($order->items));
		$item=Item::model()->findByPk(4);
		$this->assertTrue($item->order instanceof Order);
		$this->assertEquals([
			'key1'=>2,
			'key2'=>2,
			'name'=>'order 22'],$item->order->attributes);
	}

	public function testEagerRelation2()
	{
		$post=Post::model()->with('author','firstComment','comments','categories')->findByPk(2);
	}

	private function checkEagerLoadedModel($post)
	{
		$this->assertEquals([
			'id'=>2,
			'username'=>'user2',
			'password'=>'pass2',
			'email'=>'email2'],$post->author->attributes);
		$this->assertTrue($post->firstComment instanceof Comment);
		$this->assertEquals([
			'id'=>4,
			'content'=>'comment 4',
			'post_id'=>2,
			'author_id'=>2],$post->firstComment->attributes);
		$this->assertEquals(2,count($post->comments));
		$this->assertEquals([
			'id'=>5,
			'content'=>'comment 5',
			'post_id'=>2,
			'author_id'=>2],$post->comments[0]->attributes);
		$this->assertEquals([
			'id'=>4,
			'content'=>'comment 4',
			'post_id'=>2,
			'author_id'=>2],$post->comments[1]->attributes);
		$this->assertEquals(2,count($post->categories));
		$this->assertEquals([
			'id'=>4,
			'name'=>'cat 4',
			'parent_id'=>1],$post->categories[0]->attributes);
		$this->assertEquals([
			'id'=>1,
			'name'=>'cat 1',
			'parent_id'=>null],$post->categories[1]->attributes);
	}

	public function testEagerRelation()
	{
		$post=Post::model()->with('author','firstComment','comments','categories')->findByPk(2);
		$this->checkEagerLoadedModel($post);
		$post=Post::model()->findByPk(2,[
			'with'=>['author','firstComment','comments','categories'],
		]);
		$this->checkEagerLoadedModel($post);

		$post=Post::model()->with('author','firstComment','comments','categories')->findByPk(4);
		$this->assertEquals([
			'id'=>2,
			'username'=>'user2',
			'password'=>'pass2',
			'email'=>'email2'],$post->author->attributes);
		$this->assertNull($post->firstComment);
		$this->assertEquals([],$post->comments);
		$this->assertEquals([],$post->categories);
	}

	public function testLazyRecursiveRelation()
	{
		$post=PostExt::model()->findByPk(2);
		$this->assertEquals(2,count($post->comments));
		$this->assertTrue($post->comments[0]->post instanceof Post);
		$this->assertTrue($post->comments[1]->post instanceof Post);
		$this->assertTrue($post->comments[0]->author instanceof User);
		$this->assertTrue($post->comments[1]->author instanceof User);
		$this->assertEquals(3,count($post->comments[0]->author->posts));
		$this->assertEquals(3,count($post->comments[1]->author->posts));
		$this->assertTrue($post->comments[0]->author->posts[1]->author instanceof User);

		// test self join
		$category=Category::model()->findByPk(1);
		$this->assertEquals(2,count($category->nodes));
		$this->assertTrue($category->nodes[0]->parent instanceof Category);
		$this->assertTrue($category->nodes[1]->parent instanceof Category);
		$this->assertEquals(0,count($category->nodes[0]->children));
		$this->assertEquals(2,count($category->nodes[1]->children));
	}

	public function testEagerRecursiveRelation()
	{
		//$post=Post::model()->with(array('comments'=>'author','categories'))->findByPk(2);
		$post=Post::model()->with('comments.author','categories')->findByPk(2);
		$this->assertEquals(2,count($post->comments));
		$this->assertEquals(2,count($post->categories));

		$posts=PostExt::model()->with('comments')->findAll();
		$this->assertEquals(5,count($posts));
	}

	public function testRelationWithCondition()
	{
		$posts=Post::model()->with('comments')->findAllByPk([2,3,4],['order'=>'t.id']);
		$this->assertEquals(3,count($posts));
		$this->assertEquals(2,count($posts[0]->comments));
		$this->assertEquals(4,count($posts[1]->comments));
		$this->assertEquals(0,count($posts[2]->comments));

		$post=Post::model()->with('comments')->findByAttributes(['id'=>2]);
		$this->assertTrue($post instanceof Post);
		$this->assertEquals(2,count($post->comments));
		$posts=Post::model()->with('comments')->findAllByAttributes(['id'=>2]);
		$this->assertEquals(1,count($posts));

		$post=Post::model()->with('comments')->findBySql('select * from posts where id=:id',[':id'=>2]);
		$this->assertTrue($post instanceof Post);
		$posts=Post::model()->with('comments')->findAllBySql('select * from posts where id=:id1 OR id=:id2',[':id1'=>2,':id2'=>3]);
		$this->assertEquals(2,count($posts));

		$post=Post::model()->with('comments','author')->find('t.id=:id',[':id'=>2]);
		$this->assertTrue($post instanceof Post);

		$posts=Post::model()->with('comments','author')->findAll([
			'select'=>'title',
			'condition'=>'t.id=:id',
			'limit'=>1,
			'offset'=>0,
			'order'=>'t.title',
			'group'=>'t.id',
			'params'=>[':id'=>2]]);
		$this->assertTrue($posts[0] instanceof Post);

		$posts=Post::model()->with('comments','author')->findAll([
			'select'=>'title',
			'condition'=>'t.id=:id',
			'limit'=>1,
			'offset'=>2,
			'order'=>'t.title',
			'params'=>[':id'=>2]]);
		$this->assertTrue($posts===[]);
	}

	public function testRelationWithColumnAlias()
	{
		$users=User::model()->with('posts')->findAll([
			'select'=>'id, username AS username2',
			'order'=>'username2',
		]);

		$this->assertEquals(4,count($users));
		$this->assertEquals($users[1]->username,null);
		$this->assertEquals($users[1]->username2,'user2');
	}

	public function testRelationalWithoutFK()
	{
		$users=UserNoFk::model()->with('posts')->findAll();
		$this->assertEquals(4,count($users));
		$this->assertEquals(3,count($users[1]->posts));

		$posts=PostNoFk::model()->with('author')->findAll();
		$this->assertEquals(5,count($posts));
		$this->assertTrue($posts[2]->author instanceof UserNoFk);
	}

	public function testRelationWithNewRecord()
	{
		$user=new User;
		$posts=$user->posts;
		$this->assertTrue(is_array($posts) && empty($posts));

		$post=new Post;
		$author=$post->author;
		$this->assertNull($author);
	}

	public function testRelationWithDynamicCondition()
	{
		$user=User::model()->with('posts')->findByPk(2);
		$this->assertEquals($user->posts[0]->id,2);
		$this->assertEquals($user->posts[1]->id,3);
		$this->assertEquals($user->posts[2]->id,4);
		$user=User::model()->with(['posts'=>['order'=>'posts.id DESC']])->findByPk(2);
		$this->assertEquals($user->posts[0]->id,4);
		$this->assertEquals($user->posts[1]->id,3);
		$this->assertEquals($user->posts[2]->id,2);
	}

	public function testEagerTogetherRelation()
	{
		$post=Post::model()->with('author','firstComment','comments','categories')->findByPk(2);
		$comments=$post->comments;
		$this->assertEquals([
			'id'=>2,
			'username'=>'user2',
			'password'=>'pass2',
			'email'=>'email2'],$post->author->attributes);
		$this->assertTrue($post->firstComment instanceof Comment);
		$this->assertEquals([
			'id'=>4,
			'content'=>'comment 4',
			'post_id'=>2,
			'author_id'=>2],$post->firstComment->attributes);
		$this->assertEquals(2,count($post->comments));
		$this->assertEquals([
			'id'=>5,
			'content'=>'comment 5',
			'post_id'=>2,
			'author_id'=>2],$post->comments[0]->attributes);
		$this->assertEquals([
			'id'=>4,
			'content'=>'comment 4',
			'post_id'=>2,
			'author_id'=>2],$post->comments[1]->attributes);
		$this->assertEquals(2,count($post->categories));
		$this->assertEquals([
			'id'=>4,
			'name'=>'cat 4',
			'parent_id'=>1],$post->categories[0]->attributes);
		$this->assertEquals([
			'id'=>1,
			'name'=>'cat 1',
			'parent_id'=>null],$post->categories[1]->attributes);

		$post=Post::model()->with('author','firstComment','comments','categories')->findByPk(4);
		$this->assertEquals([
			'id'=>2,
			'username'=>'user2',
			'password'=>'pass2',
			'email'=>'email2'],$post->author->attributes);
		$this->assertNull($post->firstComment);
		$this->assertEquals([],$post->comments);
		$this->assertEquals([],$post->categories);
	}

	public function testRelationalCount()
	{
		$count=Post::model()->with('author','firstComment','comments','categories')->count();
		$this->assertEquals(5,$count);

		$count=Post::model()->count(['with'=>['author','firstComment','comments','categories']]);
		$this->assertEquals(5,$count);

		$count=Post::model()->with('author','firstComment','comments','categories')->count('t.id=4');
		$this->assertEquals(1,$count);

		$count=Post::model()->with('author','firstComment','comments','categories')->count('t.id=14');
		$this->assertEquals(0,$count);
	}

	public function testRelationalStat()
	{
		$users=User::model()->with('postCount')->findAll();
		$this->assertEquals(4,count($users));
		$this->assertEquals(1,$users[0]->postCount);
		$this->assertEquals(3,$users[1]->postCount);
		$this->assertEquals(1,$users[2]->postCount);

		$users=User::model()->findAll();
		$this->assertEquals(4,count($users));
		$this->assertEquals(1,$users[0]->postCount);
		$this->assertEquals(3,$users[1]->postCount);
		$this->assertEquals(1,$users[2]->postCount);

		$orders=Order::model()->with('itemCount')->findAll();
		$this->assertEquals(4,count($orders));
		$this->assertEquals(2,$orders[0]->itemCount);
		$this->assertEquals(1,$orders[1]->itemCount);
		$this->assertEquals(0,$orders[2]->itemCount);
		$this->assertEquals(2,$orders[3]->itemCount);

		$orders=Order::model()->findAll();
		$this->assertEquals(4,count($orders));
		$this->assertEquals(2,$orders[0]->itemCount);
		$this->assertEquals(1,$orders[1]->itemCount);
		$this->assertEquals(0,$orders[2]->itemCount);
		$this->assertEquals(2,$orders[3]->itemCount);

		$categories=Category::model()->with('postCount')->findAll();
		$this->assertEquals(7,count($categories));
		$this->assertEquals(3,$categories[0]->postCount);
		$this->assertEquals(1,$categories[1]->postCount);
		$this->assertEquals(1,$categories[2]->postCount);
		$this->assertEquals(1,$categories[3]->postCount);
		$this->assertEquals(0,$categories[4]->postCount);
		$this->assertEquals(0,$categories[5]->postCount);
		$this->assertEquals(0,$categories[6]->postCount);

		$categories=Category::model()->findAll();
		$this->assertEquals(7,count($categories));
		$this->assertEquals(3,$categories[0]->postCount);
		$this->assertEquals(1,$categories[1]->postCount);
		$this->assertEquals(1,$categories[2]->postCount);
		$this->assertEquals(1,$categories[3]->postCount);
		$this->assertEquals(0,$categories[4]->postCount);
		$this->assertEquals(0,$categories[5]->postCount);
		$this->assertEquals(0,$categories[6]->postCount);

		$users=User::model()->with('postCount','posts.commentCount')->findAll();
		$this->assertEquals(4,count($users));
	}

	/**
	 * @depends testRelationalStat
	 * @see https://github.com/yiisoft/yii/issues/873
	 */
	public function testRelationalStatWithScopes()
	{
		// CStatRelation with scopes, HAS_MANY case
		$users=User::model()->findAll();
		// user1
		$this->assertEquals(0,$users[0]->recentPostCount1);
		$this->assertEquals(0,$users[0]->recentPostCount2);
		// user2
		$this->assertEquals(2,$users[1]->recentPostCount1);
		$this->assertEquals(2,$users[1]->recentPostCount2);
		// user3
		$this->assertEquals(1,$users[2]->recentPostCount1);
		$this->assertEquals(1,$users[2]->recentPostCount2);
		// user4
		$this->assertEquals(0,$users[3]->recentPostCount1);
		$this->assertEquals(0,$users[3]->recentPostCount2);

		// CStatRelation with scopes, MANY_MANY case
		$categories=Category::model()->findAll();
		// category1
		$this->assertEquals(2,$categories[0]->recentPostCount1);
		$this->assertEquals(2,$categories[0]->recentPostCount2);
		// category2
		$this->assertEquals(0,$categories[1]->recentPostCount1);
		$this->assertEquals(0,$categories[1]->recentPostCount2);
		// category3
		$this->assertEquals(0,$categories[2]->recentPostCount1);
		$this->assertEquals(0,$categories[2]->recentPostCount2);
		// category4
		$this->assertEquals(1,$categories[3]->recentPostCount1);
		$this->assertEquals(1,$categories[3]->recentPostCount2);
	}

	public function testLazyLoadingWithConditions()
	{
		$user=User::model()->findByPk(2);
		$posts=$user->posts;
		$this->assertEquals(3,count($posts));
		$posts=$user->posts(['condition'=>'posts.id>=3', 'alias'=>'posts']);
		$this->assertEquals(2,count($posts));
	}

	public function testDuplicateLazyLoadingBug()
	{
		$user=User::model()->with([
			'posts'=>['on'=>'posts.id=-1']
		])->findByPk(1);
		// with the bug, an eager loading for 'posts' would be trigger in the following
		// and result with non-empty posts
		$this->assertTrue($user->posts===[]);
	}

	public function testTogether()
	{
		// test without together
		$users=UserNoTogether::model()->with('posts.comments')->findAll();
		$postCount=0;
		$commentCount=0;
		foreach($users as $user)
		{
			$postCount+=count($user->posts);
			foreach($posts=$user->posts as $post)
				$commentCount+=count($post->comments);
		}
		$this->assertEquals(4,count($users));
		$this->assertEquals(5,$postCount);
		$this->assertEquals(10,$commentCount);

		// test with together
		$users=UserNoTogether::model()->with('posts.comments')->together()->findAll();
		$postCount=0;
		$commentCount=0;
		foreach($users as $user)
		{
			$postCount+=count($user->posts);
			foreach($posts=$user->posts as $post)
				$commentCount+=count($post->comments);
		}
		$this->assertEquals(3,count($users));
		$this->assertEquals(4,$postCount);
		$this->assertEquals(10,$commentCount);
	}

	public function testTogetherWithOption()
	{
		// test with together off option
		$users=User::model()->with([
			'posts'=>[
				'with'=>[
					'comments'=>[
						'joinType'=>'INNER JOIN',
						'together'=>false,
					],
				],
				'joinType'=>'INNER JOIN',
				'together'=>false,
			],
		])->findAll();

		$postCount=0;
		$commentCount=0;
		foreach($users as $user)
		{
			$postCount+=count($user->posts);
			foreach($posts=$user->posts as $post)
				$commentCount+=count($post->comments);
		}
		$this->assertEquals(4,count($users));
		$this->assertEquals(5,$postCount);
		$this->assertEquals(10,$commentCount);

		// test with together on option
		$users=User::model()->with([
			'posts'=>[
				'with'=>[
					'comments'=>[
						'joinType'=>'INNER JOIN',
						'together'=>true,
					],
				],
				'joinType'=>'INNER JOIN',
				'together'=>true,
			],
		])->findAll();

		$postCount=0;
		$commentCount=0;
		foreach($users as $user)
		{
			$postCount+=count($user->posts);
			foreach($posts=$user->posts as $post)
				$commentCount+=count($post->comments);
		}
		$this->assertEquals(3,count($users));
		$this->assertEquals(4,$postCount);
		$this->assertEquals(10,$commentCount);
	}

	public function testCountByAttributes()
	{
		$n=Post::model()->countByAttributes(['author_id'=>2]);
		$this->assertEquals(3,$n);

	}

	public function testScopes()
	{
		$models1=Post::model()->post23()->findAll();
		$models2=Post::model()->findAll(['scopes'=>'post23']);

		foreach([$models1,$models2] as $models)
		{
			$this->assertEquals(2,count($models));
			$this->assertEquals(2,$models[0]->id);
			$this->assertEquals(3,$models[1]->id);
		}

		$model1=Post::model()->post23()->find();
		$model2=Post::model()->find(['scopes'=>'post23']);

		foreach([$model1,$model2] as $model)
			$this->assertEquals(2,$model->id);

		$models1=Post::model()->post23()->post3()->findAll();
		$models2=Post::model()->findAll(['scopes'=>['post23','post3']]);

		foreach([$models1,$models2] as $models)
		{
			$this->assertEquals(1,count($models));
			$this->assertEquals(3,$models[0]->id);
		}

		$models1=Post::model()->post23()->findAll('id=3');
		$models2=Post::model()->post23()->findAll(['condition'=>'id=3','scopes'=>'post23']);

		foreach([$models1,$models2] as $models)
		{
			$this->assertEquals(1,count($models));
			$this->assertEquals(3,$models[0]->id);
		}

		$models1=Post::model()->recent()->with('author')->findAll();
		$models2=Post::model()->with('author')->findAll(['scopes'=>'recent']);
		$models3=Post::model()->with('author')->findAll(['scopes'=>['recent']]);
		$models4=Post::model()->with('author')->findAll(['scopes'=>[['recent'=>[]]]]);

		foreach([$models1,$models2,$models3,$models4] as $models)
		{
			$this->assertEquals(5,count($models));
			$this->assertEquals(5,$models[0]->id);
			$this->assertEquals(4,$models[1]->id);
		}

		$models1=Post::model()->recent(3)->findAll();
		$models2=Post::model()->findAll(['scopes'=>['recent'=>3]]);
		$models3=Post::model()->findAll(['scopes'=>[['recent'=>3]]]);

		foreach([$models1,$models2,$models3] as $models)
		{
			$this->assertEquals(3,count($models));
			$this->assertEquals(5,$models[0]->id);
			$this->assertEquals(4,$models[1]->id);
		}

		//default scope
		$models=PostSpecial::model()->findAll();
		$this->assertEquals(2,count($models));
		$this->assertEquals(2,$models[0]->id);
		$this->assertEquals(3,$models[1]->id);

		//default scope + scope
		$models1=PostSpecial::model()->desc()->findAll();
		$models2=PostSpecial::model()->findAll(['scopes'=>'desc']);

		foreach([$models1,$models2] as $models)
		{
			$this->assertEquals(2,count($models));
			$this->assertEquals(3,$models[0]->id);
			$this->assertEquals(2,$models[1]->id);
		}

		//behavior scope
		$models=Post::model()->findAll(['scopes'=>'behaviorPost23']);
		$this->assertEquals(2,count($models));
		$this->assertEquals(2,$models[0]->id);
		$this->assertEquals(3,$models[1]->id);

		//behavior parametrized scope
		$models=Post::model()->findAll(['scopes'=>['behaviorRecent'=>3]]);
		$this->assertEquals(3,count($models));
		$this->assertEquals(5,$models[0]->id);
		$this->assertEquals(4,$models[1]->id);
	}

	public function testScopeWithRelations()
	{
		$user1=User::model()->with('posts:post23')->findByPk(2);
		$user2=User::model()->with(['posts'=>['scopes'=>'post23']])->findByPk(2);
		$user3=User::model()->findByPk(2,['with'=>['posts'=>['scopes'=>'post23']]]);
		//ensure alais overloading work correctly
		$user4=User::model()->with(['posts:post23A'=>['alias'=>'alias']])->findByPk(2);
		$user5=User::model()->with(['posts'=>['scopes'=>'post23A','alias'=>'alias']])->findByPk(2);
		$user6=User::model()->findByPk(2,['with'=>['posts'=>['scopes'=>'post23A','alias'=>'alias']]]);

		foreach([$user1,$user2,$user3,$user4,$user5,$user6] as $user)
		{
			$this->assertEquals(2,count($user->posts));
			$this->assertEquals(2,$user->posts[0]->id);
			$this->assertEquals(3,$user->posts[1]->id);
		}

		$user1=User::model()->with(['posts'=>['scopes'=>['p'=>4]]])->findByPk(2);
		$user2=User::model()->with(['posts'=>['scopes'=>['p'=>[4]]]])->findByPk(2);
		$user3=User::model()->with(['posts'=>['scopes'=>[['p'=>4]]]])->findByPk(2);
		$user4=User::model()->with(['posts'=>['scopes'=>[['p'=>[4]]]]])->findByPk(2);
		$user5=User::model()->findByPk(2,['with'=>['posts'=>['scopes'=>['p'=>4]]]]);
		$user6=User::model()->findByPk(2,['with'=>['posts'=>['scopes'=>['p'=>[4]]]]]);
		$user7=User::model()->findByPk(2,['with'=>['posts'=>['scopes'=>[['p'=>4]]]]]);
		$user8=User::model()->findByPk(2,['with'=>['posts'=>['scopes'=>[['p'=>[4]]]]]]);

		foreach([$user1,$user2,$user3,$user4,$user5,$user6,$user7,$user8] as $user)
		{
			$this->assertEquals(1,count($user->posts));
			$this->assertEquals(4,$user->posts[0]->id);
		}

		$user=UserSpecial::model()->findByPk(2);
		$posts=$user->posts;
		$this->assertEquals(2,count($posts));
		$this->assertEquals(2,$posts[0]->id);
		$this->assertEquals(3,$posts[1]->id);

		$user=UserSpecial::model()->findByPk(2);
		$posts=$user->posts(['params'=>[':id1'=>4],'order'=>'posts.id DESC']);
		$this->assertEquals(2,count($posts));
		$this->assertEquals(4,$posts[0]->id);
		$this->assertEquals(3,$posts[1]->id);

		$user=User::model()->with('posts:post23')->findByPk(2);
		$posts=$user->posts(['scopes'=>'post23']);
		$this->assertEquals(2,count($posts));
		$this->assertEquals(2,$posts[0]->id);
		$this->assertEquals(3,$posts[1]->id);

		//related model behavior scope
		$user1=User::model()->with('posts:behaviorPost23')->findByPk(2);
		$user2=User::model()->with(['posts'=>['scopes'=>'behaviorPost23']])->findByPk(2);
		$user3=User::model()->findByPk(2,['with'=>['posts'=>['scopes'=>'behaviorPost23']]]);

		foreach([$user1,$user2,$user3] as $user)
		{
			$this->assertEquals(2,count($user->posts));
			$this->assertEquals(2,$user->posts[0]->id);
			$this->assertEquals(3,$user->posts[1]->id);
		}

		//related model with behavior parametrized scope
		$user1=User::model()->with(['posts'=>['scopes'=>['behaviorP'=>4]]])->findByPk(2);
		$user2=User::model()->with(['posts'=>['scopes'=>['behaviorP'=>[4]]]])->findByPk(2);
		$user3=User::model()->with(['posts'=>['scopes'=>[['behaviorP'=>4]]]])->findByPk(2);
		$user4=User::model()->with(['posts'=>['scopes'=>[['behaviorP'=>[4]]]]])->findByPk(2);
		$user5=User::model()->findByPk(2,['with'=>['posts'=>['scopes'=>['behaviorP'=>4]]]]);
		$user6=User::model()->findByPk(2,['with'=>['posts'=>['scopes'=>['behaviorP'=>[4]]]]]);
		$user7=User::model()->findByPk(2,['with'=>['posts'=>['scopes'=>[['behaviorP'=>4]]]]]);
		$user8=User::model()->findByPk(2,['with'=>['posts'=>['scopes'=>[['behaviorP'=>[4]]]]]]);

		foreach([$user1,$user2,$user3,$user4,$user5,$user6,$user7,$user8] as $user)
		{
			$this->assertEquals(1,count($user->posts));
			$this->assertEquals(4,$user->posts[0]->id);
		}

		//related model with 'scopes' as relation option
		$user=User::model()->with('postsOrderDescFormat1')->findByPk(2);
		$this->assertEquals(3,count($user->postsOrderDescFormat1));
		$this->assertEquals([4,3,2],[
			$user->postsOrderDescFormat1[0]->id,
			$user->postsOrderDescFormat1[1]->id,
			$user->postsOrderDescFormat1[2]->id,
		]);
		$user=User::model()->with('postsOrderDescFormat2')->findByPk(2);
		$this->assertEquals(3,count($user->postsOrderDescFormat2));
		$this->assertEquals([4,3,2],[
			$user->postsOrderDescFormat2[0]->id,
			$user->postsOrderDescFormat2[1]->id,
			$user->postsOrderDescFormat2[2]->id,
		]);
	}

	public function testResetScope()
	{
		// resetting named scope
		$posts=Post::model()->post23()->resetScope()->findAll();
		$this->assertEquals(5,count($posts));

		// resetting default scope
		$posts=PostSpecial::model()->resetScope()->findAll();
		$this->assertEquals(5,count($posts));
	}

	public function testJoinWithoutSelect()
	{
        // 1:1 test
		$groups=Group::model()->findAll([
			'with'=>[
				'description'=>[
					'select'=>false,
					'joinType'=>'INNER JOIN',
				],
			],
		]);

		$result=[];
		foreach($groups as $group)
		{
			// there should be nothing in relation
			$this->assertFalse($group->hasRelated('description'));
			$result[]=[$group->id,$group->name];
		}

		$this->assertEquals([
			[1,'group1'],
			[2,'group2'],
			[3,'group3'],
			[4,'group4'],
		],$result);

		// 1:M test
		$users=User::model()->findAll([
			'with'=>[
				'roles'=>[
					'select'=>false,
					'joinType'=>'INNER JOIN',
				],
			],
		]);

		$result=[];
		foreach($users as $user)
		{
			// there should be nothing in relation
			$this->assertFalse($user->hasRelated('roles'));
			$result[]=[$user->id,$user->username,$user->email];
		}

		$this->assertEquals([
			[1,'user1','email1'],
			[2,'user2','email2'],
		],$result);
	}

	public function testHasManyThroughEager()
	{
		// just bridge
		$user=User::model()->with('groups')->findByPk(1);

		$result=[];
		foreach($user->groups as $group)
			$result[]=[$user->username,$group->name];

		$this->assertEquals([
			['user1','group1'],
			['user1','group2'],
		],$result);

		// just bridge, base limited
		$users=User::model()->with('groups')->findAll(['limit'=>1]);

		$result=[];
		foreach($users as $user)
		{
			foreach($user->groups as $group)
				$result[]=[$user->username,$group->name];
		}

		$this->assertEquals([
			['user1','group1'],
			['user1','group2'],
		],$result);

		// 'through' should not clear existing relations defined via short syntax
		$user=User::model()->with('groups.description')->findByPk(1);

		$result=[];
		foreach($user->groups as $group)
			$result[]=[$user->username,$group->name,$group->description->name];

		$this->assertEquals([
			['user1','group1','room1'],
			['user1','group2','room2'],
		],$result);

		// 'through' should not clear existing with
		$user=User::model()->with(['groups'=>['with'=>'description']])->findByPk(1);

		$result=[];
		foreach($user->groups as $group)
			$result[]=[$user->username,$group->name,$group->description->name];

		$this->assertEquals([
			['user1','group1','room1'],
			['user1','group2','room2'],
		],$result);

		// bridge fields handling
		$user=User::model()->with('roles','groups')->findByPk(1);

		$result=[];
		foreach($user->groups as $group)
			$result[]=[$user->username,$group->name];

		$this->assertEquals([
			['user1','group1'],
			['user1','group2'],
		],$result);

		$result=[];
		foreach($user->roles as $role)
			$result[]=[$user->username,$role->name];

		$this->assertEquals([
			['user1','dev'],
			['user1','user'],
		],$result);

		// bridge fields handling, another relations order
		$user=User::model()->with('groups','roles')->findByPk(1);

		$result=[];
		foreach($user->groups as $group)
			$result[]=[$user->username,$group->name];

		$this->assertEquals([
			['user1','group1'],
			['user1','group2'],
		],$result);

		$result=[];
		foreach($user->roles as $role)
			$result[]=[$user->username,$role->name];

		$this->assertEquals([
			['user1','dev'],
			['user1','user'],
		],$result);

		// bridge fields handling, base limited
		$users=User::model()->with('roles','groups')->findAll(['limit'=>1]);

		$result=[];
		foreach($users as $user)
		{
			foreach($user->groups as $group)
				$result[]=[$user->username,$group->name];
		}

		$this->assertEquals([
			['user1','group1'],
			['user1','group2'],
		],$result);

		$result=[];
		foreach($users as $user)
		{
			foreach($user->roles as $role)
				$result[]=[$user->username,$role->name];
		}

		$this->assertEquals([
			['user1','dev'],
			['user1','user'],
		],$result);

		// nested through
		$group=Group::model()->with('comments')->findByPk(1);

		$result=[];
		foreach($group->comments as $comment)
			$result[]=[$group->name,$comment->content];

		$this->assertEquals([
			['group1','comment 1'],
			['group1','comment 2'],
			['group1','comment 3'],
			['group1','comment 4'],
			['group1','comment 5'],
			['group1','comment 6'],
			['group1','comment 7'],
			['group1','comment 8'],
			['group1','comment 9'],
		],$result);

		// nested through, base limited
		$groups=Group::model()->with('comments')->findAll(['limit'=>1]);

		$result=[];
		foreach($groups as $group)
		{
			foreach($group->comments as $comment)
				$result[]=[$group->name,$comment->content];
		}

		$this->assertEquals([
			['group1','comment 1'],
			['group1','comment 2'],
			['group1','comment 3'],
			['group1','comment 4'],
			['group1','comment 5'],
			['group1','comment 6'],
			['group1','comment 7'],
			['group1','comment 8'],
			['group1','comment 9'],
		],$result);

		// self through
		$teachers=User::model()->with('students')->findAll();

		$result=[];
		foreach($teachers as $teacher)
		{
			foreach($teacher->students as $student)
				$result[]=[$teacher->username,$student->username];
		}

		$this->assertEquals([
			['user1','user3'],
			['user2','user4'],
		],$result);

		// self through, bridge fields handling for right part
		$teachers=User::model()->with('mentorships','students')->findAll();

		$result=[];
		foreach($teachers as $teacher)
		{
			foreach($teacher->students as $student)
				$result[$student->primaryKey]=['teacher'=>$teacher->username,'student'=>$student->username];

			foreach($teacher->mentorships as $mentorship)
				$result[$mentorship->student_id]['progress']=$mentorship->progress;
		}

		$this->assertEquals([
			3=>['teacher'=>'user1','student'=>'user3','progress'=>'good'],
			4=>['teacher'=>'user2','student'=>'user4','progress'=>'average'],
		],$result);

		// self through, base limited
		$teachers=User::model()->with('students')->findAll(['limit'=>1]);

		$result=[];
		foreach($teachers as $teacher)
		{
			foreach($teacher->students as $student)
				$result[]=[$teacher->username,$student->username];
		}

		$this->assertEquals([
			['user1','user3'],
		],$result);
	}

	public function testHasManyThroughLazy()
	{
		$user=User::model()->findByPk(1);

		$result=[];
		foreach($user->groups as $group)
			$result[]=[$user->username,$group->name];

		$this->assertEquals([
			['user1','group1'],
			['user1','group2'],
		],$result);


		$user=User::model()->findByPk(1);

		$result=[];
		foreach($user->groups(['with'=>'description']) as $group)
			$result[]=[$user->username,$group->name,$group->description->name];

		$this->assertEquals([
			['user1','group1','room1'],
			['user1','group2','room2'],
		],$result);

		// nested through
		$group=Group::model()->findByPk(1);

		$result=[];
		foreach($group->comments as $comment)
			$result[]=[$group->name,$comment->content];

		$this->assertEquals([
			['group1','comment 1'],
			['group1','comment 2'],
			['group1','comment 3'],
			['group1','comment 4'],
			['group1','comment 5'],
			['group1','comment 6'],
			['group1','comment 7'],
			['group1','comment 8'],
			['group1','comment 9'],
		],$result);

		// self through
		$teacher=User::model()->findByPk(1);

		$result=[];
		foreach($teacher->students as $student)
			$result[]=[$teacher->username,$student->username];

		$this->assertEquals([
			['user1','user3'],
		],$result);
	}

	/**
	 * @see issue2274
	 */
	function testMergingWith()
	{
		User::model()->nonEmptyPosts()->findAll([
			'with'=>[
        		'posts'=>[
            		'joinType'=>'INNER JOIN',
        		],
    		]
		]);
	}

	/**
	 * @see github issue 206
	 * Unable to pass CDbCriteria to relation while array works fine.
	 */
	public function testIssue206()
	{
		$user = User::model()->findByPk(2);
		$result1 = $user->posts(['condition' => 'id IN (2,3)']);

		$criteria = new CDbCriteria();
		$criteria->addInCondition('id', [2,3]);
		$user = User::model()->findByPk(2);
		$result2 = $user->posts($criteria);

		$this->assertEquals($result1, $result2);
	}

	/**
	 * @see https://github.com/yiisoft/yii/issues/268
	 */
	public function testCountIsSubStringOfFieldName()
	{
		$result = User::model()->with('profiles')->count(['select'=>'country AS country','condition'=>'t.id=2']);
		$this->assertEquals(1,$result);
	}

	/**
	 * verify https://github.com/yiisoft/yii/issues/2756
	 */
	public function testLazyFindCondition()
	{
		$user = User::model()->findByPk(2);
		$this->assertEquals(3, count($user->posts()));
		$this->assertEquals(2, count($user->posts(['condition' => 'id IN (2,3)'])));
		$this->assertEquals(2, count($user->postsCondition()));
	}

	/**
	 * https://github.com/yiisoft/yii/issues/1070
	 */
	public function testIssue1070()
	{
		$dataProvider=new CActiveDataProvider('UserWithDefaultScope');

		foreach($dataProvider->getData() as $item)
		{
			try
			{
				$item->links[0]->from_user;
				$result=true;
			}
			catch ( CDbException $e )
			{
				$result=false;
			}

			$this->assertTrue($result);
		}
	}

	/**
	 * https://github.com/yiisoft/yii/issues/507
	 */
	public function testIssue507()
	{
		$this->assertEquals(2, count(UserWithDefaultScope::model()->findAll()));

	}

	/**
	 * @see https://github.com/yiisoft/yii/issues/135
	 */
	public function testCountWithHaving()
	{
		$criteriaWithHaving = new CDbCriteria();
		$criteriaWithHaving->group = 'id';
		$criteriaWithHaving->having = 'id = 1';
		$count = Post::model()->count($criteriaWithHaving);

		$this->assertEquals(1, $count, 'Having condition has not been applied on count!');
	}

	/**
	 * @see https://github.com/yiisoft/yii/issues/135
	 * @see https://github.com/yiisoft/yii/issues/2201
	 */
	public function testCountWithHavingRelational()
	{
		$criteriaWithHaving = new CDbCriteria();
		$criteriaWithHaving->select = 't.id AS test_field';
		$criteriaWithHaving->with = ['author'];
		$criteriaWithHaving->group = 't.id';
		$criteriaWithHaving->having = 'test_field = :test_field';
		$criteriaWithHaving->params['test_field'] = 1;
		$count = Post::model()->count($criteriaWithHaving);

		$this->assertEquals(1, $count, 'Having condition has not been applied on count with relation!');
	}

	/**
	 * @depends testFind
	 *
	 * @see https://github.com/yiisoft/yii/issues/2216
	 */
	public function testFindBySinglePkByArrayWithMixedKeys()
	{
		$posts=Post::model()->findAllByPk(['some'=>3]);
		$this->assertEquals(1,count($posts));
		$this->assertEquals(3,$posts[0]->id);

		$posts=Post::model()->findAllByPk(['some'=>3, 'another'=>2]);
		$this->assertEquals(2,count($posts));
		$this->assertEquals(2,$posts[0]->id);
		$this->assertEquals(3,$posts[1]->id);
	}

	/**
	 * @depends testFind
	 *
	 * @see https://github.com/yiisoft/yii/issues/101
	 */
	public function testHasManyThroughHasManyWithCustomSelect()
	{
		$model=User::model()->with('studentsCustomSelect')->findByPk(1);
		$this->assertTrue(is_object($model),'Unable to get master records!');
		$this->assertTrue(count($model->students)>0,'Empty slave records!');
	}

	/**
	 * @depends testFind
	 *
	 * @see https://github.com/yiisoft/yii/issues/139
	 */
	public function testLazyLoadThroughRelationWithCondition()
	{
		$masterModel=Group::model()->findByPk(1);
		$this->assertTrue(count($masterModel->users)>0,'Test environment is missing!');
		$this->assertEquals(0,count($masterModel->usersWhichEmptyByCondition),'Unable to apply condition from through relation!');
	}

	/**
	 * @depends testFind
	 *
	 * @see https://github.com/yiisoft/yii/issues/662
	 */
	public function testThroughBelongsToLazy()
	{
		$comments=Comment::model()->findAll();
		foreach($comments as $comment)
		{
			$this->assertFalse(empty($comment->postAuthor));
			// equal relation definition with BELONGS_TO: https://github.com/yiisoft/yii/pull/2530
			$this->assertFalse(empty($comment->postAuthorBelongsTo));
			$this->assertTrue($comment->postAuthor->equals($comment->postAuthorBelongsTo));
		}
	}

	public function testThroughBelongsEager()
	{
		$comments=Comment::model()->with('postAuthorBelongsTo')->findAll();
		foreach($comments as $comment)
		{
			$this->assertFalse(empty($comment->postAuthor));
			// equal relation definition with BELONGS_TO: https://github.com/yiisoft/yii/pull/2530
			$this->assertFalse(empty($comment->postAuthorBelongsTo));
			$this->assertTrue($comment->postAuthor->equals($comment->postAuthorBelongsTo));
		}
	}

	public function testNamespacedTableName()
	{
		if(!version_compare(PHP_VERSION,"5.3.0",">="))
			$this->markTestSkipped('PHP 5.3.0 or higher required for namespaces.');
		require_once(dirname(__FILE__).'/../data/models-namespaced.php');
		$this->assertEquals("posts",Post::model()->tableName());
		$this->assertEquals("Example",CActiveRecord::model("yiiArExample\\testspace\\Example")->tableName());
	}

	/**
	 * https://github.com/yiisoft/yii/issues/2884
	 */
	public function testDefaultScopeAlias()
	{
		$this->assertEquals('user3', UserWithDefaultScopeAlias::model()->resetScope()->findByPk(3)->username);
		$this->assertNull(UserWithDefaultScopeAlias::model()->findByPk(3));
		$this->assertNotNull(UserWithDefaultScopeAlias::model()->findByPk(1));
	}
}
