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
import translation from 'angular-ui-translation/angular-translation'

import clarolineAPI from '../services/module'
import Routing from './routing.js'
import DesktopHomeMainCtrl from './Controller/DesktopHomeMainCtrl'
import ClaroDesktopHomeTabsDirective from './Directive/ClaroDesktopHomeTabsDirective'
import ClaroWidgetsDirective from '../widgets/Directive/ClaroWidgetsDirective'

angular.module('DesktopHomeModule', [
    'ui.bootstrap', 
    'ui.bootstrap.tpls',
    'ui.translation',
    'ui.router',
    'ClarolineAPI'
])
.controller('DesktopHomeMainCtrl', ['$http', '$sce', 'ClarolineAPIService', DesktopHomeMainCtrl])
.directive('claroDesktopHomeTabs', () => new ClaroDesktopHomeTabsDirective)
.directive('claroWidgets', () => new ClaroWidgetsDirective)
.config(Routing)
