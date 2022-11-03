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

    public $linkOptions = ['target' => '_blank'];

    public $imageOptions = ['class' => 'img-responsive'];

    /**
     * {@inheritdoc}
     */
    public function render($content = null)
    {
        $model = $this->model;

        $text = $model->getAttribute($this->attribute);
        $img = $model->renderThumbImage($this->attribute, true, $this->imageOptions);
        $url = $model->getUploadUrl($this->attribute);

        $this->parts['{input}'] = Html::tag('p', $text ? Html::a($img, $url, $this->linkOptions) : ' ', $this->inputOptions);

        return parent::render($content);
    }
}
