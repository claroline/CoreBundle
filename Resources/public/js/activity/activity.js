(function () {
    'use strict';

    var activity = window.Claroline.Activity = {
        'iframeChange': null
    };

    /**
     * resize a iframe
     */
    activity.height = function (element)
    {
        var height = $(element).contents().find('body').first().height();

        if (height) {
            $(element).css('height', height);
        }
    };

    /** Events **/

    $('body').on('click', '.activity-tabs > li > a:not(.add-resource)', function () {
        var id = $(this).data('id');

        $('.activity-tabs > li').removeClass('active');
        $(this).parent().addClass('active');
        $('.activities > div').addClass('hide');
        $('#' + id).removeClass('hide');

        $('#' + id + ' .activity-iframe').each(function () {
            activity.height(this);
        });
    });

    $('.activity-iframe').load(function () {
        var iframe = this;
        setTimeout(function () {
            activity.height(iframe);
        }, 50);
    });

    $(window).on('resize', function () {
        clearTimeout(activity.iframeChange);
        activity.iframeChange = setTimeout(function () {
            $('.activity-iframe').each(function () {
                activity.height(this);
            });
        }, 300);
    });

    $(document).ready(function () {
        clearTimeout(activity.iframeChange);
        activity.iframeChange = setTimeout(function () {
            $('.activity-iframe').each(function () {
                activity.height(this);
            });
        }, 300);
    });

}());
