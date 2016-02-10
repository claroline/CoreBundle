var controller = function(
    $http, 
    clarolineSearch, 
    clarolineAPI,
    $uibModal,
    $scope
) { 
    this.search = '';
    this.savedSearch = [];
    this.fields = [];
    this.selected = [];
    this.alerts = [];
    var vm = this;

    var translate = function(key, data) {
        if (!data) data = {};
        return translator.trans(key, data, 'platform');
    }

    var generateQsForSelected = function() {
        var qs = '';

        for (var i = 0; i < this.selected.length; i++) {
            qs += 'groupIds[]=' + this.selected[i].id + '&';
        }

        return qs;
    }.bind(this);

    var deleteCallback = function(data) {
        
        for (var i = 0; i < this.selected.length; i++) {
            this.alerts.push({
                type: 'success',
                msg: translate('group_removed', {group: this.selected[i].name})
            });
        }

        this.dataTableOptions.paging.count -= this.selected.length;
        clarolineAPI.removeElements(this.selected, this.groups);
        this.selected.splice(0, this.selected.length);
    }.bind(this);

    $http.get(Routing.generate('api_get_group_searchable_fields')).then(function(d) {
        vm.fields = d.data;
    });

    var columns = [
        {name: translate('name'), prop: "name", isCheckboxColumn: true, headerCheckbox: true},
        {
            name: translate('actions'),
            cellRenderer: function(scope) {
                var groupId = scope.$row.id;
                var users = '<a ui-sref="users.groups.users({groupId: ' + groupId + '})"><i class="fa fa-users"></i> </a>';
                var edit =  '<a class="pointer" ng-click="gc.clickEdit($row)"><i class="fa fa-cog"></i></a>';
                var actions = users + edit;

                return actions;
            }
        }
    ];

    this.dataTableOptions = {
        scrollbarV: false,
        columnMode: 'force',
        headerHeight: 50,
        footerHeight: 50,
        selectable: true,
        multiSelect: true,
        checkboxSelection: true,
        columns: columns,
        paging: {
            externalPaging: true,
            size: 10
        }
    };

    this.onSearch = function(searches) {
        this.savedSearch = searches;
        clarolineSearch.find('api_get_search_groups', searches, this.dataTableOptions.paging.offset, this.dataTableOptions.paging.size).then(function(d) {
            this.groups = d.data.groups;
            this.dataTableOptions.paging.count = d.data.total;
        }.bind(this));
    }.bind(this);

    this.paging = function(offset, size) {
        clarolineSearch.find('api_get_search_groups', this.savedSearch, offset, size).then(function(d) {
            var groups = d.data.groups;

            //I know it's terrible... but I have no other choice with this table.
            for (var i = 0; i < offset * size; i++) {
                groups.unshift({});
            }

            this.groups = groups;
            this.dataTableOptions.paging.count = d.data.total;
        }.bind(this));
    }.bind(this);

    this.clickDelete = function() {
        var url = Routing.generate('api_delete_groups') + '?' + generateQsForSelected();

        var groups = '';

        for (var i = 0; i < this.selected.length; i++) {
            groups +=  this.selected[i].name
            if (i < this.selected.length - 1) groups += ', ';
        }

        clarolineAPI.confirm(
            {url: url, method: 'DELETE'},
            deleteCallback,
            translate('delete_groups'),
            translate('delete_groups_confirm', {group_list: groups})
        );
    }.bind(this);

    this.clickEdit = function(group) {
        var modalInstance = $uibModal.open({
            templateUrl: Routing.generate('api_get_edit_group_form', {'_format': 'html', 'group': group.id}),
            controller: 'EditModalController'
        });

        modalInstance.result.then(function (result) {
            if (!result) return;
            //dirty but it works
            vm.groups = clarolineAPI.replaceById(result, vm.groups);
        });
    }

    this.clickNew = function() {
        var modalInstance = $uibModal.open({
            templateUrl: Routing.generate('api_get_create_group_form', {'_format': 'html'}),
            controller: 'CreateModalController'
        });

        modalInstance.result.then(function (result) {
            if (!result) return;
            //dirty but it works
            console.log(result);
            vm.groups.push(result);
            vm.dataTableOptions.paging.count = vm.groups.length;

            this.alerts.push({
                type: 'success',
                msg: translate('group_created', {group: result.name})
            });
        }.bind(this));
    }.bind(this);

    this.closeAlert = function(index) {
        this.alerts.splice(index, 1);
    }.bind(this);
};

angular.module('GroupsManager').controller('GroupController', [
    '$http',
    'clarolineSearch',
    'clarolineAPI',
    '$uibModal',
    '$scope',
    controller
]);