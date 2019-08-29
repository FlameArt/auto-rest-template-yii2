<?php
namespace rest\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use yii\filters\ContentNegotiator;
use yii\web\Response;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['error', 'index', 'crudschema'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['index','crudschema'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
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
     * {@inheritdoc}
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
     * Displays homepage.
     *
     */
    public function actionIndex()
    {
        return [
            "Welcome"=> "Welcome!"
        ];
    }

    /**
     * Получить схему таблиц и их полей для Crud`a
     */
    public function actionCrudschema()
    {

        // сканируем все файлы в директории DB
        // $files = scandir(Yii::getAlias('@app') . "/../common/models/DB/");
        $files = scandir(Yii::getAlias('@app') . "/controllers/api");

        // загружаем таблицы
        $tables = [];
        foreach ($files as $file) {

            // Получаем класс контроллера
            if (!is_file(Yii::getAlias('@app') . "/controllers/api/" . $file)) continue;
            $controllerClassName = '\\rest\\controllers\\api\\' . str_replace(".php", "", $file);
            $controllerClassPath = Yii::getAlias('@app') . "/controllers/api/" . $file;
            if (!class_exists($controllerClassName))
                include($controllerClassPath);
            $controllerClass = new $controllerClassName($controllerClassName, $controllerClassName);
            $thisModelClass = $controllerClass->modelClass;

            // Создаём класс из названия в контроллере
            $class = new $thisModelClass();

            // Компонуем информацию о таблице и её полях
            $table = [

                // имя таблицы
                'name' => $class::tableName(),

                // поля
                'fields' => []
            ];

            // Получаем правила для элементов
            $rules = $class::rules();

            // Проходимся по полям таблицы
            foreach ($class::attributeLabels() as $name => $desc) {

                // Добавляем поле
                $field = [
                    'name' => $name,
                    'comment' => $desc,
                    'type' => 'integer',
                    'max_symbols' => null,
                    'required' => false,
                    'linkedto' => null,
                ];

                // Проходимся по правилам для этого поля
                foreach ($rules as $rule)

                    // В нулевом элементе - массив всех полей правила, ищем там текущее поле
                    if (in_array($name, $rule[0])) {

                        // Далее по типам правил: если строка или число - вводим это в тип с параметрами длины
                        if ($rule[1] === 'string' || $rule[1] === 'integer' || $rule[1] === 'boolean' || $rule[1] === 'number' || $rule[1] === 'safe') {
                            $field['type'] = $rule[1];
                            if($field['type']==='number') $field['type'] = 'double';
                            if($field['type']==='safe') $field['type'] = 'timestamp';
                            if (isset($rule['max']))
                                $field['max_symbols'] = $rule['max'];
                        }

                        // Если этот элемент обязателен
                        if ($rule[1] === 'required')
                            $field['required'] = true;

                        // Если это поле - зависимое от другой таблицы, связанное
                        if ($rule[1] === 'exist') {

                            // Вставляем зависимый класс, если ещё не был вставлен
                            if (!class_exists($rule['targetClass']))
                                include($rule['targetClass']);

                            $linkedClass = new $rule['targetClass']();

                            $field['linkedto'] = [
                                'table' => $linkedClass::tableName(),
                                'field' => current($rule['targetAttribute'])
                            ];

                        }

                    }

                $table['fields'][] = $field;

            }


            $tables[] = $table;

        }

        return $tables;
    }


}
