<template>
  <v-container fluid
               class="actions-buffer">
    <v-row v-if="!$store.state.options.orderProperties.expanded"
           dense>
      <v-col cols="12"
             md="5"
             lg="5">
        <order-failed-block v-model="order"
                            :access-rights="accessRights" />
        <order-not-approved-block v-model="order"
                                  :access-rights="accessRights" />
        <v-card>
          <v-card-text class="body-2">
            <order-properties-caption v-model="order"
                                      :access-rights="accessRights" />
            <v-fade-transition hide-on-leave>
              <div v-if="order.id">
                <order-general-info-block v-model="order"
                                          :access-rights="accessRights" />
                <order-cargo-block v-model="order"
                                   class="pt-3"
                                   :access-rights="accessRights"
                                   one-column />
                <order-cost-of-services-block v-model="order"
                                              class="pt-3"
                                              :access-rights="accessRights" />
              </div>
              <v-skeleton-loader v-else
                                 type="card-heading, paragraph@3, card-heading, paragraph@2, text@2, paragraph@2, text@3" />
            </v-fade-transition>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12"
             md="7"
             lg="7">
        <v-card>
          <v-row no-gutters>
            <v-col>
              <v-tabs ref="tabs"
                      v-model="currentTab"
                      show-arrows>
                <v-tab>
                  <div v-if="primaryDispatchPoint || primaryDispatchPoint"
                       class="flex-grow-0 text-no-wrap d-block text-ellipsis">
                    {{primaryDispatchPoint|accessor('address.country.code')}}
                    {{primaryDispatchPoint|accessor('address.postCode')}}
                    -
                    {{primaryDeliveryPoint|accessor('address.country.code')}}
                    {{primaryDeliveryPoint|accessor('address.postCode')}}
                  </div>
                  <div v-else
                       class="d-block">
                    {{$t('shipmentPoints')}}
                  </div>
                  <v-icon v-if="isDefaultTab(0)"
                          color="success"
                          x-small>
                    mdi-book-outline
                  </v-icon>
                </v-tab>
                <v-tab :disabled="!order.id">
                  {{$t('Jobs')}}/{{$t('Stages')}}
                  <v-icon v-if="isDefaultTab(1)"
                          color="success"
                          x-small>
                    mdi-book-outline
                  </v-icon>
                </v-tab>
                <v-tab :disabled="!order.id">
                  {{$t('Bills')}}
                  <v-icon v-if="isDefaultTab(2)"
                          color="success"
                          x-small>
                    mdi-book-outline
                  </v-icon>
                </v-tab>
                <v-tab v-if="$isGranted('ROLE_INT_TRANSFER_PLAN')"
                       :disabled="!order.id">
                  <trol-tooltip-wrapper bottom>
                    <template v-slot:activator="{on}">
                      <v-row no-gutters
                             align="center"
                             v-on="on">
                        <v-col class="flex-grow-0 pr-1 pb-0">
                          <v-icon x-small
                                  :color="transfersStatusColors.background[order.internalTransferStatus]"
                                  class="ml-0">
                            mdi-circle
                          </v-icon>
                        </v-col>
                        <v-col class="flex-grow-0 text-no-wrap">
                          {{$t('internalTransfers')}}
                        </v-col>
                      </v-row>
                    </template>
                    {{order.internalTransferStatus ? $t('transfersStatuses.' + order.internalTransferStatus) : '- - -'}}
                  </trol-tooltip-wrapper>
                  <v-icon v-if="isDefaultTab(3)"
                          color="success"
                          x-small>
                    mdi-book-outline
                  </v-icon>
                </v-tab>
                <v-tab :disabled="!order.id">
                  {{$t('Files')}}
                  <v-icon v-if="isDefaultTab($isGranted('ROLE_INT_TRANSFER_PLAN') ? 4 : 3)"
                          color="success"
                          x-small>
                    mdi-book-outline
                  </v-icon>
                </v-tab>
                <!--                <v-tab :disabled="!order.id">-->
                <!--                  {{$t('workflows')}}-->
                <!--                  <v-icon v-if="isDefaultTab($isGranted('ROLE_INT_TRANSFER_PLAN') ? 5 : 4)"-->
                <!--                          color="success"-->
                <!--                          x-small>-->
                <!--                    mdi-book-outline-->
                <!--                  </v-icon>-->
                <!--                </v-tab>-->
              </v-tabs>
            </v-col>
            <v-col class="flex-grow-0 pa-2">
              <operation-default-tab-menu type="order"
                                          @update:defaultTab="currentTab = $event"
                                          @update:mode="loadData" />
            </v-col>
          </v-row>
        </v-card>
        <div class="mt-1 tabs-margin">
          <v-tabs-items :value="currentTab"
                        class="no-background pa-1">
            <v-tab-item class="min-tab-height"
                        eager>
              <v-expand-transition>
                <div v-if="order.id">
                  <v-card>
                    <v-card-text>
                      <operation-shipment-points-collection ref="dispatchPoints"
                                                            v-model="order"
                                                            :access-rights="accessRights"
                                                            :max-points="$getValue(order,'service.maxDispatchPoints')"
                                                            :bus="bus"
                                                            mode="dispatch"
                                                            @update:primaryPoint="primaryDispatchPoint = $event" />
                    </v-card-text>
                  </v-card>
                  <v-card class="mt-2">
                    <v-card-text>
                      <reloading-point v-model="order"
                                       :access-rights="accessRights"
                                       :max-points="1"
                                       :bus="bus" />
                    </v-card-text>
                  </v-card>
                  <v-card class="mt-2">
                    <v-card-text>
                      <operation-customs-points-collection ref="customsPoints"
                                                           v-model="order"
                                                           :access-rights="accessRights"
                                                           :max-points="2"
                                                           :bus="bus" />
                    </v-card-text>
                  </v-card>
                  <v-card class="mt-2">
                    <v-card-text>
                      <operation-shipment-points-collection ref="deliveryPoints"
                                                            v-model="order"
                                                            :access-rights="accessRights"
                                                            :max-points="$getValue(order,'service.maxDeliveryPoints')"
                                                            :bus="bus"
                                                            mode="delivery"
                                                            @update:primaryPoint="primaryDeliveryPoint = $event" />
                    </v-card-text>
                  </v-card>
                </div>
              </v-expand-transition>
            </v-tab-item>
            <v-tab-item class="min-tab-height">
              <v-card>
                <v-card-text>
                  <order-jobs v-if="!!order.id"
                              ref="stagesTab"
                              :operation="order"
                              :jobs-collection-url="['tms', 'jobs', 'by-order', order.id]"
                              :internal-collection-url="['tms', 'orders', order.id, 'stages']"
                              :create-url="{name:'JobCreateFromOrder', params:{orderId: order.id}}"
                              :can-create="accessRights.edit && !order.isChangesLocked"
                              no-internal-collection-filter>
                    <template v-slot:collection="scope">
                      <operation-stages :collection.sync="scope.internalCollection[scope.operation.id]"
                                        :access-rights="scope.operation.accessRights"
                                        :operation="scope.operation"
                                        :orders-collection-url="['tms', 'order-job-links', 'by-job', scope.operation.id]"
                                        :order="order"
                                        @refreshCollection="loadData" />
                    </template>
                    <template v-slot:control="scope">
                      <stage-properties-dialog :access-rights="scope.operation.accessRights"
                                               :operation="scope.operation"
                                               :order="order"
                                               :orders-collection="[]"
                                               :orders-collection-url="['tms', 'order-job-links', 'by-job', scope.operation.id]"
                                               x-small
                                               @input="loadData" />
                    </template>
                  </order-jobs>
                </v-card-text>
              </v-card>
            </v-tab-item>
            <v-tab-item class="min-tab-height">
              <v-card>
                <v-card-text>
                  <order-jobs v-if="!!order.id"
                              :operation="order"
                              :jobs-collection-url="['tms', 'jobs', 'by-order', order.id]"
                              :internal-collection-url="['finances', 'bills', 'by-order', order.id]"
                              :create-url="{name: 'BillCreateFromOperation', params:{operationId: order.id}}"
                              can-create>
                    <template v-slot:collection="scope">
                      <operation-bills :collection="scope.internalCollection[scope.operation.id]"
                                       :operation="order" />
                    </template>
                  </order-jobs>
                </v-card-text>
              </v-card>
            </v-tab-item>
            <v-tab-item v-if="$isGranted('ROLE_INT_TRANSFER_PLAN')"
                        class="min-tab-height">
              <v-card>
                <v-card-text>
                  <internal-transfers v-if="!!order.id"
                                      ref="transfers"
                                      :order="order"
                                      @update="loadData(false)" />
                </v-card-text>
              </v-card>
            </v-tab-item>
            <v-tab-item class="min-tab-height">
              <v-card>
                <v-card-text>
                  <order-jobs v-if="!!order.id"
                              ref="fileCollection"
                              :operation="order"
                              :jobs-collection-url="['tms', 'jobs', 'by-order', order.id]"
                              :internal-collection-url="['tms', 'operations', order.id,'related-files']">
                    <template v-slot:action="_scope">
                      <trol-file-upload-dialog v-if="!order.isChangesLocked || $isGranted('ROLE_FORWARDER_UPLOAD_FILES_TO_ARCHIVED')"
                                               :path="['tms', 'operations', order.id, 'files', 'upload']"
                                               tms-switch
                                               pcc-switch
                                               :available-tags="fileTags"
                                               change-tags
                                               @uploaded="$refs.fileCollection.loadInternalCollection()" />
                    </template>
                    <template v-slot:collection="scope">
                      <operation-files :collection.sync="scope.internalCollection[scope.operation.id]"
                                       :base-operation="order"
                                       :current-operation="scope.operation"
                                       :access-rights="accessRights"
                                       :available-tags="fileTags" />
                    </template>
                    <template v-slot:appendix="scope">
                      <printable-bill-files :operation="order"
                                            :files-collection="scope.internalCollection"
                                            :access-rights="accessRights" />
                    </template>
                  </order-jobs>
                </v-card-text>
              </v-card>
            </v-tab-item>
            <!--            <v-tab-item class="min-tab-height">-->
            <!--              <order-workflow-collection ref="workflows"-->
            <!--                                         :order="order" />-->
            <!--            </v-tab-item>-->
          </v-tabs-items>
        </div>
      </v-col>
    </v-row>
    <v-row v-else
           dense>
      <v-col cols="12">
        <order-failed-block v-model="order"
                            :access-rights="accessRights" />
        <order-not-approved-block v-model="order"
                                  :access-rights="accessRights" />
        <v-expansion-panels v-model="panelsState.main"
                            accordion>
          <v-expansion-panel>
            <v-expansion-panel-header class="py-3">
              <v-row no-gutters
                     align="center">
                <v-col>
                  <order-properties-caption v-model="order"
                                            :access-rights="accessRights"
                                            :extended-info="panelsState.main !== 0" />
                </v-col>
                <v-col class="flex-grow-0">
                  <operation-default-tab-menu type="order"
                                              @update:expandedBlocks="panelsState = $event"
                                              @update:mode="loadData" />
                </v-col>
              </v-row>
            </v-expansion-panel-header>
            <v-expansion-panel-content>
              <v-sheet class="body-2">
                <v-row v-if="order.id"
                       dense>
                  <v-col cols="12"
                         md="4"
                         lg="3">
                    <order-general-info-block v-model="order"
                                              :access-rights="accessRights" />
                  </v-col>
                  <v-col class="flex-grow-0">
                    <v-divider vertical />
                  </v-col>
                  <v-col cols="12"
                         md="">
                    <order-cargo-block v-model="order"
                                       :access-rights="accessRights" />
                  </v-col>
                  <v-col class="flex-grow-0">
                    <v-divider vertical />
                  </v-col>
                  <v-col cols="12"
                         md="3">
                    <order-cost-of-services-block v-model="order"
                                                  :access-rights="accessRights" />
                  </v-col>
                </v-row>
              </v-sheet>
            </v-expansion-panel-content>
          </v-expansion-panel>
        </v-expansion-panels>
      </v-col>
      <v-col cols="12"
             md="5">
        <v-expansion-panels v-model="panelsState.route">
          <v-expansion-panel>
            <v-expansion-panel-header class="body-1">
              <v-row align="end"
                     no-gutters>
                <v-col class="flex-grow-0 text-no-wrap pr-2">
                  {{$t('shipmentPoints')}}
                </v-col>
                <v-col class="caption text--secondary">
                  <div class="d-inline-block">
                    {{primaryDispatchPoint|accessor('address.country.code')}}
                    {{primaryDispatchPoint|accessor('address.postCode')}}
                  </div>
                  -
                  <div class="d-inline-block">
                    {{primaryDeliveryPoint|accessor('address.country.code')}}
                    {{primaryDeliveryPoint|accessor('address.postCode')}}
                  </div>
                </v-col>
              </v-row>
            </v-expansion-panel-header>
            <v-expansion-panel-content eager>
              <v-expand-transition>
                <div v-if="order.id">
                  <operation-shipment-points-collection ref="dispatchPoints"
                                                        v-model="order"
                                                        :access-rights="accessRights"
                                                        :max-points="$getValue(order,'service.maxDispatchPoints')"
                                                        :bus="bus"
                                                        mode="dispatch"
                                                        @update:primaryPoint="primaryDispatchPoint = $event" />
                  <reloading-point v-model="order"
                                   :access-rights="accessRights"
                                   :max-points="1"
                                   :bus="bus" />
                  <operation-customs-points-collection ref="customsPoints"
                                                       v-model="order"
                                                       class="mt-2"
                                                       :access-rights="accessRights"
                                                       :bus="bus"
                                                       :max-points="2" />
                  <operation-shipment-points-collection ref="deliveryPoints"
                                                        v-model="order"
                                                        class="mt-2"
                                                        :bus="bus"
                                                        :access-rights="accessRights"
                                                        :max-points="$getValue(order,'service.maxDeliveryPoints')"
                                                        mode="delivery"
                                                        @update:primaryPoint="primaryDeliveryPoint = $event" />
                </div>
              </v-expand-transition>
            </v-expansion-panel-content>
          </v-expansion-panel>
        </v-expansion-panels>
        <v-expansion-panels v-model="panelsState.bills"
                            class="mt-2">
          <v-expansion-panel>
            <v-expansion-panel-header class="body-1">
              {{$t('Bills')}}
            </v-expansion-panel-header>
            <v-expansion-panel-content>
              <order-jobs v-if="!!order.id"
                          :operation="order"
                          :jobs-collection-url="['tms', 'jobs', 'by-order', order.id]"
                          :internal-collection-url="['finances', 'bills', 'by-order', order.id]"
                          :create-url="{name: 'BillCreateFromOperation', params:{operationId: order.id}}"
                          can-create>
                <template v-slot:collection="scope">
                  <operation-bills :collection="scope.internalCollection[scope.operation.id]"
                                   :operation="order" />
                </template>
              </order-jobs>
            </v-expansion-panel-content>
          </v-expansion-panel>
        </v-expansion-panels>
        <!--        <v-expansion-panels v-model="panelsState.workflows"-->
        <!--                            class="mt-2">-->
        <!--          <v-expansion-panel>-->
        <!--            <v-expansion-panel-header class="body-1">-->
        <!--              {{$t('WorkflowInstances')}}-->
        <!--            </v-expansion-panel-header>-->
        <!--            <v-expansion-panel-content>-->
        <!--              <order-workflow-collection ref="workflows"-->
        <!--                                         :order="order" />-->
        <!--            </v-expansion-panel-content>-->
        <!--          </v-expansion-panel>-->
        <!--        </v-expansion-panels>-->
      </v-col>
      <v-col cols="12"
             md="7">
        <v-expansion-panels v-model="panelsState.jobs"
                            accordion>
          <v-expansion-panel>
            <v-expansion-panel-header class="body-1">
              {{$t('Jobs')}}/{{$t('Stages')}}
            </v-expansion-panel-header>
            <v-expansion-panel-content>
              <order-jobs v-if="!!order.id"
                          ref="stagesTab"
                          :operation="order"
                          :jobs-collection-url="['tms', 'jobs', 'by-order', order.id]"
                          :internal-collection-url="['tms', 'orders', order.id, 'stages']"
                          :create-url="{name:'JobCreateFromOrder', params:{orderId: order.id}}"
                          :can-create="accessRights.edit && !order.isChangesLocked"
                          no-internal-collection-filter>
                <template v-slot:collection="scope">
                  <operation-stages :collection.sync="scope.internalCollection[scope.operation.id]"
                                    :access-rights="scope.operation.accessRights"
                                    :operation="order"
                                    :orders-collection-url="['tms', 'order-job-links', 'by-job', scope.operation.id]"
                                    :order="order"
                                    @refreshCollection="loadData" />
                </template>
                <template v-slot:control="scope">
                  <stage-properties-dialog :access-rights="scope.operation.accessRights"
                                           :operation="scope.operation"
                                           :order="order"
                                           :orders-collection="[]"
                                           :orders-collection-url="['tms', 'order-job-links', 'by-job', scope.operation.id]"
                                           x-small
                                           @input="loadData" />
                </template>
              </order-jobs>
            </v-expansion-panel-content>
          </v-expansion-panel>
        </v-expansion-panels>
        <v-expansion-panels v-model="panelsState.files"
                            accordion
                            class="mt-2">
          <v-expansion-panel>
            <v-expansion-panel-header class="body-1">
              {{$t('Files')}}
            </v-expansion-panel-header>
            <v-expansion-panel-content>
              <order-jobs v-if="!!order.id"
                          ref="fileCollection"
                          :operation="order"
                          :jobs-collection-url="['tms', 'jobs', 'by-order', order.id]"
                          :internal-collection-url="['tms', 'operations', order.id,'related-files']">
                <template v-slot:action="_scope">
                  <trol-file-upload-dialog v-if="!order.isChangesLocked || $isGranted('ROLE_FORWARDER_UPLOAD_FILES_TO_ARCHIVED')"
                                           :path="['tms', 'operations', order.id, 'files', 'upload']"
                                           tms-switch
                                           pcc-switch
                                           :available-tags="fileTags"
                                           change-tags
                                           @uploaded="$refs.fileCollection.loadInternalCollection()" />
                </template>
                <template v-slot:collection="scope">
                  <operation-files :collection.sync="scope.internalCollection[scope.operation.id]"
                                   :base-operation="order"
                                   :current-operation="scope.operation"
                                   :access-rights="accessRights"
                                   :available-tags="fileTags" />
                </template>
                <template v-slot:appendix="scope">
                  <printable-bill-files :operation="order"
                                        :files-collection="scope.internalCollection"
                                        :access-rights="accessRights" />
                </template>
              </order-jobs>
            </v-expansion-panel-content>
          </v-expansion-panel>
        </v-expansion-panels>
        <v-expansion-panels v-if="$isGranted('ROLE_INT_TRANSFER_PLAN')"
                            v-model="panelsState.transfers"
                            class="mt-2">
          <v-expansion-panel>
            <v-expansion-panel-header class="body-1">
              {{$t('internalTransfers')}}
              <div class="pl-2">
                <v-chip small
                        :color="transfersStatusColors.background[order.internalTransferStatus]"
                        class="compact">
                  {{order.internalTransferStatus ? $t('transfersStatuses.' + order.internalTransferStatus) : '- - -'}}
                </v-chip>
              </div>
            </v-expansion-panel-header>
            <v-expansion-panel-content>
              <internal-transfers v-if="!!order.id"
                                  ref="transfers"
                                  :order="order"
                                  @update="loadData(false)" />
            </v-expansion-panel-content>
          </v-expansion-panel>
        </v-expansion-panels>
      </v-col>
    </v-row>
    <properties-actions
      :loading="loading"
      reload
      list
      :copy="!!order.id"
      :copy-url="{name: 'OrderCopy', params: {sourceId: order.id}}"
      @reload="loadData" />
  </v-container>
