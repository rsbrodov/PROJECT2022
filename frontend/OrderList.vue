<template>
  <v-container fluid>
    <search-layout
      :loading="loading"
      can-create
      :create-url="{name:'OrderCreate'}"
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
                              :label="$t('Loading')+' '+$t('time.from')"
                              hide-details
                              clearable />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 md="3"
                 lg="2">
            <trol-date-picker v-model="props.searchBy.dateUntil"
                              :label="$t('Loading')+' '+$t('time.until')"
                              hide-details
                              clearable />
          </v-col>
          <!--          <v-col class="flex-grow-0">-->
          <!--            <v-btn>-->
          <!--              asdf-->
          <!--            </v-btn>-->
          <!--          </v-col>-->
          <v-col cols="12"
                 sm="6"
                 lg="2">
            <trol-lazy-select v-model="props.searchBy.counterparty"
                              item-text="name"
                              item-value="id"
                              item-disabled="dummy"
                              items-url="/lazy-select/counterparty"
                              selected-url="/lazy-select/counterparty/{id}"
                              :label="$t('Counterparty')"
                              clearable
                              hide-details />
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
                              hide-details
                              @input="props.searchBy.jobType = undefined" />
          </v-col>
          <v-col cols="12"
                 sm="3"
                 md="3"
                 lg="1">
            <trol-country-select v-model="props.searchBy.dispatchCountry"
                                 :label="$t('dispatch')"
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
                 md="3"
                 lg="1">
            <v-text-field v-model="props.searchBy.order"
                          :label="$t('Order')"
                          hide-details
                          clearable />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 md="3"
                 lg="1">
            <v-text-field v-model="props.searchBy.job"
                          :label="$t('Job')"
                          hide-details
                          clearable />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 lg="2">
            <trol-lazy-select v-model="props.searchBy.supervisor"
                              item-text="name"
                              item-value="id"
                              item-disabled="dummy"
                              items-url="/lazy-select/user-contact"
                              selected-url="/lazy-select/user-contact/{id}"
                              :label="$t('Supervisor')"
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
                              clearable
                              hide-details />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 md="3"
                 lg="1">
            <trol-lazy-select v-model="props.searchBy.departments"
                              item-text="filterName"
                              item-value="id"
                              :items-url="['company-structure', 'departments', 'lazy-select', 'for-requests']"
                              :label="$t('departmentAlt')"
                              clearable
                              full-load
                              hide-details />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 lg="2">
            <trol-lazy-select v-model="props.searchBy.forwarder"
                              item-text="name"
                              item-value="id"
                              item-disabled="dummy"
                              items-url="/lazy-select/user-contact"
                              selected-url="/lazy-select/user-contact/{id}"
                              :label="$t('Forwarder')"
                              clearable
                              hide-details />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 md="3"
                 lg="1">
            <trol-lazy-select v-model="props.searchBy.serviceOffice"
                              item-text="name"
                              item-value="id"
                              item-disabled="dummy"
                              items-url="/lazy-select/service-office"
                              :label="$t('Office')"
                              full-load
                              cached
                              clearable
                              hide-details />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 md="3"
                 lg="2">
            <v-select v-model="props.searchBy.status"
                      :items="statusTypes"
                      :label="$t('Status')"
                      clearable
                      multiple
                      hide-details />
          </v-col>
        </v-row>
        <search-layout-drawer :drawer-filters="drawerFilters"
                              :filters-collection="props.searchBy">
          <v-row dense>
            <v-col cols="12"
                   sm="6"
                   md="3"
                   lg="2">
              <trol-lazy-select v-model="props.searchBy.nextEvent"
                                item-text="name"
                                item-value="id"
                                item-disabled="dummy"
                                enabled-items-url="tms/stage-types/lazy-select/enabled"
                                items-url="tms/stage-types/lazy-select"
                                selected-url="tms/stage-types/lazy-select/current/{id}"
                                :label="$t('Next event')"
                                full-load
                                cached
                                clearable
                                hide-details />
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3"
                   lg="2">
              <v-select v-model="props.searchBy.greenOrders"
                        item-text="name"
                        item-value="id"
                        item-disabled="dummy"
                        :items="yesNoFilterChoices"
                        :label="$t('First orders')"
                        clearable
                        hide-details />
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3"
                   lg="2">
              <trol-lazy-select v-model="props.searchBy.documentProcessing"
                                item-text="name"
                                item-value="id"
                                item-disabled="dummy"
                                items-url="/lazy-select/user-contact"
                                selected-url="/lazy-select/user-contact/{id}"
                                :label="$t('Document processing')"
                                clearable
                                hide-details />
            </v-col>

            <v-col cols="12"
                   sm="6"
                   md="3"
                   lg="1">
              <v-select v-model="props.searchBy.archived"
                        item-text="name"
                        item-value="id"
                        item-disabled="dummy"
                        :items="yesNoFilterChoices"
                        :label="$t('Archive')"
                        clearable
                        hide-details />
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3"
                   lg="1">
              <v-select v-model="props.searchBy.confirmed"
                        item-text="name"
                        item-value="id"
                        item-disabled="dummy"
                        :items="yesNoFilterChoices"
                        :label="$t('Confirmed')"
                        clearable
                        hide-details />
            </v-col>
            <v-col cols="12"
                   sm="3"
                   md="3"
                   lg="1">
              <v-select v-model="props.searchBy.custIsIncoming"
                        item-text="name"
                        item-value="id"
                        :items="yesNoFilterChoices"
                        :label="$t('Incoming')"
                        hide-details
                        clearable />
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3"
                   lg="1">
              <v-switch v-model="props.searchBy.insurance"
                        :label="$t('voluntaryCargoInsurance')"
                        color="primary"
                        hide-details />
            </v-col>
            <v-col cols="12"
                   sm="6"
                   lg="2">
              <v-select v-model="props.searchBy.currentInsuranceDetailsStatusCode"
                        :items="cargoInsuranceStatuses"
                        :label="$t('voluntaryCargoInsuranceStatus')"
                        clearable
                        hide-details
                        multiple />
            </v-col>
            <v-col cols="12"
                   sm="6"
                   lg="2">
              <trol-lazy-select v-model="props.searchBy.fcStatusAuthor"
                                item-text="name"
                                item-value="id"
                                item-disabled="dummy"
                                items-url="/lazy-select/user-contact/by-access-role/ROLE_ORDER_FINANCE_CONTROL"
                                selected-url="/lazy-select/user-contact/{id}"
                                :label="$t('fcStatusAuthor')"
                                clearable
                                hide-details />
            </v-col>
            <v-col cols="12"
                   sm="6"
                   lg="2">
              <v-select v-model="props.searchBy.financeControlStatus"
                        :items="financeControlStatuses"
                        :label="$t('financeControlStatus')"
                        clearable
                        hide-details
                        multiple />
            </v-col>
            <v-col cols="12"
                   sm="6"
                   lg="2">
              <v-select v-model="props.searchBy.orderCompleted"
                        :items="yesNoFilterChoices"
                        item-text="name"
                        item-value="id"
                        :label="$t('orderCompleted')"
                        clearable
                        hide-details />
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3"
                   lg="2">
              <trol-lazy-select v-model="props.searchBy.customsSpecialist"
                                item-text="name"
                                item-value="id"
                                item-disabled="dummy"
                                items-url="/lazy-select/user-contact"
                                selected-url="/lazy-select/user-contact/{id}"
                                :label="$t('customsSpecialist')"
                                clearable
                                hide-details />
            </v-col>
            <v-col cols="12"
                   sm="6"
                   lg="2">
              <trol-lazy-select v-model="props.searchBy.managerSubstitute"
                                item-text="name"
                                item-value="id"
                                item-disabled="dummy"
                                items-url="/lazy-select/user-contact"
                                selected-url="/lazy-select/user-contact/{id}"
                                :label="$t('Substitute')"
                                clearable
                                hide-details />
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3"
                   lg="2">
              <trol-lazy-select v-model="props.searchBy.paymentForm"
                                item-text="name"
                                item-value="id"
                                item-disabled="dummy"
                                enabled-items-url="/lazy-select/payment-form/enabled"
                                items-url="/lazy-select/payment-form"
                                full-load
                                :label="$t('Payment form')"
                                clearable
                                multiple
                                hide-details />
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3"
                   lg="2">
              <v-select v-model="props.searchBy.resale"
                        item-text="name"
                        item-value="id"
                        item-disabled="dummy"
                        :items="yesNoFilterChoices"
                        :label="$t('Resale')"
                        clearable
                        hide-details />
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3"
                   lg="2">
              <v-switch v-model="props.searchBy.hideFailed"
                        :label="$t('Hide failed')"
                        color="primary"
                        hide-details />
            </v-col>
            <v-col cols="12"
                   sm="6"
                   md="3"
                   lg="2">
              <v-select v-model="props.searchBy.firstOrderStatus"
                        item-text="name"
                        item-value="id"
                        item-disabled="dummy"
                        :items="yesNoFilterChoices"
                        :label="$t('FirstOrderCustomer')"
                        clearable
                        hide-details />
            </v-col>
          </v-row>
        </search-layout-drawer>
      </template>
    </search-layout>
    <v-card class="fit-width mt-2">
      <v-card-text>
        <trol-data-table ref="dataTable"
                         :data="data"
                         :totals="totals"
                         :columns="columns"
                         :storage-key="$options.name"
                         :page="props.page"
                         :row-count="props.rowCount"
                         :sorting.sync="props.sorting"
                         :shown-columns.sync="props.shownColumns"
                         :columns-order.sync="props.columnsOrder"
                         :quick-filter.sync="quickFilter"
                         external-sorting
                         external-filter
                         external-grouping
                         @update:quickFilter="props.page = 0"
                         @update:sorting="props.page = 0"
                         @loadData="loadData"
                         @changePage="changePage"
                         @changeRowCount="changeRowCount">
          <template v-slot:th-id="slotData">
            {{slotData.column.label}}
            <trol-tooltip-wrapper top>
              <template v-slot:activator="{on}">
                <v-icon small
                        class="cursor-default"
                        color="primary"
                        v-on="on">
                  mdi-information-outline
                </v-icon>
              </template>
              <div>
                <v-icon color="warning"
                        small>
                  mdi-currency-usd-off
                </v-icon>
                - {{$t('noPrintableBill')}}
              </div>
              <div>
                <v-icon color="error"
                        small>
                  mdi-currency-usd-off
                </v-icon>
                - {{$t('No client\'s bill')}}
              </div>
              <div>
                <v-icon color="warning"
                        small>
                  mdi-file-document
                </v-icon>
                - {{$t('Application created after start of moving')}}
              </div>
              <div>
                <v-icon color="error"
                        small>
                  mdi-file-document
                </v-icon>
                - {{$t('No application')}}
              </div>
              <div>
                <v-icon color="success"
                        small>
                  mdi-timelapse
                </v-icon>
                - {{$t('Order of new or restored client')}}
              </div>
              <div>
                <v-icon color="warning"
                        small>
                  mdi-timelapse
                </v-icon>
                - {{$t('orderOfNewProduct')}}
              </div>
              <div>
                <v-icon color="success"
                        small>
                  mdi-numeric-1-box-outline
                </v-icon>
                - {{$t('FirstOrderCustomer')}}
              </div>
            </trol-tooltip-wrapper>
          </template>
          <template v-slot:th-cargoDeclaredValue="_slotData">
            <v-row no-gutters
                   align="end"
                   class="flex-nowrap">
              <v-col>
                <span class="text-pre-wrap">{{$t('declaredValue').split(' ').join("\n")}}</span>
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
                  <span class="text-pre-wrap">{{$t('highDeclaredValueTableTooltip[0]')}}
                    <span class="warning--text">{{$store.state.options.cargoInsuredValue}}</span>
                    {{$store.state.options.systemCurrency}}{{$t('highDeclaredValueTableTooltip[1]')}}
                  </span>
                </trol-tooltip-wrapper>
              </v-col>
            </v-row>
          </template>
          <template v-slot:th-expectedDate="slotData">
            <v-row align="end"
                   class="flex-nowrap"
                   no-gutters>
              <v-col>
                <span class="text-pre-wrap">{{slotData.column.label.split(' ').join("\n")}}</span>
              </v-col>
              <v-col class="pl-1">
                <trol-tooltip-wrapper top>
                  <template v-slot:activator="{on}">
                    <v-icon small
                            class="cursor-default"
                            color="primary"
                            v-on="on">
                      mdi-information-outline
                    </v-icon>
                  </template>
                  <div>
                    <v-icon color="success"
                            small>
                      mdi-check
                    </v-icon>
                    - {{$t('Closed')}}
                  </div>
                  <div>
                    <v-icon color="error"
                            small>
                      mdi-alert-circle
                    </v-icon>
                    - {{$t('Overdue')}}
                  </div>
                  <div>
                    <v-icon color="warning"
                            small>
                      mdi-alert
                    </v-icon>
                    - {{$t('Today')}}
                  </div>
                </trol-tooltip-wrapper>
              </v-col>
            </v-row>
          </template>
          <template v-slot:cell-id="slotData">
            <div class="text-no-wrap">
              <router-link v-if="slotData.cell"
                           :to="{name:'OrderProperties', params:{id:slotData.row[0].id}}">
                {{slotData.cell | id}}
              </router-link>
              <span v-if="slotData.row[0].customerRecentlyAdded"><v-icon color="success"
                                                                         class="cursor-default"
                                                                         small>mdi-timelapse</v-icon></span>
              <span v-if="slotData.row[0].orderOfNewProduct"><v-icon color="warning"
                                                                     class="cursor-default"
                                                                     small>mdi-timelapse</v-icon></span>
              <span v-if="slotData.row.incomeBills < 1"><v-icon color="error"
                                                                small>mdi-currency-usd-off</v-icon></span>
              <span v-else-if="slotData.row.printableIncomeBills < 1 || slotData.row.billWithoutPrintableBills >0"><v-icon color="warning"
                                                                                                                           small>mdi-currency-usd-off</v-icon></span>
              <span v-if="slotData.row.first_files_date"><v-icon color="warning"
                                                                 small>mdi-file-document</v-icon></span>
              <span v-if="slotData.row.first_stage_date"><v-icon color="error"
                                                                 small>mdi-file-document</v-icon></span>
              <div v-if="!slotData.row[0].managerApproved"
                   class="error--text">
                {{$t('Not approved')}}
              </div>
              <span v-if="slotData.row[0].firstOrderStatus === true"><v-icon color="success"
                                                                             small>mdi-numeric-1-box-outline</v-icon></span>
            </div>
          </template>
          <template v-slot:cell-cargoDeclaredValue="slotData">
            <div class="text-no-wrap"
                 :class="{'error--text': parseFloat(slotData.cell) >= $store.state.options.cargoInsuredValue}">
              {{slotData.cell|finance}} {{$store.state.options.systemCurrency}}
            </div>
          </template>
          <template v-slot:cell-request="slotData">
            <router-link v-if="slotData.cell"
                         :to="'/requests/'+slotData.cell">
              {{slotData.cell}}
            </router-link>
          </template>
          <template v-slot:cell-client="slotData">
            <router-link v-if="slotData.cell && slotData.row[0].counterparty"
                         :to="{name: 'CounterpartyProperties', params:{id:slotData.row[0].counterparty.id}}">
              {{slotData.row[0].counterparty.name}}
            </router-link>
          </template>
          <template v-slot:cell-alias-weight-number="slotData">
            <template v-if="slotData.cell">
              {{slotData.cell.toFixed(2)}}
            </template>
          </template>
          <template v-slot:cell-paymentForm="slotData">
            <template v-if="slotData.cell">
              {{$getValue(slotData.cell, 'name', '')}}
            </template>
          </template>
          <template v-slot:cell-alias-date-field="slotData">
            <template v-if="slotData.cell">
              {{slotData.cell|dateShort}}
            </template>
          </template>
          <template v-slot:cell-expectedDate="slotData">
            <div class="text-no-wrap">
              <div v-if="getValue(slotData.row[0],'currentStage.actualEndDateTime')">
                {{getValue(slotData.row[0], 'currentStage.actualEndDateTime')|dateShort}}
                <v-icon class="cursor-default"
                        color="success"
                        small>
                  mdi-check
                </v-icon>
              </div>
              <div v-else>
                {{slotData.cell|dateShort}}
                <v-icon v-if="getDateDiff(new Date(slotData.cell).setHours(0,0,0,0),new Date().setHours(0,0,0,0)) < 0"
                        color="error"
                        small>
                  mdi-alert-circle
                </v-icon>
                <v-icon v-if="getDateDiff(new Date(slotData.cell).setHours(0,0,0,0),new Date().setHours(0,0,0,0)) === 0"
                        color="warning"
                        small>
                  mdi-alert
                </v-icon>
              </div>
            </div>
          </template>
          <template v-slot:cell-archived="slotData">
            <v-icon v-if="slotData.cell"
                    color="success"
                    small>
              mdi-check
            </v-icon>
          </template>
          <template v-slot:cell-resale="slotData">
            <v-icon v-if="slotData.cell"
                    color="success"
                    small>
              mdi-check
            </v-icon>
            <span v-else>---</span>
          </template>
          <template v-slot:cell-product="slotData">
            <router-link v-if="slotData.cell"
                         :to="'/tms/products/'+slotData.cell.id"
                         append>
              {{slotData.cell.name}}
            </router-link>
          </template>
          <template v-slot:cell-ltl="slotData">
            <v-icon v-if="slotData.cell"
                    color="success"
                    small>
              mdi-check
            </v-icon>
          </template>
          <template v-slot:cell-currentFinanceControlStatus="slotData">
            {{$t('financeControlStatuses.' + slotData.cell)}}
          </template>
          <template v-slot:cell-currentInsuranceDetailsStatusCode="slotData">
            <div v-if="getValue(slotData.row[0],'insurance.code') === 'transportir'">
              <v-chip v-if="slotData.cell"
                      small
                      :dark="slotData.cell !== 'NOT_READY'"
                      :color="cargoInsuranceStatusColors[slotData.cell]"
                      class="pr-1 compact">
                {{$t('cargoInsuranceStatuses.' + slotData.cell)}}
              </v-chip>
            </div>
            <div v-else />
          </template>
        </trol-data-table>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script>
