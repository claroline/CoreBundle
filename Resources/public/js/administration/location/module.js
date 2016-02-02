(function () {
    'use strict';

    var translate = function(key) {
        return translator.trans(key, {}, 'platform');
    }

    var AdministrationModule = angular.module('AdministrationModule', [
        'ui.router',
        'ncy-angular-breadcrumb'
    ]);

    AdministrationModule.config(function($stateProvider, $urlRouterProvider) {
        $stateProvider
            .state(
                'administration',
                {
                    url: "/administration",
                    templateUrl: function($stateParam) {
                        return AngularApp.webDir +
                            'bundles/clarolinecore/js/administration/main/Partial/main.html';
                    },
                    ncyBreadcrumb: {
                        label: translate('administration')
                    }

                }
            );

        $urlRouterProvider.otherwise("/administration");
    });
})();