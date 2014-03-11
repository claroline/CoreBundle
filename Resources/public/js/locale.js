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

    var routing = window.Routing;
    var modal = window.Claroline.Modal;

    $('body').on('click', '.locale-select', function () {
        if (!$(this).parents('.modal').get(0)) {
            modal.fromRoute('claroline_locale_select');
        } else {
            $.ajax(routing.generate('claroline_locale_change', {'_locale': $(this).html().toLowerCase()}))
            .done(function () {
                window.location.reload();
            });
        }
    });

}());
