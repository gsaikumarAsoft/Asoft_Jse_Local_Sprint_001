<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid">
      <div class="content">
        <div v-if="!settlement_account">
          <b-table
            responsive
            striped
            hover
            show-empty
            :empty-text="'No Settlement Accounts have been Created. Create a Settlement Account below.'"
            id="foreign-brokers"
            :items="settlement_accounts"
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
          <b-button @click="settlement_account={}">Create Settlement Account</b-button>
          <b-button @click="importAccounts">Import Accounts</b-button>
          <b-button @click="exportBalances">Export Balances</b-button>
        </div>
        <b-card v-else id="modal-1" :title="formTitle">
          <p class="my-4">Please update the fields below as required!</p>
          <form ref="form" @submit.stop.prevent="handleSubmit">
            <b-form-group
              sm
              label="Local Broker"
              label-for="name-input"
              invalid-feedback="Name is required"
            >
              <b-form-select
                required
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
                required
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
                required
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

            <b-button type="submit" variant="primary">Submit</b-button>
            <b-button type="button" variant="warning" @click="settlement_account = null">Cancel</b-button>
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
export default {
  mixins: [currenciesMixin],
  components: {
    "head-nav": headNav
  },
  data() {
    return {
      settlement_accounts: [],
      settlement_account: null,
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
          label: "Status",
          sortable: true
        }
      ],
      nameState: null
    };
  },
  computed: {
    formTitle() {
      return (
        (!!(this.settlement_account && this.settlement_account.id)
          ? "Update"
          : "Create") + " Settlement Account"
      );
    },
    rows() {
      return this.settlement_accounts.length;
    }
  },
  methods: {
    importAccounts() {
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
      for (var i = 0; i < this.settlement_accounts.length; i++) {
        tableData.push([
          this.settlement_accounts[i].local_broker["name"],
          this.settlement_accounts[i].foreign_broker["name"],
          this.settlement_accounts[i].bank_name,
          this.settlement_accounts[i].account,
          this.settlement_accounts[i].email,
          this.settlement_accounts[i].account_balance,
          this.settlement_accounts[i].amount_allocated
        ]);
      }

      // console.log(this.settlement_accounts[i])
      // tableData.push(this.settlement_accounts[i]);

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

    async handleSubmit() {
      // Exit when the form isn't valid
      //Determine if a new user is being created or we are updating an existing user
      let account;

      let isNew = !this.settlement_account.id;

      if (isNew) {
        //create with id = null
        account = {
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
        };
      } else {
        //update
        account = this.settlement_account;
      }

      console.log("account", account);

      this.$swal.fire({
        title: `Settlement Account`,
        html: `${isNew ? "Creating" : "Updating"} Settlement Account`,
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        }
      });

      // console.log(account);
      try {
        await axios.post("../store-settlement-broker", account);
        await this.getSettlementList();
        this.$swal(
          `Settlement Account ${isNew ? "created" : "updated"} for ${
            this.settlement_account.email
          }`
        );
        this.settlement_account = null;
        this.nameState = null;
      } catch (error) {
        console.error("settlement save failed", error);
        this.$swal("Oops...", "Something went wrong!", "error");
      }
    },

    async settlmentAccountHandler(b) {
      // console.log(b);
      console.log(b);

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
        cancelButtonAriaLabel: "cancel"
      });
      if (result.value) {
        this.settlement_account = b;
      }
      if (result.dismiss === "cancel") {
        await this.destroy(b.id);
      }
    },

    setLocalBroker() {
      // console.log(this);
    },

    async getSettlementList() {
      ({ data: this.settlement_accounts } = await axios.get(
        "../settlement-list"
      )); //.then(response => {
      console.log("settlement_accounts)", this.settlement_accounts);
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
        await axios.delete(`../settlement-account-delete/${id}`); //.then(response => {
        await this.getSettlementList();
        this.$swal(
          "Deleted!",
          "Settlement Account Has Been Removed.",
          "success"
        );
      } catch (error) {
        console.error("destroy", error);
        this.$swal("Oops...", "Something went wrong!", "error");
      }
    },

    async getlocalBrokers() {
      const { data: local_brokers } = await axios.get("../local-brokers");
      this.local_brokers = local_brokers.map(({ user }) => ({
        text: user.name,
        value: user.id
      }));
    },
    async getForeiognBrokers() {
      const { data: foreign_brokers } = await axios.get("../foreign-brokers");
      this.foreign_brokers = foreign_brokers.map(({ user }) => ({
        text: user.name,
        value: user.id
      }));
    }
  },
  async mounted() {
    await Promise.all([
      this.getlocalBrokers(),
      this.getForeiognBrokers(),
      this.getSettlementList()
    ]);
  }
};
</script>
