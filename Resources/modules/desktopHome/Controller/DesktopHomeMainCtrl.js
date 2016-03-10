/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
    
export default class DesktopHomeMainCtrl {
        
    constructor($http, $uibModal, $sce, ClarolineAPIService) {
        this.$http = $http
        this.$uibModal = $uibModal
        this.$sce = $sce
        this.ClarolineAPIService = ClarolineAPIService
        this.adminHomeTabs = []
        this.userHomeTabs = []
        this.workspaceHomeTabs = []
        this.widgets = []
        this.editionMode = false
        this.isHomeLocked = true
        this.selectedTabId = 0
        this.selectedTabIsLocked = true
        this.gridsterOptions = {
            columns: 12,
            floating: true,
            resizable: {
                enabled: false,
                handles: ['e', 's', 'w', 'ne', 'se', 'sw', 'nw'],
                start: (event, $element, widget) => {},
                resize: (event, $element, widget) => {},
                stop: (event, $element, widget) => {
                    this._updateWidgetsDisplay()
                }
            },
            draggable: {
                enabled: false,
                handle: '.widget-heading',
                start: (event, $element, widget) => {},
                drag: (event, $element, widget) => {},
                stop: (event, $element, widget) => {
                    this._updateWidgetsDisplay()
                }
            }
        }
        this._addUserHomeTabCallback = this._addUserHomeTabCallback.bind(this)
        this._updateUserHomeTabCallback = this._updateUserHomeTabCallback.bind(this)
        this._removeAdminHomeTabCallback = this._removeAdminHomeTabCallback.bind(this)
        this._removeUserHomeTabCallback = this._removeUserHomeTabCallback.bind(this)
        this._removeWorkspaceHomeTabCallback = this._removeWorkspaceHomeTabCallback.bind(this)
        this._updateWidgetsDisplay = this._updateWidgetsDisplay.bind(this)
        this._removeWidgetCallback = this._removeWidgetCallback.bind(this)
        this.initialize()
    }
    
    _addUserHomeTabCallback(data) {
        this.userHomeTabs.push(data)
    }
    
    _updateUserHomeTabCallback(data) {
        if (data['tabId']) {
        
            for (let i = 0; i < this.userHomeTabs.length; i++) {
                
                if (data['tabId'] === this.userHomeTabs[i]['tabId']) {
                    this.userHomeTabs[i]['tabName'] = data['tabName']
                    this.userHomeTabs[i]['color'] = data['color']
                    break;
                }
            }
        }
    }
    
    _removeAdminHomeTabCallback(data) {
        
        if (data['tabId']) {
        
            for (let i = 0; i < this.adminHomeTabs.length; i++) {

                if (data['tabId'] === this.adminHomeTabs[i]['tabId']) {
                    this.adminHomeTabs.splice(i, 1)
                    break
                }
            }

            if (data['tabId'] === this.selectedTabId) {
                this.selectDefaultHomeTab()
            }
        }
    }
    
    _removeUserHomeTabCallback(data) {
        
        if (data['tabId']) {
        
            for (let i = 0; i < this.userHomeTabs.length; i++) {

                if (data['tabId'] === this.userHomeTabs[i]['tabId']) {
                    this.userHomeTabs.splice(i, 1)
                    break
                }
            }

            if (data['tabId'] === this.selectedTabId) {
                this.selectDefaultHomeTab()
            }
        }
    }
    
    _removeWorkspaceHomeTabCallback(data) {
        
        if (data['tabId']) {
        
            for (let i = 0; i < this.workspaceHomeTabs.length; i++) {

                if (data['tabId'] === this.workspaceHomeTabs[i]['tabId']) {
                    this.workspaceHomeTabs.splice(i, 1)
                    break
                }
            }

            if (data['tabId'] === this.selectedTabId) {
                this.selectDefaultHomeTab()
            }
        }
    }
    
    _updateWidgetsDisplay() {
        console.log(this.widgets)
    }
    
    _removeWidgetCallback(data) {
        
        if (data['id']) {
        
            for (let i = 0; i < this.widgets.length; i++) {

                if (data['id'] === this.widgets[i]['instanceId']) {
                    this.widgets.splice(i, 1)
                    break
                }
            }
        }
    }
    
    toggleEditionMode() {
        const route = Routing.generate('api_put_desktop_home_edition_mode_toggle')
        this.$http.put(route).then(datas => {
            
            if (datas['status'] === 200) {
                this.editionMode = datas['data']
                this.updateGristerEdition()
            }
        })
    }
    
    showTab(tabId) {
        this.selectedTabId = tabId
        this.loadWidgets(tabId)
    }
    
    hideAmdinHomeTab($event, tabConfigId) {
        $event.stopPropagation()
        
        if (!this.isHomeLocked && this.editionMode) {
            const url = Routing.generate('api_put_admin_home_tab_visibility_toggle', {htc: tabConfigId})

            this.ClarolineAPIService.confirm(
                {url, method: 'PUT'},
                this._removeAdminHomeTabCallback,
                Translator.trans('home_tab_delete_confirm_title', {}, 'platform'),
                Translator.trans('home_tab_delete_confirm_message', {}, 'platform')
            )
        }
    }
    
