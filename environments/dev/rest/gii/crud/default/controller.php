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

// Определяем: есть ли поле user, которое надо заполнять
$USER_FILL = false;
foreach ($tableSchema->columns as $column){
	if(strtolower($column->name) == "user") {
		$USER_FILL = true;
		break;
	}
}


echo "<?php\n";
?>

namespace <?= $path."\\api\\base" ?>;


// Базовый импорт
use Yii,yii\web\Controller,yii\web\Response,yii\db\ActiveQuery,yii\filters\AccessControl,yii\filters\ContentNegotiator,yii\filters\VerbFilter;

// Импорт модели таблицы <?=ucfirst($tableclass)?>

use common\models\DB\<?=$tableclass?>;


/**
 * API управления таблицей <?=ucfirst($tableclass)?>
 */
class <?=ucfirst($controllerClass)?>Controller extends Controller
{
    /**
     * Поведения
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [

                    // Действия, для которых нужна авторизация
                    [
                        'actions' => ['create','update','delete','moveto','get','getmany','getall','gettree','addorupdate'],
                        'allow' => true,
                        'roles' => ['@']
                    ],

                    // Действия, разрешённые без авторизации
                    [
                        'actions' => [],
                        'allow' => true,
                        'roles' => ['?']
                    ],

                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'create' => ['POST'],
                    'update' => ['POST'],
                    'addorupdate' => ['POST'],
                    'delete' => ['POST'],
					'moveto' => ['POST'],
                    'get' => ['POST','GET'],
                    'getmany' => ['POST','GET'],
                    'getall' => ['POST','GET'],
					'gettree' => ['POST','GET'],
                ],
            ],

            // JSON формат вывода
            'contentNegotiator' => [
                'class' => ContentNegotiator::className(),
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ]
        ];
    }

    /**
     * Расширенные действия
     * @return array
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Отключаем CSRF для API
     * @param \yii\base\Action $action
     * @return bool
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }


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

    /**
     * Получить несколько записей из таблицы по запросу
     * Входящий POST параметр: params (массив)
     * @var $params array Входящие параметры указаны явно, используются вместо параметров из POST запроса
     * @return array
     */
    public function actionGetmany($params = NULL) {

        # Получаем переменные
        $params = $params === NULL ? Yii::$app->request->getBodyParams() : $params;

<?php if($USER_FILL):?>
        // Заполняем пользователя, чтобы к записи не имели доступа посторонние
        $params['user'] = Yii::$app->user->id;
<?php endif;?>


        # Создаём запрос
        $query = <?=$tableclass?>::find();

        # Дополнительные параметры, которые надо включить в ответ пользователю
        $newOutValues = [];

        # Делаем предварительный ручной фильтр особых входящих параметров
        # Всё передаётся и изменяется по ссылке
        self::ParamsFilter($params,$query,$newOutValues);

        # Объект без sql-фильтров, чтобы посчитать общее число
        # результатов, когда очистим params, чтобы в оценки их числа
        # все остальные фильтры были применены
        $cleanQuery = clone $query;

        # Проверка на наличие страниц
        $page=false;
        $pageSize=false;

        # Указана страница и число страниц
        if(isset($params['page']) && isset($params['pageSize'])) {
            $page = (int)$params['page'];
            $pageSize = (int)$params['pageSize'];
            unset($params['page']); # удаляем переменную из поиска
            unset($params['pageSize']); # удаляем переменную из поиска
        }

        # Указана только страница: устанавливаем базовый показатель числа объектов на странице в 20
        elseif (isset($params['page'])) {
            $page = (int)$params['page'];
            $pageSize = 20;
            unset($params['page']); # удаляем переменную из поиска
        }

        # Указано только число объектов на страницу: устанавливаем страницу на первую
        elseif(isset($params['pageSize'])) {
            $page = 1;
            $pageSize = (int)$params['pageSize'];
            unset($params['pageSize']); # удаляем переменную из поиска
        }

        # Если есть страницы - делаем по ним запрос
        if($page!==false && $pageSize!==false) {
            $query  -> offset(($page-1)*$pageSize)
                -> limit($pageSize);
        }


        # Указано поле для сортировки
        $sortfield='id';
        if(isset($params['sortby'])) {
            $sortfield=$params['sortby'];
            unset($params['sortby']); # удаляем переменную из поиска
        }

        # Указан формат сортировки
        if(isset($params['sort'])) {
            switch (strtolower($params['sort'])) {
                case 'asc': {
                    $query->orderBy([$sortfield=>SORT_ASC]);
                    break;
                }
                case 'desc': {}
                default:{
                    $query->orderBy([$sortfield=>SORT_DESC]);
                    break;
                }
            }
            unset($params['sort']); # удаляем переменную из поиска
        }

        # Явно указан limit
        if(isset($params['limit'])) {
            $query->limit((int)$params['limit']);
            unset($params['limit']);
        }


        # Если была постраничная разбивка, считаем общее число результатов
        $itemsTotal=false;
        if($page!==false && $pageSize!==false) {
            $itemsTotal = $cleanQuery->where($params)->count();
        }

        # Ищем запись
        $items = $query
                ->where($params)
                ->asArray()
                ->all();

        # Записи не найдено
        if($items==NULL) {
            return [
                'result' => 'success',
                'data' => []
            ];
        }

        # Применяем пост-фильтр к выводу и получаем результат по ссылке
        # Если пост-фильтра не было, это гарантирует быстрый возврат управления
        # без расхода доп памяти на копирование
        $items = &self::PostFilter($items,$newOutValues);

        # Подготавливаем выходной массив
        $out = [
            'result' => 'success',
            'data' => $items
        ];

        # Включаем в вывод дополнительные переменные, определённые ранее
        $out += $newOutValues;

        # Если была постраничная разбивка - указываем её параметры
        if($page!==false && $pageSize!==false) {
            $out += [
                'page' => $page,         # Страница
                'pageSize' => $pageSize, # Число результатов на странице
                'total' => $itemsTotal,  # Общее число элементов на всех страницах
                'pagesTotal' => floor($itemsTotal/$pageSize)-1, # Общее кол-во страниц
            ];
        }

        # Отдаём найденный список
        return $out;

    }

