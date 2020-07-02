<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid">
      <div class="content">
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
        <b-button v-b-modal.modal-1 @click="create = true">Create Client</b-button>
        <b-modal id="modal-1" :title="modalTitle" @ok="handleOk" @hidden="resetModal">
          <p class="my-4">Please update the fields below as required!</p>
          <form ref="form" @submit.stop.prevent="handleSubmit">
            <b-form-group label="JCSD" label-for="JCSD-input" invalid-feedback="JCSD is required">
              <b-form-input
                id="JCSD-input"
                v-model="broker.jcsd"
                type="number"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group label="Name" label-for="name-input" invalid-feedback="Name is required">
              <b-form-input id="name-input" v-model="broker.name" :state="nameState" required></b-form-input>
            </b-form-group>
            <b-form-group
              label="Email"
              label-for="email-input"
              invalid-feedback="Email is required"
            >
              <b-form-input id="name-input" v-model="broker.email" :state="nameState" required></b-form-input>
            </b-form-group>
            <b-form-group
              label="Account Balance"
              label-for="JCSD-input"
              invalid-feedback="Account Balance is required"
            >
              <b-form-input
                id="Account Balance-input"
                v-model="broker.account_balance"
                :state="nameState"
                type="number"
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
                v-model="broker.open_orders"
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
import axios from "axios";
import headNav from "./../partials/Nav.vue";
export default {
  props: ["broker_traders"],
  components: {
    headNav
  },
  data() {
    return {
      create: false,
      local_broker_clients: [],
      local_brokers: [],
      trading_accounts: [],
      broker: {},
      perPage: 5,
      currentPage: 1,
      fields: [
        {
          key: "name",
          sortable: true
        },
        {
          key: "email",
          sortable: true
        },
        {
          key: "status",
          sortable: true
        },
        {
          key: "account_balance",
          label: "Balance",
          sortable: true,
          formatter: (value, key, item) => {
            var formatter = new Intl.NumberFormat("en-US", {
              style: "currency",
              currency: "USD"
            });

            var cal = item.account_balance;
            return formatter.format(cal);
          }
        },
        {
          key: "open_orders",
          label: "Open Orders",
          sortable: true
        },
        // {
        //   key: "orders_limit",
        //   label: "Available",
        //   sortable: true
        // },
        {
          // A virtual column with custom formatter
          key: "available",
          label: "Available",
          formatter: (value, key, item) => {
            var formatter = new Intl.NumberFormat("en-US", {
              style: "currency",
              currency: "USD"
            });

            var cal = item.orders_limit - item.open_orders;
            return formatter.format(cal);
          }
        },
        {
          key: "jcsd",
          label: "JCSD",
          sortable: true
        }
      ],
      modalTitle: "Client Update",
      nameState: null
    };
  },
  computed: {
    rows() {
      return this.local_broker_clients.length;
    }
  },
  watch: {
    create: function(data) {
      if (data) {
        this.modalTitle = "Create Client Account";
      } else {
        this.modalTitle = "Client Account Update";
      }
    }
  },
  methods: {
    checkFormValidity() {
      const valid = this.$refs.form.checkValidity();
      this.nameState = valid;
      return valid;
    },
    resetModal() {
      this.create = false;
      this.broker = {};
    },
    handleOk(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault();
      // Trigger submit handler
      this.handleSubmit();
    },
    async handleSubmit() {
      // Exit when the form isn't valid
      if (!this.checkFormValidity()) {
      } else {
        this.$bvModal.hide("modal-1"); //Close the modal if it is open
        //Determine if a new client is being created or we are updating an existing client
        if (this.create) {
          //Exclude ID
          await this.storeBrokerClient({
            name: this.broker.name,
            local_broker_id: parseInt(this.$userId),
            open_orders: this.broker.open_orders,
            account_balance: this.broker.account_balance,
            email: this.broker.email,
            jcsd: this.broker.jcsd,
            status: "Unverified"
          });
          // this.getClients();
        } else {
          //Include ID
          await this.storeBrokerClient({
            id: this.broker.id,
            local_broker_id: parseInt(this.$userId),
            open_orders: this.broker.open_orders,
            account_balance: this.broker.account_balance,
            name: this.broker.name,
            email: this.broker.email,
            jcsd: this.broker.jcsd,
            status: "Unverified"
          });
          // this.getClients();
          this.$swal(`Account Updated`);
        }

        this.resetModal();
        this.$nextTick(() => {
          this.$bvModal.hide("modal-1");
        });
      }
    },
    async getClients(broker) {
      try {
        const { data } = await axios.get("trading-accounts", broker);
        console.log("get clients", data);
        this.local_broker_clients = [];
        var broker = data[0];
        this.local_broker_clients = broker.clients;
      } catch (error) {}
    },
    async brokerClientHandler(b) {
      this.broker = b;
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
        cancelButtonAriaLabel: "cancel"
      });
      if (result.value) {
        this.$bvModal.show("modal-1");
      }
      if (result.dismiss === "cancel") {
        await this.destroy(b.id);
        await this.$swal("Deleted!", "Client Has Been Removed.", "success");
      }
    },
    async storeBrokerClient(broker) {
      console.log(this.broker);
      const result = await this.$swal.fire({
        title: "Creating Client Account",
        html: "One moment while we setup  a new Client Account",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        }
      });
      console.log("Storing Broker Client");
      try {
        await axios.post("store-broker-client", broker);
        await this.$swal(`Account created`);
        setTimeout(location.reload.bind(location), 1000);
      } catch (error) {
        if (error.response.data.message.includes("Duplicate entry")) {
          await this.$swal(
            `An Account with this email address already exists. Please try using a different email`
          );
        }
      }
    },
    add() {
      this.create = true;
    },
    async destroy(id) {
      await axios.delete(`client-broker-delete/${id}`);
      await this.getClients();
    }
  },
  async mounted() {
    var client_data = JSON.parse(this.broker_traders);
    var clients = client_data[0].clients;
    this.local_broker_clients = clients;
    // this.getClients();
    const { data: local_brokers } = await axios.get("local-brokers");
    console.log("local brokers", local_brokers);
    for (let i = 0; i < local_brokers.length; i++) {
      this.local_brokers.push({
        text: local_brokers[i].name,
        value: local_brokers[i].id
      });
    }
  }
};
</script>
