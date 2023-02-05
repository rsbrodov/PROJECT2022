<template>
  <v-container fluid>
    <search-layout
      :loading="loading"
      multi-line
      @search="search"
      @resetSearch="resetSearch">
      <template v-slot:searchFields>
        <v-row dense>
          <v-col cols="12"
                 sm="6"
                 md="3"
                 lg="2">
            <trol-date-picker v-model="props.searchBy.dateFrom"
                              :label="$t('Period from')"
                              hide-details
                              clearable />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 md="3"
                 lg="2">
            <trol-date-picker v-model="props.searchBy.dateUntil"
                              :label="$t('Period until')"
                              hide-details
                              clearable />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 md="3"
                 lg="2">
            <trol-lazy-select v-model="props.searchBy.service"
                              item-text="name"
                              item-value="id"
                              item-disabled="dummy"
                              enabled-items-url="/lazy-select/service/enabled"
                              items-url="/lazy-select/service"
                              selected-url="/lazy-select/service/current/{id}"
                              :label="$t('Service')"
                              full-load
                              cached
                              clearable
                              hide-details
                              @input="onServiceChange" />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 lg="2">
            <trol-lazy-select v-model="props.searchBy.counterparty"
                              item-text="name"
                              item-value="id"
                              item-disabled="dummy"
                              items-url="/lazy-select/counterparty/client"
                              selected-url="/lazy-select/counterparty/{id}"
                              :label="$t('Client')"
                              clearable
                              hide-details />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 md="3"
                 lg="1">
            <trol-lazy-select v-model="props.searchBy.currency"
                              :label="$t('Currency')"
                              item-text="name"
                              item-value="id"
                              item-disabled="dummy"
                              items-url="/lazy-select/currency/enabled"
                              hide-details
                              clearable
                              cached
                              full-load />
          </v-col>
          <v-col cols="12"
                 sm="3"
                 md="3"
                 lg="1">
            <trol-country-select v-model="props.searchBy.dispatchCountry"
                                 :label="$t('Loading')"
                                 clearable
                                 hide-details />
          </v-col>
          <v-col cols="12"
                 sm="3"
                 md="3"
                 lg="1">
            <trol-country-select v-model="props.searchBy.deliveryCountry"
                                 :label="$t('delivery')"
                                 clearable
                                 hide-details />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 lg="2">
            <trol-lazy-select v-model="props.searchBy.clientManager"
                              item-text="name"
                              item-value="id"
                              item-disabled="dummy"
                              items-url="/lazy-select/user-contact"
                              selected-url="/lazy-select/user-contact/{id}"
                              :label="$t('Client-manager')"
                              full-load
                              clearable
                              hide-details />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 md="3"
                 lg="2">
            <trol-lazy-select v-model="props.searchBy.product"
                              item-text="name"
                              item-value="id"
                              item-disabled="dummy"
                              :enabled-items-url="enabledProductsUrl"
                              :items-url="productsUrl"
                              selected-url="/lazy-select/product/current/{id}"
                              :label="$t('Product')"
                              full-load
                              cached
                              clearable
                              hide-details />
          </v-col>
        </v-row>
      </template>
    </search-layout>
    <v-row no-gutters
           class="d-flex card-collection">
      <v-col cols="12"
             md="6"
             lg="3">
        <v-card class="fit-width mt-2 card-data">
          <v-card-text>
            <trol-dashboard-table
              :data="managersTableData"
              :columns="managersTableColumns">
              <template v-slot:cell-managerName="scope">
                <span v-if="scope.cell"
                      class="text-no-wrap filter-title"
                      @click="filtering('clientManager', parseInt(scope.row.managerId))">
                  <v-icon v-if="scope.cell && props.searchBy.clientManager === parseInt(scope.row.managerId)"
                          color="accent"
                          x-small>
                    mdi-filter-check
                  </v-icon>
                  {{scope.cell}}</span>
              </template>
              <template v-slot:cell-conclusion="scope">
                <span v-if="scope.cell"
                      :class="highlightColorClass(scope.cell)">{{scope.cell|finance(2, true)}}%</span>
              </template>
            </trol-dashboard-table>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12"
             md="6"
             lg="6">
        <v-card class="fit-width mt-2 card-data">
          <v-card-text>
            <trol-dashboard-table
              :data="productsTableData"
              :columns="productsTableColumns">
              <template v-slot:cell-productName="scope">
                <span v-if="scope.cell"
                      class="text-no-wrap filter-title"
                      @click="filtering('product', parseInt(scope.row.productId))">
                  <v-icon v-if="scope.cell && props.searchBy.product === parseInt(scope.row.productId)"
                          color="accent"
                          x-small>
                    mdi-filter-check
                  </v-icon>
                  {{scope.cell}}</span>
              </template>
              <template v-slot:cell-conclusion="scope">
                <span v-if="scope.cell"
                      :class="highlightColorClass(scope.cell)">{{scope.cell|finance(2, true)}}%</span>
              </template>
              <template v-slot:cell-planProfit="scope">
                <span v-if="scope.cell"
                      class="text-no-wrap">{{scope.cell|finance(2, true)}} {{scope.row.currencyName|currencySymbol}}</span>
              </template>
              <template v-slot:cell-factProfit="scope">
                <span v-if="scope.cell"
                      class="text-no-wrap">{{scope.cell|finance(2, true)}} {{scope.row.currencyName|currencySymbol}}</span>
              </template>
            </trol-dashboard-table>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12"
             md="6"
             lg="3">
        <v-card class="mt-2 card-data">
          <v-card-text>
            <trol-dashboard-table
              :data="pathTableData"
              :columns="pathTableColumns">
              <template v-slot:cell-dispatchCountryCode="scope">
                <span v-if="scope.cell"
                      class="text-no-wrap filter-title"
                      @click="filtering('pathDispatch', parseInt(scope.row.dispatchCountryId))">
                  <v-icon v-if="scope.cell && props.searchBy.dispatchCountry === parseInt(scope.row.dispatchCountryId)"
                          color="accent"
                          x-small>
                    mdi-filter-check
                  </v-icon>
                  {{scope.cell}}</span>
              </template>
              <template v-slot:cell-deliveryCountryCode="scope">
                <span v-if="scope.cell"
                      class="text-no-wrap filter-title"
                      @click="filtering('pathDelivery', parseInt(scope.row.deliveryCountryId))">
                  <v-icon v-if="scope.cell && props.searchBy.deliveryCountry === parseInt(scope.row.deliveryCountryId)"
                          color="accent"
                          x-small>
                    mdi-filter-check
                  </v-icon>
                  {{scope.cell}}</span>
              </template>
              <template v-slot:cell-conclusion="scope">
                <span v-if="scope.cell"
                      :class="highlightColorClass(scope.cell)">{{scope.cell|finance(2, true)}}%</span>
              </template>
            </trol-dashboard-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    <v-card class="fit-width mt-2">
      <v-card-text>
        <trol-data-table ref="dataTable"
                         :data="data"
                         :columns="columnsComputed"
                         :storage-key="$options.name"
                         :shown-columns.sync="props.shownColumns"
                         :columns-order.sync="props.columnsOrder"
                         no-quick-search
                         no-column-select
                         no-scale-controls
                         external-sorting
                         external-filter
                         external-grouping
                         @loadData="loadData">
          <template v-slot:th-conclusion="slotData">
            <v-row no-gutters
                   class="flex-nowrap"
                   align="end">
              <v-col class="flex-grow-0 text-wrap">
                {{slotData.column.label}}
              </v-col>
              <v-col class="flex-grow-0 pl-1">
                <trol-tooltip-wrapper top>
                  <template v-slot:activator="{on}">
                    <v-icon small
                            class="cursor-default"
                            color="primary"
                            v-on="on">
                      mdi-information-outline
                    </v-icon>
                  </template>
                  <v-row no-gutters>
                    <v-col cols="12"
                           class="pl-2">
                      <div>
                        <v-icon class="primary--text"
                                small>
                          mdi-square
                        </v-icon>
                        - {{$t('Profit within the expected')}}
                      </div>
                      <div>
                        <v-icon class="success--text"
                                small>
                          mdi-square
                        </v-icon>
                        - {{$t('Profit exceeded expected')}}
                      </div>
                      <div>
                        <v-icon class="error--text"
                                small>
                          mdi-square
                        </v-icon>
                        - {{$t('Profit is negative')}}
                      </div>
                    </v-col>
                  </v-row>
                </trol-tooltip-wrapper>
              </v-col>
            </v-row>
          </template>
          <template v-slot:cell-ordersId="scope">
            <router-link :to="{name:'OrderProperties', params:{id:scope.cell}}">
              {{scope.cell}}
            </router-link>
          </template>
          <template v-slot:cell-customerRequestId="scope">
            <router-link :to="{name:'TrolCustomerRequestProperties', params:{id:scope.cell}}">
              {{scope.cell}}
            </router-link>
          </template>
          <template v-slot:cell-managerName="scope">
            <router-link :to="{name:'CompanyStructureUserProperties', params:{id:scope.row.userId}}">
              {{scope.cell}}
            </router-link>
          </template>
          <template v-slot:cell-counterpartyName="scope">
            <router-link :to="{name:'CounterpartyProperties', params:{id:scope.row.counterpartyId}}">
              {{scope.cell}}
            </router-link>
          </template>
          <template v-slot:cell-planAmountPrice="scope">
            <span class="text-no-wrap">{{scope.cell ? scope.cell : 0 |finance(2, true)}} {{scope.row.currencyName|currencySymbol}}</span>
          </template>
          <template v-slot:cell-factAmountProfit="scope">
            <span v-if="scope.cell"
                  class="text-no-wrap">{{scope.cell|finance(2, true)}} {{scope.row.currencyName|currencySymbol}}</span>
          </template>
          <template v-slot:cell-planAmountCost="scope">
            <span v-if="scope.cell"
                  class="text-no-wrap">{{scope.cell|finance(2, true)}} {{scope.row.currencyName|currencySymbol}}</span>
          </template>
          <template v-slot:cell-factAmountCost="scope">
            <span v-if="scope.cell"
                  class="text-no-wrap">{{scope.cell|finance(2, true)}} {{scope.row.currencyName|currencySymbol}}</span>
          </template>
          <template v-slot:cell-planProfit="scope">
            <span v-if="scope.cell"
                  class="text-no-wrap">{{scope.cell|finance(2, true)}} {{scope.row.currencyName|currencySymbol}}</span>
          </template>
          <template v-slot:cell-factProfit="scope">
            <span v-if="scope.cell"
                  class="text-no-wrap">{{scope.cell|finance(2, true)}} {{scope.row.currencyName|currencySymbol}}</span>
          </template>
          <template v-slot:cell-conclusion="scope">
            <span v-if="scope.cell"
                  class="text-no-wrap"
                  :class="highlightColorClass(scope.cell)">{{scope.cell|finance(2, true)}}%</span>
          </template>
        </trol-data-table>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script>
