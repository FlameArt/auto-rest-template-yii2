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


namespace <?= $path."\\api" ?>;


// Базовый импорт
use Yii,yii\db\ActiveQuery;

// Импорт модели таблицы <?=ucfirst($tableclass)?>
use common\models\DB\<?=$tableclass?>;


/**
 * API управления таблицей  <?=ucfirst($tableclass)?>
 */
class <?=ucfirst($controllerClass)?>Controller extends base\<?=ucfirst($controllerClass)?>Controller
{

    /*************************************************************
     * ПОЛУЧЕНИЕ ДАННЫХ
     *************************************************************/

    /**
     * Ручная фильтрация произвольных входящих параметров перед запросом
     * @param $params array Список параметров
     * @param $query ActiveQuery Объект запроса
     * @param $newOutValues array Дополнительные переменные, которые надо отдать пользователю
     */
    private static function ParamsFilter(&$params,&$query,&$newOutValues) {

        # Ручная фильтрация параметров и применение их к запросу

    }

    /**
     * Ручная фильтрация полученных по запросу строк из таблицы перед
     * отправкой результатов
     * @param $items
     * @param $newOutValues array Дополнительные переменные, которые надо отдать пользователю
     * @return array Новый массив результатов
     */
    private static function PostFilter(&$items,&$newOutValues) {

        return $items;

    }

}
