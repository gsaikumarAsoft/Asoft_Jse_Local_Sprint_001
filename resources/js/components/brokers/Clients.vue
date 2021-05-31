<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid" style="margin-top: 20px;">
      <div class="content">
        <b-card title="Client Accounts" v-if="!broker_client">
          <b-table
            striped
            hover
            responsive
            show-empty
            :empty-text="'No Clients have been Created. Create a Client below.'"
            id="local-brokers"
            :items="local_broker_clients"
            :fields="fields"
            :per-page="perPage"
            :current-page="currentPage"
            @row-clicked="brokerClientHandler"
          >
            <template slot="index" slot-scope="row">{{ row }}</template>
          </b-table>
          <b-pagination
            v-model="currentPage"
            :total-rows="rows"
            :per-page="perPage"
            aria-controls="local-brokers"
          ></b-pagination>
          <b-button v-b-modal.modal-1 @click="broker_client={}">Create Client</b-button>
          <b-button v-if="local_broker_clients.length > 0" @click="exportClients">Export Clients</b-button>
        </b-card>
        <b-card :title="title" v-else>
          <p class="my-4">Please update the fields below as required!</p>
          <form ref="form" @submit.stop.prevent="handleSubmit">
            <b-form-group label="JCSD" label-for="JCSD-input" invalid-feedback="JCSD is required">
              <b-form-input
                id="JCSD-input"
                v-model="broker_client.jcsd"
                type="number"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group label="Name" label-for="name-input" invalid-feedback="Name is required">
              <b-form-input
                id="name-input"
                v-model="broker_client.name"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="Email"
              label-for="email-input"
              invalid-feedback="Email is required"
            >
              <b-form-input
                id="name-input"
                v-model="broker_client.email"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="Account Balance"
              label-for="JCSD-input"
              invalid-feedback="Account Balance is required"
            >
              <b-form-input
                id="Account Balance-input"
                v-model="broker_client.account_balance"
                :state="nameState"
                type="text"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="Open Orders"
              label-for="open-orders-input"
              invalid-feedback="Open Orders is required"
            >
              <b-form-input
                id="Open Orders-input"
                v-model="broker_client.open_orders"
                :state="nameState"
                type="number"
                step="any"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="Unsettled Orders"
              label-for="open-orders-input"
              invalid-feedback="OUnsettledOrders is required"
            >
              <b-form-input
                id="Unsettled Orders-input"
                v-model="broker_client.filled_orders"
                :state="nameState"
                type="number"
                step="any"
                required
              ></b-form-input>
            </b-form-group>
            <b-button type="submit" variant="primary">Submit</b-button>
            <b-button variant="danger" @click="broker_client=null">Cancel</b-button>
          </form>
        </b-card>
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import jsPDF from "jspdf";
import axios from "axios";
import headNav from "./../partials/Nav.vue";
import checkErrorMixin from "../../mixins/CheckError.js";
export default {
  props: ["broker_traders"],
  components: {
    headNav,
  },
  mixins: [checkErrorMixin],
  data() {
    return {
      local_broker_clients: [],
      local_brokers: [],
      trading_accounts: [],
      broker_client: null,
      perPage: 5,
      currentPage: 1,
      fields: [
        {
          key: "name",
          sortable: true,
        },
        {
          key: "email",
          sortable: true,
        },
        {
          key: "status",
          sortable: true,
        },
        {
          key: "account_balance",
          label: "Balance",
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
          key: "open_orders",
          label: "Open Orders",
          sortable: true,
          formatter: (value, key, item) => {
            var formatter = new Intl.NumberFormat("en-US", {
              style: "currency",
              currency: "USD",
            });
            var cal = item.open_orders;
            return formatter.format(cal);
          },
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
            return formatter.format(item.filled_orders);
            // return formatter.format(cal);
          },
        },
        {
          // A virtual column with custom formatter
          key: "available",
          label: "Available",
          formatter: (value, key, item) => {
            var formatter = new Intl.NumberFormat("en-US", {
              style: "currency",
              currency: "USD",
            });

            var spent = Number(item.filled_orders) + Number(item.open_orders);

            var cal = item.account_balance - spent;
            return formatter.format(cal);
          },
        },
        {
          key: "jcsd",
          label: "JCSD",
          sortable: true,
        },
      ],
      modalTitle: "Client Update",
      nameState: null,
    };
  },
  computed: {
    isNew() {
      return !(this.broker_client && this.broker_client.id);
    },
    title() {
      return `${this.isNew ? "Create" : "Update"} Client Account`;
    },
    rows() {
      return this.local_broker_clients.length;
    },
  },
  watch: {},
  methods: {
    exportClients() {
      const tableData = [];
      for (var i = 0; i < this.local_broker_clients.length; i++) {
        tableData.push([
          this.local_broker_clients[i].name,
          this.local_broker_clients[i].email,
          this.local_broker_clients[i].status,
          this.local_broker_clients[i].account_balance,
          this.local_broker_clients[i].open_orders,
          this.local_broker_clients[i].filled_orders,
          this.local_broker_clients[i].jcsd,
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
            "Name",
            "Email",
            "Status",
            "Account Balance",
            "Open Orders",
            "Unsettled Trades",
            "JCSD",
          ],
        ],
        body: tableData,
      });
      doc.save("Broker Admin Clients.pdf");
    },
    async handleSubmit() {
      // Exit when the form isn't valid
      //Determine if a new client is being created or we are updating an existing client
      let account;
      if (this.isNew) {
        //Exclude ID
        account = {
          name: this.broker_client.name,
          local_broker_id: parseInt(this.$userId),
          open_orders: this.broker_client.open_orders,
          filled_orders: this.broker_client.filled_orders,
          account_balance: this.broker_client.account_balance,
          email: this.broker_client.email,
          jcsd: this.broker_client.jcsd,
          status: "Unverified",
        };
        // this.getClients();
      } else {
        //Include ID
        account = {
          id: this.broker_client.id,
          local_broker_id: parseInt(this.$userId),
          open_orders: this.broker_client.open_orders,
          filled_orders: this.broker_client.filled_orders,
          account_balance: this.broker_client.account_balance,
          name: this.broker_client.name,
          email: this.broker_client.email,
          jcsd: this.broker_client.jcsd,
          status: "Unverified",
        };
      }
      await this.storeBrokerClient(account);
    },
    async getClients(broker) {
      const { data } = await axios.get("trading-accounts", broker);
      var broker = data[0];
      this.local_broker_clients = broker.clients;
      window.location.reload();
    },

    async brokerClientHandler(b) {
      const result = await this.$swal({
        title: "",
        icon: "info",
        html: `Would you like to Edit Or Delete the following Client <b>(${b.name})</b> `,
        showCloseButton: true,
        showCancelButton: true,
        // focusConfirm: true,
        cancelButtonColor: "#DD6B55",
        confirmButtonText: "Edit",
        confirmButtonAriaLabel: "delete",
        cancelButtonText: "Delete",
        cancelButtonAriaLabel: "cancel",
      });
      if (result.value) {
        this.broker_client = b;
      }
      if (result.dismiss === "cancel") {
        await this.destroy(b.id);
        await this.$swal("Deleted!", "Client Has Been Removed.", "success");
      }
    },

    async storeBrokerClient(client) {
      //console.log(this.broker_client);
      const result = this.$swal.fire({
        title: "Creating Client Account",
        html: "One moment while we setup  a new Client Account",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        },
      });
      //console.log("Storing Broker Client");
      try {
        await axios.post("store-broker-client", client);
        await this.getClients();
        this.$swal.close();
        this.broker_client = null;
        //setTimeout(location.reload.bind(location), 1000);
      } catch (error) {
        this.checkDuplicateError(error);
      }
    },

    async destroy(id) {
      this.$swal.fire({
        title: `Deleting Client Account`,
        html: "One moment while we delete the Account",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        },
      });
      try {
        await axios.delete(`client-broker-delete/${id}`);
        await this.getClients();
        this.$swal.close();
        this.broker_client = null;
      } catch (error) {
        this.checkDeleteError(error);
      }
    },
  },
  async mounted() {
    var client_data = JSON.parse(this.broker_traders);
    var clients = client_data[0].clients;
    this.local_broker_clients = clients;
    // this.getClients();
    const { data: local_brokers } = await axios.get("local-brokers");
    //console.log("local brokers", local_brokers);
    for (let i = 0; i < local_brokers.length; i++) {
      this.local_brokers.push({
        text: local_brokers[i].name,
        value: local_brokers[i].id,
      });
    }
  },
};
</script>
