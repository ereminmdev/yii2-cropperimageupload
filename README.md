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
            'scenarios' => ['default'], //['create', 'update'],
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
    <?= $form->field($model, 'avatar')->widget(CropperImageUploadWidget::class) ?>
    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>
```

## Tips

- If need for re-create thumbs, add to console/controller:

```php
foreach (Product::find()->each() as $model) {
    $file_name = $model->getAttribute('avatar');

    if ($model->recreateThumbs('avatar', true, true)) {
        $this->stdout('Recreated successful: ' . $file_name . PHP_EOL);
    } else {
        $this->stdout('Error when recreating: ' . $file_name . PHP_EOL, Console::FG_RED);
    }
}
```

- To support svg images:

```php
public function rules()
{
    return [
        [['avatar'], 'file', 'extensions' => 'jpg, jpeg, gif, png, svg'],
    ];
}
```