</template>

<script>
import {mapState} from 'vuex';
import PropertiesActions from '@/components/PropertiesActions/PropertiesActions';
import OrderGeneralInfoBlock from '@/views/TMS/Orders/PropertiesParts/OrderGeneralInfoBlock';
import OrderCargoBlock from '@/views/TMS/Orders/PropertiesParts/OrderCargoBlock';
import OrderPropertiesCaption from '@/views/TMS/Orders/PropertiesParts/OrderPropertiesCaption';
import OrderNotApprovedBlock from '@/views/TMS/Orders/PropertiesParts/OrderNotApprovedBlock';
import OperationCustomsPointsCollection from '@/views/TMS/Operations/PropertiesParts/OperationCustomsPointsCollection';
import OperationShipmentPointsCollection from '@/views/TMS/Operations/PropertiesParts/OperationShipmentPointsCollection';
import OrderCostOfServicesBlock from '@/views/TMS/Orders/PropertiesParts/OrderCostOfServicesBlock';
import OrderJobs from '@/views/TMS/Orders/PropertiesParts/OrderJobs';
import OperationStages from '@/views/TMS/Operations/PropertiesParts/OperationStages';
import TrolFileUploadDialog from '@/components/File/FileUploadDialog';
import OrderFailedBlock from '@/views/TMS/Orders/PropertiesParts/OrderFailedBlock';
import OperationFiles from '@/views/TMS/Operations/PropertiesParts/OperationFiles';
import OperationBills from '@/views/TMS/Operations/PropertiesParts/OperationBills';
import PrintableBillFiles from '@/views/TMS/Operations/PropertiesParts/PrintableBillFiles';
import StagePropertiesDialog from '@/views/TMS/Operations/PropertiesParts/Dialogs/StagePropertiesDialog';
import OperationDefaultTabMenu from '@/views/TMS/Operations/PropertiesParts/OperationDefaultTabMenu';
import Vue from 'vue';
import InternalTransfers from '@/views/TMS/Orders/PropertiesParts/InternalTransfers';
import {transfersStatusColors} from '@/utils/DataObjects';
import ReloadingPoint from '@/views/TMS/Orders/PropertiesParts/ReloadingPoint';

