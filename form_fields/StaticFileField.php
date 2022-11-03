<?php

namespace ereminmdev\yii2\cropperimageupload\form_fields;

use yii\helpers\Html;
use yii\widgets\ActiveField;

/**
 * Render form field:
 *   $form->field($model, 'field', ['class' => StaticFileField::class])
 */
class StaticFileField extends ActiveField
{
    public $inputOptions = ['class' => 'img-responsive'];

    public $linkOptions = ['target' => '_blank'];

    /**
     * {@inheritdoc}
     */
    public function render($content = null)
    {
        $model = $this->model;

        $text = $model->getAttribute($this->attribute);
        $url = $model->getUploadUrl($this->attribute);

        $this->parts['{input}'] = Html::tag('p', Html::a($text ?: ' ', $url, $this->linkOptions), $this->inputOptions);

        return parent::render($content);
    }
}
