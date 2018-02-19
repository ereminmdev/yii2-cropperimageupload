# yii2-cropperimageupload

Crop image upload for Yii framework.

This widget depend on:
- https://github.com/mohorev/yii2-upload-behavior
- https://github.com/fengyuanchen/cropperjs

## Install

``composer require ereminmdev/yii-cropperimageupload``

## Use

```
public function behaviors()
{
    return [
        ...
        'avatar' => [
            'class' => CropperImageUploadBehavior::class,
            'attribute' => 'avatar',
            'scenarios' => ['create', 'update'],
            'placeholder' => '@app/modules/user/assets/images/avatar.jpg',
            'path' => '@webroot/upload/avatar/{id}',
            'url' => '@web/upload/avatar/{id}',
            'thumbs' => [
                'thumb' => ['width' => 42, 'height' => 42, 'mode' => ManipulatorInterface::THUMBNAIL_OUTBOUND],
                'preview' => ['width' => 200, 'height' => 200, 'mode' => ManipulatorInterface::THUMBNAIL_OUTBOUND],
            ],
            'cropAspectRatio' => 1,
        ],
    ];
}
```

View file:

```php
<?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'photo')->widget(CropperImageUploadWidget::class) ?>
    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>
```