export default {
  name: 'OrderProperties',
  components: {
    ReloadingPoint,
    InternalTransfers,
    StagePropertiesDialog,
    OperationDefaultTabMenu,
    PrintableBillFiles,
    OperationBills,
    OperationFiles,
    OrderFailedBlock,
    TrolFileUploadDialog,
    OperationStages,
    OrderJobs,
    OrderCostOfServicesBlock,
    OperationShipmentPointsCollection,
    OperationCustomsPointsCollection,
    OrderNotApprovedBlock,
    OrderPropertiesCaption,
    OrderCargoBlock,
    OrderGeneralInfoBlock,
    PropertiesActions,
  },
  props: {
    id: {type: [String, Number], required: true},
  },
  data () {
    return {
      transfersStatusColors,
      primaryDispatchPoint: undefined,
      primaryDeliveryPoint: undefined,
      order: {},
      currentTab: undefined,
      loading: {
        reload: false,
      },
      bus: new Vue(),
      accessRights: {},
      fileTags: [
        {
          namespace: 'order',
          tags: [
            'app',
          ],
        },
      ],
      panelsState: {...this.$store.state.options.orderProperties.expandedBlocks},
    };
  },
  computed: {
    ...mapState({
      defaultTab: state => state.options.orderProperties.defaultTab,
    }),
  },
  watch: {
    primaryDispatchPoint () {
      this.$nextTick(() => {
        if (this.$refs.tabs) {
          this.$refs.tabs.callSlider();
        }
      });
    },
    primaryDeliveryPoint () {
      this.$nextTick(() => {
        if (this.$refs.tabs) {
          this.$refs.tabs.callSlider();
        }
      });
    },
    'panelsState.route' () {
      this.$nextTick(() => {
        this.bus.$emit('updateExpandableFields');
      });
    },
    currentTab () {
      this.$nextTick(() => {
        this.bus.$emit('updateExpandableFields');
      });
    },
  },
  created () {
    this.loadData();
    this.currentTab = this.defaultTab;
  },
  destroyed () {
    this.bus.$destroy();
  },
  methods: {
    async loadData (loadCollections = true) {
      try {
        this.loading.reload = true;
        const success       = await this.API.get.progress(['tms', 'orders', this.id]);
        this.order          = success.data.order;
        this.accessRights   = success.data.accessRights;
        if (loadCollections) {
          this.reloadParts();
        }
      } finally {
        this.loading.reload = false;
      }
    },
    reloadParts () {
      this.$nextTick(() => {
        if (this.$refs.dispatchPoints) {
          this.$refs.dispatchPoints.loadData();
          this.$refs.customsPoints.loadData();
          this.$refs.deliveryPoints.loadData();
          // if (this.$refs.workflows) {
          //   this.$refs.workflows.loadData();
          // }
          if (this.$refs.transfers) {
            this.$refs.transfers.loadData();
          }
          if (this.$refs.stagesTab) {
            this.$refs.stagesTab.loadData();
          }
        }
      });
    },
    isDefaultTab (tabIndex) {
      return tabIndex === this.defaultTab;
    },
  },
};
</script>

<style lang="sass"
       scoped>
.min-tab-height
  min-height: 300px

.tabs-margin
  margin-left: -4px
  margin-right: -4px
</style>
