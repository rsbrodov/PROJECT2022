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
                 lg="2">
            <trol-lazy-select v-model="props.searchBy.serviceOffice"
                              item-text="name"
                              item-value="id"
                              item-disabled="dummy"
                              items-url="/lazy-select/service-office"
                              :label="$t('Office')"
                              full-load
                              cached
                              clearable
                              hide-details
                              multiple />
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
                 lg="2">
            <trol-lazy-select v-model="props.searchBy.product"
                              item-text="name"
                              item-value="id"
                              item-disabled="dummy"
                              :enabled-items-url="enabledProductsUrl"
                              :items-url="productsUrl"
                              :label="$t('Product')"
                              selected-url="/lazy-select/product/current/{id}"
                              full-load
                              cached
                              clearable
                              hide-details
                              @input="props.searchBy.jobType = []" />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 lg="2">
            <trol-lazy-select v-model="props.searchBy.jobType"
                              item-text="name"
                              item-value="id"
                              item-disabled="dummy"
                              :items-url="jobTypeUrl"
                              :enabled-items-url="[...jobTypeUrl,'enabled']"
                              :selected-url="['tms', 'job-types', 'lazy-select', 'current', '{id}']"
                              :label="$t('Job type')"
                              full-load
                              cached
                              clearable
                              hide-details
                              multiple />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 lg="2">
            <v-select v-model="props.searchBy.year"
                      item-text="name"
                      item-value="id"
                      item-disabled="dummy"
                      :items="yearFilterChoices"
                      :label="$t('Year')"
                      clearable
                      hide-details />
          </v-col>
          <v-col cols="12"
                 sm="6"
                 lg="2">
            <v-select v-model="props.searchBy.month"
                      item-text="name"
                      item-value="id"
                      item-disabled="dummy"
                      :items="monthFilterChoices"
                      :label="$t('MonthByFirstJob')"
                      clearable
                      hide-details />
          </v-col>
        </v-row>
      </template>
    </search-layout>
    <v-card class="fit-width mt-2">
      <v-card-text>
        <div ref="topScrollbar"
             v-scroll.self="onHorizontalScroll"
             class="data-table-top-scroll">
          <div ref="topScrollContent"
               :style="{width: tableWidth}"
               class="data-table-top-scroll-content" />
        </div>
        <div ref="tableContent"
             v-scroll.self="onHorizontalScroll"
             class="data-table-row">
          <div ref="dataTable"
               class="table-block">
            <v-simple-table dense>
              <template v-slot>
                <thead class="">
                  <tr v-for="(row, indexRow) in columnsComputed"
                      :key="indexRow">
                    <th v-for="(th, indexTh) in row"
                        :key="indexTh"
                        :rowspan="th.rowspan"
                        :colspan="th.colspan"
                        class="text-center">
                      {{th.label}}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="(element, index) in data">
                    <tr :key="'first-tr-'+index"
                        class="text-center">
                      <td>{{element.serviceOfficeName}}</td>
                      <td>
                        <router-link :to="{name: 'CounterpartyProperties', params:{id: element.clientId}}">
                          {{element.clientName}}
                        </router-link>
                      </td>
                      <td>{{element.productName}}</td>
                      <td>{{element.January?element.January:'-'}}</td>
                      <td>{{element.February?element.February:'-'}}</td>
                      <td>{{element.March?element.March:'-'}}</td>
                      <td>{{element.April?element.April:'-'}}</td>
                      <td>{{element.May?element.May:'-'}}</td>
                      <td>{{element.June?element.June:'-'}}</td>
                      <td>{{element.July?element.July:'-'}}</td>
                      <td>{{element.August?element.August:'-'}}</td>
                      <td>{{element.September?element.September:'-'}}</td>
                      <td>{{element.October?element.October:'-'}}</td>
                      <td>{{element.November?element.November:'-'}}</td>
                      <td>{{element.December?element.December:'-'}}</td>
                      <td>{{element.Total}}</td>
                    </tr>
                    <tr v-if="(data[index+1] && data[index].monthByFirstJob != data[index+1].monthByFirstJob) || !data[index+1]"
                        :key="'second-tr-'+index"
                        class="sub-total-row">
                      <td colspan="1">
                        {{$t('months.' + subTotalData[data[index].monthByFirstJob].monthName)}}
                      </td>
                      <td colspan="2">
                        {{$t('New clients')}}: {{subTotalData[data[index].monthByFirstJob].countClient}}
                      </td>
                      <td colspan="12" />
                      <td class="text-center">
                        {{subTotalData[data[index].monthByFirstJob].total}}
                      </td>
                    </tr>
                  </template>
                </tbody>
              </template>
            </v-simple-table>
          </div>
        </div>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script>
import extend from 'lodash/extend';
import debounce from 'debounce';
import {SearchState} from '@/services';
import {SearchLayout} from '@/components/DataTable';
import {transfersStatusColors, yearFilterChoices, monthFilterChoices} from '@/utils/DataObjects';
import {getValue} from '@/utils/PathAccessor';