    /*************************************************************
     * РЕДАКТИРОВАНИЕ ТАБЛИЦЫ
     *************************************************************/


    /**
     * Добавить новую запись
     */
    public function actionCreate() {

        // Создаём новую запись
        $item = new <?=$tableclass?>();

        // Параметры загрузки
        $load_params = Yii::$app->request->post();
        $append_param = null;

        // Исключаем из загрузки лишние параметры
        if(isset($load_params['appendTo'])) {
            $append_param = $load_params['appendTo'];
            unset($load_params['appendTo']);
        }
        if(isset($load_params['id'])) {
            unset($load_params['id']);
        }

        // Загружаем в неё исходные данные
        if(!$item->load($load_params,'')) {
            return [
                'err' => true,
                'text' => 'Ошибка при загрузке данных'
            ];
        }

        // Если нужно присоединить к дереву
        if($append_param!==null) {

            // родитель
            $parent = <?=$tableclass?>::findOne($append_param);

            // родителя нет
            if($parent==NULL) {
                return [
                    'err' => true,
                    'text' => "Не найдена родительская запись"
                ];
            }

            // присоединяем
            $item->appendTo($parent);

        }

        // Уточнение параметров

        // Сохраняем в базу
        if($item->save(true)) {

            $item = <?=$tableclass?>::findOne(['id'=>$item->id]);

            // Успех: возвращаем результирующую строку
            return [
                'result' => 'success',
                'data' => $item,
                'parent' => isset($parent) ? $parent->id : 0
            ];

        }
        else {

            // Ошибка в валидации данных: выводим список ошибок
            return [
                'err' => true,
                'text' => implode("<br>",$item->getErrors())
            ];

        }

    }

    /**
     * Изменить существующую запись
     * $id
     * @return array
     */
    public function actionUpdate() {

        // Получаем переменные
        $id = Yii::$app->request->post('id');


        // Ищем запись
        $item = <?=$tableclass?>::findOne($id);

        // Записи не найдено
        if($item==NULL) {
            return [
                'err' => true,
                'text' => "Запись #".$id." не найдена"
            ];
        }

		// Загружаем в неё исходные данные
		if(!$item->load(Yii::$app->request->post(),'')) {
			return [
				'err' => true,
				'text' => 'Ошибка при загрузке данных'
			];
		}

		// Уточнение параметров


		// Сохраняем в базу
		if($item->save(true)) {

            // Успех: возвращаем результирующую строку
            return [
                'result' => 'success',
                'data' => $item
            ];

        }
        else {

            // Ошибка в валидации данных: выводим список ошибок
            return [
                'err' => true,
                'text' => $item->getErrors()
            ];

        }

    }


    /**
     * Добавить новую запись или обновить существующую
     */
    public function actionAddorupdate() {

        // Получаем переменные
        $id = Yii::$app->request->post('id');


        // Ищем запись
        $item = <?=$tableclass?>::findOne($id);

        // Записи не найдено - создаём новую, или обновляем существующую
        if($item==NULL)
            return $this->actionCreate();
        else
            return $this->actionUpdate();

    }

