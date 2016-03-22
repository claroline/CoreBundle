/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

export default class WidgetService {
  constructor($http, $uibModal, ClarolineAPIService) {
    this.$http = $http
    this.$uibModal = $uibModal
    this.ClarolineAPIService = ClarolineAPIService
    this.widgets = []
    this.options = {
      canEdit: false
    }
    this.widgetsDisplayOptions = {}
    this.widgetHasChanged = false
    this.gridsterOptions = {
      columns: 12,
      floating: true,
      resizable: {
        enabled: false,
        handles: ['ne', 'se', 'sw', 'nw'],
        start: (event, $element, widget) => {},
        resize: (event, $element, widget) => {
          this.widgetHasChanged = true
        },
        stop: (event, $element, widget) => {

          if (this.widgetHasChanged) {
            this.widgetHasChanged = false
            this._updateWidgetsDisplay()
          }
        }
      },
      draggable: {
        enabled: false,
        handle: '.widget-header',
        start: (event, $element, widget) => {},
        drag: (event, $element, widget) => {
          this.widgetHasChanged = true
        },
        stop: (event, $element, widget) => {

          if (this.widgetHasChanged) {
            this.widgetHasChanged = false
            this._updateWidgetsDisplay()
          }
        }
      }
    }
    this._addUserWidgetCallback = this._addUserWidgetCallback.bind(this)
    this._updateUserWidgetCallback = this._updateUserWidgetCallback.bind(this)
    this._updateWidgetsDisplay = this._updateWidgetsDisplay.bind(this)
    this._removeWidgetCallback = this._removeWidgetCallback.bind(this)
  }

  _addUserWidgetCallback(data) {
    this.widgetsDisplayOptions[data['displayId']] = {
      id: data['displayId'],
      row: data['row'],
      col: data['col'],
      sizeX: data['sizeX'],
      sizeY: data['sizeY']
    }
    this.widgets.push(data)
    this.checkDesktopWidgetsDisplayOptions()
  }

  _updateUserWidgetCallback(data) {
    const index = this.widgets.findIndex(w => w['instanceId'] === data['instanceId'])

    if (index > -1) {
      this.widgets[index]['instanceName'] = data['instanceName']
      this.widgets[index]['color'] = data['color']
      this.widgets[index]['textTitleColor'] = data['textTitleColor']
    }
  }

  _updateWidgetsDisplay() {
    this.checkDesktopWidgetsDisplayOptions()
  }

  _removeWidgetCallback(data) {

    if (data['id']) {
      const index = this.widgets.findIndex(w => data['id'] === w['instanceId'])

      if (index > -1) {
        this.widgets.splice(index, 1)
      }
      this.checkDesktopWidgetsDisplayOptions()
    }
  }

  getWidgets() {
    return this.widgets
  }

  getWidgetsDisplayOptions() {
    return this.widgetsDisplayOptions
  }

  getOptions() {
    return this.options
  }

  getGridsterOptions() {
    return this.gridsterOptions
  }

  loadDesktopWidgets(tabId, isEditionEnabled) {
    this.options['canEdit'] = false

    if (tabId === 0) {
      this.widgets.splice(0, this.widgets.length)
    } else {
      const route = Routing.generate('api_get_desktop_home_tab_widgets', {homeTab: tabId})
      this.$http.get(route).then(datas => {

        if (datas['status'] === 200) {
          this.options['canEdit'] = isEditionEnabled && !datas['data']['isLockedHomeTab']
          this.widgets.splice(0, this.widgets.length)
          angular.merge(this.widgets, datas['data']['widgets'])
          this.generateWidgetsDisplayOptions()
          this.checkDesktopWidgetsDisplayOptions()
          this.updateGristerEdition()
        }
      })
    }
  }

  generateWidgetsDisplayOptions() {
    this.widgets.forEach(w => {
      const displayId = w['displayId']
      this.widgetsDisplayOptions[displayId] = {
        id: w['displayId'],
        row: w['row'],
        col: w['col'],
        sizeX: w['sizeX'],
        sizeY: w['sizeY']
      }
    })
  }

