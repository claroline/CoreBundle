/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

(function () {
    'use strict';

    window.Claroline = window.Claroline || {};

    var common = window.Claroline.Common = {};
    var routing = window.Routing;

    /**
     * This function creates a new element in the document with a given class name.
     *
     * @param tag The tag name of the new element.
     * @param className The class name of the new element.
     */
    common.createElement = function (tag, className)
    {
        return $(document.createElement(tag)).addClass(className);
    };

    /**
     * Upload a file and add it in a TinyMCE editor.
     *
     * @param form A HTML form element.
     * @param element A HTML modal element.
     *
     */
    common.uploadfile = function (form, element, callBack) {
        var workspace = $(form).data('workspace');
        $(form).upload(
            routing.generate('claro_resource_create', {'resourceType' : 'file', 'parentId' : workspace}),
            function (done) {
                if (done.getResponseHeader('Content-Type')  === 'application/json') {
                    var resource = $.parseJSON(done.responseText)[0];
                    var nodes = {};
                    var mimeType = 'mime_type'; //camel case fix in order to have 0 jshint errors
                    nodes[resource.id] = new Array(resource.name, resource.type, resource[mimeType]);
                    $(element).modal('hide');
                    callBack(nodes);
                    $.ajax(
                        routing.generate('claro_resource_open_perms', {'node': resource.id})
                    );
                } else {
                    $('.progress', element).addClass('hide');
                    $('.alert', element).removeClass('hide');
                    $('.progress-bar', element).attr('aria-valuenow', 0).css('width', '0%').find('sr-only').text('0%');
                }
            },
            function (progress) {
                var percent = Math.round((progress.loaded * 100) / progress.totalSize);

                $('.progress', element).removeClass('hide');
                $('.alert', element).addClass('hide');
                $('.progress-bar', element)
                    .attr('aria-valuenow', percent)
                    .css('width', percent + '%')
                    .find('sr-only').text(percent + '%');
            }
        );
    };

    /**
     * jQuery Upload HTML5
     *
     * example:
     *
     * $('input').upload(
     *     home.path + 'resource/create/file/' + workspace,
     *     function (res) {
     *         console.log('done', res);
     *     },
     *     function (progress) {
     *         $('.progress-bar').css('width', Math.round((progress.loaded * 100) / progress.totalSize) + '%')
     *     }
     * );
     *
     */
    $.fn.upload = function (remote, successFn, progressFn) {
        return this.each(function () {

            var formData = new FormData($(this).parents('form').get(0));

            $.ajax({
                url: remote,
                type: 'POST',
                xhr: function () {
                    var myXhr = $.ajaxSettings.xhr();
                    if (myXhr.upload && progressFn) {
                        myXhr.upload.addEventListener('progress', progressFn, false);
                    }
                    return myXhr;
                },
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                complete : function (res) {
                    if (successFn) {
                        successFn(res);
                    }
                }
            });
        });
    };

}());
