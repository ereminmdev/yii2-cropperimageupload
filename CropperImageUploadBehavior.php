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
 */
class CropperImageUploadBehavior extends UploadImageBehavior
{
    /**
     * @var bool use cropper
     */
    public $crop = true;
    /**
     * @var float crop aspect ratio (width/height)
     */
    public $ratio;
    /**
     * @var string attribute that stores crop value
     * if empty, crop value is got from attribute field
     */
    public $crop_field;
    /**
     * @var string attribute that stores cropped image name
     */
    public $cropped_field;
    /**
     * @var array the thumbnail profiles
     * - `width`
     * - `height`
     * - `quality`
     */
    public $thumbs = [];
    /**
     * @var array the options for the Cropper plugin.
     * Please refer to the Cropper documentation page for possible options.
     * @see https://github.com/fengyuanchen/cropperjs/blob/master/README.md#options
     */
    public $cropperOptions = [];
    /**
     * @var array of options for cropper result method
     */
    public $cropperResultOpts = [];

    protected $crop_value;
    protected $action;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->cropped_field = $this->cropped_field !== null ? $this->cropped_field : $this->attribute;
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        $model = $this->owner;
        if (in_array($model->scenario, $this->scenarios)) {
            if (empty($this->crop_field)) {
                $this->crop_value = $model->getAttribute($this->attribute);
                $changed = !empty($this->crop_value);
            } else {
                $this->crop_value = $model->getAttribute($this->crop_field);
                $changed = $model->isAttributeChanged($this->crop_field);
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
        $value = $this->crop_value;

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
