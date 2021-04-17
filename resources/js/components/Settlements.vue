<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid" style="margin-top: 100px;">
      <div class="content">
        <b-card title="Settlement Accounts" v-if="!settlement_account">
          <b-table
            responsive
            striped
            hover
            show-empty
            :empty-text="'No Settlement Accounts have been Created. Create a Settlement Account below.'"
            id="foreign-brokers"
            :items="broker_settlement_accounts"
            :fields="fields"
            :per-page="perPage"
            :current-page="currentPage"
            @row-clicked="settlmentAccountHandler"
          >
            <template v-slot:cell(hash)="data">
              <!-- `data.value` is the value after formatted by the Formatter -->
              <b-button @click="resetFilledOrders(data)">Clear Unsettled Trades</b-button>
              <!--{{ data.value }}-->
            </template>
          </b-table>
          <b-pagination
            v-model="currentPage"
            :total-rows="rows"
            :per-page="perPage"
            aria-controls="foreign-brokers"
          ></b-pagination>
          <b-button @click="settlement_account={}">Create Settlement Account</b-button>
          <!-- <b-button @click="importAccounts">Import Accounts</b-button> -->
          <b-button @click="exportBalances">Export Balances</b-button>
        </b-card>
        <b-card id="modal-1" :title="title" v-else>
          <p class="my-4">Please update the fields below as required!</p>
          <form ref="form" @submit.stop.prevent="handleSubmit">
            <b-form-group
              sm
              label="Local Broker"
              label-for="name-input"
              invalid-feedback="Name is required"
            >
              <b-form-select
                label="Local Broker"
                label-for="localBroker-input"
                invalid-feedback="A Local Broker is required"
                sm
                v-model="settlement_account.local_broker_id"
                :options="local_brokers"
                required
              ></b-form-select>
            </b-form-group>
            <b-form-group
              label="Foreign Broker"
              label-for="foreign-input"
              invalid-feedback="Foreign Broker is required"
            >
              <b-form-select
                v-model="settlement_account.foreign_broker_id"
                :options="foreign_brokers"
                required
              ></b-form-select>
            </b-form-group>
            <b-form-group label="Bank" label-for="bank-input" invalid-feedback=" Bank is required">
              <b-form-input
                id="bank-input"
                v-model="settlement_account.bank_name"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="Account Number"
              label-for="account-input"
              invalid-feedback=" Account is required"
            >
              <b-form-input
                id="account-input"
                v-model="settlement_account.account"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="Bank Email"
              label-for="email-input"
              invalid-feedback=" Email is required"
            >
              <b-form-input
                id="email-input"
                v-model="settlement_account.email"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              sm
              label="Account Currency"
              label-for="currency-input"
              invalid-feedback="currency is required"
            >
              <b-form-select
                label="Currency"
                label-for="Currency-input"
                invalid-feedback="A currency is required"
                sm
                v-model="settlement_account.currency"
                :options="currencies"
                required
              ></b-form-select>
            </b-form-group>
            <b-form-group
              label="Account Balance "
              label-for="umir-input"
              invalid-feedback=" Account Balance  is required"
            >
              <b-form-input
                id="balance-input"
                v-model="settlement_account.account_balance"
                :state="nameState"
                type="text"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="Amount Allocated "
              label-for="umir-input"
              invalid-feedback=" Amount Allocated  is required"
            >
              <b-form-input
                id="allocated-input"
                v-model="settlement_account.amount_allocated"
                :state="nameState"
                type="text"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="Unsettled Trades "
              label-for="umir-input"
              invalid-feedback=" Unsettled Trades  is required"
            >
              <b-form-input
                id="allocated-input"
                v-model="settlement_account.filled_orders"
                :state="nameState"
                type="text"
                required
              ></b-form-input>
            </b-form-group>
            <b-button type="submit" variant="primary">Submit</b-button>
            <b-button variant="danger" @click="settlement_account=null">Cancel</b-button>
          </form>
        </b-card>
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import axios from "axios";
import headNav from "./partials/Nav.vue";
import currenciesMixin from "./../mixins/Currencies.js";
import checkErrorMixin from "./../mixins/CheckError.js";
export default {
  mixins: [currenciesMixin, checkErrorMixin],
  components: {
    "head-nav": headNav,
  },
  data() {
    return {
      broker_settlement_accounts: [],
      settlement_account: null,
      local_brokers: [],
      foreign_brokers: [],
      perPage: 5,
      currentPage: 1,
      fields: [
        {
          key: `local_broker.name`,
          label: "Local Broker",
          sortable: true,
        },
        {
          key: "foreign_broker.name",
          label: "Foreign Broker",
          sortable: true,
        },
        {
          key: "bank_name",
          label: "JCSD Email",
          sortable: true,
        },
        {
          key: "account",
          sortable: true,
        },
        {
          key: "email",
          label: "Bank Email",
          sortable: true,
        },
        {
          key: "filled_orders",
          label: "Unsettled Trades",
          sortable: true,
          formatter: (value, key, item) => {
            var formatter = new Intl.NumberFormat("en-US", {
              style: "currency",
              currency: "USD",
            });

            var cal = item.filled_orders;
            return formatter.format(cal);
          },
        },
        {
          key: "account_balance",
          sortable: true,
          formatter: (value, key, item) => {
            var formatter = new Intl.NumberFormat("en-US", {
              style: "currency",
              currency: "USD",
            });

            var cal = item.account_balance;
            return formatter.format(cal);
          },
        },
        {
          key: "available_balance",
          sortable: true,
          formatter: (value, key, item) => {
            var formatter = new Intl.NumberFormat("en-US", {
              style: "currency",
              currency: "USD",
            });

            var spent =
              Number(item.amount_allocated) + Number(item.filled_orders);

            var cal = item.account_balance - spent;
            return formatter.format(cal);
          },
        },
        {
          key: "amount_allocated",
          sortable: true,
          formatter: (value, key, item) => {
            var formatter = new Intl.NumberFormat("en-US", {
              style: "currency",
              currency: "USD",
            });

            var cal = item.amount_allocated;
            return formatter.format(cal);
          },
        },
        {
          key: "currency",
          sortable: true,
        },
        {
          key: "settlement_agent_status",
          label: "Status",
          sortable: true,
        },
        {
          key: "hash",
          label: "",
        },
      ],
      nameState: null,
    };
  },
  computed: {
    isNew() {
      return !(this.settlement_account && this.settlement_account.id);
    },
    title() {
      return `${this.isNew ? "Create" : "Update"} Broker Settlement Account`;
    },
    rows() {
      return this.broker_settlement_accounts.length;
    },
  },
  methods: {
    async resetFilledOrders(d) {
      //console.log(d.item);
      //Identify the settlement account based on its hash
      const account = d.item;
      account.filled_orders = 0;
      try {
        await axios.post("../store-settlement-broker", account);
        this.$swal(
          "Trades Updated!",
          "Unsettled trades have been cleared.",
          "success"
        );
        account.filled_orders = false;
      } catch (error) {
        //console.log(error);
        // this.checkDuplicateError(error);
      }
    },
    importAccounts() {
      this.$swal
        .fire({
          title: "Importing",
          html: "One moment while we import new settlement accounts.",
          timerProgressBar: true,
          onBeforeOpen: () => {
            this.$swal.showLoading();
          },
        })
        .then((result) => {});
    },
    exportBalances() {
      const tableData = [];
      for (var i = 0; i < this.broker_settlement_accounts.length; i++) {
        tableData.push([
          this.broker_settlement_accounts[i].local_broker["name"],
          this.broker_settlement_accounts[i].foreign_broker["name"],
          this.broker_settlement_accounts[i].bank_name,
          this.broker_settlement_accounts[i].account,
          this.broker_settlement_accounts[i].email,
          this.broker_settlement_accounts[i].account_balance,
          this.broker_settlement_accounts[i].amount_allocated,
        ]);
      }

      // //console.log(this.broker_settlement_accounts[i])
      // tableData.push(this.broker_settlement_accounts[i]);

      var doc = new jsPDF();
      //   // It can parse html:
      //   doc.autoTable({ html: "#foreign-brokers" });

      // Or use javascript directly:
      doc.autoTable({
        head: [
          [
            "Local Broker",
            "Foreign Broker",
            "JCSD Email",
            "Account",
            "Email",
            "Account Balance",
            "Amount Allocated",
          ],
        ],
        body: tableData,
      });
      doc.save("BrokerSettlementReport.pdf");
    },

    async handleSubmit() {
      // Exit when the form isn't valid
      //Determine if a new user is being created or we are updating an existing user
      const account = { ...this.settlement_account };
      if (this.isNew) {
        //Exclude ID
        account["id"] = null;
        account["status"] = "Unverified";
      }

      this.$swal.fire({
        title: `${this.isNew ? "Creating" : "Updating"} Settlement Account`,
        html: "One moment while we setup the Account",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        },
      });

      // //console.log(account);
      try {
        await axios.post("../store-settlement-broker", account);
        await this.getSettlementList();
        this.settlement_account = null;
        this.$swal.close();
      } catch (error) {
        this.checkDuplicateError(error);
      }
    },

    async settlmentAccountHandler(b) {
      // //console.log(b);
      //console.log("selected account1", b);
      const result = await this.$swal({
        title: "",
        icon: "info",
        html: `Would you like to Edit Or Delete the following Settlement Account</b> `,
        // showCloseButton: true,
        showCancelButton: true,
        focusConfirm: true,
        cancelButtonColor: "#DD6B55",
        confirmButtonText: "Edit",
        confirmButtonAriaLabel: "delete",
        cancelButtonText: "Delete",
        cancelButtonAriaLabel: "cancel",
      });
      if (result.value) {
        this.settlement_account = { ...b };
        //console.log("selected account2", this.settlement_account);
      }
      if (result.dismiss === "cancel") {
        await this.destroy(b.id);
      }
    },

    async getSettlementList() {
      ({ data: this.broker_settlement_accounts } = await axios.get(
        "../settlement-list"
      )); //.then(response => {
      //console.log(
        "broker_settlement_accounts",
        this.broker_settlement_accounts
      );
    },

    async destroy(id) {
      this.$swal.fire({
        title: `Deleting Settlement Account`,
        html: "One moment while we delete the Account",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        },
      });
      try {
        await axios.delete(`../settlement-account-delete/${id}`); //.then(response => {
        await this.getSettlementList();
        this.$swal(
          "Deleted!",
          "Settlement Account Has Been Removed.",
          "success"
        );
      } catch (error) {
        this.checkDeleteError(error);
      }
    },

    async getlocalBrokers() {
      const { data } = await axios.get("../local-brokers");
      this.local_brokers = data.map(({ user }) => ({
        text: user.name,
        value: user.id,
      }));
      //console.log("local brokers", this.local_brokers);
    },
    async getForeignBrokers() {
      const { data } = await axios.get("../foreign-brokers");
      this.foreign_brokers = data.map(({ user }) => ({
        text: user.name,
        value: user.id,
      }));
      //console.log("foreign brokers", this.foreign_brokers);
    },
  },
  async mounted() {
    await Promise.all([
      this.getlocalBrokers(),
      this.getForeignBrokers(),
      this.getSettlementList(),
    ]);
  },
};
</script>