import extend from 'lodash/extend';
import {SearchState} from '@/services';
import {getValue} from '@/utils/PathAccessor';
import getDateDiff from '@/utils/DateDiff';
import {TrolDataTable, SearchLayout} from '@/components/DataTable';
import {yesNoFilterChoices, financeControlStatuses, cargoInsuranceStatusColors, cargoInsuranceStatuses} from '@/utils/DataObjects';
import SearchLayoutDrawer from '../../../components/DataTable/SearchLayoutDrawer';
import {dateCustom} from '@/filters/dateFormat';

const propsDefaults = {
  shownColumns: [],
  sorting: {},
  page: 0,
  rowCount: 100,
  searchBy: {
    dateFrom: undefined,
    dateUntil: undefined,
    counterparty: undefined,
    greenOrders: undefined,
    service: undefined,
    product: undefined,
    nextEvent: undefined,
    archived: undefined,
    confirmed: undefined,
    dispatchCountry: undefined,
    order: undefined,
    job: undefined,
    supervisor: undefined,
    clientManager: undefined,
    forwarder: undefined,
    documentProcessing: undefined,
    status: undefined,
    serviceOffice: undefined,
    deliveryCountry: undefined,
    hideFailed: undefined,
    custIsIncoming: undefined,
    departments: undefined,
    customsSpecialist: undefined,
    insurance: undefined,
    paymentForm: undefined,
    firstOrderStatus: undefined,
  },
};

