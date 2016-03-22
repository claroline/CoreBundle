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
import DesktopWidgetInstanceCreationModalCtrl from './Controller/DesktopWidgetInstanceCreationModalCtrl'
import WidgetInstanceEditionModalCtrl from './Controller/WidgetInstanceEditionModalCtrl'
import WidgetService from './Service/WidgetService'
import WidgetsDirective from './Directive/WidgetsDirective'

//import Interceptors from '../interceptorsDefault'
//import HtmlTruster from '../html-truster/module'
//import bootstrap from 'angular-bootstrap'

angular.module('WidgetsModule', [
  'ui.bootstrap',
  'ui.bootstrap.tpls',
  'colorpicker.module',
  'ui.translation',
  'ClarolineAPI',
  'gridster'
])
.controller('DesktopWidgetInstanceCreationModalCtrl', DesktopWidgetInstanceCreationModalCtrl)
.controller('WidgetInstanceEditionModalCtrl', WidgetInstanceEditionModalCtrl)
.service('WidgetService', WidgetService)
.directive('widgets', () => new WidgetsDirective)
