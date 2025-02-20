<?php

namespace ereminmdev\yii2\cropperimageupload;

use Yii;
use yii\bootstrap\Modal;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;

/**
 * Class cropper_image_upload
 * @package common\widgets\cropper_image_upload
 */
class CropperImageUploadWidget extends InputWidget
{
    /**
     * @var bool use cropper
     */
    public $crop = true;
    /**
     * @var float crop aspectRatio (width/height). By default, the crop box is free ratio.
     */
    public $cropAspectRatio;
    /**
     * @var string bs modal window selector
     * If not set, will be created Modal::widget().
     */
    public $modalSel;
    /**
     * @var string attribute name storing crop value or crop value itself if no model
     * if not set, will be the same as $attribute
     */
    public $cropField;
    /**
     * @var string resulting image selector
     */
    public $resultImageSel = '.img-result';
    /**
     * @var array the options for the Cropper plugin.
     * @see https://github.com/fengyuanchen/cropperjs/blob/master/README.md#options
     */
    public $cropperOptions = [];
    /**
     * @var array of options for cropper result method
     * - 'type' => 'image/jpeg' image MIME type with no parameters
     * - 'encoderOptions' => .92 number in the range 0.0 to 1.0, desired quality level
     * - 'background' => 'white' color|gradient|pattern (https://www.w3schools.com/tags/canvas_fillstyle.asp)
     */
    public $cropperResultOpts = [];
    /**
     * @var array the options for the $.fn.cropperImageUpload plugin.
     */
    public $clientOptions = [];
    /**
     * @var string to render $parts into html string
     */
    public $template = "{crop_input}\n{input}";
    /**
     * @var array different parts of the input. This will be used together with
     * [[template]] to generate the final field HTML code. The keys are the token names in [[template]],
     * while the values are the corresponding HTML code. Valid tokens include `{input}` and `{crop_input}`.
     * Note that you normally don't need to access this property directly as
     * it is maintained by various methods of this class.
     */
    public $parts = [];
    /**
     * @var string class name added to field block
     */
    public $containerClass = 'field-cropper-image-upload';

    protected $crop_id;


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        Html::addCssClass($this->field->options, $this->containerClass);

        if ($this->cropField === null) {
            $this->cropField = $this->attribute;
        }

        if ($this->hasModel()) {
            $model = $this->model;
            $behavior = $model->hasMethod('findCropperBehavior') ? $model->findCropperBehavior($this->attribute) : null;
            if ($behavior !== null) {
                $this->crop = $behavior->crop;
                $this->cropAspectRatio = $behavior->cropAspectRatio;
                $this->cropperOptions = ArrayHelper::merge($this->cropperOptions, $behavior->cropperOptions);
                $this->cropperResultOpts = ArrayHelper::merge($this->cropperResultOpts, $behavior->cropperResultOpts);
            }
        }

        $form = $this->field->form;
        if (!isset($form->options['enctype'])) {
            $form->options['enctype'] = 'multipart/form-data';
        }

        $this->options['accept'] = 'image/*';
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        echo $this->renderTemplate();

        if ($this->modalSel === null) {
            $this->modalSel = '#' . $this->options['id'] . '_modal';
            echo Modal::widget([
                'options' => [
                    'id' => $this->options['id'] . '_modal',
                ],
            ]);
        }

        $options = ArrayHelper::merge([
            'aspectRatio' => $this->cropAspectRatio,
            'modalSel' => $this->modalSel,
            'containerSel' => '.' . $this->containerClass,
            'cropInputSel' => '#' . $this->options['id'] . '_crop',
            'resultImageSel' => $this->resultImageSel,
            'btnSaveText' => Yii::t('app', 'Ok'),
            'btnCancelText' => Yii::t('app', 'Cancel'),
            'cropperOptions' => $this->cropperOptions,
            'cropperResultOpts' => $this->cropperResultOpts,
        ], $this->clientOptions);

        $this->registerPlugin($options);
    }

    /**
     * @param string|callable $content the content within the field container.
     * @return string the rendering result.
     */
    public function renderTemplate($content = null)
    {
        if ($content === null) {
            if (!isset($this->parts['{input}'])) {
                $this->parts['{input}'] = $this->renderInput();
            }
            if (!isset($this->parts['{crop_input}'])) {
                $this->parts['{crop_input}'] = $this->renderCropInput();
            }
            $content = strtr($this->template, $this->parts);

        } elseif (!is_string($content)) {
            $content = call_user_func($content, $this);
        }

        return $content;
    }

    /**
     * @return string
     */
    public function renderInput()
    {
        if ($this->hasModel()) {
            return Html::activeInput('file', $this->model, $this->attribute, $this->options);
        } else {
            return Html::fileInput($this->name, $this->value, $this->options);
        }
    }

    /**
     * @return string
     */
    public function renderCropInput()
    {
        $options = [
            'id' => $this->options['id'] . '_crop',
        ];

        if ($this->cropField) {
            if ($this->hasModel()) {
                return Html::activeHiddenInput($this->model, $this->cropField, $options);
            } else {
                return Html::hiddenInput($this->cropField, $options);
            }
        }

        return '';
    }

    /**
     * @param array $options
     */
    protected function registerPlugin($options)
    {
        $view = $this->getView();

        if ($this->crop) {
            CropperImageUploadAsset::register($view);
            $view->registerJs('jQuery("#' . $this->options['id'] . '").cropperImageUpload(' . Json::encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT) . ');');
        }
    }
}
