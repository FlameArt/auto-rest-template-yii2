<?php
/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator yii\gii\generators\crud\Generator */

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


echo "<div style='display: none;'>";
echo $form->field($generator, 'controllerClass');
echo "</div>";