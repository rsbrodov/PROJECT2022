<template>
  <v-sheet>
    <v-row no-gutters
           align="end">
      <v-col class="body-1">
        {{$t('Cargo')}}
        <v-chip v-if="job.cargo.damaged"
                small
                class="compact"
                color="error">
          <v-icon small
                  class="mr-1">
            mdi-alert
          </v-icon>
          {{$t('damaged')}}
        </v-chip>
      </v-col>
      <v-col v-if="$isGranted('ROLE_EDIT_CARGO_DAMAGE')"
             class="flex-grow-0 align-self-center">
        <confirm-btn v-if="job.cargo.damaged"
                     text
                     x-small
                     color="warning"
                     :api-url="['tms', 'jobs', job.id, 'cargo-damaged']"
                     api-method="post"
                     :api-data="{damaged: !job.cargo.damaged}"
                     @confirmed="$emit('input', $event)">
          {{$t('cancelDamage')}}
        </confirm-btn>
        <confirm-btn v-else
                     color="warning"
                     text
                     x-small
                     :api-url="['tms', 'jobs', job.id, 'cargo-damaged']"
                     api-method="post"
                     :api-data="{damaged: !job.cargo.damaged}"
                     @confirmed="$emit('input', $event)">
          {{$t('damage')}}
        </confirm-btn>
      </v-col>
      <v-col class="flex-grow-0">
        <job-cargo-dialog v-if="accessRights.edit && !job.isChangesLocked"
                          v-model="job" />
      </v-col>
    </v-row>
    <v-divider />
    <div class="properties-grid">
      <div class="label text--secondary">
        {{$t('Description')}}:
      </div>
      <div>
        {{job|accessor('cargoShortDescription', '---')}}
      </div>
      <div class="label text--secondary">
        {{$t('tnved')}}:
      </div>
      <div class="flex-wrap">
        <v-row v-for="item in job.tnved"
               :key="item.id"
               dense
               class="caption"
               style="width: 100%">
          <v-col v-if="item.name">
            <span class="font-weight-bold accent--text">{{item.code}}</span> {{item.name|truncate(50)}}
          </v-col>
          <v-col v-else>
            <span class="font-weight-bold accent--text">{{item.code}}</span> {{item.treeName|truncate(50)}}
          </v-col>
          <v-col class="flex-grow-0">
            <trol-tooltip-wrapper left>
              <template v-slot:activator="{on}">
                <v-icon color="info"
                        small
                        v-on="on">
                  mdi-information-outline
                </v-icon>
              </template>
              <div style="max-width: 500px">
                {{item.name}}
              </div>
            </trol-tooltip-wrapper>
          </v-col>
        </v-row>
      </div>
      <div class="label text--secondary">
        {{$t('Warning')}}:
      </div>
      <div :class="{'warning--text':job.cargoWarning}">
        {{job|accessor('cargoWarning', '---')}}
      </div>
      <div class="label text--secondary">
        {{$t('countryOfOrigin')}}:
      </div>
      <div>
        {{job|accessor('cargoOriginCountry.code', '---')}} ({{job|accessor('cargoOriginCountry.name', '---')}})
      </div>
      <div class="label text--secondary">
        {{$t('declaredValue')}}:
      </div>
      <div>
        <v-row no-gutters
               align="center">
          <v-col v-if="parseFloat(job.cargoDeclaredValue) >= parseFloat($store.state.options.cargoInsuredValue)"
                 class="flex-grow-0 pr-1">
            <trol-tooltip-wrapper top>
              <template v-slot:activator="{on}">
                <v-icon color="warning"
                        small
                        v-on="on">
                  mdi-cash-multiple
                </v-icon>
              </template>
              {{$t('Cargo declared value is more than')}}
              <span class="warning--text">{{$store.state.options.cargoInsuredValue}}</span>
              {{$store.state.options.systemCurrency}}
            </trol-tooltip-wrapper>
          </v-col>
          <v-col>
            {{job.cargoDeclaredValue|finance}} EUR
          </v-col>
        </v-row>
      </div>
      <div class="label text--secondary">
        {{$t('cargoLiquid')}}:
      </div>
      <div class="align-center">
        <v-row no-gutters
               align="center">
          <v-col v-if="job.cargo.liquid"
                 class="flex-grow-0 pr-1">
            <v-icon small
                    color="warning">
              mdi-bus-alert
            </v-icon>
          </v-col>
          <v-col>
            {{$t(job.cargo.liquid ? 'Yes' : 'No')}}
          </v-col>
        </v-row>
      </div>
      <div class="properties-grid-span pt-0">
        <v-row no-gutters
               align="start">
          <v-col cols="12">
            <v-divider />
          </v-col>
          <v-col>
            <div class="properties-grid">
              <div class="label text--secondary">
                {{$t('grossWeight')}}:
              </div>
              <div>
                {{job.cargoWeight|finance(3)}} t
              </div>
              <div class="label text--secondary">
                Coll:
              </div>
              <div>
                {{job.cargoPlaces}}
              </div>
            </div>
          </v-col>
          <v-col>
            <div class="properties-grid">
              <div class="label text--secondary">
                {{$t('Volume')}}:
              </div>
              <div>
                <span>{{job.cargoVolume}} m<sup>3</sup></span>
              </div>
              <div class="label text--secondary">
                LDM:
              </div>
              <div>
                {{job.cargoLDM|finance(2)}}
              </div>
            </div>
          </v-col>
          <v-col cols="12">
            <v-divider />
          </v-col>
        </v-row>
      </div>
      <div v-if="job.cargoSizeChanged"
           class="properties-grid-span">
        <v-row no-gutters
               align="start">
          <v-col cols="12">
            <v-row no-gutters
                   align="end">
              <v-col>
                <v-icon small
                        color="warning">
                  mdi-alert
                </v-icon>
                {{$t('cargoParametersChanged')}}
              </v-col>
              <v-col class="flex-grow-0">
                <confirm-btn color="success"
                             x-small
                             :api-url="['tms','jobs',job.id, 'accept-cargo-changes']"
                             @confirmed="onCargoChangesAccepted">
                  {{$t('Accept')}}
                </confirm-btn>
              </v-col>
            </v-row>
          </v-col>
          <v-col cols="12"
                 class="pt-1">
            <v-divider />
          </v-col>
          <v-col>
            <div class="properties-grid">
              <div class="label text--secondary">
                {{$t('grossWeight')}}:
              </div>
              <div :class="{'warning--text': computedCargo.cargoWeight !== job.cargoWeight}">
                {{computedCargo.cargoWeight|finance(3)}} t
              </div>
              <div class="label text--secondary">
                Coll:
              </div>
              <div :class="{'warning--text': computedCargo.cargoPlaces !== job.cargoPlaces}">
                {{computedCargo.cargoPlaces}}
              </div>
            </div>
          </v-col>
          <v-col>
            <div class="properties-grid">
              <div class="label text--secondary">
                {{$t('Volume')}}:
              </div>
              <div :class="{'warning--text': computedCargo.cargoVolume !== job.cargoVolume}">
                <span>{{computedCargo.cargoVolume}} m<sup>3</sup></span>
              </div>
              <div class="label text--secondary">
                LDM:
              </div>
              <div :class="{'warning--text': computedCargo.cargoLDM !== job.cargoLDM}">
                {{computedCargo.cargoLDM|finance(2)}}
              </div>
            </div>
          </v-col>
          <v-col cols="12">
            <v-divider />
          </v-col>
        </v-row>
      </div>
      <div class="label text--secondary">
        {{$t('Comment')}}:
      </div>
      <div>
        <span class="text-pre-wrap">{{job.comment}}</span>
      </div>
    </div>
  </v-sheet>
</template>

<script>
import {cargoStackableDataObject} from '@/utils/DataObjects';
import JobCargoDialog from '@/views/TMS/Job/PropertiesParts/Dialogs/JobCargoDialog';

export default {
  name: 'JobCargoBlock',
  components: {JobCargoDialog},
  props: {
    value: {
      type: Object,
      default: () => {
        return {};
      },
    },
    accessRights: {
      type: Object,
      required: true,
    },
    computedCargo: {
      type: Object,
      required: true,
    },
  },
  data () {
    return {
      cargoStackableDataObject,
    };
  },
  computed: {
    job: {
      get () {
        return this.value;
      },
      set (val) {
        this.$emit('input', val);
      },
    },
  },
  methods: {
    onCargoChangesAccepted (job) {
      this.job = job;
      this.$store.dispatch('snackbar/add', {
        color: 'success', content: 'changesAccepted',
      });
    },
  },
};
</script>

<style lang="sass"
       scoped>
.order-collection-block
  width: 100%
</style>
