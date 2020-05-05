<?php

namespace rest\controllers;

// Imports
use yii\base\Model;
use yii\helpers\Json;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use \yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use yii\db\Expression;




/**
 * REST API Controller Objects */
class ActiveRestController extends ActiveController
{

    /**
     * @var array Поля, которые не надо выводить при запросе
     */
    public $filterFields = [];

    /**
     * @var array Поля, которые надо расширить
     */
    public $extendFields = [];

    /**
     * Добавляем к стандартным собственный обработчик
     * @return array
     */
    public function actions()
    {
        $actions = parent::actions();
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        return $actions;
    }

    /**
     * Добавляем возможность запостить json в index action
     * @return array
     */
    protected function verbs()
    {
        $verbs = parent::verbs();
        $verbs['index'] = ['GET', 'HEAD', 'POST'];
        return $verbs;
    }

    public function prepareDataProvider()
    {

        // Отдаём заголовки, чтобы можно было принять паджинацию
        header("Access-Control-Expose-Headers: X-Pagination-Current-Page,X-Pagination-Per-Page,X-Pagination-Page-Count,X-Pagination-Total-Count");

        // Данные
        $data = Json::decode(\Yii::$app->request->getRawBody(), true);

        /**
         * Экземпляр модели
         * @var $DBModel ActiveRecord
         */
        $ModelClass = ($this->modelClass);
        $DBModel = new $ModelClass();

        /**
         * Создаём новый поиск
         * @var $DB ActiveQuery
         */
        $DB = $DBModel::find();

        // Список полей
        $DBFields = $DBModel::tableFields();

        // Сортировка
        if(isset($data['sort'])) {
            // Спилитим несколько параметров
            $tsort = $data['sort'];
            if(is_string($tsort)) $tsort = explode(',', $tsort);

            foreach ($tsort as $sortitem) {
                if (substr($sortitem, 0, 1) === '-')
                    $DB->addOrderBy([substr($sortitem, 1) => SORT_DESC]);
                else
                    $DB->addOrderBy([ $sortitem => SORT_ASC]);
            }
        }

        // Удалить дубликаты, но нужно указывать одно поле в Fields
        if(isset($data['RemoveDuplicates']))
            $DB->distinct();

        // Паджинация
        $pagination = [];
        if(isset($data['page']))
            $pagination['page'] = (int)($data['page'])-1;
        if(isset($data['per-page']))
            $pagination['pageSize'] = (int)$data['per-page'];

        // Указываем отдельные поля
        if(isset($data['fields']))
            $DB->addSelect($data['fields']);
        else
            // По-умолчанию получаем все столбцы
            $DB->addSelect([\Yii::$app->db->quoteColumnName($DBModel::tableName()).".*"]);

        // Объединяем extends поля в контроллере с полями в запросе
        // [можно указывать даже просто строку, как интуитивно понятно]
        $expand_fields = []; // $this->extendFields;
        if(isset($data['expand'])) $expand_fields = $data['expand'];
        if(is_string($expand_fields)) $expand_fields = [$expand_fields];
        if(is_array($expand_fields)) $this->extendFields = array_unique(array_merge($expand_fields,$this->extendFields));


        // Указываем фильтры
        if(isset($data['where']))

            foreach ($data['where'] as $key=>$value) {

                // Поиск по обычному полю
                if(!array_key_exists($key, $DBFields) || $DBFields[$key] !== 'json') {

                    // Если есть extend поля, то при отсутствии указания на конкретную таблицу, нужно её указать, чтобы избежать перекрёстных where условий
                    if(count($this->extendFields)>0) {
                        if(strpos($key, ".")===false)
                            $key = $DBModel::tableName() . "." . $key;
                    }

                    // Массивные значения добавляем как условия, т.к. это может быть типа LIKE или NOT IN
                    if (is_array($value))
                        $DB->andWhere($value);
                    else
                        $DB->andWhere([$key => $value]);



                }

                else {

                    // Поиск по JSON-полю
                    if(is_array($value)===false || count($value)===0) continue;

                    // Генерим условие ИЛИ для каждого элемента
                    // Совместимо с MySQL 5.7, в 8.0 можно использовать супербыстрый оператор MEMBER OF()
                    $OR = null;
                    foreach ($value as $item) {
                        if(is_string($item))
                            $OR[] = new Expression("JSON_CONTAINS(" . \Yii::$app->db->quoteColumnName($key) . ",\"" . str_replace("\'", "", \Yii::$app->db->quoteValue($item) . "\")"));
                        else
                            $OR[] = new Expression("JSON_CONTAINS(" . \Yii::$app->db->quoteColumnName($key) . "," . \Yii::$app->db->quoteValue(json_encode($item)) . ")");
                    }

                    // Склеиваем предыдущие условия AND через внутренний OR
                    $DB->andWhere(array_merge(['OR'],$OR));

                }

            }

        // Далее получаем все поля, которые надо получить из других таблиц
        // PERFORMANCE: использован самый быстрый способ получения - через 1 LEFT JOIN запрос сразу для всех записей
        //              такой суммарно даёт 8 запросов против 14 для ->with, и для (15+число записей) для expand
        //              данные затем распределяются по массивам в TableModel для каждой таблицы и выводятся в поле extra


        // Делаем механику экстра-полей
        foreach ($this->extendFields as $field) {

            // Находим модель зависимого класса по имени поля, чтобы из него получить имя таблицы
            foreach ($DBModel->rules() as $rule) {

                if($rule[0][0]===$field && isset($rule['targetClass'])) {

                    // Класс найден - вписываем найденную таблицу в LEFT JOIN
                    $DB->leftJoin(($rule['targetClass'])::tableName(),
                        ($rule['targetClass'])::tableName() . "." . $rule['targetAttribute'][$field] .
                        ' = ' . $DBModel::tableName() . "." . $field);

                    // Записываем все поля таблицы для вывода
                    foreach (($rule['targetClass'])::tableFields() as $fieldName=>$fieldValue)
                        $DB->addSelect(['__'.$field."__".$fieldName => ($rule['targetClass'])::tableName() . "." . $fieldName]);

                }

            }

        }

        // Добавляем в модель специфические обработчики, если нужно
        $DB = $this->ExtendQuery($DB, $data);

        // Отдаём ActiveDataProvider, который поддерживает авто-пагинацию и сортировку
        return new ActiveDataProvider([
            'query' => $DB,
            'pagination' => $pagination
        ]);
    }

    /**
     * Расширить поиск по модели
     * @param $model ActiveQuery
     * @param $data array json-запрос, который был получен
     * @return ActiveQuery
     */
    public function ExtendQuery($model, $data)
    {
        return $model;
    }

}