import extend from 'lodash/extend';
import {SearchState} from '@/services';
import {TrolDataTable, SearchLayout} from '@/components/DataTable';
import {transfersStatusColors, monthFilterChoices} from '@/utils/DataObjects';
import {getValue} from '@/utils/PathAccessor';
import {dateCustom} from '@/filters/dateFormat';
import TrolDashboardTable from '@/components/Dashboard/StatisticTable';

const propsDefaults = {
  shownColumns: [],
  grouping: undefined,
  searchBy: {
    dateFrom: undefined,
    dateUntil: undefined,
    product: undefined,
    service: undefined,
    counterparty: undefined,
    clientManager: undefined,
    currency: 3,
    dispatchCountry: undefined,
    deliveryCountry: undefined,
  },
};
const dateFrom = new Date();
dateFrom.setMonth(dateFrom.getMonth() - 1);
propsDefaults.searchBy.dateFrom = dateCustom(dateFrom, 'yyyy-MM-01');
const dateUntil = new Date(dateFrom.getFullYear(), dateFrom.getMonth() + 1, 0);

propsDefaults.searchBy.dateUntil = dateCustom(dateUntil, 'yyyy-MM-dd');

export default {
  name: 'PlanFactReport',
  components: {SearchLayout, TrolDashboardTable, TrolDataTable},
  data () {
    return {
      monthFilterChoices,
      transfersStatusColors,
      loading: true,
      props: extend({}, propsDefaults),
      data: [],
      managersTableData: [],
      productsTableData: [],
      pathTableData: [],
      quickFilter: '',
      cancelingApi: this.API.getCancelingApi(),
      scrolling: false,
    };
  },
  computed: {
    productsUrl () {
      return this.getValue(this.props.searchBy, 'service')
        ? ['lazy-select', 'service', this.props.searchBy.service, 'product']
        : ['lazy-select', 'product'];
    },
    enabledProductsUrl () {
      return [...this.productsUrl, 'enabled'];
    },
    managersTableColumns () {
      return [
        {label: this.$t('Manager'), path: 'managerName', name: 'managerName', headerClass: {'text-left': true}},
        {label: this.$t('Execution of the plan'), path: 'conclusion', name: 'conclusion', right: true},
      ];
    },
    productsTableColumns () {
      return [
        {label: this.$t('Product'), path: 'productName', name: 'productName', headerClass: {'text-left': true}},
        {label: this.$t('Plan profit'), path: 'planProfit', name: 'planProfit', right: true},
        {label: this.$t('Fact profit'), path: 'factProfit', name: 'factProfit', right: true},
        {label: this.$t('Execution of the plan'), path: 'conclusion', name: 'conclusion', right: true},
      ];
    },
    pathTableColumns () {
      return [
        {label: this.$t('Loading'), path: 'dispatchCountryCode', name: 'dispatchCountryCode', headerClass: {'text-left': true}},
        {label: this.$t('delivery'), path: 'deliveryCountryCode', name: 'deliveryCountryCode', headerClass: {'text-left': true}},
        {label: this.$t('Execution of the plan'), path: 'conclusion', name: 'conclusion', right: true},
      ];
    },
    columnsComputed () {
      return [
        {label: this.$t('Order'), path: 'ordersId', name: 'ordersId'},
        {label: this.$t('Request'), path: 'customerRequestId', name: 'customerRequestId'},
        {label: this.$t('Manager'), path: 'managerName', name: 'managerName'},
        {label: this.$t('Client'), path: 'counterpartyName', name: 'counterpartyName'},
        {label: this.$t('Service'), path: 'serviceName', name: 'serviceName'},
        {label: this.$t('Product'), path: 'productName', name: 'productName'},
        {label: this.$t('Loading'), path: 'dispatchCountryCode', name: 'dispatchCountryCode'},
        {label: this.$t('delivery'), path: 'deliveryCountryCode', name: 'deliveryCountryCode'},
        {label: this.$t('Plan price'), path: 'planAmountPrice', name: 'planAmountPrice', right: true},
        {label: this.$t('Fact price'), path: 'factAmountProfit', name: 'factAmountProfit', right: true},
        {label: this.$t('Plan cost'), path: 'planAmountCost', name: 'planAmountCost', right: true},
        {label: this.$t('Fact cost'), path: 'factAmountCost', name: 'factAmountCost', right: true},
        {label: this.$t('Plan profit'), path: 'planProfit', name: 'planProfit', right: true},
        {label: this.$t('Fact profit'), path: 'factProfit', name: 'factProfit', right: true},
        {label: this.$t('Execution of the plan'), path: 'conclusion', name: 'conclusion', right: true},
      ];
    },
  },
  created () {
    this.props = SearchState.restore(this.$options.name, this.props);
    this.loadData();
  },
  methods: {
    getValue,
    onServiceChange () {
      this.props.searchBy.product = undefined;
    },
    async loadData () {
      this.loading = true;
      try {
        const success = await this.cancelingApi.post.progress(['reports', 'plan-fact-report'],
          {
            ...this.props,
            quickFilter: this.quickFilter,
          },
        );
        SearchState.save(this.$options.name, this.props);
        this.data = [];
        this.managersTableData = [];
        this.productsTableData = [];
        this.pathTableData = [];
        this.data   = success.data.page.totalTable.map((row, index) => {
          return {
            ...row,
            originalIndex: index,
          };
        });
        this.managersTableData   = success.data.page.managers.map((row, index) => {
          return {
            ...row,
            originalIndex: index,
          };
        });
        this.productsTableData   = success.data.page.products.map((row, index) => {
          return {
            ...row,
            originalIndex: index,
          };
        });
        this.pathTableData   = success.data.page.path.map((row, index) => {
          return {
            ...row,
            originalIndex: index,
          };
        });
      } catch (error) {
      } finally {
        this.loading = false;
      }
    },
    search () {
      this.props.page = 0;
      this.loadData();
    },
    resetSearch () {
      this.props.searchBy = extend({}, propsDefaults.searchBy);
    },
    highlightColorClass (value) {
      const result = {};

      if (Number(value) > 100) {
        result['success--text'] = true;
      } else if (Number(value) < 0) {
        result['error--text'] = true;
      } else {
        result['primary--text'] = true;
      }

      return result;
    },
    filtering (mode, param1) {
      if (mode === 'clientManager') {
        this.props.searchBy.clientManager = this.props.searchBy.clientManager === param1 ? undefined : param1;
      }
      if (mode === 'product') {
        this.props.searchBy.product = this.props.searchBy.product === param1 ? undefined : param1;
      }
      if (mode === 'pathDispatch') {
        this.props.searchBy.dispatchCountry = this.props.searchBy.dispatchCountry === param1 ? undefined : param1;
      }
      if (mode === 'pathDelivery') {
        this.props.searchBy.deliveryCountry = this.props.searchBy.deliveryCountry === param1 ? undefined : param1;
      }
      this.loadData();
    },
  },
};
</script>
<style scoped>
 .card-data{
   margin: 0 4px;
   min-height: 210px;
 }
 .card-collection{
   margin: 0 -4px;
 }
 .card-data > .v-card__text {
   padding: 0!important;
 }
 .filter-title:hover{
   cursor: pointer;
 }
</style>
