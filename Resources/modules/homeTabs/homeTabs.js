/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import 'angular/index'

import bootstrap from 'angular-bootstrap'
import colorpicker from 'angular-bootstrap-colorpicker'
import translation from 'angular-ui-translation/angular-translation'

import clarolineAPI from '../services/module'
import WidgetsModule from '../widgets/widgets'
import DesktopHomeTabCreationModalCtrl from './Controller/DesktopHomeTabCreationModalCtrl'
import HomeTabEditionModalCtrl from './Controller/HomeTabEditionModalCtrl'
import HomeTabService from './Service/HomeTabService'
import DesktopHomeTabsDirective from './Directive/DesktopHomeTabsDirective'

//import Interceptors from '../interceptorsDefault'
//import HtmlTruster from '../html-truster/module'
//import bootstrap from 'angular-bootstrap'

angular.module('HomeTabsModule', [
  'ui.bootstrap',
  'ui.bootstrap.tpls',
  'colorpicker.module',
  'ui.translation',
  'ClarolineAPI',
  'WidgetsModule'
])
.controller('DesktopHomeTabCreationModalCtrl', DesktopHomeTabCreationModalCtrl)
.controller('HomeTabEditionModalCtrl', HomeTabEditionModalCtrl)
.service('HomeTabService', HomeTabService)
.directive('homeTabs', () => new DesktopHomeTabsDirective)