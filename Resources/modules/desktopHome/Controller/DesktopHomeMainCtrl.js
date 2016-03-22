/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
    
export default class DesktopHomeMainCtrl {
        
  constructor($http, HomeTabService, WidgetService) {
    this.$http = $http
    this.HomeTabService = HomeTabService
    this.WidgetService = WidgetService
    this.adminHomeTabs = HomeTabService.getAdminHomeTabs()
    this.userHomeTabs = HomeTabService.getUserHomeTabs()
    this.workspaceHomeTabs = HomeTabService.getWorkspaceHomeTabs()
    this.homeTabsOptions = HomeTabService.getOptions()
    this.widgets = WidgetService.getWidgets()
    this.widgetsOptions = WidgetService.getOptions()
    this.widgetsDisplayOptions = WidgetService.getWidgetsDisplayOptions()
    this.editionMode = false
    this.isHomeLocked = true
    this.gridsterOptions = WidgetService.getGridsterOptions()
    this.initialize()
  }

  toggleEditionMode() {
    const route = Routing.generate('api_put_desktop_home_edition_mode_toggle')
    this.$http.put(route).then(datas => {

      if (datas['status'] === 200) {
        this.editionMode = datas['data']
        this.homeTabsOptions['canEdit'] = !this.isHomeLocked && this.editionMode
        this.widgetsOptions['canEdit'] = !this.isHomeLocked && this.editionMode && !this.homeTabsOptions['selectedTabIsLocked']
        this.WidgetService.updateGristerEdition()
      }
    })
  }

  showTab(tabId, tabConfigId, tabIsLocked) {
    this.homeTabsOptions['selectedTabId'] = tabId
    this.homeTabsOptions['selectedTabConfigId'] = tabConfigId
    this.homeTabsOptions['selectedTabIsLocked'] = tabIsLocked
    this.WidgetService.loadDesktopWidgets(tabId, !this.isHomeLocked && this.editionMode)
  }

  createUserHomeTab() {
    this.HomeTabService.createUserHomeTab()
  }

  editUserHomeTab($event, tabId) {
    $event.stopPropagation()
    this.HomeTabService.editUserHomeTab(tabId)
  }

  hideAmdinHomeTab($event, tabConfigId) {
    $event.stopPropagation()
    this.HomeTabService.hideAmdinHomeTab(tabConfigId)
  }

  deleteUserHomeTab($event, tabConfigId) {
    $event.stopPropagation()
    this.HomeTabService.deleteUserHomeTab(tabConfigId)
  }

  deletePinnedWorkspaceHomeTab($event, tabConfigId) {
    $event.stopPropagation()
    this.HomeTabService.deletePinnedWorkspaceHomeTab(tabConfigId)
  }

  createUserWidget(tabConfigId) {

    if (!this.isHomeLocked && this.editionMode) {
      this.WidgetService.createUserWidget(tabConfigId)
    }
  }

  editUserWidget($event, widgetDisplayId) {
    $event.stopPropagation()

    if (!this.isHomeLocked && this.editionMode) {
      this.WidgetService.editUserWidget(widgetDisplayId)
    }
  }

  deleteUserWidget($event, widgetHTCId) {
    $event.stopPropagation()

    if (!this.isHomeLocked && this.editionMode) {
      this.WidgetService.deleteUserWidget(widgetHTCId)
    }
  }

  hideAdminWidget($event, widgetHTCId) {
    $event.stopPropagation()

    if (!this.isHomeLocked && this.editionMode) {
      this.WidgetService.hideAdminWidget(widgetHTCId)
    }
  }

  initialize() {
    const route = Routing.generate('api_get_desktop_options')
    this.$http.get(route).then(datas => {

      if (datas['status'] === 200) {
        this.isHomeLocked = datas['data']['isHomeLocked']
        this.editionMode = datas['data']['editionMode']
        this.homeTabsOptions['canEdit'] = !this.isHomeLocked && this.editionMode
        this.HomeTabService.loadDesktopHomeTabs()
      }
    })
  }
}