(function ($) {
    'use strict';

    $.fn.cropperImageUpload = function (options) {
        var settings = $.extend(true, {}, $.fn.cropperImageUpload.defaults, options);

        return this.each(function () {
            var $input = $(this);

            //$input.attr('data-cropper-settings', settings);

            if (settings.watchOnChange) {
                $input.on('change', function () {
                    var file = this.files[0];

                    if (file && file.type.match('image.*')) {
                        var reader = new FileReader();

                        reader.onloadend = function () {
                            if (settings.onBeforeCrop.call($input, reader.result, settings) && settings.modalSel) {
                                $input.cropperImageUpload.cropInModal($input, settings, reader.result);
                            }
                        };

                        reader.readAsDataURL(file);
                    }
                });
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
            type: 'image/png', // image MIME type with no parameters
            encoderOptions: .92, // number in the range 0.0 to 1.0, desired quality level
            background: 'white' // color|gradient|pattern (https://www.w3schools.com/tags/canvas_fillstyle.asp)
        },

        watchOnChange: true,

        imageTag: 'img',
        imageAttrs: {
            class: 'img-responsive'
        },
        imageCSS: {},

        btnSaveText: 'Save',
        btnCancelText: 'Cancel',
        btnRotateLeft: '←',
        btnRotateRight: '→',

        onBeforeCrop: function (imageSrc, settings) {
            return true;
        },
        onCropSave: function (resp, $cropInput, $resultImage) {
            $cropInput.val(resp).trigger('change');
            if ($resultImage) {
                $resultImage.attr('src', resp);
            }
        },
        onCropCancel: function () {
            $(this).val('');
        }
    };

    $.fn.cropperImageUpload.cropInModal = function ($input, settings, imageSrc) {
        var $container = settings.containerSel ? $input.closest(settings.containerSel) : $input.parent(),
            $cropInput = settings.cropInputSel ? $container.find(settings.cropInputSel) : $input.prev('input'),
            $modal = $(settings.modalSel),
            $resultImage = $container.find(settings.resultImageSel),
            $image, cropper,
            $body = $modal.find('.modal-body');

        $modal.find('.modal-title').html(settings.modalTitle);

        var $footer = settings.modalFooter ? settings.modalFooter : $('<div class="cropper-modal-footer">' +
            '<button type="button" class="btn btn-primary btn-save" data-dismiss="modal">' + settings.btnSaveText + '</button>' +
            '&nbsp; ' +
            '<button type="button" class="btn btn-default btn-cancel" data-dismiss="modal">' + settings.btnCancelText + '</button>' +
            '&nbsp; &nbsp; &nbsp; &nbsp; ' +
            (settings.btnRotateLeft ? '<button type="button" class="btn btn-default btn-rotate" data-deg="-90">' + settings.btnRotateLeft + '</button>' : '') +
            '&nbsp; ' +
            (settings.btnRotateRight ? '<button type="button" class="btn btn-default btn-rotate" data-deg="90">' + settings.btnRotateRight + '</button>' : '') +
            '</div>');

        $footer.find('.btn-save').on('click', function (e) {
            var canvas = cropper.getCroppedCanvas();

            if (settings.cropperResultOpts.background !== 'transparent') {
                var dCanvas = document.createElement('canvas');
                dCanvas.width = canvas.width;
                dCanvas.height = canvas.height;
                var dCtx = dCanvas.getContext('2d');
                dCtx.fillStyle = settings.cropperResultOpts.background;
                dCtx.fillRect(0, 0, dCanvas.width, dCanvas.height);
                dCtx.drawImage(canvas, 0, 0);
                canvas = dCanvas;
            }

            var imgData = canvas.toDataURL(settings.cropperResultOpts.type, settings.cropperResultOpts.encoderOptions);
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

        $body.empty();
        $modal.modal('show');

        $modal.one('shown.bs.modal', function () {
            $image = $('<' + settings.imageTag + '/>')
                .attr('src', imageSrc)
                .one('load', function () {
                    $image.attr(settings.imageAttrs).css(settings.imageCSS);

                    var $imageWrapper = $('<div class="cropper-image-upload__container"></div>').append($image);

                    $body.append($imageWrapper).append($footer);

                    var w = $imageWrapper.innerWidth();
                    var vw = w - 80;
                    var wh = Math.round(vw / settings.aspectRatio);
                    $imageWrapper.height(wh + 80);

                    var cropperOptions = {
                        aspectRatio: settings.aspectRatio,
                        autoCropArea: 1
                    };
                    cropperOptions = $.extend(true, {}, cropperOptions, settings.cropperOptions);

                    cropper = new Cropper($image[0], cropperOptions);
                });
        });
    };

}(jQuery));