    /**
     * Удалить запись
     * $id
     * @return array
     */
    public function actionDelete() {

        // Получаем переменные
        $id = Yii::$app->request->post('id');


        // Ищем запись
        $item = <?=$tableclass?>::findOne($id);

        // Записи не найдено
        if($item==NULL) {
            return [
                'err' => true,
                'text' => "Запись #".$id." не найдена"
            ];
        }

        // Удаляем запись
        try {

            if($item->delete()>0) {
                return [
                    'result' => 'success',
                ];
            }

        } catch (\Exception $e) {

            return [
                'err' => true,
                'text' => "Не удалось удалить: " . $e->getMessage()
            ];

        }

        return [
            'err' => true,
            'text' => "Запись #".$id." не удалось удалить"
        ];

    }

    /**
     * Переместить элемент в позицию после другого элемента в дереве
     * $id integer           ID исходной записи, которую надо переместить
     * $mode string          Метод, которым управлять положением записи
     * $parent integer       ID Родительского элемента
     * $modeposition integer ID элемента до которого или после которого вставлять исходный
     * @return array
     */
    public function actionMoveto() {

        // заполняем переменные
        list($id,$mode,$parent,$modeposition)=[
            Yii::$app->request->post('id'),
            Yii::$app->request->post('mode'),
            Yii::$app->request->post('parent'),
            Yii::$app->request->post('modeposition'),
        ];

        // Исходный элемент
        $item = <?=$tableclass?>::findOne($id);

        // Не найден
        if($item == NULL) {
            return [
                'err' => true,
                'text' => "Не найдена запись"
            ];
        }

        // Родитель
        if($mode == 'append') {
            $parent_item = <?=$tableclass?>::findOne($parent);

            // не найден
            if($parent_item == NULL) {
                return [
                    'err' => true,
                    'text' => 'Не найдена родительская запись'
                ];
            }
        }

        // Элемент, после которого вставлять
        if($mode=='after' || $mode=='before') {
            $mode_element = <?=$tableclass?>::findOne($modeposition);

            // Не найден
            if ($mode_element == NULL) {
                return [
                    'err' => true,
                    'text' => "Не найден элемент после которого вставлять"
                ];
            }
        }

        // Определение типа позиции вставки
        switch($mode) {

            // Вставить после записи
            case 'after': {
                $item->insertAfter($mode_element);
                break;
            }

            // Вставить перед записью
            case 'before': {
                $item->insertBefore($mode_element);
                break;
            }

            // Добавить к определённому родителю
            case 'append': {
                $item->appendTo($parent_item);
                break;
            }
            default: {
                return [
                    'err' => true,
                    'text' => 'Ошибка перемещения записи: неизвестный тип перемещения'
                ];
            }
        }

        // Сохраняем
        if(!$item->save()) {
            return [
                'err' => true,
                'text' => 'Ошибка перемещения записи: не удалось сохранить позицию'
            ];
        }

        // Возвращаем успешное выполнение
        return [
            'result' => 'success'
        ];

    }


    /**
     * Получить запись из таблицы
     * $id
     * @return array
     */
    public function actionGet() {

        // Получаем переменные
        $id = Yii::$app->request->post('id');

<?php if($USER_FILL):?>
        // Заполняем пользователя, чтобы к записи не имели доступа посторонние
        $params['user'] = Yii::$app->user->id;
<?php endif;?>
        $params['id'] = $id;

        // Ищем запись
        $item = <?=$tableclass?>::findOne($params);

        // Записи не найдено
        if($item==NULL) {
            return [
                'err' => true,
                'text' => "Запись #".$id." не найдена"
            ];
        }

        // Возвращаем
        return [
            'result' => 'success',
            'data' => $item
        ];

    }

    /**
	* Получить все записи таблицы
	*/
	public function actionGetall()
	{

		$params=[];

<?php if($USER_FILL):?>
        // Заполняем пользователя, чтобы к записи не имели доступа посторонние
        $params['user'] = Yii::$app->user->id;
<?php endif;?>

        // Ищем все записи
        $items = $this->actionGetmany($params);

        // Возвращаем результат
        return $items;

	}

    /**
     * Получить дерево
     */
    public function actionGettree() {

        // Получаем переменные
        $params = Yii::$app->request->getBodyParams();

<?php if($USER_FILL):?>
        // Заполняем пользователя, чтобы к записи не имели доступа посторонние
        $params['user'] = Yii::$app->user->id;
<?php endif;?>

        // Ищем запись
        return <?=$tableclass?>::getTree($params);

    }



}
