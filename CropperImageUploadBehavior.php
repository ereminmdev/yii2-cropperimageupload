<?php

namespace ereminmdev\yii2\cropperimageupload;

use mongosoft\file\UploadImageBehavior;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;

/**
 * Class CropperImageUploadBehavior
 * @package ereminmdev\yii2\cropperimageupload
 *
 * @property ActiveRecord $owner
 * @property void $uploadedFile
 */
class CropperImageUploadBehavior extends UploadImageBehavior
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
     * @var string attribute that stores crop value
     * if empty, crop value is got from attribute field
     */
    public $cropField;
    /**
     * @var string attribute that stores cropped image name
     */
    public $croppedField;
    /**
     * @var array the thumbnail profiles
     * - `width`
     * - `height`
     * - `quality`
     */
    public $thumbs = [];
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

    protected $cropValue;
    protected $action;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->croppedField = $this->croppedField !== null ? $this->croppedField : $this->attribute;
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        $model = $this->owner;
        if (in_array($model->scenario, $this->scenarios)) {
            if (empty($this->cropField)) {
                $this->cropValue = $model->getAttribute($this->attribute);
                $changed = !empty($this->cropValue);
            } else {
                $this->cropValue = $model->getAttribute($this->cropField);
                $changed = $model->isAttributeChanged($this->cropField);
            }

            if ($changed) {
                $this->getUploadedFile();
            }

            parent::beforeValidate();
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        parent::beforeSave();

        $model = $this->owner;
        if (in_array($model->scenario, $this->scenarios)) {
            if ($this->action == 'delete') {
                $this->delete($this->attribute, true);
                $this->owner->setAttribute($this->attribute, '');
            }
        }
    }

    /**
     * @param string $attribute
     * @param string|false $thumb
     * @param string $placeholderUrl
     * @return string
     */
    public function getImageUrl($attribute, $thumb = 'thumb', $placeholderUrl = '')
    {
        $thumb = in_array($thumb, array_keys($this->thumbs)) ? $thumb : false;

        $behavior = $this->findCropperBehavior($attribute);
        if ($behavior !== null) {
            if ($thumb !== false) {
                return $behavior->getThumbUploadUrl($attribute, $thumb);
            } else {
                return $behavior->getUploadUrl($attribute);
            }
        } else {
            if ($thumb !== false) {
                return $this->getPlaceholderUrl($thumb);
            } else {
                return $placeholderUrl;
            }
        }
    }

    /**
     * @param string $attribute
     * @return self|null
     */
    public function findCropperBehavior($attribute)
    {
        if ($this->attribute == $attribute) {
            return $this;
        } else {
            $owner = $this->owner;
            foreach ($owner->getBehaviors() as $behavior) {
                if (($behavior instanceof self) && ($behavior->attribute == $attribute)) {
                    return $behavior;
                }
            }
        }
        return null;
    }

    public function getUploadedFile()
    {
        $value = $this->cropValue;

        if (mb_strpos($value, 'action=') === 0) {
            $this->action = mb_substr($value, 7);
        } elseif ((mb_strpos($value, 'data:image') === 0) && mb_strpos($value, 'base64,')) {
            $this->createFromBase64($value);
        } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
            $this->createFromUrl($value);
        };
    }

    /**
     * @param string $temp_name
     * @param string $temp_path
     * @return UploadUrlFile
     */
    public function createUploadedFile($temp_name, $temp_path)
    {
        return new UploadUrlFile([
            'name' => $temp_name,
            'tempName' => $temp_path,
            'type' => FileHelper::getMimeTypeByExtension($temp_path),
            'size' => filesize($temp_path),
            'error' => UPLOAD_ERR_OK,
        ]);
    }

    /**
     * @param string $data
     */
    protected function createFromBase64($data)
    {
        try {
            list($type, $data) = explode(';', $data);
            list(, $data) = explode(',', $data);
            $ext = mb_substr($type, mb_strrpos($type, '/') + 1);
            $data = base64_decode($data);

            $temp_name = Yii::$app->security->generateRandomString() . '.' . $ext;
            $temp_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $temp_name;

            file_put_contents($temp_path, $data);

            $this->owner->setAttribute($this->attribute, $this->createUploadedFile($temp_name, $temp_path));
        } catch (\Exception $e) {
        }
    }

    /**
     * @param string $url
     */
    protected function createFromUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) return;

        $url = str_replace(' ', '+', $url);
        $url = strpos($url, '//') === 0 ? 'http://' . ltrim($url, '/') : $url;

        try {
            $ext = preg_match('/\.(jpe?g|gif|png){1}.*$/', $url, $match) ? $match[1] : pathinfo($url, PATHINFO_EXTENSION);
            $temp_name = Yii::$app->security->generateRandomString() . '.' . $ext;
            $temp_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $temp_name;

            copy($url, $temp_path);

            $this->owner->setAttribute($this->attribute, $this->createUploadedFile($temp_name, $temp_path));
        } catch (\Exception $e) {
        }
    }
}
