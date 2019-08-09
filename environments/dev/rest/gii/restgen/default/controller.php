<?php
/**
 * Генератор CRUD Ajax
 */

use yii\db\ActiveRecordInterface;
use yii\helpers\StringHelper;


/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator
 * @var $tableSchema
 */

$controllerClass = StringHelper::basename($generator->controllerClass);
//$modelClass = StringHelper::basename($generator->modelClass);
$modelClass = $tableName;

$path = Yii::$app->controllerNamespace;

/* @var $class ActiveRecordInterface */
$class = $generator->modelClass;
//$pks = $class::primaryKey();
//$urlParams = $generator->generateUrlParams();
//$actionParams = $generator->generateActionParams();
//$actionParamComments = $generator->generateActionParamComments();




// Преобразуем название таблицы в класс базы DB\ActiveQuery
$tableclass = ucfirst(\yii\helpers\Inflector::id2camel($modelClass,"_"));

echo "<?php\n";
?>

namespace rest\controllers\api;

// Imports
use yii\rest\ActiveController;
use rest\controllers\ActiveRestController;


/**
 * REST API Controller <?=ucfirst($tableclass)?>
 */
class <?=ucfirst($controllerClass)?>Controller extends ActiveRestController
{

    public $modelClass = 'common\models\DB\<?=ucfirst($tableclass)?>';

    /**
     * @var array Поля, которые не надо выводить при запросе
     */
    public $filterFields = [];

}