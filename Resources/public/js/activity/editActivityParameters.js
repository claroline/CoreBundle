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

    function disableRuleOption()
    {
        var actionSelect = $('.activity-rule-action').val();
        
        if (actionSelect === 'none') {
            $('.activity-rule-option').attr('disabled', 'disabled');
        } else {
            $('.activity-rule-option').attr('disabled', false);
        }
    }
    
    function updateRuleActions(actions)
    {
        var ruleActionSelect = $('#activity_rule_form_action');
        var selectValue = ruleActionSelect.val();
        
        ruleActionSelect.children().each(function () {
            var value = $(this).val();
            
            if (value === 'none' || actions.indexOf(value) >= 0) {
                $(this).removeClass('hidden');
            } else {
                $(this).addClass('hidden');
                
                if (value === selectValue) {
                    ruleActionSelect.val('none');
                    disableRuleOption();
                }
            }
        });
    }

    function checkAvailableActions()
    {
        var primaryResourceType = $('#activity_form_primaryResource').data('type');
        var route;
        
        if (typeof primaryResourceType === 'undefined') {
            route = Routing.generate(
                'claro_get_rule_actions_from_resource_type'
            );
        } else {
            route = Routing.generate(
                'claro_get_rule_actions_from_resource_type',
                {'resourceTypeName': primaryResourceType}
            );
        }
        
        $.ajax({
            url: route,
            type: 'GET',
            success: function (datas) {
                var actions = [];
                
                if (datas !== 'false') {
                    actions = $.parseJSON(datas);
                }
                updateRuleActions(actions);
            }
        });
    }
    
    $('#activity_form_primaryResource').on('change', function () {
        checkAvailableActions();
    });
    
    $('#activity_rule_form_action').on('change', function () {
        disableRuleOption();
    });
    
    checkAvailableActions();
    disableRuleOption();
})();