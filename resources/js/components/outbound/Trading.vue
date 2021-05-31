<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid" style="margin-top: 20px;">
      <div class="content">
        <h1>Trading Account View For Foreign Broker</h1>
        <b-table
          striped
          hover
          show-empty
          :empty-text="'No Trading Accounts have been created'"
          id="foreign-brokers"
          :items="broker_trading_account"
          :fields="fields"
          :per-page="perPage"
          :current-page="currentPage"
        ></b-table>
        <b-pagination
          v-model="currentPage"
          :total-rows="rows"
          :per-page="perPage"
          aria-controls="foreign-tradings"
        ></b-pagination>
        <!-- <b-button v-b-modal.modal-1 @click="create = true">Create Trading Account</b-button> -->
        <!-- <b-button @click="exportBalances">Export Balances</b-button> -->
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import axios from "axios";
import headNav from "./../partials/Nav.vue";
export default {
  props: ["accounts"],
  components: {
    "head-nav": headNav
  },
  data() {
    return {
      create: false,
      broker_trading_account: JSON.parse(this.accounts),
      trading_account: {},
      local_brokers: [],
      foreign_brokers: [],
      perPage: 5,
      currentPage: 1,
      fields: [
        {
          key: "umir",
          //   label: "Foreign Broker",
          sortable: true
        },
        {
          key: "target_comp_id",
          //   label: "Foreign Broker",
          sortable: true
        },
        {
          key: "sender_comp_id",
          //   label: "Trading Agent",
          sortable: true
        },
        {
          key: "socket",
          sortable: true
        },
        {
          key: "port",
          sortable: true
        },
        {
          key: "created_at",
          sortable: true
        }
      ],
      modalTitle: "Create Broker Trading Account",
      nameState: null
    };
  },
  computed: {
    rows() {
      return this.broker_trading_account.length;
    }
  },
  methods: {},
  mounted() {
    // console.log(this.broker_trading_account);
  }
};
</script>
