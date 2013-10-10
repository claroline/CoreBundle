(function () {
    'use strict';

    var displayedHomeTabId = $('#hometab-id-div').attr('hometab-id');
    var configValue = ($('#config-value-div').attr('config-value')).trim();
    var withConfig = (configValue === '') ? 0 : parseInt(configValue);
    var currentHomeTabId;
    var currentHomeTabOrder;
    var currentElement;

    function openHomeTabModal(title, content)
    {
        $('#hometab-modal-title').html(title);
        $('#hometab-modal-body').html(content);
        $('#hometab-modal-box').modal('show');
    }

    function closeHomeTabModal()
    {
        $('#hometab-modal-box').modal('hide');
        $('#hometab-modal-title').empty();
        $('#hometab-modal-body').empty();
    }

    // Click on the configuration button
    $('#switch-config-mode').click(function () {
        withConfig = (withConfig + 1) % 2;

        if (withConfig === 0) {
            $('.toggle-visible').each(function () {
                $(this).addClass('hidden');
            });

            var currentVisibilityElement = $('#visible-hometab-id-' + displayedHomeTabId);

            if (currentVisibilityElement.hasClass('icon-eye-close')) {
                window.location = Routing.generate(
                    'claro_display_desktop_home_tabs',
                    {'tabId': -1, 'withConfig': withConfig}
                );
            }
        } else {
            $('.toggle-visible').each(function () {
                $(this).removeClass('hidden');
            });
        }
    });

    // Click on a HomeTab
    $('.hometab-link').click(function (e) {
        e.preventDefault();
        e.stopPropagation();
        var homeTabId = $(this).parents('.hometab-element').attr('hometab-id');

        window.location = Routing.generate(
            'claro_display_desktop_home_tabs',
            {'tabId': homeTabId, 'withConfig' : withConfig}
        );
    });

    $('.hometab-visibility-btn').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var visibilityBtn = $(this);
        currentElement = visibilityBtn.parents('.hometab-element');
        var homeTabConfigId = currentElement.attr('hometab-config-id');
        var visible = (visibilityBtn.attr('visiblility-value')).trim();
        var newVisible = (visible === 'visible') ? 'invisible' : 'visible';

        $.ajax({
            url: Routing.generate(
                'claro_home_tab_update_visibility',
                {'homeTabConfigId': homeTabConfigId, 'visible': newVisible}
            ),
            type: 'POST',
            success: function () {
                if (newVisible === 'visible') {
                    visibilityBtn.attr('visiblility-value', 'visible')
                    visibilityBtn.removeClass('icon-eye-close');
                    visibilityBtn.addClass('icon-eye-open');
                    currentElement.removeClass('toggle-visible');
                } else {
                    visibilityBtn.attr('visiblility-value', 'invisible')
                    visibilityBtn.removeClass('icon-eye-open');
                    visibilityBtn.addClass('icon-eye-close');
                    currentElement.addClass('toggle-visible');
                }
            }
        });
    });

    $('.hometab-delete-btn').click(function (e) {
        e.preventDefault();
        e.stopPropagation();

        currentElement = $(this).parents('.hometab-element');
        currentHomeTabId = currentElement.attr('hometab-id');
        currentHomeTabOrder = currentElement.attr('hometab-order');
        $('#delete-hometab-validation-box').modal('show');
    });

    $('.hometab-rename-btn').click(function (e) {
        e.preventDefault();
        e.stopPropagation();

        currentElement = $(this).parents('.hometab-element');
        currentHomeTabId = currentElement.attr('hometab-id');

        $.ajax({
            url: Routing.generate(
                'claro_desktop_home_tab_edit_form',
                {'homeTabId': currentHomeTabId}
            ),
            type: 'GET',
            success: function (datas) {
                openHomeTabModal(
                    Translator.get('platform' + ':' + 'home_tab_edition'),
                    datas
                );
            }
        });
    });

    $('#add-hometab-btn').click(function (e) {
        e.preventDefault();
        e.stopPropagation();

        $.ajax({
            url: Routing.generate('claro_desktop_home_tab_create_form'),
            type: 'GET',
            success: function (datas) {
                openHomeTabModal(
                    Translator.get('platform' + ':' + 'home_tab_creation'),
                    datas
                );
            }
        });
    });

    // Click on OK button of delete confirmation modal
    $('#delete-hometab-confirm-ok').click(function () {
        $.ajax({
            url: Routing.generate(
                'claro_desktop_home_tab_delete',
                {'homeTabId': currentHomeTabId, 'tabOrder': currentHomeTabOrder}
            ),
            type: 'DELETE',
            success: function () {
                $('#delete-hometab-validation-box').modal('hide');

                if (displayedHomeTabId === currentHomeTabId) {
                    window.location = Routing.generate(
                        'claro_display_desktop_home_tabs',
                        {'tabId': -1, 'withConfig': withConfig}
                    );
                } else {
                    currentElement.remove();
                }
            }
        });
    });

    // Click on OK button of the Create/Rename HomeTab form modal
    $('body').on('click', '#form-hometab-ok-btn', function (e) {
        e.stopImmediatePropagation();
        e.preventDefault();

        var form = document.getElementById('hometab-form');
        var action = form.getAttribute('action');
        var formData = new FormData(form);

        $.ajax({
            url: action,
            data: formData,
            type: 'POST',
            processData: false,
            contentType: false,
            complete: function(jqXHR) {
                switch (jqXHR.status) {
                    case 201:
                        closeHomeTabModal();
                        window.location = Routing.generate(
                            'claro_display_desktop_home_tabs',
                            {'tabId': 0, 'withConfig': 1}
                        );
                        break;
                    case 204:
                        closeHomeTabModal();
                        window.location = Routing.generate(
                            'claro_display_desktop_home_tabs',
                            {'tabId': currentHomeTabId, 'withConfig': 1}
                        );
                        break;
                    default:
                        $('#hometab-modal-body').html(jqXHR.responseText);
                }
            }
        });
    });

    // Click on CANCEL button of the Create/Rename HomeTab form modal
    $('body').on('click', '#form-hometab-cancel-btn', function () {
        closeHomeTabModal();
    });
})();