/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
    
export default class DesktopHomeMainCtrl {
        
    constructor($http, $sce, ClarolineAPIService) {
        this.$http = $http
        this.$sce = $sce
        this.ClarolineAPIService = ClarolineAPIService
        this.adminHomeTabs = []
        this.userHomeTabs = []
        this.workspaceHomeTabs = []
        this.widgets = []
        this.editionMode = false
        this.isHomeLocked = true
        this.selectedTabId = 0
        
        this._removeWorkspaceHomeTabCallback = this._removeWorkspaceHomeTabCallback.bind(this)
        this.initialize()
    }
    
    toggleEditionMode() {
        const route = Routing.generate('api_put_desktop_home_edition_mode_toggle')
        this.$http.put(route).then(datas => {
            
            if (datas['status'] === 200) {
                this.editionMode = datas['data']
            }
        })
    }
    
    showTab(tabId) {
        this.selectedTabId = tabId
    }
    
    deletePinnedWorkspaceHomeTab(tabId, tabConfigId) {
        
        if (this.editionMode) {
            const route = Routing.generate('api_delete_pinned_workspace_home_tab', {htc: tabConfigId})

            this.ClarolineAPIService.confirm(
                {route, method: 'DELETE'},
                this._removeWorkspaceHomeTabCallback,
                Translator.trans('home_tab_bookmark_delete_confirm_title', {}, 'platform'),
                Translator.trans('home_tab_bookmark_delete_confirm_title', {}, 'platform')
            );
    
//            this.$http.delete(route).then(datas => {
//                
//                if (datas['status'] === 200 && datas['data']['tabId'] === tabId) {
//                    this.removeWorkspaceHomeTab(tabId)
//                }
//            })
        }
    }
    
    removeAdminHomeTab(tabId) {
        
        for (let i = 0; i < this.adminHomeTabs.length; i++) {
            
            if (tabId === this.adminHomeTabs[i]['tabId']) {
                this.adminHomeTabs.splice(i, 1)
                break
            }
        }
        
        if (tabId === this.selectedTabId) {
            this.selectDefaultHomeTab()
        }
    }
    
    removeUserHomeTab(tabId) {
        
        for (let i = 0; i < this.userHomeTabs.length; i++) {
            
            if (tabId === this.userHomeTabs[i]['tabId']) {
                this.userHomeTabs.splice(i, 1)
                break
            }
        }
        
        if (tabId === this.selectedTabId) {
            this.selectDefaultHomeTab()
        }
    }
    
    _removeWorkspaceHomeTabCallback(data) {
        console.log(data)
        
//        for (let i = 0; i < this.workspaceHomeTabs.length; i++) {
//            
//            if (tabId === this.workspaceHomeTabs[i]['tabId']) {
//                this.workspaceHomeTabs.splice(i, 1)
//                break
//            }
//        }
//        
//        if (tabId === this.selectedTabId) {
//            this.selectDefaultHomeTab()
//        }
    }
    
    selectDefaultHomeTab() {
        this.selectedTabId = 0
        
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
            // Load widgets datas
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