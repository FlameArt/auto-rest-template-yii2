<?php
/**
 * This is the template for generating the model class of a specified table.
 */

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\model\Generator */
/* @var $tableName string full table name */
/* @var $className string class name */
/* @var $queryClassName string query class name */
/* @var $tableSchema yii\db\TableSchema */
/* @var $labels string[] list of attribute labels (name => label) */
/* @var $rules string[] list of validation rules */
/* @var $relations array list of relations (name => relation declaration) */

// Определяем: есть ли поле user, которое надо заполнять при создании записи
$USER_FILL = false;
foreach ($tableSchema->columns as $column){
	if(strtolower($column->name) == "user") {
		$USER_FILL = true;
		break;
	}
}


echo "<?php\n";
?>

namespace <?= $generator->ns ?>;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

use common\models\DB\RelationBehaviors\MaterializedPathBehavior;



/**
 * Расширенный класс таблицы <?= $tableName ?>
 */
class <?=$className?> extends models\Table<?= $className ?>

{

    /**
     * Поведения для таблицы <?= $tableName ?>

     * @return array
     */
	public function behaviors()
	{
		return [

<?php if($USER_FILL): ?>
			// Автоматическое заполнение поля user при создании и обновлении записи: указать поле
			[
				'class' => BlameableBehavior::className(),
				'createdByAttribute' => 'user', # Заполнять пользователя при создании, false - не учитывать
				'updatedByAttribute' => false, # Заполнять пользователя при обновлении, false - не учитывать
			],
<?php endif; ?>

			// Автоматический учёт времени: указываются поля дат создания и изменения в таблице
			/*
			[
				'class' => TimestampBehavior::className(),
				'createdAtAttribute' => 'created_at', # Дата создания, false - не учитывать
				'updatedAtAttribute' => 'changed_at', # Дата изменения, false - не учитывать
				'value' => new Expression('NOW()'),
			],
			*/


			// Nested Sortable: быстрая сортировка дерева через Materialize Path
			/*
			[
				'class' => MaterializedPathBehavior::className(),
				'delimiter' => '.',
				'pathAttribute' => 'sort_m_path',
				'depthAttribute' => 'sort_m_path_depth',
				'sortable' => [
					'sortAttribute' => 'sort_m_path_sort'
				]
			]
			*/

		];
	}

}
