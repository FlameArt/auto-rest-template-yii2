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

$className = "Table".$className;

echo "<?php\n";
?>

namespace <?= $generator->ns ?>\models;

use Yii;

/**
 * Ссылки на расширенные версии таблиц
 */
<?php
$rel_arr = [];
foreach ($relations as $name => $relation) {
	$str = $generator->ns . '\\' . $relation[1];
	if(!isset($rel_arr[$str])) {
		echo 'use ' . $str . ";\n";
		$rel_arr[$str]=true;
	}
}
?>

use yii\helpers\ArrayHelper;
use yii\helpers\Json;



/**
 * This is the model class for table "<?= $generator->generateTableName($tableName) ?>".
 *
<?php foreach ($tableSchema->columns as $column): ?>
 * @property <?= "{$column->phpType} \${$column->name}\n" ?>
<?php endforeach; ?>
<?php if (!empty($relations)): ?>
 *
<?php foreach ($relations as $name => $relation): ?>
 * @property <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst($name) . "\n" ?>
<?php endforeach; ?>
<?php endif; ?>
 */
class <?= $className ?> extends <?= '\\' . ltrim($generator->baseClass, '\\') . "\n" ?>
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '<?= $generator->generateTableName($tableName) ?>';
    }
<?php if ($generator->db !== 'db'): ?>

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('<?= $generator->db ?>');
    }
<?php endif; ?>

    /**
     * Список всех полей ДБ с их оригинальными типами
     * @return array
     */
    public static function tableFields() {
        return [
<?php foreach ($tableSchema->columns as $column): ?>
            '<?=$column->name?>' => '<?=$column->type?>',
<?php endforeach; ?>
        ];
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [<?= "\n            " . implode(",\n            ", $rules) . ",\n        " ?>];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
<?php foreach ($labels as $name => $label): ?>
            <?= "'$name' => " . $generator->generateString($label) . ",\n" ?>
<?php endforeach; ?>
        ];
    }


    public function beforeValidate()
    {
        # Кодируем json обратно в текст, чтобы валидатор корректно принял его для записи в базу
        foreach ($this->tableFields() as $key=>$value){
            if(isset($this[$key]) && $value==='json' && !is_string($this[$key])) {
                $this[$key]=Json::encode($this[$key]);
            }
        }
        return parent::beforeValidate(); // TODO: Change the autogenerated stub
    }

<?php foreach ($relations as $name => $relation): ?>

    /**
     * @return \yii\db\ActiveQuery
     */
    public function get<?= $name ?>()
    {
        <?= $relation[0] . "\n" ?>
    }
<?php endforeach; ?>


<?php foreach ($full_relations as $name => $relation): ?>
    /**
    * Расширяемые колонки поля <?= $name ?>, которые находятся в другой таблице
    * будут заполнены при необходимости за 1 запрос и выведены в поле <?= $name ?>_
    */
    public <?php
    $full_rel_i = 0;
    foreach ($relation['columns'] as $column_name => $column_value) {
        $full_rel_i++;
        if($full_rel_i>1) echo ", ";
        echo '$__' . strtolower($name) ."__". $column_name;
    } ?>;
<?php endforeach; ?>

<?php foreach ($full_relations as $name => $relation): ?>
    /**
     * Компонуем все запрошенные через JOIN элементы поля <?=ucfirst($name)?> в массив, который выведет его
     * @return array
     */
    public function get<?=ucfirst($name)?>_(){

        $related_table = ('<?= $generator->ns .'\\'. $relation['tableModel'] ?>')::tableFields();
        $need_fields = (Yii::$app->controller)->extendFields;
        foreach (get_object_vars($this) as $key=>$value){
            if(strpos($key,"__<?=$name?>__")===0) {
                $rkey = substr($key, strpos($key, '__', 2) + 2);
                if($related_table[$rkey]==='json')
                    $related_fields[$rkey] = json_decode($value);
                else
                    $related_fields[$rkey] = $value;
            }
        }

        return $related_fields;

    }

