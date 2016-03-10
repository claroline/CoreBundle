/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import 'angular/angular.min'

import UIRouter from 'angular-ui-router'
import bootstrap from 'angular-bootstrap'
import colorpicker from 'angular-bootstrap-colorpicker'
import translation from 'angular-ui-translation/angular-translation'

import clarolineAPI from '../services/module'
import Routing from './routing.js'
import DesktopHomeMainCtrl from './Controller/DesktopHomeMainCtrl'
import HomeTabCreationModalCtrl from './Controller/HomeTabCreationModalCtrl'
import HomeTabEditionModalCtrl from './Controller/HomeTabEditionModalCtrl'
import ClaroDesktopHomeTabsDirective from './Directive/ClaroDesktopHomeTabsDirective'
import ClaroWidgetsDirective from '../widgets/Directive/ClaroWidgetsDirective'

angular.module('DesktopHomeModule', [
    'ui.bootstrap', 
    'ui.bootstrap.tpls',
    'colorpicker.module',
    'ui.translation',
    'ui.router',
    'ClarolineAPI',
    'gridster'
])
.controller('DesktopHomeMainCtrl', ['$http', '$uibModal', '$sce', 'ClarolineAPIService', DesktopHomeMainCtrl])
.controller('HomeTabCreationModalCtrl', HomeTabCreationModalCtrl)
.controller('HomeTabEditionModalCtrl', HomeTabEditionModalCtrl)
.directive('claroDesktopHomeTabs', () => new ClaroDesktopHomeTabsDirective)
.directive('claroWidgets', () => new ClaroWidgetsDirective)
.config(Routing)
