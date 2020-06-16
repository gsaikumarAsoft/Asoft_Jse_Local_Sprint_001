<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid">
      <div class="content">
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
          @row-clicked="AccountHandler"
        >
          <template slot="index" slot-scope="row">{{ row }}</template>
        </b-table>
        <b-pagination
          v-model="currentPage"
          :total-rows="rows"
          :per-page="perPage"
          aria-controls="foreign-brokers"
        ></b-pagination>
        <b-button v-b-modal.modal-1 @click="create = true">Create Account</b-button>
        <b-modal id="modal-1" :title="modalTitle" @ok="handleOk" @hidden="resetModal">
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
                v-model="settlement_account.broker_settlement_account"
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
                v-model="settlement_account.trading_account_number"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group label="UMIR" label-for="umir-input" invalid-feedback=" UMIR is required">
              <b-form-input
                id="umir-input"
                v-model="settlement_account.umir"
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
                v-model="settlement_account.target_comp_id"
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
                v-model="settlement_account.sender_comp_id"
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
                v-model="settlement_account.socket"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group label="Port" label-for="port-input" invalid-feedback="Port is required">
              <b-form-input
                id="port-input"
                v-model="settlement_account.port"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
          </form>
        </b-modal>
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import axios from "axios";
import headNav from "./partials/Nav";
export default {
  props: ["accounts"],
  components: {
    "head-nav": headNav
  },
  data() {
    return {
      create: false,
      trading_accounts: [],
      broker_settlement_accounts: [],
      settlement_account: {},
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
    AccountHandler(b) {
      this.settlement_account = b;
      this.settlement_account.broker_settlement_account =
        b.broker_settlement_account_id;
      this.$swal({
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
      }).then(result => {
        if (result.value) {
          this.$bvModal.show("modal-1");
        }
        if (result.dismiss === "cancel") {
          this.destroy(b.id);
          this.$swal(
            "Deleted!",
            "Trading Account Has Been Removed.",
            "success"
          );
        }
      });
    },
    checkFormValidity() {
      const valid = this.$refs.form.checkValidity();
      this.nameState = valid;
      return valid;
    },
    resetModal() {
      this.create = false;
      this.settlement_account = {};
    },
    handleOk(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault();
      // Trigger submit handler
      this.handleSubmit();
    },
    handleSubmit() {
      // Exit when the form isn't valid
      if (!this.checkFormValidity()) {
      } else {
        this.$bvModal.hide("modal-1"); //Close the modal if it is open

        //Determine if a new client is being created or we are updating an existing client
        if (this.create) {
          //Exclude ID
          this.storeTradingAccount({
            name: this.settlement_account.name,
            // local_broker_id: 1,
            local_broker_id: this.settlement_account.local_broker_id,
            foreign_broker_id: this.settlement_account.foreign_broker_id,
            umir: this.settlement_account.umir,
            target_comp_id: this.settlement_account.target_comp_id,
            sender_comp_id: this.settlement_account.sender_comp_id,
            socket: this.settlement_account.socket,
            port: this.settlement_account.port,
            trading_account_number: this.settlement_account
              .trading_account_number,
            settlement_account_number: this.settlement_account
              .broker_settlement_account
          });
          this.$swal(`Creating Account`);
        } else {
          //Include ID
          this.storeTradingAccount({
            id: this.settlement_account.id,
            local_broker_id: this.settlement_account.local_broker_id,
            foreign_broker_id: this.settlement_account.foreign_broker_id,
            umir: this.settlement_account.umir,
            target_comp_id: this.settlement_account.target_comp_id,
            sender_comp_id: this.settlement_account.sender_comp_id,
            socket: this.settlement_account.socket,
            port: this.settlement_account.port,
            trading_account_number: this.settlement_account
              .trading_account_number,
            settlement_account_number: this.settlement_account
              .broker_settlement_account
          });
          this.$swal(`Updating Account`);
        }
        this.resetModal();
        this.$nextTick(() => {
          this.$bvModal.hide("modal-1");
        });
      }
    },
    storeTradingAccount(account) {
      this.$bvModal.hide("modal-1");
      axios
        .post("store-trading-account", account)
        .then(response => {
          this.getTradingAccountsList();
          this.$swal(`Account setup complete`);
          // setTimeout(location.reload.bind(location), 2000);
        })
        .catch(error => {});
    },
    setLocalBroker() {},
    getTradingAccountsList() {
      axios.get("trader-list").then(response => {
        let data = response.data;
        this.trading_accounts = [];
        this.trading_accounts = data;
      });
    },
    getSettlementAccounts() {
      axios.get("settlement-list").then(response => {
        let data = response.data;
        let i;
        for (i = 0; i < data.length; i++) {
          this.broker_settlement_accounts.push({
            text:
              data[i].foreign_broker["name"] +
              "-" +
              data[i].local_broker["name"] +
              "-" +
              data[i].bank_name +
              "-" +
              data[i].account,
            value: data[i].id
          });
        }
      });
    },
    storeBrokerTradingAccount() {
      axios
        .post("/store-settlement-broker", this.settlement_account)
        .then(response => {
          this.getTradingAccountsList();
          setTimeout(location.reload.bind(location), 1000);
          this.create = false;
        })
        .catch(error => {});
    },
    add() {
      this.create = true;
    },
    destroy(id) {
      axios.delete(`trading-account-delete/${id}`).then(response => {
        this.getTradingAccountsList();
      });
    }
  },
  mounted() {
    axios.get("local-brokers").then(response => {
      let local_brokers = response.data;
      // console.log(local_brokers);
      let i;
      for (i = 0; i < local_brokers.length; i++) {
        this.local_brokers.push({
          text: local_brokers[i].user.name,
          value: local_brokers[i].id
        });
      }
    });
    axios.get("foreign-brokers").then(response => {
      let foreign_brokers = response.data;
      let i;
      for (i = 0; i < foreign_brokers.length; i++) {
        let data = foreign_brokers[i].user;
        this.foreign_brokers.push({
          text: data.name,
          value: foreign_brokers[i].id
        });
      }
    });

    this.getTradingAccountsList();
    this.getSettlementAccounts();
  }
};
</script>