const dateFrom = new Date();
dateFrom.setFullYear(dateFrom.getFullYear() - 1);
propsDefaults.searchBy.dateFrom = dateCustom(dateFrom, 'yyyy-MM-dd');

export default {
  name: 'OrderList',
  components: {
    TrolDataTable,
    SearchLayout,
    SearchLayoutDrawer,
  },
  data () {
    return {
      props: extend({}, propsDefaults),
      cargoInsuranceStatusColors,
      cargoInsuranceStatuses,
      orderTypes: [
        {text: this.$t('First orders'), value: 'firstOrders'},
        {text: this.$t('Other orders'), value: 'otherOrders'},
      ],
      statusTypes: [
        {text: this.$t('No stages'), value: 'no-stages'},
        {text: this.$t('Ready'), value: 'ready'},
        {text: this.$t('On the way'), value: 'in-progress'},
        {text: this.$t('Not started'), value: 'not-started'},
        {text: this.$t('failure'), value: 'failure'},
      ],
      drawerFilters: [
        'nextEvent',
        'greenOrders',
        'documentProcessing',
        'hideFailed',
        'archived',
        'confirmed',
        'financeControlStatus',
        'fcStatusAuthor',
        'custIsIncoming',
        'orderCompleted',
        'customsSpecialist',
        'managerSubstitute',
        'insurance',
      ],
      financeControlStatuses,
      data: [],
      totals: {},
      loading: false,
      quickFilter: '',
      yesNoFilterChoices,
      cancelingApi: this.API.getCancelingApi(),
    };
  },
  computed: {
    columns () {
      let columns = [];

      columns = [
        {label: this.$t('Order'), path: '0.id', name: 'id', sortable: true},
        {label: this.$t('Cargo declared value'), path: '0.cargoDeclaredValue', name: 'cargoDeclaredValue', right: true},
        {label: this.$t('Application'), path: '0.clientApplication', name: 'clientApplication', sortable: true},
        {label: 'J', path: 'jobCount', name: 'jobCount', sortable: true, right: true},
        {label: 'LTL', right: true, name: 'ltl'},
        {label: this.$t('Client'), path: '0.counterparty.name', name: 'client', sortable: true, maxWidth: '150px'},
        {label: this.$t('Product'), path: '0.product', name: 'product', sortable: true},
        {label: this.$t('Request'), path: '0.request.id', name: 'request', sortable: true},
        {label: this.$t('Cargo'), path: '0.cargoShortDescription', name: 'description', sortable: true, maxWidth: '150px'},
        {label: this.$t('Form'), path: '0.paymentForm', name: 'paymentForm', sortable: true},
        {label: 'GW', path: '0.cargoWeight', name: 'gw', alias: 'weight-number', sortable: true, right: true},
        {label: 'Vol', path: '0.cargoVolume', name: 'vol', alias: 'weight-number', sortable: true, right: true},
        {label: 'CW', path: '0.cargoWeightComm', name: 'cw', alias: 'weight-number', sortable: true, right: true},
        {label: 'LDM', path: '0.cargoLDM', name: 'ldm', alias: 'weight-number', sortable: true, right: true},
        {
          label: this.$t('voluntaryCargoInsurance'),
          path: 'currentInsuranceDetailsStatusCode',
          name: 'currentInsuranceDetailsStatusCode',
          sortable: true,
        },
        {label: this.$t('Loading date'), path: '0.loadingDate', name: 'loadingDate', sortable: true, alias: 'date-field', headerForceWrap: true},
        {label: this.$t('Loading'), path: 'loadingAddress', name: 'loadingAddress', sortable: true, maxWidth: '150px'},
        {
          label: this.$t('Clearance'),
          name: 'clearance',
          path: 'clearance',
          sortable: true,
          maxWidth: '150px',
          computed: (row) => {
            return getValue(row, 'clearance', '---').split('\n')[0];
          },
        },
        {label: this.$t('delivery'), path: 'unloadingAddress', name: 'unloadingAddress', sortable: true},
        {label: this.$t('Next event'), path: '0.currentStage.type.name', name: 'nextEvent', sortable: true, headerForceWrap: true},
        {
          label: this.$t('Expected date'),
          name: 'expectedDate',
          path: 'currentStageDate',
          sortable: true,
          headerForceWrap: true,
        },
        {
          label: this.$t('Vehicle number'),
          path: '0.currentJob.vehicleNumber',
          name: 'vehicleNumber',
          sortable: true,
          maxWidth: '150px',
        },
        {
          label: this.$t('Container'),
          path: '0.currentJob.containerNumber',
          name: 'containerNumber',
          sortable: true,
          maxWidth: '150px',
        },
        {label: this.$t('orderCompletion'), name: 'currentCompletionMarkerDate', path: '0.currentCompletionMarker.date', sortable: true},
        {label: this.$t('Archive'), name: 'archived', path: '0.archived', sortable: true},
        {label: this.$t('Resale'), name: 'resale', path: '0.resale', sortable: true},
        {label: this.$t('financeControlShort'), name: 'currentFinanceControlStatus', path: '0.currentFinanceControlStatus.code', sortable: true},
      ];

      return columns;
    },
    productsUrl () {
      return this.getValue(this.props.searchBy, 'service')
        ? ['lazy-select', 'service', this.props.searchBy.service, 'product']
        : ['lazy-select', 'product'];
    },
    enabledProductsUrl () {
      return [...this.productsUrl, 'enabled'];
    },
  },

  created () {
    this.props = SearchState.restore(this.$options.name, this.props);
    this.loadData();
  },
  methods: {
    getValue,
    getDateDiff,
    onServiceChange () {
      this.props.searchBy.product = undefined;
    },
    async loadData () {
      this.loading = true;
      try {
        const success = await this.cancelingApi.post.progress(['tms', 'orders'],
          {
            ...this.props,
            quickFilter: this.quickFilter,
          },
        );

        SearchState.save(this.$options.name, this.props);
        this.totals = success.data.totals;
        this.data   = success.data.page.map((row, index) => {
          return {
            ...row,
            originalIndex: index,
          };
        });
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
    changePage (page) {
      this.props.page = page;
      this.loadData();
    },
    changeRowCount (rowCount) {
      this.props.rowCount = rowCount;
      this.loadData();
    },
  },
};
</script>

<style scoped>
</style>
