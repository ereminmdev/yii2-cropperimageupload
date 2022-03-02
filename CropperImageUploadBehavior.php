<?php

namespace ereminmdev\yii2\cropperimageupload;

use mohorev\file\UploadImageBehavior;
use Yii;
use yii\base\ErrorException;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

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
     * @var array the thumbnail profiles
     * - `width`
     * - `height`
     * - `quality`
     */
    public $thumbs = [];
    /**
     * @var bool convert uploaded file to WebP image format
     */
    public $convertToWebP = true;
    /**
     * @var int quality ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file)
     */
    public $convertQuality = 80;
    /**
     * @var bool|string remove directory after model deleted. Set true to use `path` option or string as path.
     */
    public $deleteDir = true;

    protected $cropValue;
    protected $action;


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->croppedField = $this->croppedField !== null ? $this->croppedField : $this->attribute;
    }

    /**
     * {@inheritdoc}
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

            if ($this->convertToWebP) {
                $this->convertUploadedFileToWebP();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave()
    {
        parent::beforeSave();

        $model = $this->owner;
        if (in_array($model->scenario, $this->scenarios)) {
            if ($this->action == 'delete') {
                $this->removeImage($this->attribute);
                $this->owner->setAttribute($this->attribute, '');
            }
        }
    }

    /**
     * {@inheritdoc}
     * @throws ErrorException
     */
    public function afterDelete()
    {
        parent::afterDelete();

        if ($this->deleteDir) {
            $dir = $this->deleteDir === true ? $this->resolvePath($this->path) : $this->deleteDir;
            $dir = Yii::getAlias($dir);
            FileHelper::removeDirectory($dir);
        }
    }

    /**
     * @param string $attribute
     */
    public function removeImage($attribute)
    {
        $this->delete($attribute, true);
    }

    /**
     * @param string $attribute
     * @param string|false $thumb
     * @param string $placeholderUrl
     * @return string
     */
    public function getImageUrl($attribute, $thumb = 'thumb', $placeholderUrl = '')
    {
        $behavior = $this->findCropperBehavior($attribute) ?? $this;
        $thumb = in_array($thumb, array_keys($behavior->thumbs)) ? $thumb : false;

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
     * Returns file url for the attribute.
     * @param string $attribute
     * @return string|null
     */
    public function getUploadUrl($attribute)
    {
        $behavior = $this->findCropperBehavior($attribute) ?? $this;

        if ($behavior === $this) {
            return parent::getUploadUrl($attribute);
        } else {
            return $behavior->getUploadUrl($attribute);
        }
    }

    /**
     * Returns file path for the attribute.
     * @param string $attribute
     * @param boolean $old
     * @return string|null
     */
    public function getUploadPath($attribute, $old = false)
    {
        $behavior = $this->findCropperBehavior($attribute) ?? $this;

        if ($behavior === $this) {
            return parent::getUploadPath($attribute, $old);
        } else {
            return $behavior->getUploadPath($attribute, $old);
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
        }
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
    public function createFromBase64($data)
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
    public function createFromUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) return;

        $url = str_replace(' ', '+', $url);
        $url = strpos($url, '//') === 0 ? 'http://' . ltrim($url, '/') : $url;

        try {
            $ext = preg_match('/\.(jpe?g|gif|png).*$/', $url, $match) ? $match[1] : pathinfo($url, PATHINFO_EXTENSION);
            $temp_name = Yii::$app->security->generateRandomString() . '.' . $ext;
            $temp_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $temp_name;

            file_put_contents($temp_path, file_get_contents($url));

            $this->owner->setAttribute($this->attribute, $this->createUploadedFile($temp_name, $temp_path));
        } catch (\Exception $e) {
        }
    }

    public function convertUploadedFileToWebP()
    {
        if ($this->file instanceof UploadedFile) {
            $tempName = $this->file->tempName;
            $newTempName = $tempName . '.webp';
            $extension = $this->file->extension;

            if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
                return;
            }

            $im = in_array($extension, ['jpg', 'jpeg']) ? @imagecreatefromjpeg($tempName) : @imagecreatefrompng($tempName);

            if ($im) {
                imagewebp($im, $newTempName, $this->convertQuality);
                imagedestroy($im);
            }

            $newName = preg_replace('/' . $extension . '$/', 'webp', $this->file->name);
            $this->file = $this->createUploadedFile($newName, $newTempName);
            $this->owner->setAttribute($this->attribute, $this->file);
        }
    }

    /**
     * @param string $attribute
     * @param bool $removeDirectory
     * @param bool $saveModel
     * @return bool
     */
    public function recreateThumbs($attribute, $removeDirectory = false, $saveModel = false)
    {
        $model = $this->owner;

        $temp_name = $model->getAttribute($attribute);
        $source_path = $model->getUploadPath($attribute);

        if (!empty($temp_name) && is_readable($source_path)) {
            $temp_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $temp_name;

            try {
                if (copy($source_path, $temp_path)) {
                    $uploadedFile = $this->createUploadedFile($temp_name, $temp_path);
                    $model->setAttribute($attribute, $uploadedFile);

                    if ($removeDirectory) {
                        FileHelper::removeDirectory(dirname($source_path));
                    }

                    if ($saveModel) {
                        $model->save(true, [$attribute]);
                    }

                    @unlink($temp_path);

                    return true;
                }
            } catch (\Exception $e) {
            }
        }

        return false;
    }
}
