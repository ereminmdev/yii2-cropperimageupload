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
                'preview' => ['width' => 240, 'height' => 240],
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

- view:

```php
<?= $form->field($model, 'avatar')->widget(CropperImageUploadWidget::class) ?>
```

## Tips

- Re-create thumbs:

```php
foreach (User::find()->each() as $model) {
    $filename = $model->getAttribute('avatar');

    if ($model->recreateThumbs('avatar', true, true)) {
        $this->stdout('Recreated successful: ' . $filename . PHP_EOL);
    } else {
        $this->stdout('Error when recreating: ' . $filename . PHP_EOL, Console::FG_RED);
    }
}
```

- Store in WebP format:

1) Add `cropperResultOpts` option to behavior config:

```php
public function behaviors()
{
    return [
        ...
        'avatar' => [
            'thumbs' => [
                'thumb' => ['width' => 60, 'height' => 60, 'quality' => 80],
            ],            
            ...
            'cropperResultOpts' => ['type' => 'image/webp'],
        ],
    ];
}
```

2) Add `webp` extension to rules:

```
public function rules()
{
    return [
        [['avatar'], 'file', 'extensions' => 'jpg, jpeg, gif, png, svg, webp'],
    ];
}
```
