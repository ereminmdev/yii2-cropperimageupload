<?php

namespace ereminmdev\yii2\cropperimageupload\form_fields;

use yii\helpers\Html;
use yii\widgets\ActiveField;

/**
 * Render form field:
 *   $form->field($model, 'field', ['class' => StaticImageField::class])
 */
class StaticImageField extends ActiveField
{
    public $inputOptions = [];

    /**
     * {@inheritdoc}
     */
    public function render($content = null)
    {
        $model = $this->model;

        $text = $model->getAttribute($this->attribute);
        $img = $model->renderThumbImage($this->attribute);
        $url = $model->getUploadUrl($this->attribute);

        $this->parts['{input}'] = Html::tag('p', $text ? Html::a($img, $url, ['target' => '_blank']) : ' ', $this->inputOptions);

        return parent::render($content);
    }
}
