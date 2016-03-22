/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

export default class WidgetInstanceEditionModalCtrl {
    constructor($http, $uibModal, $uibModalInstance, ClarolineAPIService, widgetDisplayId, callback) {
        this.$http = $http
        this.$uibModal = $uibModal
        this.$uibModalInstance = $uibModalInstance
        this.ClarolineAPIService = ClarolineAPIService
        this.widgetDisplayId = widgetDisplayId
        this.callback = callback
        this.widgetInstance = {}
    }

    submit() {
        let data = this.ClarolineAPIService.formSerialize(
            'widget_instance_config_form',
            this.widgetInstance
        )
        const route = Routing.generate(
            'api_put_widget_instance_edition',
            {'_format': 'html', wdc: this.widgetDisplayId}
        )
        const headers = {headers: {'Content-Type': 'application/x-www-form-urlencoded'}}

        this.$http.put(route, data, headers).then(
            d => {
                this.$uibModalInstance.close(d.data)
            },
            d => {
                if (d.status === 400) {
                    this.$uibModalInstance.close()
                    const instance = this.$uibModal.open({
                        template: d.data,
                        controller: 'WidgetInstanceEditionModalCtrl',
                        controllerAs: 'wfmc',
                        bindToController: true,
                        resolve: {
                            widgetDisplayId: () => { return this.widgetDisplayId },
                            callback: () => { return this.callback },
                            widgetInstance: () => { return this.widgetInstance }
                        }
                    })

                    instance.result.then(result => {

                        if (!result) {
                            return
                        } else {
                            this.callback(result)
                        }
                    })
                }
            }
        )
    }
}