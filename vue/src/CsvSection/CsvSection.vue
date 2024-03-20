<!--
  © 2024 Jorge Powers. All rights reserved.

  @link https://jorgeuos.com
  @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="card">
    <div class="card-content">
      <h2 class="card-title">{{ translate('DataExport_CsvCardTitle') }}</h2>

      <div>
        <p>
          <label for="csvSite">Select a site: <span v-bind="this.site">
            {{ site }}</span></label><br>
        </p>
        <p>
          <SiteSelector id="csvSite" v-model="site" :show-all-sites-item="false"
            :switch-site-on-select="false"
            :show-selected-site="true"
            :default-to-first-site="true"
            @update:model-value="site = $event; websiteChanged()"
            ></SiteSelector>
        </p>
        <p>
          <label for="date">
            <b>
              Date:
            </b>
          </label>
        </p>
        <p>
          <input type="date" id="date" name="date" v-model="date" @change="dateChanged"
           style="max-width:200px;">
        </p>
        <p>
          <button @click="downloadCSV" class="btn">Download CSV</button>
        </p>
      </div>

      <MatomoDialog v-model="showDialog">
        <div class="ui-confirm exampleDialog">
          <h2>CSV Export - Select all visits and actions by siteId</h2>
          <h2>Alert</h2>
          <p>
            The count is greater than 1 right now!
          </p>

          <input type="button" value="OK" role="yes" />
        </div>
      </MatomoDialog>
    </div>
  </div>
</template>

<style lang="less" scoped>
.example-component {
  display: flex;
  justify-content: center;
  align-items: center;

  button {
    background-color: indigo;
    color: white;
    margin: 8px;
  }
}
</style>

<script lang="ts">
import { defineComponent } from 'vue';
import {
  translate,
  AjaxHelper,
  MatomoDialog,
  SiteSelector,
  SiteRef,
  Site,
  Matomo,
} from '../../../../CoreHome/vue/src/index.ts';
// } from 'CoreHome';

interface CsvSectionState {
  isLoading: boolean;
  count: number;
  showDialog: boolean;
  idSite: string | number;
  siteName: string;
  site: SiteRef;
  sites: Record<string, Site>;
  tokenAuth: string;
  login: string;
  date: string;
}

export default defineComponent({
  props: {
    defaultSite: {
      type: Object,
      required: true,
    },
  },
  components: {
    MatomoDialog,
    SiteSelector,
  },
  data(): CsvSectionState {
    return {
      isLoading: false,
      count: 12,
      showDialog: false,
      idSite: '',
      siteName: '',
      site: this.defaultSite as SiteRef,
      sites: {},
      tokenAuth: '', // Placeholder for the token_auth
      login: '',
      date: '',
    };
  },
  async mounted() {
    console.log('Mounted...');
    console.log('Sites:', this.sites);
  },
  setup() {
    console.log('Setup...');
    const sitesPromise = AjaxHelper.fetch<(string | number)[]>({
      method: 'SitesManager.getSitesIdWithAdminAccess',
      filter_limit: '-1',
    });

    return {
      translate,
      getSites() {
        return sitesPromise;
      },
    };
  },
  methods: {
    downloadCSV() {
      console.log('Download CSV for site ID:', this.site);
      console.log('Date:', this.date);

      if (!this.site) {
        console.error('Site ID is undefined or not selected.');
        return;
      }
      const redirectParams = {
        module: 'DataExport',
        action: 'selectAllVisitsAndActions',
        idSite: this.site.id,
        date: this.date,
      };
      // Redirect because download is not possible with ajax
      Matomo.helper.redirect(redirectParams);
    },
    websiteChanged() {
      console.log('websiteChanged this.site:', this.site);
      console.log('websiteChanged this.site:', this.site);
    },
    dateChanged() {
      console.log('dateChanged this.date:', this.date);
    },
    sendRequest() {
      console.log('sendRequest: selectAllVisitsAndActionsSiteId');
    },
  },
});
</script>
