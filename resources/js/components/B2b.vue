<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid" style="margin-top: 20px;">
      <div class="content">
        <b-card title="Trading Accounts" v-if="!trading_account">
          <b-table
            striped
            hover
            show-empty
            :empty-text="'No Trading Account Configurations have been Created. Create a Trading Account Configuration below.'"
            id="foreign-brokers"
            :items="trading_accounts"
            :fields="fields"
            :per-page="perPage"
            :current-page="currentPage"
            @row-clicked="accountHandler"
          >
            <template slot="index" slot-scope="row">{{ row }}</template>
          </b-table>
          <b-pagination
            v-model="currentPage"
            :total-rows="rows"
            :per-page="perPage"
            aria-controls="foreign-brokers"
          ></b-pagination>
          <b-button @click="trading_account = {}">Create Account</b-button>
        </b-card>
        <b-card :title="title" v-else>
          <p class="my-4">Please update the fields below as required!</p>
          <form ref="form" @submit.stop.prevent="handleSubmit">
            <!-- <b-form-group
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
                v-model="trading_account.local_broker_id"
                :options="local_brokers"
              ></b-form-select>
            </b-form-group>
            <b-form-group
              label="Foreign Broker"
              label-for="foreign-input"
              invalid-feedback="Foreign Broker is required"
            >
              <b-form-select
                v-model="trading_account.foreign_broker_id"
                Socket
                :options="foreign_brokers"
              ></b-form-select>
            </b-form-group>-->
            <b-form-group
              label="Settlement Account Number"
              label-for="Settlement-Account-Number-input"
              invalid-feedback="Settlement Account Number is required"
            >
              <b-form-select
                label="Settlement Account"
                label-for="localBroker-input"
                invalid-feedback="A Settlement Account is required"
                sm
                v-model="trading_account.broker_settlement_account"
                :options="broker_settlement_accounts_options"
              ></b-form-select>
            </b-form-group>
            <b-form-group
              label="Trading Account Number"
              label-for="Trading-Account-Number-input"
              invalid-feedback="Trading Account Number is required"
            >
              <b-form-input
                id="trading-account-number-input"
                v-model="trading_account.trading_account_number"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group label="UMIR" label-for="umir-input" invalid-feedback=" UMIR is required">
              <b-form-input
                id="umir-input"
                v-model="trading_account.umir"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="TargetCompID"
              label-for="target-input"
              invalid-feedback="TargetCompID is required"
            >
              <b-form-input
                id="target-input"
                v-model="trading_account.target_comp_id"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="senderCompID"
              label-for="sender-input"
              invalid-feedback="SenderCompID is required"
            >
              <b-form-input
                id="sender-input"
                v-model="trading_account.sender_comp_id"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="Socket"
              label-for="socket-input"
              invalid-feedback="Socket is required"
            >
              <b-form-input
                id="socket-input"
                v-model="trading_account.socket"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group label="Port" label-for="port-input" invalid-feedback="Port is required">
              <b-form-input
                id="port-input"
                v-model="trading_account.port"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-button type="submit" variant="primary">Submit</b-button>
            <b-button variant="danger" @click="trading_account=null">Cancel</b-button>
          </form>
        </b-card>
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import axios from "axios";
import headNav from "./partials/Nav.vue";
import checkErrorMixin from "./../mixins/CheckError.js";
export default {
  props: ["accounts"],
  components: {
    "head-nav": headNav,
  },
  mixins: [checkErrorMixin],
  data() {
    return {
      trading_accounts: [],

      broker_settlement_accounts: [],
      trading_account: null,
      local_brokers: [],
      foreign_brokers: [],
      perPage: 5,
      currentPage: 1,
      fields: [
        {
          key: `local_broker`,
          label: "Local Broker",
          sortable: true,
        },
        {
          key: "foreign_broker",
          label: "Foreign Broker",
          sortable: true,
        },
        {
          key: "settlement_account.bank_name",
          label: "Bank",
          sortable: true,
        },
        {
          key: "settlement_account.account",
          label: "Account",
          sortable: true,
        },
        {
          key: "trading_account_number",
          label: "Trader Account Number",
          sortable: true,
        },
        {
          key: "sender_comp_id",
          label: "Sender Comp",
          sortable: true,
        },
        {
          key: "target_comp_id",
          label: "Target Comp",
          sortable: true,
        },
        {
          key: "socket",
          label: "Socket",
          sortable: true,
        },
        {
          key: "status",
          label: "Status",
          sortable: true,
        },
        {
          key: "port",
          label: "Port",
          sortable: true,
        },
      ],
      nameState: null,
    };
  },
  computed: {
    isNew() {
      return !(this.trading_account && this.trading_account.id);
    },
    title() {
      return `${this.isNew ? "Create" : "Update"} Trading Account`;
    },
    rows() {
      return this.trading_accounts.length;
    },

    broker_settlement_accounts_options() {
      return this.broker_settlement_accounts.map((x) => ({
        text: [
          x.foreign_broker["name"],
          x.local_broker["name"],
          x.bank_name,
          x.account,
        ].join("-"),
        value: x.id,
      }));
    },
  },
  watch: {},
  methods: {
    setCorrectBrokersIDs() {
      const new_settlement_account = this.trading_account
        .broker_settlement_account;
      const correctBrokers = {};
      correctBrokers["correct_local_broker_id"] = this.foreign_brokers.find(
        (x) => x.user_id === new_settlement_account.foreign_broker_id
      );
      correctBrokers["correct_foreign_broker_id"] = this.foreign_brokers.find(
        (x) => x.user_id === new_settlement_account.local_broker_id
      );
      return correctBrokers;
    },

    async accountHandler(b) {
      const result = await this.$swal({
        title: "",
        icon: "info",
        html: `Would you like to Edit Or Delete the following Trading account for : <b>(${b.settlement_account.account})</b> `,
        showCloseButton: true,
        showCancelButton: true,
        cancelButtonColor: "#DD6B55",
        confirmButtonText: "Edit",
        confirmButtonAriaLabel: "delete",
        cancelButtonText: "Delete",
        cancelButtonAriaLabel: "cancel",
      });
      if (result.value) {
        this.trading_account = b;
        this.trading_account.broker_settlement_account =
          b.broker_settlement_account_id;
      }
      if (result.dismiss === "cancel") {
        await this.destroy(b.id);
      }
    },

    async handleSubmit() {
      // Exit when the form isn't valid
      let account;
      //Determine if a new client is being created or we are updating an existing client

      const {
        correct_foreign_broker_id,
        correct_local_broker_id,
      } = this.setCorrectBrokersIDs();

      if (this.isNew) {
        //Exclude ID
        account = {
          name: this.trading_account.name,
          // local_broker_id: 1,
          local_broker_id: correct_local_broker_id,
          foreign_broker_id: correct_foreign_broker_id,
          umir: this.trading_account.umir,
          target_comp_id: this.trading_account.target_comp_id,
          sender_comp_id: this.trading_account.sender_comp_id,
          socket: this.trading_account.socket,
          port: this.trading_account.port,
          trading_account_number: this.trading_account.trading_account_number,
          settlement_account_number: this.trading_account
            .broker_settlement_account,
        };
      } else {
        //Include ID
        account = {
          id: this.trading_account.id,
          local_broker_id: correct_local_broker_id,
          foreign_broker_id: correct_foreign_broker_id,
          umir: this.trading_account.umir,
          target_comp_id: this.trading_account.target_comp_id,
          sender_comp_id: this.trading_account.sender_comp_id,
          socket: this.trading_account.socket,
          port: this.trading_account.port,
          trading_account_number: this.trading_account.trading_account_number,
          settlement_account_number: this.trading_account
            .broker_settlement_account,
        };
      }

      ////console.log("account", account);

      this.$swal.fire({
        title: `${this.isNew ? "Creating" : "Updating"} Trading Account`,
        html: "One moment while we setup the Account",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        },
      });

      try {
        await axios.post("store-trading-account", account);
        await this.getTradingAccountsList();
        this.trading_account = null;
        this.$swal.close();
        // setTimeout(location.reload.bind(location), 2000);
      } catch (error) {
        this.checkDuplicateError(error);
      }

      /*  try {
        await axios.post("/store-settlement-broker", this.trading_account);
        //.then(response => {
        this.getTradingAccountsList();
        setTimeout(location.reload.bind(location), 1000);
        this.create = false;
      } catch (error) {} */
    },

    async getTradingAccountsList() {
      ({ data: this.trading_accounts } = await axios.get("trader-list"));
    },

    async getSettlementAccounts() {
      ({ data: this.broker_settlement_accounts } = await axios.get(
        "settlement-list"
      ));
      ////console.log("getSettlementAccounts", this.broker_settlement_accounts);
    },

    async destroy(id) {
      this.$swal.fire({
        title: `Deleting Trading Account`,
        html: "One moment while we delete the Account",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        },
      });
      try {
        await axios.delete(`trading-account-delete/${id}`); //.then(response => {
        await this.getTradingAccountsList();
        this.trading_account = null;
        this.$swal.close();
      } catch (error) {
        this.checkDeleteError(error);
      }
    },

    async getLocalBrokers() {
      const { data } = await axios.get("local-brokers");
      // ////console.log(local_brokers);
      this.local_brokers = data.map((broker) => ({
        user_id: broker.user.id,
        text: broker.user.name,
        value: broker.id,
      }));
      ////console.log("local brokers", this.local_brokers);
    },

    async getForeignBrokers() {
      const { data } = await axios.get("foreign-brokers"); //.then(response => {
      this.foreign_brokers = data.map((broker) => ({
        user_id: broker.user.id,
        text: broker.user.name,
        value: broker.id,
      }));
      ////console.log("foreign brokers", this.foreign_brokers);
    },
  },

  async mounted() {
    ////console.log("accounts", this.accounts);
    await Promise.all([
      this.getLocalBrokers(),
      this.getForeignBrokers(),
      this.getTradingAccountsList(),
      this.getSettlementAccounts(),
    ]);
  },
};
</script>
