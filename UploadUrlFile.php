<?php

namespace ereminmdev\yii2\cropperimageupload;

use yii\web\UploadedFile;

class UploadUrlFile extends UploadedFile
{
    /**
     * {@inheritdoc}
     */
    public function saveAs($file, $deleteTempFile = true)
    {
        if ($this->error == UPLOAD_ERR_OK) {
            $result = copy($this->tempName, $file);
            if ($deleteTempFile) {
                unlink($this->tempName);
            }
            return $result;
        }
        return false;
    }
}
