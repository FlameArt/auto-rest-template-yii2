<?php

namespace rest\controllers;

// Imports
use yii\helpers\Json;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use \yii\db\ActiveRecord;


/**
 * REST API Controller Objects */
class ActiveRestController extends ActiveController
{

    /**
     * @var array Поля, которые не надо выводить при запросе
     */
    public $filterFields = [];

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

        // Создаём новый поиск
        $DB = ($this->modelClass)::find();

        // Сортировка, пагинация работают автоматически

        // Указываем отдельные поля
        if(isset($data['fields']))
            $DB->select($data['fields']);

        // Указываем фильтры
        if(isset($data['where']))
            foreach ($data['where'] as $key=>$value)
                $DB->andWhere([$key=>$value]);

        // Отдаём ActiveDataProvider, который поддерживает авто-пагинацию и сортировку
        return new ActiveDataProvider([
            'query' => $DB,
        ]);
    }

}