const propsDefaults = {
  shownColumns: [],
  grouping: undefined,
  sorting: {},
  searchBy: {
    product: undefined,
    service: undefined,
    jobType: [],
    counterparty: undefined,
    clientManager: undefined,
    serviceOffice: [],
    year: new Date().getFullYear(),
    month: undefined,
  },
};

export default {
  name: 'NewClientsReport',
  components: {SearchLayout},
  data () {
    return {
      yearFilterChoices,
      monthFilterChoices,
      transfersStatusColors,
      loading: true,
      props: extend({}, propsDefaults),
      data: [],
      subTotalData: [],
      totalCount: 0,
      quickFilter: '',
      cancelingApi: this.API.getCancelingApi(),
      tableWidth: '0',
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
    jobTypeUrl () {
      return this.getValue(this.props.searchBy, 'product')
        ? ['tms', 'products', this.props.searchBy.product, 'job-types', 'lazy-select']
        : ['tms', 'job-types', 'lazy-select'];
    },
    columnsComputed () {
      return [
        [
          {label: this.$t('Office'), rowspan: 2, colspan: 1},
          {label: this.$t('Client'), rowspan: 2, colspan: 1},
          {label: this.$t('Product'), rowspan: 2, colspan: 1},
          {label: '1 Q', rowspan: 1, colspan: 3},
          {label: '2 Q', rowspan: 1, colspan: 3},
          {label: '3 Q', rowspan: 1, colspan: 3},
          {label: '4 Q', rowspan: 1, colspan: 3},
          {label: this.$t('Total'), rowspan: 1, colspan: 1},
        ],
        [
          {label: this.$t('months.January'), rowspan: 1, colspan: 1},
          {label: this.$t('months.February'), rowspan: 1, colspan: 1},
          {label: this.$t('months.March'), rowspan: 1, colspan: 1},
          {label: this.$t('months.April'), rowspan: 1, colspan: 1},
          {label: this.$t('months.May'), rowspan: 1, colspan: 1},
          {label: this.$t('months.June'), rowspan: 1, colspan: 1},
          {label: this.$t('months.July'), rowspan: 1, colspan: 1},
          {label: this.$t('months.August'), rowspan: 1, colspan: 1},
          {label: this.$t('months.September'), rowspan: 1, colspan: 1},
          {label: this.$t('months.October'), rowspan: 1, colspan: 1},
          {label: this.$t('months.November'), rowspan: 1, colspan: 1},
          {label: this.$t('months.December'), rowspan: 1, colspan: 1},
          {label: this.totalCount, rowspan: 1, colspan: 1},
        ],
      ];
    },
  },
  watch: {
    data () {
      this.updateWidthDebounced();
    },
  },
  created () {
    this.props = SearchState.restore(this.$options.name, this.props);
    this.loadData();
  },
  mounted () {
    this.updateWidth();
  },
  methods: {
    getValue,
    async loadData () {
      this.loading = true;
      try {
        const success = await this.cancelingApi.post.progress(['reports', 'new-clients'],
          {
            ...this.props,
            quickFilter: this.quickFilter,
          },
        );
        SearchState.save(this.$options.name, this.props);
        this.totalCount = success.data.page.totalCount;
        this.data   = success.data.page.data.map((row, index) => {
          return {
            ...row,
            originalIndex: index,
          };
        });
        this.subTotalData   = success.data.page.subTotalData;
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
    onHorizontalScroll (event) {
      if (this.scrolling) {
        this.scrolling = false;
        return;
      }
      this.scrolling = true;

      if (event.target === this.$refs.topScrollbar) {
        this.$refs.tableContent.children[0].children[0].children[0].scrollLeft = this.$refs.topScrollbar.scrollLeft;
      }
      if (event.target === this.$refs.tableContent.children[0].children[0].children[0]) {
        this.$refs.topScrollbar.scrollLeft = this.$refs.tableContent.children[0].children[0].children[0].scrollLeft;
      }
    },
    updateWidthDebounced: debounce(function () {
      this.updateWidth();
    }, 100),
    updateWidth () {
      const myImage = document.getElementsByClassName('v-data-table__wrapper')[0];
      myImage.addEventListener('scroll', this.onHorizontalScroll);
      this.tableWidth = this.$refs.dataTable.children[0].children[0].children[0].offsetWidth + 'px';
    },
  },
};
</script>

<style scoped lang="css">
table.text-center * {
  text-align: center
}
th, td{
  border: 1px solid rgba(0, 0, 0, 0.3);
}
.v-card-text{
  padding: 0!important;
}

.data-table-row, .data-table-top-scroll {
  min-width: 100%;
  overflow-x: auto;
  margin-left: -16px;
  margin-right: -16px;
}

.data-table-top-scroll {
  padding-top: 10px;
}

.data-table-top-scroll-content {
  height: 1px;
}

</style>
<style>
.v-data-table__wrapper > table {
  border-collapse: collapse!important;
}
</style>
<style lang="sass"
       scoped>
.theme--dark
  th, td
    color: #FFFFFFFF
  thead
    background: #393939!important

.theme--light
  th, td
    color: black!important
  thead, .sub-total-row
    background: #fce4ec !important
  .sub-total-row
    background: #E3F2FD !important
</style>
