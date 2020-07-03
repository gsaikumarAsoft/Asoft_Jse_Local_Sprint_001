<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid">
      <div class="content">
        <div v-if="!trading_account">
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
          <b-button v-b-modal.modal-1 @click="trading_account = {}">Create Account</b-button>
        </div>

        <b-card v-else :title="modalTitle">
          <p class="my-4">Please update the fields below as required!</p>
          <form @submit.stop.prevent="handleSubmit">
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
                :options="broker_settlement_accounts"
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
            <b-button type="button" variant="warning" @click="trading_account = null">Cancel</b-button>
          </form>
        </b-card>
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import axios from "axios";
import headNav from "./partials/Nav.vue";
export default {
  props: ["accounts"],
  components: {
    "head-nav": headNav
  },
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
          sortable: true
        },
        {
          key: "foreign_broker",
          label: "Foreign Broker",
          sortable: true
        },
        {
          key: "settlement_account.bank_name",
          label: "Settlement Agent",
          sortable: true
        },
        {
          key: "settlement_account.account",
          label: "Account",
          sortable: true
        },
        {
          key: "trading_account_number",
          label: "Trader Account Number",
          sortable: true
        },
        {
          key: "sender_comp_id",
          label: "Sender Comp",
          sortable: true
        },
        {
          key: "target_comp_id",
          label: "Target Comp",
          sortable: true
        },
        {
          key: "socket",
          label: "Socket",
          sortable: true
        },
        {
          key: "status",
          label: "Status",
          sortable: true
        },
        {
          key: "port",
          label: "Port",
          sortable: true
        }
      ],
      modalTitle: "Trader Update",
      nameState: null
    };
  },
  computed: {
    rows() {
      return this.trading_accounts.length;
    }
  },
  methods: {
    async accountHandler(b) {
      console.log("selected trading_account", b);

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
        cancelButtonAriaLabel: "cancel"
      });

      console.log("result", result);

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
      const isNew = !this.trading_account.id;

      const trading_account = {
        id: this.trading_account.id,
        local_broker_id: this.trading_account.local_broker_id,
        foreign_broker_id: this.trading_account.foreign_broker_id,
        umir: this.trading_account.umir,
        target_comp_id: this.trading_account.target_comp_id,
        sender_comp_id: this.trading_account.sender_comp_id,
        socket: this.trading_account.socket,
        port: this.trading_account.port,
        trading_account_number: this.trading_account.trading_account_number,
        settlement_account_number: this.trading_account
          .broker_settlement_account
      };

      //Determine if a new client is being created or we are updating an existing client
      if (isNew) {
        trading_account["id"] = null;
        trading_account["name"] = this.trading_account.name;
      }

      console.log("trading_account", trading_account);
      this.$swal.fire({
        title: `Settlement Account`,
        html: `${isNew ? "Creating" : "Updating"} Trading Account`,
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        }
      });

      try {
        await axios.post("store-trading-account", trading_account);
        // await axios.post("/store-settlement-broker", this.trading_account);
        this.getTradingAccountsList();
        this.$swal(`Account setup complete`);
        // setTimeout(location.reload.bind(location), 2000);
        this.trading_account = null;
      } catch (error) {
        console.error("tarding save failed", error);
        this.$swal("Oops...", "Something went wrong!", "error");
      }
    },

    async getTradingAccountsList() {
      ({ data: this.trading_accounts } = await axios.get("trader-list")); //.then(response => {
      //});
    },

    async getSettlementAccounts() {
      const { data } = await axios.get("settlement-list"); //.then(response => {
      this.broker_settlement_accounts = data.map(account => ({
        text: [
          account.foreign_broker["name"],
          account.local_broker["name"],
          account.bank_name,
          account.account
        ].join("-"),
        value: account.id
      }));
    },

    async storeBrokerTradingAccount() {
      try {
        await axios.post("/store-settlement-broker", this.trading_account);
        //.then(response => {
        this.getTradingAccountsList();
        setTimeout(location.reload.bind(location), 1000);
      } catch (error) {}
    },

    async destroy(id) {
      try {
        this.$swal.fire({
          title: `Settlement Account`,
          html: "Deleting Settlement Account.......",
          timerProgressBar: true,
          onBeforeOpen: () => {
            this.$swal.showLoading();
          }
        });
        await axios.delete(`trading-account-delete/${id}`); //.then(response => {
        await this.getTradingAccountsList();
        this.$swal("Deleted!", "Trading Account Has Been Removed.", "success");
      } catch (error) {
        console.error("destroy", error);
        this.$swal("Oops...", "Something went wrong!", "error");
      }
    },

    async getLocalBrokers() {
      const { data: local_brokers } = await axios.get("local-brokers");
      this.local_brokers = local_brokers.map(broker => ({
        text: broker.user.name,
        value: broker.id
      }));
    },

    async getForeignBrokers() {
      const { data: foreign_brokers } = await axios.get("foreign-brokers");
      this.foreign_brokers = foreign_brokers.map(broker => ({
        text: broker.user.name,
        value: broker.id
      }));
    }
  },

  async mounted() {
    await Promise.all([
      this.getLocalBrokers(),
      this.getForeignBrokers(),
      this.getTradingAccountsList(),
      this.getSettlementAccounts()
    ]);

    console.log("trading", this.trading_accounts);
  }
};
</script>
