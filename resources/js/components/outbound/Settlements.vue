<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid" style="margin-top: 20px;">
      <div class="content">
        <h1>Settlement View For Foreign Broker</h1>
        <b-table
          striped
          hover
          show-empty
          :empty-text="'No Settlement Accounts have been created'"
          id="foreign-brokers"
          :items="broker_settlement_account"
          :fields="fields"
          :per-page="perPage"
          :current-page="currentPage"
        ></b-table>
        <b-pagination
          v-model="currentPage"
          :total-rows="rows"
          :per-page="perPage"
          aria-controls="foreign-settlements"
        ></b-pagination>
        <!-- <b-button v-b-modal.modal-1 @click="create = true">Create Settlement Account</b-button> -->
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
    "head-nav": headNav,
  },
  data() {
    return {
      create: false,
      broker_settlement_account: JSON.parse(this.accounts),
      settlement_account: {},
      local_brokers: [],
      foreign_brokers: [],
      perPage: 5,
      currentPage: 1,
      fields: [
        {
          key: "foreign_broker.name",
          label: "Foreign Broker",
          sortable: true,
        },
        {
          key: "bank_name",
          label: "Bank",
          sortable: true,
        },
        {
          key: "account",
          sortable: true,
        },
        {
          key: "email",
          sortable: true,
        },
        {
          key: "account_balance",
          sortable: true,
        },
        {
          key: "amount_allocated",
          sortable: true,
        },
        {
          key: "currency",
          sortable: true,
        },
        {
          key: "status",
          sortable: true,
        },
      ],
      modalTitle: "Create Broker Settlement Account",
      nameState: null,
    };
  },
  computed: {
    rows() {
      return this.broker_settlement_account.length;
    },
  },
  methods: {},
  mounted() {},
};
</script>
