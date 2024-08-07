# yii2-cropperimageupload

Image crop and upload for Yii framework.

Depends on:

- https://github.com/mohorev/yii2-upload-behavior
- https://github.com/fengyuanchen/cropperjs

## Install

``composer require --prefer-dist ereminmdev/yii-cropperimageupload``

## Use

Add some code to model and view files:

- model:

```php
public function behaviors()
{
    return [
        'avatar' => [
            'class' => CropperImageUploadBehavior::class,
            'attribute' => 'avatar',
            'scenarios' => ['default', 'create', 'update'],
            'placeholder' => '@app/modules/user/assets/images/no-avatar.jpg',
            'path' => '@webroot/upload/avatar/{id}',
            'url' => '@web/upload/avatar/{id}',
            'thumbs' => [
                'thumb' => ['width' => 60, 'height' => 60, 'quality' => 80, 'mode' => ManipulatorInterface::THUMBNAIL_OUTBOUND],
                'preview' => ['width' => 240, 'height' => 240, 'bg_alpha' => 0],
            ],
            'cropAspectRatio' => 1,
        ],
    ];
}

public function rules()
{
    return [
        [['avatar'], 'file', 'extensions' => 'jpg, jpeg, gif, png, svg, webp'],
    ];
}
```

- form field:

```php
<?= $form->field($model, 'avatar')->widget(CropperImageUploadWidget::class) ?>
```

- html image:

```php
<?= $model->renderThumbImage('avatar') ?>
<?= $model->renderThumbImage('avatar', 'preview', ['alt' => 'Avatar']) ?>
```

## Tips

- Re-create thumbs:

```php
foreach (User::find()->each() as $model) {
    $filename = $model->getAttribute('avatar');

    if ($model->recreateThumbs('avatar', true, true)) {
        $this->stdout('Recreated successful: ' . $filename . "\n");
    } else {
        $this->stdout('Error when recreating: ' . $filename . "\n", Console::FG_RED);
    }
}
```