<?php endforeach; ?>

    /**
     * Запрашиваем у движка дополнительные поля из выдачи, указанные в ExtraFields, которые должны быть выведены
     * @return array
     */
    public function fields()
    {
        $need_fields = (Yii::$app->controller)->extendFields;
        $arr = [];
        foreach ($need_fields as $field)
            $arr[]=$field."_";
        return array_merge(parent::fields(),$arr);
    }


<?php if ($queryClassName): ?>
<?php
    $queryClassFullName = ($generator->ns === $generator->queryNs) ? $queryClassName : '\\' . $generator->queryNs . '\\' . $queryClassName;
    echo "\n";
?>
    /**
     * @inheritdoc
     * @return <?= $queryClassFullName ?> the active query used by this AR class.
     */
    public static function find()
    {
        return new <?= $queryClassFullName ?>(get_called_class());
    }
<?php endif; ?>

	/**
	 * Вернуть всех потомков записи в виде дерева
	 *
	 * @param null $rootID 	Может принимать значения:
	 * 						NULL 				- вернуть всё дерево начиная с первого элемента для текущего пользователя
	 * 						ID элемента 		- вернуть дерево конкретного элемента
	 * 						Массив запроса [] 	- массив с запросом для поиска корневого элемента
	 * 						ActiveRecord 		- уже найденная запись, поиск не нужен
	 *
	 * @param null $depth глубина ветвей
	 * @param array $filters Массив фильтров в формате Yii ['!=','status','0']
     * @param array $mixtable Обогатить каждый элемент дерева этой таблицей [leftjoin для дерева, но быстрее]
     * @param array $mixtableConditions Условия поиска строк для обогащения. Формат: ['Параметр строки дерева'=>'Параметр строки таблицы'], например ['id'=>'task','user'=>'user']
     * @param string $mixPrefix Префикс для выходных обогащённых параметров
	 * @return array
	 */
	public static function getTree($rootID = null, $depth = null, &$filters = [], &$mixtable=[], &$mixtableConditions=[], &$mixPrefix = "joined") {

		// Ищем корневой элемент
		$root = [];

		// Корневой элемент не указан - ищем корень для пользователя
		if($rootID === NULL) {

			$root = self::findOne([
				'sort_m_path_depth' => 0,
				'user' => Yii::$app->user->id
			]);

		}
		// Массив запроса -> ищем по нему
		elseif(is_array($rootID)) {
			$root = self::findOne($rootID);
		}
		// Выслан целый объект
		elseif(is_object($rootID)) {
			$root = $rootID;
		}
		// Корневой элемент указан по ID
		else {
			$root = self::findOne($rootID);
		}

		// Не найден корневой элемент пользователя - ошибка
		if ($root==NULL) return [];

		// Найден - строим для элемента дерево
		$tree = $root -> populateTree ($depth)->children;

		// Рекурсивно перебираем дерево, чтобы вытащить детей из relations в массив, который можно отдать через JSON
        // А также обогощаем его инфой пользователя
		$arrTree = self::getTreeArray($tree,$filters,$mixtable,$mixtableConditions,$mixPrefix);

		// Возвращаем структуированный результат
		return [
			'tree' => $arrTree,
			'rootid' => $root->id
		];

	}

	/**
	 * Получить дерево как массив с детьми
	 * На выходе получаем обычный массив, т.к. ассоциативный не держит порядок элементов
	 * @param $data
	 * @param $filters array Массив фильтров в формате Yii ['!=','status','0']
     * @param $mixtable array Данные для обогащения
     * @param $mixtableConditions array Условия поиска строки для обогащения
     * @param $mixPrefix string Префикс для выходных обогащённых параметров
	 * @return array
	 */
	public static function getTreeArray(&$data,&$filters,&$mixtable, &$mixtableConditions, &$mixPrefix) {

		// Перебираем элементы объекта
		$done_arr=[];
		foreach($data as $item) {

			// Получаем основное значение
			$this_arr = ArrayHelper::toArray($item->attributes);

            // Удаляем колонки сортировки, кроме уровня вложенности (он может пригодится)
            unset($this_arr['sort_m_path']);
            unset($this_arr['sort_m_path_sort']);

			// Проводим его через фильтр
			if(self::filterTreeArray($this_arr,$filters)===false) continue;

			// Обогащаем данные
            if(count($mixtable)>0)
                self::enrichmentTreeElement($this_arr,$mixtable,$mixtableConditions,$mixPrefix);

			// Получаем детей
			if(isset($item['children']))
				$this_arr['children'] =self::getTreeArray($item['children'],$filters,$mixtable,$mixtableConditions,$mixPrefix);

			// Добавляем результат в выходной массив
			$done_arr[] = $this_arr;

		}

		// Возвращаем итоговый массив
		return $done_arr;

	}

	/**
	 * Фильтрация данных дерева
	 * @param $item
	 * @param $filters array Массив фильтров в формате Yii ['!=','status','0']
	 * @return boolean
	 */
	private static function filterTreeArray($item,$filters) {

		foreach($filters as $filter) {

			switch($filter[0]) {
				case '!=': {
					if($item[$filter[1]]!=$filter[2]) break;
					return false;
				}
				case '>': {
					if((int)$item[$filter[1]]>(int)$filter[2]) break;
					return false;
				}
				case '<': {
					if((int)$item[$filter[1]]<(int)$filter[2]) break;
					return false;
				}
			}

		}

		return true;

	}


    /**
     * Обогатить строку дерева данными другой таблицы [быстрый аналог leftjoin для деревьев]
     * @param $row
     * @param $mixtable
     * @param $mixtableConditions
     * @param $mixname
     * @return array;
     */
	public static function enrichmentTreeElement( &$row , &$mixtable , &$mixtableConditions, &$mixPrefix ) {

	    // Перебираем все строки таблицы обогащения [оптимизированным способом под PHP7]
        for($i=0;$i<count($mixtable);$i++) {

            // Перебираем все условия поиска
            $finded = true;
            foreach ($mixtableConditions as $key=>$item) {

                // Ключ не совпадает - пропускаем всю строку
                if(!($mixtable[$i][$key]==$item || $mixtable[$i][$key]==$row[$item])) {
                    $finded=false;
                }

            }

            # После перебора всех значений, если совпадение по всем параметрам - заполняем значениями из микс таблицы
            if($finded) {

                foreach ($mixtable[$i] as $attr=>$value) {
                    $row[$mixPrefix.$attr]=$value;
                }

                # Выходим из поиска, по одному совпадению на строку
                return $row;

            }

        }

        # Цикл не нашёл значения для обогащения, заполняем атрибуты пустыми значениями (берём с первого элемента)
        foreach ($mixtable[0] as $attr=>$value) {
            $row[$mixPrefix.$attr]=NULL;
        }

        return $row;

    }


	/**
	 * Вывести элементы в виде списка, т.е. пар значений id:name
	 * Без указания параметров - выводится вся таблица
	 *
	 * @param null $arrayORrootelement 	Может быть массивом объектов ActiveRecord или
	 * 									рутовым элементом, детей которого надо получить и вернуть
	 * @return array
	 */
	public static function getList($arrayORrootelement = null) {

		// Вычисляем тип элемента
		$items=[];

		// Готовый массив -> сразу переходим к обработке
		if(is_array($arrayORrootelement)) {
			$items = $arrayORrootelement;
		}

		// Не задан -> выбираем элементы всей таблицы
		elseif($arrayORrootelement==NULL) {
			$items = self::find()->all();
		}

		// ID рутового элемента -> выбираем всех детей
		elseif(is_numeric($arrayORrootelement)) {
			$root = self::findOne((int)$arrayORrootelement);
			if($root==NULL) return [];
			$items = $root->getDescendants(1)->all();
		}

		// Неопознанный тип
		else {
			return [];
		}

		// перебираем элементы
		$list = [];
		foreach($items as $item) {
			$list[]=$item['name'];
		}

		// возвращаем элементы
		return $list;

	}

    /**
     * Получить в виде массива объектов, где ключи массива - ID элементов, а его значения - объекты ActiveRecord
     * @param array $where Фильтр для выборки
     * @return array
     */
	public static function getArray($where=[]) {

	    # Ищем все элементы по фильтру
	    $items=self::find()->where($where)->all();

	    # Выходной массив
        $data=[];

        # Сопоставляем id и объекты
	    foreach ($items as $item) {
	        $data[(int)$item->id]=$item;
        }

        # Возвращаем результат
        return $data;

    }

}
