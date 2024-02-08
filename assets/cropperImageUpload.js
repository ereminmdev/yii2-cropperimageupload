(function ($) {
    'use strict';

    $.fn.cropperImageUpload = function (options) {
        let settings = $.extend(true, {}, $.fn.cropperImageUpload.defaults, options);

        return this.each(function () {
            let $input = $(this);

            //$input.attr('data-cropper-settings', settings);

            if (settings.watchOnChange) {
                $input.on('change', function () {
                    let file = this.files[0];

                    if (file && file.type.match('image.*')) {
                        let reader = new FileReader();

                        reader.onloadend = function () {
                            if (settings.onBeforeCrop.call($input, reader.result, settings) && settings.modalSel) {
                                $input.cropperImageUpload.cropInModal($input, settings, reader.result);
                            }
                        };

                        reader.readAsDataURL(file);
                    }
                });
            }

            if (settings.editOnResultClick) {
                let $container = settings.containerSel ? $input.closest(settings.containerSel) : $input.parent(),
                    $resultImage = $container.find(settings.resultImageSel);

                if ($resultImage) {
                    $resultImage.on('click', function () {
                        $input.trigger('click');
                    });
                }
            }
        });
    };

    $.fn.cropperImageUpload.defaults = {
        aspectRatio: NaN,
        containerSel: null,
        cropInputSel: null,
        resultImageSel: null,

        modalSel: '',
        modalTitle: null,
        modalFooter: null,

        cropperOptions: {},

        cropperResultOpts: {
            type: 'image/webp', // image MIME type with no parameters
            encoderOptions: .8, // number in the range 0.0 to 1.0, desired quality level
            background: 'transparent' // color|gradient|pattern (https://www.w3schools.com/tags/canvas_fillstyle.asp)
        },

        watchOnChange: true,
        editOnResultClick: true,

        imageTag: 'img',
        imageAttrs: {
            class: 'img-responsive'
        },
        imageCSS: {},

        btnSaveText: 'Save',
        btnCancelText: 'Cancel',
        btnRotateRight: '↻',
        btnFlipHorizontal: '↔',
        btnFlipVertical: '↕',

        onBeforeCrop: function (imageSrc, settings) {
            return true;
        },
        onCropSave: function (resp, $cropInput, $resultImage) {
            if ($resultImage) {
                $resultImage.attr('src', resp);
                $resultImage.removeAttr('data-src');
            }
            $cropInput.val(resp).trigger('change');
            $(this).val('');
        },
        onCropCancel: function () {
            $(this).val('');
        }
    };

    $.fn.cropperImageUpload.cropInModal = function ($input, settings, imageSrc) {
        let $container = settings.containerSel ? $input.closest(settings.containerSel) : $input.parent(),
            $cropInput = settings.cropInputSel ? $container.find(settings.cropInputSel) : $input.prev('input'),
            $modal = $(settings.modalSel),
            $resultImage = $container.find(settings.resultImageSel),
            $image, cropper, $currentSize,
            $body = $modal.find('.modal-body');

        $modal.find('.modal-title').html(settings.modalTitle);

        let $footer = settings.modalFooter ? settings.modalFooter : $('<div class="cropper-modal-footer">' +
            '<button type="button" class="btn btn-primary btn-save" data-dismiss="modal">' + settings.btnSaveText + '</button>' +
            '       ' +
            '<div class="btn-group">' +
            '<button type="button" class="btn btn-default btn-rotate" data-deg="90">' + settings.btnRotateRight + '</button>' +
            '<button type="button" class="btn btn-default btn-flip-h">' + settings.btnFlipHorizontal + '</button>' +
            '<button type="button" class="btn btn-default btn-flip-v">' + settings.btnFlipVertical + '</button>' +
            '<button type="button" class="btn btn-default btn-current-size"></button>' +
            '</div>' +
            '       ' +
            '<button type="button" class="btn btn-link btn-cancel" data-dismiss="modal">' + settings.btnCancelText + '</button>' +
            '</div>');

        $footer.find('.btn-save').on('click', function (e) {
            let canvas = cropper.getCroppedCanvas();

            if (settings.cropperResultOpts.background !== 'transparent') {
                let dCanvas = document.createElement('canvas');
                dCanvas.width = canvas.width;
                dCanvas.height = canvas.height;
                let dCtx = dCanvas.getContext('2d');
                dCtx.fillStyle = settings.cropperResultOpts.background;
                dCtx.fillRect(0, 0, dCanvas.width, dCanvas.height);
                dCtx.drawImage(canvas, 0, 0);
                canvas = dCanvas;
            }

            let imgData = canvas.toDataURL(settings.cropperResultOpts.type, settings.cropperResultOpts.encoderOptions);
            settings.onCropSave.call($input, imgData, $cropInput, $resultImage);
            e.preventDefault();
        });

        $footer.find('.btn-cancel').on('click', function (e) {
            settings.onCropCancel.call($input);
            e.preventDefault();
        });

        $footer.find('.btn-rotate').on('click', function (e) {
            cropper.rotate(parseInt($(this).data('deg')));
            e.preventDefault();
        });

        let flipH = -1;
        $footer.find('.btn-flip-h').on('click', function (e) {
            cropper.scale(flipH, 1);
            flipH *= -1;
            e.preventDefault();
        });

        let flipV = -1;
        $footer.find('.btn-flip-v').on('click', function (e) {
            cropper.scale(1, flipV);
            flipV *= -1;
            e.preventDefault();
        });

        $currentSize = $footer.find('.btn-current-size');

        $body.empty();
        $modal.off('shown.bs.modal hidden.bs.modal');
        $modal.modal('show');

        $modal.one('shown.bs.modal', function () {
            $image = $('<' + settings.imageTag + '/>')
                .attr('src', imageSrc)
                .one('load', function () {
                    $image.attr(settings.imageAttrs).css(settings.imageCSS);

                    let $imageWrapper = $('<div class="cropper-image-upload__container"></div>').append($image);

                    $body.append($imageWrapper).append($footer);

                    let w = $imageWrapper.innerWidth();
                    let vw = w - 80;
                    let wh = Math.round(vw / settings.aspectRatio);
                    $imageWrapper.height(wh + 80);

                    $image.on('crop', (event) => {
                        $currentSize.html(Math.floor(event.detail.width) + 'x' + Math.floor(event.detail.height));
                    });

                    let cropperOptions = {
                        aspectRatio: settings.aspectRatio,
                        autoCropArea: 1,
                        dragMode: 'move'
                    };
                    cropperOptions = $.extend(true, {}, cropperOptions, settings.cropperOptions);

                    cropper = new Cropper($image[0], cropperOptions);
                });
        });

        $modal.one('hidden.bs.modal', function () {
            $body.empty();
        });
    };

}(jQuery));
