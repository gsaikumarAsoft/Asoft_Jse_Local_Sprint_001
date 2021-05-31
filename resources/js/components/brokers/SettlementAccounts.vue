<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid" style="margin-top: 20px;">
      <div class="content">
        <!-- <pre>{{broker_settlement_account}}</pre> -->
        <b-card title="Settlement Accounts" v-if="!settlement_account">
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
          >
            <!-- @row-clicked="settlmentAccountHandler" -->
            <template slot="index" slot-scope="row">{{ row }}</template>
          </b-table>
          <b-pagination
            v-model="currentPage"
            :total-rows="rows"
            :per-page="perPage"
            aria-controls="foreign-brokers"
          ></b-pagination>
          <!-- <b-button v-b-modal.modal-1 @click="create = true">Create Settlement Account</b-button> -->
          <b-button @click="exportBalances">Export Balances</b-button>
        </b-card>

        <b-card title="Update Settlement Account" v-else>
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
              invalid-feedback="Bank Email is required"
            >
              <b-form-input
                id="email-input"
                v-model="settlement_account.email"
                :state="nameState"
                required
              ></b-form-input>
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
import headNav from "./../partials/Nav.vue";
import checkErrorMixin from "../../mixins/CheckError.js";
export default {
  props: ["settlement_accounts"],
  components: {
    "head-nav": headNav,
  },
  mixins: [checkErrorMixin],
  data() {
    return {
      broker_settlement_account: this.settlement_accounts,
      settlement_account: null,
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
      ],

      nameState: null,
    };
  },
  computed: {
    rows() {
      return this.broker_settlement_account.length;
    },
  },
  methods: {
    exportBalances() {
      const tableData = [];
      for (var i = 0; i < this.broker_settlement_account.length; i++) {
        tableData.push([
          // this.broker_settlement_account[0].local_broker["name"],
          this.broker_settlement_account[0].foreign_broker["name"],
          this.broker_settlement_account[0].bank_name,
          this.broker_settlement_account[0].account,
          this.broker_settlement_account[0].email,
          this.broker_settlement_account[0].account_balance,
          this.broker_settlement_account[0].amount_allocated,
        ]);
      }

      // //console.log(this.broker_settlement_account[i])
      // tableData.push(this.broker_settlement_account[i]);

      var doc = new jsPDF();
      //   // It can parse html:
      //   doc.autoTable({ html: "#foreign-brokers" });

      // Or use javascript directly:
      doc.autoTable({
        head: [
          [
            // "Local Broker",
            "Foreign Broker",
            "Bank",
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
      let account = {
        account: this.settlement_account.account,
        account_balance: this.settlement_account.account_balance,
        amount_allocated: this.settlement_account.amount_allocated,
        bank_name: this.settlement_account.bank_name,
        email: this.settlement_account.email,
        foreign_broker_id: this.settlement_account.foreign_broker_id,
        id: this.settlement_account.id,
        local_broker_id: this.settlement_account.local_broker_id,
        status: "Unverified",
      };

      this.$swal.fire({
        title: `Updating Settlement Account`,
        html: "One moment while we setup the Account",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        },
      });

      // //console.log(account);
      try {
        await axios.post("../store-settlement-broker", account);
        //.then(response => {
        await this.getSettlementList();
        setTimeout(location.reload.bind(location));
      } catch (error) {
        this.checkDuplicateError(error);
      }
    },

    settlmentAccountHandler(b) {
      //console.log(b);
      this.settlement_account = b;
    },

    setLocalBroker() {
      // //console.log(this);
    },
    async getSettlementList() {
      // axios.get("../settlement-list").then(response => {
      //   let broker_settlement_accounts = response.data;
      //   this.broker_settlement_account = [];
      //   // this.broker_settlement_account = broker_settlement_accounts;
      //   // //console.log(this.broker_settlement_account);
      // });
      // setTimeout(location.reload.bind(location));
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
        this.$swal.close();
        setTimeout(location.reload.bind(location));
      } catch (error) {
        this.checkDeleteError(error);
      }
    },

    async getlocalBrokers() {
      const { data } = await axios.get("local-brokers");
      this.local_brokers = data.map(({ user }) => ({
        text: user.name,
        value: user.id,
      }));
      //console.log("local brokers", this.local_brokers);
    },
    async getForeignBrokers() {
      const { data } = await axios.get("foreign-broker-list");
      this.foreign_brokers = data.map(({ user }) => ({
        text: user.name,
        value: user.id,
      }));
      //console.log("foreign brokers", this.foreign_brokers);
    },
  },
  async mounted() {
    await Promise.all([this.getlocalBrokers(), this.getForeignBrokers()]);

    // await this.getSettlementList();
  },
};
</script>
