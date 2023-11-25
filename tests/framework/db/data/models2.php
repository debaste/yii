<?php

/**
 * @property integer $id
 * @property string $username
 * @property string $password
 * @property string $email
 */
class User2 extends CActiveRecord
{
	public static function model($class=__CLASS__)
	{
		return parent::model($class);
	}

	public function rules()
	{
		return [
			['username, password, email', 'required'],
			['username, password', 'match', 'pattern'=>'/^[\d\w_]+$/'],
			['email', 'email'],
			['username', 'length', 'min'=>3, 'max'=>32],
			['password', 'length', 'min'=>6, 'max'=>32],
		];
	}

	public function relations()
	{
		return [
			'posts'=>[self::HAS_MANY,'Post2','author_id'],
			'friends'=>[self::MANY_MANY,'User2','test.user_friends(id,friend)'],

			// CActiveRecord2Test::testIssue2122()
			'commentsWithParam'=>[self::HAS_MANY,'Comment2','author_id','on'=>'"commentsWithParam"."post_id">:postId',
				'params'=>[':postId'=>1]],
			'postsWithParam'=>[self::HAS_MANY,'Post2',['post_id'=>'id'],'through'=>'commentsWithParam'],
		];
	}

	public function tableName()
	{
		return 'test.users';
	}
}

/**
 * @property integer $id
 * @property string $title
 * @property string $create_time
 * @property integer $author_id
 * @property string $content
 */
class Post2 extends CActiveRecord
{
	public static function model($class=__CLASS__)
	{
		return parent::model($class);
	}

	public function relations()
	{
		return [
			'author'=>[self::BELONGS_TO,'User2','author_id'],
			'firstComment'=>[self::HAS_ONE,'Comment2','post_id','order'=>'"firstComment".content'],
			'comments'=>[self::HAS_MANY,'Comment2','post_id','order'=>'comments.content DESC'],
			'categories'=>[self::MANY_MANY,'Category2','test.post_category(post_id,category_id)','order'=>'categories.id DESC'],
		];
	}

	public function rules()
	{
		return [
			['title', 'required'],
		];
	}

	public function tableName()
	{
		return 'test.posts';
	}
}

/**
 * @property integer $id
 * @property string $title
 * @property string $create_time
 * @property integer $author_id
 * @property string $content
 */
class NullablePost2 extends CActiveRecord
{
	public static function model($class=__CLASS__)
	{
		return parent::model($class);
	}

	public function tableName()
	{
		return 'test.nullable_posts';
	}
}

/**
 * @property integer $id
 * @property string $title
 * @property string $create_time
 * @property integer $author_id
 * @property string $content
 */
class PostExt2 extends CActiveRecord
{
	public $title='default title';
	public $id;

	public static function model($class=__CLASS__)
	{
		return parent::model($class);
	}

	public function tableName()
	{
		return 'test.posts';
	}

	public function relations()
	{
		return [
			'comments'=>[self::HAS_MANY,'Comment2','post_id','order'=>'comments.content DESC','with'=>['post'=>['alias'=>'post'], 'author']],
		];
	}
}

/**
 * @property integer $id
 * @property string $content
 * @property integer $post_id
 * @property integer $author_id
 */
class Comment2 extends CActiveRecord
{
	public static function model($class=__CLASS__)
	{
		return parent::model($class);
	}

	public function relations()
	{
		return [
			'post'=>[self::BELONGS_TO,'Post2','post_id'],
			'author'=>[self::BELONGS_TO,'User2','author_id'],
		];
	}

	public function tableName()
	{
		return 'test.comments';
	}
}

/**
 * @property integer $id
 * @property string $name
 * @property integer $parent_id
 */
class Category2 extends CActiveRecord
{
	public static function model($class=__CLASS__)
	{
		return parent::model($class);
	}

	public function tableName()
	{
		return 'test.categories';
	}

	public function relations()
	{
		return [
			'posts'=>[self::MANY_MANY, 'Post2', 'test.post_category(post_id,category_id)'],
			'parent'=>[self::BELONGS_TO,'Category2','parent_id'],
			'children'=>[self::HAS_MANY,'Category2','parent_id'],
			'nodes'=>[self::HAS_MANY,'Category2','parent_id','with'=>['parent','children']],
		];
	}
}

/**
 * @property integer $key1
 * @property integer $key2
 * @property string $name
 */
class Order2 extends CActiveRecord
{
	public static function model($class=__CLASS__)
	{
		return parent::model($class);
	}

	public function relations()
	{
		return [
			'items'=>[self::HAS_MANY,'Item2','col1, col2'],
		];
	}

	public function tableName()
	{
		return 'test.orders';
	}
}

/**
 * @property integer $id
 * @property string $name
 * @property integer $col1
 * @property integer $col2
 */
class Item2 extends CActiveRecord
{
	public static function model($class=__CLASS__)
	{
		return parent::model($class);
	}

	public function relations()
	{
		return [
			'order'=>[self::BELONGS_TO,'Order2','col1, col2','alias'=>'_order'],
		];
	}

	public function tableName()
	{
		return 'test.items';
	}
}

/**
 * @property integer $int_col
 * @property integer $int_col2
 * @property string $char_col
 * @property string $char_col2
 * @property string $char_col3
 * @property float $numeric_col
 * @property float $real_col
 * @property string $blob_col
 * @property string $time
 * @property integer $bool_col
 * @property integer $bool_col2
 */
class ComplexType2 extends CActiveRecord
{
	public static function model($class=__CLASS__)
	{
		return parent::model($class);
	}

	public function tableName()
	{
		return 'yii_types';
	}
}
