<?php

use yii\gii\generators\model\Generator;

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator yii\gii\generators\model\Generator */

$connection = Yii::$app->getDb();
$command = $connection->createCommand("SHOW TABLES");
$tables = $command->queryAll();


$items=[];
foreach($tables as $table) {
	$item=array_pop($table);
	$items[$item]=$item;
}

echo $form->field($generator, 'tableName')->checkboxList(
	$items,
	[
		'style' => 'display: flex; flex-direction: column;',
		'item' => function($index, $label, $name, $checked, $value) {
			$checked = $checked ? 'checked="checked"' : "";
			return "<label style='border-bottom:none; cursor: pointer; font-weight: 300'><input type=\"checkbox\" {$checked} name=\"{$name}\" value=\"{$value}\"> {$label}</label>";
		}
	]
);


echo $form->field($generator, 'generateRelations')->dropDownList([
    Generator::RELATIONS_NONE => 'Не создавать связи',
    Generator::RELATIONS_ALL => 'Все связи',
    Generator::RELATIONS_ALL_INVERSE => 'Все связи в обратном порядке',
]);

echo $form->field($generator, 'ns');



echo "<div style='display: none;'>";
echo $form->field($generator, 'generateLabelsFromComments')->checkbox();
echo $form->field($generator, 'baseClass');
echo $form->field($generator, 'db');
echo $form->field($generator, 'useTablePrefix')->checkbox();
echo $form->field($generator, 'generateQuery')->checkbox();
echo $form->field($generator, 'queryNs');
echo $form->field($generator, 'queryClass');
echo $form->field($generator, 'queryBaseClass');
echo $form->field($generator, 'enableI18N')->checkbox();
echo $form->field($generator, 'messageCategory');
echo $form->field($generator, 'useSchemaName')->checkbox();

echo "</div>";


