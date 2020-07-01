<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid">
      <div class="content">
        <b-table
          responsive
          striped
          hover
          show-empty
          :empty-text="'No Settlement Accounts have been Created. Create a Settlement Account below.'"
          id="foreign-brokers"
          :items="broker_settlement_account"
          :fields="fields"
          :per-page="perPage"
          :current-page="currentPage"
          @row-clicked="settlmentAccountHandler"
        >
          <template slot="index" slot-scope="row">{{ row }}</template>
        </b-table>
        <b-pagination
          v-model="currentPage"
          :total-rows="rows"
          :per-page="perPage"
          aria-controls="foreign-brokers"
        ></b-pagination>
        <b-button v-b-modal.modal-1 @click="create = true">Create Settlement Account</b-button>
        <b-button @click="importAccounts">Import Accounts</b-button>
        <b-button @click="exportBalances">Export Balances</b-button>
        <b-modal id="modal-1" :title="modalTitle" @ok="handleOk" @hidden="resetModal">
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
              label="Settlement Agent Email"
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
                type="number"
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
                type="number"
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
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import axios from "axios";
import headNav from "./partials/Nav.vue";
import currenciesMixin from "./../mixins/Currencies.js";
export default {
  mixins: [currenciesMixin],
  components: {
    "head-nav": headNav
  },
  data() {
    return {     
      create: false,
      broker_settlement_account: [],
      settlement_account: {},
      local_brokers: [],
      foreign_brokers: [],
      perPage: 5,
      currentPage: 1,
      fields: [
        {
          key: `local_broker.name`,
          label: "Local Broker",
          sortable: true
        },
        {
          key: "foreign_broker.name",
          label: "Foreign Broker",
          sortable: true
        },
        {
          key: "bank_name",
          label: "Settlement Agent",
          sortable: true
        },
        {
          key: "account",
          sortable: true
        },
        {
          key: "email",
          label: "Settlement Agent Email",
          sortable: true
        },
        {
          key: "account_balance",
          sortable: true
        },
        {
          key: "amount_allocated",
          sortable: true
        },
        {
          key: "currency",
          sortable: true
        },
        {
          key: "settlement_agent_status",
          label: 'Status',
          sortable: true
        }
      ],
      modalTitle: "Create Broker Settlement Account",
      nameState: null
    };
  },
  computed: {
    rows() {
      return this.broker_settlement_account.length;
    }
  },
  methods: {
    importAccounts(){
            this.$swal
        .fire({
          title: "Importing",
          html: "One moment while we import new settlement accounts.",
          timerProgressBar: true,
          onBeforeOpen: () => {
            this.$swal.showLoading();
          }
        })
        .then(result => {});
    },
    exportBalances() {
      const tableData = [];
      for (var i = 0; i < this.broker_settlement_account.length; i++) {
        tableData.push([
          this.broker_settlement_account[i].local_broker["name"],
          this.broker_settlement_account[i].foreign_broker["name"],
          this.broker_settlement_account[i].bank_name,
          this.broker_settlement_account[i].account,
          this.broker_settlement_account[i].email,
          this.broker_settlement_account[i].account_balance,
          this.broker_settlement_account[i].amount_allocated
        ]);
      }

      // console.log(this.broker_settlement_account[i])
      // tableData.push(this.broker_settlement_account[i]);

      var doc = new jsPDF();
      //   // It can parse html:
      //   doc.autoTable({ html: "#foreign-brokers" });

      // Or use javascript directly:
      doc.autoTable({
        head: [
          [
            "Local Broker",
            "Foreign Broker",
            "Bank",
            "Account",
            "Email",
            "Account Balance",
            "Amount Allocated"
          ]
        ],
        body: tableData
      });
      doc.save("BrokerSettlementReport.pdf");
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
        //Determine if a new user is being created or we are updating an existing user
        if (this.create) {
          //Exclude ID
          this.storeBrokerSettlementAccount({
            id: null,
            currency: this.settlement_account.currency,
            account: this.settlement_account.account,
            account_balance: this.settlement_account.account_balance,
            amount_allocated: this.settlement_account.amount_allocated,
            bank_name: this.settlement_account.bank_name,
            email: this.settlement_account.email,
            foreign_broker_id: this.settlement_account.foreign_broker_id,
            local_broker_id: this.settlement_account.local_broker_id,
            status: "Unverified",
            hash: this.settlement_account.hash
          });
          this.$swal(`Account created for ${this.settlement_account.email}`);
        } else {
          //Include ID
          console.log(this.settlement_account);
          this.storeBrokerSettlementAccount(this.settlement_account);
        }

        this.resetModal();
        this.$nextTick(() => {
          this.$bvModal.hide("modal-1");
        });
      }

      this.nameState = null;
    },
    settlmentAccountHandler(b) {
      // console.log(b);
      this.settlement_account = {};
      console.log(b);
      this.settlement_account = b;
      this.$swal({
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
        cancelButtonAriaLabel: "cancel"
      }).then(result => {
        if (result.value) {
          this.$bvModal.show("modal-1");
        }
        if (result.dismiss === "cancel") {
          this.destroy(b.id);
          this.$swal(
            "Deleted!",
            "Settlement Account Has Been Removed.",
            "success"
          );
        }
      });
    },
    setLocalBroker() {
      // console.log(this);
    },
    getSettlementList() {
      axios.get("../settlement-list").then(response => {
        let broker_settlement_accounts = response.data;
        this.broker_settlement_account = [];
        this.broker_settlement_account = broker_settlement_accounts;
        // console.log(this.broker_settlement_account);
      });
    },
    storeBrokerSettlementAccount(account) {
      // console.log(account);
      axios
        .post("../store-settlement-broker", account)
        .then(response => {
          this.getSettlementList();
          this.create = false;
        })
        .catch(error => {
          // console.log(error);
        });
    },
    add() {
      this.create = true;
    },
    destroy(id) {
      axios.delete(`../settlement-account-delete/${id}`).then(response => {
        this.getSettlementList();
      });
    }
  },
  mounted() {
    axios.get("../local-brokers").then(response => {
      let local_brokers = response.data;
      let i;
      for (i = 0; i < local_brokers.length; i++) {
        this.local_brokers.push({
          text: local_brokers[i].user.name,
          value: local_brokers[i].user.id
        });
      }
    });
    axios.get("../foreign-brokers").then(response => {
      let foreign_brokers = response.data;
      let i;
      for (i = 0; i < foreign_brokers.length; i++) {
        // console.log(foreign_brokers[i].user );
        let data = foreign_brokers[i].user;
        this.foreign_brokers.push({
          text: data.name,
          value: data.id
        });
      }
    });

    this.getSettlementList();
  }
};
</script>