  checkDesktopWidgetsDisplayOptions() {
    let modifiedWidgets = []

    this.widgets.forEach(w => {
      const displayId = w['displayId']

      if (w['row'] !== this.widgetsDisplayOptions[displayId]['row'] ||
        w['col'] !== this.widgetsDisplayOptions[displayId]['col'] ||
        w['sizeX'] !== this.widgetsDisplayOptions[displayId]['sizeX'] ||
        w['sizeY'] !== this.widgetsDisplayOptions[displayId]['sizeY']) {

        const widgetDatas = {
          id: displayId,
          row: w['row'],
          col: w['col'],
          sizeX: w['sizeX'],
          sizeY: w['sizeY']
        }
        modifiedWidgets.push(widgetDatas)
      }
    })

    if (modifiedWidgets.length > 0) {
      console.log(modifiedWidgets)
      const json = JSON.stringify(modifiedWidgets)
      const route = Routing.generate('api_put_desktop_widget_display_update', {datas: json})
      this.$http.put(route).then(
        (datas) => {
          if (datas['status'] === 200) {
            const displayDatas = datas['data']

            displayDatas.forEach(d => {
              const id = d['id']
              this.widgetsDisplayOptions[id]['row'] = d['row']
              this.widgetsDisplayOptions[id]['col'] = d['col']
              this.widgetsDisplayOptions[id]['sizeX'] = d['sizeX']
              this.widgetsDisplayOptions[id]['sizeY'] = d['sizeY']
            })
          }
        },
        () => {
          console.log('error')
        }
      )
    } else {
      console.log('no modif')
    }
  }

  updateGristerEdition() {
    const editable = this.options['canEdit']
    this.gridsterOptions['resizable']['enabled'] = editable
    this.gridsterOptions['draggable']['enabled'] = editable
  }

  createUserWidget(tabConfigId) {

    if (!this.isHomeTabLocked) {
      const modal = this.$uibModal.open({
        templateUrl: Routing.generate(
          'api_get_widget_instance_creation_form',
          {'_format': 'html', htc: tabConfigId}
        ),
        controller: 'DesktopWidgetInstanceCreationModalCtrl',
        controllerAs: 'wfmc',
        resolve: {
          homeTabConfigId: () => { return tabConfigId },
          callback: () => { return this._addUserWidgetCallback }
        }
      })

      modal.result.then(result => {

        if (!result) {
          return
        } else {
          this._addUserWidgetCallback(result)
        }
      })
    }
  }

  editUserWidget(widgetDisplayId) {

    if (!this.isHomeTabLocked) {
      const modal = this.$uibModal.open({
        templateUrl: Routing.generate(
          'api_get_widget_instance_edition_form',
          {'_format': 'html', wdc: widgetDisplayId}
        ) + '?bust=' + Math.random().toString(36).slice(2),
        controller: 'WidgetInstanceEditionModalCtrl',
        controllerAs: 'wfmc',
        resolve: {
          widgetDisplayId: () => { return widgetDisplayId },
          callback: () => { return this._updateUserWidgetCallback }
        }
      })

      modal.result.then(result => {

        if (!result) {
            return
        } else {
            this._updateUserWidgetCallback(result)
        }
      })
    }
  }

  deleteUserWidget(widgetHTCId) {

    if (!this.isHomeTabLocked) {
      const url = Routing.generate(
        'api_delete_desktop_widget_home_tab_config',
        {widgetHomeTabConfig: widgetHTCId}
      )

      this.ClarolineAPIService.confirm(
        {url, method: 'DELETE'},
        this._removeWidgetCallback,
        Translator.trans('widget_home_tab_delete_confirm_title', {}, 'platform'),
        Translator.trans('widget_home_tab_delete_confirm_message', {}, 'platform')
      )
    }
  }

  hideAdminWidget(widgetHTCId) {

    if (!this.isHomeTabLocked) {
      const url = Routing.generate(
        'api_put_desktop_widget_home_tab_config_visibility_change',
        {widgetHomeTabConfig: widgetHTCId}
      )

      this.ClarolineAPIService.confirm(
        {url, method: 'PUT'},
        this._removeWidgetCallback,
        Translator.trans('widget_home_tab_delete_confirm_title', {}, 'platform'),
        Translator.trans('widget_home_tab_delete_confirm_message', {}, 'platform')
      )
    }
  }
}