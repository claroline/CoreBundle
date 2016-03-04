export default function($stateProvider, $urlRouterProvider) {        
    $stateProvider
        .state ('main', {
            url: '/main',
            template: require('./Partial/main.html'),
            controller: 'DesktopHomeMainCtrl',
            controllerAs: 'dhmc'
        })

    $urlRouterProvider.otherwise('/main')
}