    createUserHomeTab() {
        
        if (!this.isHomeLocked && this.editionMode) {
            const modal = this.$uibModal.open({
                templateUrl: Routing.generate(
                    'api_get_home_tab_creation_form',
                    {'_format': 'html'}
                ),
                controller: 'HomeTabCreationModalCtrl',
                controllerAs: 'htfmc',
                resolve: {
                    callback: () => { return this._addUserHomeTabCallback }
                }
            })

            modal.result.then(result => {

                if (!result) {
                    return
                } else {
                    this._addUserHomeTabCallback(result)
                }
            })
        }
    }
    
    editUserHomeTab($event, tabId) {
        $event.stopPropagation()
        
        if (!this.isHomeLocked && this.editionMode) {
            
            const modal = this.$uibModal.open({
                templateUrl: Routing.generate(
                    'api_get_home_tab_edition_form',
                    {'_format': 'html', homeTab: tabId}
                ) + '?bust=' + Math.random().toString(36).slice(2),
                controller: 'HomeTabEditionModalCtrl',
                controllerAs: 'htfmc',
                resolve: {
                    homeTabId: () => { return tabId },
                    callback: () => { return this._updateUserHomeTabCallback }
                }
            })

            modal.result.then(result => {
                
                if (!result) {
                    return
                } else {
                    this._updateUserHomeTabCallback(result)
                }
            })
        }
    }
    
    deleteUserHomeTab($event, tabConfigId) {
        $event.stopPropagation()
        
        if (!this.isHomeLocked && this.editionMode) {
            const url = Routing.generate('api_delete_user_home_tab', {htc: tabConfigId})

            this.ClarolineAPIService.confirm(
                {url, method: 'DELETE'},
                this._removeUserHomeTabCallback,
                Translator.trans('home_tab_delete_confirm_title', {}, 'platform'),
                Translator.trans('home_tab_delete_confirm_message', {}, 'platform')
            )
        }
    }
    
    deletePinnedWorkspaceHomeTab($event, tabConfigId) {
        $event.stopPropagation()
        
        if (this.isHomeLocked || this.editionMode) {
            const url = Routing.generate('api_delete_pinned_workspace_home_tab', {htc: tabConfigId})

            this.ClarolineAPIService.confirm(
                {url, method: 'DELETE'},
                this._removeWorkspaceHomeTabCallback,
                Translator.trans('home_tab_bookmark_delete_confirm_title', {}, 'platform'),
                Translator.trans('home_tab_bookmark_delete_confirm_message', {}, 'platform')
            )
        }
    }
    
    selectDefaultHomeTab() {
        this.selectedTabId = 0
        this.selectedTabIsLocked = true
        
        if (this.adminHomeTabs.length > 0) {
            this.selectedTabId = this.adminHomeTabs[0]['tabId']
        } else if (this.userHomeTabs.length > 0) {
            this.selectedTabId = this.userHomeTabs[0]['tabId']
        } else if (this.workspaceHomeTabs.length > 0) {
            this.selectedTabId = this.workspaceHomeTabs[0]['tabId']
        }
        this.loadWidgets(this.selectedTabId)
    }
    
    loadWidgets(tabId) {
        
        if (tabId === 0) {
            this.widgets = []
        } else {
            const route = Routing.generate('api_get_desktop_home_tab_widgets', {homeTab: tabId})
            this.$http.get(route).then(datas => {

                if (datas['status'] === 200) {
                    this.selectedTabIsLocked = datas['data']['isLockedHomeTab']
                    this.widgets = datas['data']['widgets']
                    this.updateGristerEdition()
                }
            })  
        }
    }
    
    updateGristerEdition() {
        const editable = !this.isHomeLocked && this.editionMode && !this.selectedTabIsLocked
        this.gridsterOptions['resizable']['enabled'] = editable
        this.gridsterOptions['draggable']['enabled'] = editable
    }
    
    createUserWidget(tabId) {
        
        if (!this.isHomeLocked && this.editionMode) {
            console.log('Creating Widget... ' + tabId)
        }
    }
    
    deleteUserWidget($event, widgetHTCId) {
        $event.stopPropagation()
        
        if (!this.isHomeLocked && this.editionMode) {
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
    
    hideAdminWidget($event, widgetHTCId) {
        $event.stopPropagation()
        
        if (!this.isHomeLocked && this.editionMode && !this.selectedTabIsLocked) {
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
    
    initialize() {
        const route = Routing.generate('api_get_desktop_home_tabs')
        this.$http.get(route).then(datas => {

            if (datas['status'] === 200) {
                this.adminHomeTabs = datas['data']['tabsAdmin']
                this.userHomeTabs = datas['data']['tabsUser']
                this.workspaceHomeTabs = datas['data']['tabsWorkspace']
                this.editionMode = datas['data']['editionMode']
                this.isHomeLocked = datas['data']['isHomeLocked']
                this.selectDefaultHomeTab()
            }
        })
    }
}