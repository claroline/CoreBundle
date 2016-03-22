/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import 'angular/index'

import UIRouter from 'angular-ui-router'
import bootstrap from 'angular-bootstrap'
import translation from 'angular-ui-translation/angular-translation'

import HomeTabsModule from '../homeTabs/homeTabs'
import WidgetsModule from '../widgets/widgets'
import Routing from './routing.js'
import DesktopHomeMainCtrl from './Controller/DesktopHomeMainCtrl'

angular.module('DesktopHomeModule', [
  'ui.bootstrap',
  'ui.bootstrap.tpls',
  'ui.translation',
  'ui.router',
  'HomeTabsModule',
  'WidgetsModule'
])
.controller('DesktopHomeMainCtrl', ['$http', 'HomeTabService', 'WidgetService', DesktopHomeMainCtrl])
.config(Routing)
