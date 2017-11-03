(function () {
  "use strict";

  var appMarketCompanyList = function (
    $state,
    $location,
    $q,
    MarketCompanies,
    $translate,
    $filter,
    uiGridConstants,
    UIGridHelper,
    $timeout
  ) {
    var ctrl = function () {
      var self = this;

      var cellTemplates = {
        actions: "<ng-include ng-init='actionsTpl = \"//app/modules/market/js/directives/CompanyList/ActionsColTemplate.html\"' src='actionsTpl'></ng-include>",
      };

      this.tableName = 'companyList';

      this.$state = $state;

      this.loadingPromise = null;

      this.updateData = function () {
        self.getData().then(self.onDataUpdate);
      };

      this.updateDataDebounced = _.debounce(this.updateData, 500);

      this.columnDefs = [{
        field: 'nameTranslated',
        displayName: $translate.instant('Name'),
        enableSorting: false,
        enableColumnMenu: false
      }, {
        field: 'descriptionTranslated',
        displayName: $translate.instant('Description'),
        enableSorting: false,
        enableColumnMenu: false,
        hide: ['xs', 'sm']
      }, {
        field: 'partner.company',
        displayName: $translate.instant('Partner'),
        enableSorting: false,
        enableColumnMenu: false
      }, {
        field: 'profile.nameTranslated',
        displayName: $translate.instant('Profile'),
        enableSorting: false,
        enableColumnMenu: false
      }, {
        field: 'appProfile.id',
        displayName: $translate.instant('App profile ID'),
        enableSorting: false,
        enableColumnMenu: false
      }, {
        name: 'actions',
        displayName: $translate.instant('Actions'),
        enableSorting: false,
        enableColumnMenu: false,
        enableFiltering: false,
        cellTemplate: cellTemplates.actions,
        width: 110,
        hide: ['xs']
      }];

      this.requestOptions = UIGridHelper.makeRequestOptions(this.columnDefs, this.tableName);

      this.gridHelper = new UIGridHelper.gridHelper(this.requestOptions, this.updateDataDebounced, this.columnDefs, this.tableName);

      this.onResize = function () {};

      this.gridOptions = {
        columnDefs: this.columnDefs,
        data: [],
        enableFiltering: false,
        useExternalFiltering: true,
        onRegisterApi: function (gridApi) {
          self.gridApi = gridApi;
          self.gridHelper.registerApi(gridApi);
          $timeout(self.gridHelper.applyFiltersToGrid, 200);
          self.gridApi.core.on.sortChanged(null, self.gridHelper.onSort);
          self.gridApi.core.on.filterChanged(null, self.gridHelper.onColumnFilter);
        },
        appScopeProvider: self,
        app: {
          onResize: self.onResize
        }
      };

      this.getTableStyle = function () {
        var rowHeight = 30;

        return {
          height: (rowHeight * (self.requestOptions.perPage + 1)) + 'px'
        };
      };

      this.getData = function () {
        return self.loadingPromise = MarketCompanies
          .getList(
            self.gridHelper.prepareParams(),
            {onlySingleRequest: true}
          );
      };

      this.onDataUpdate = function (response) {
        self.gridOptions.data = response.data.data;
        self.gridOptions.total = response.data.total;
        self.firstDataReceived = true;
      };

      this.isFiltered = self.gridHelper.isFiltered;
      this.clearAllFilters = self.gridHelper.clearAllFilters;

      self.updateData();
    };

    return {
      restrict: 'EA',
      scope: {},
      templateUrl: '/app/modules/market/js/directives/CompanyList/CompanyList.html',
      controller: ctrl,
      controllerAs: 'ctrl',
      bindToController: true
    };
  };

  angular.module('admin.market')
    .directive(
      'appMarketCompanyList',
      [
        '$state',
        '$location',
        '$q',
        'MarketCompanies',
        '$translate',
        '$filter',
        'uiGridConstants',
        'UIGridHelper',
        '$timeout',
        appMarketCompanyList
      ]
    );
})();