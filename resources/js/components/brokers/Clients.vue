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
        <b-button v-b-modal.modal-1 @click="addBrokerClient">Create Client</b-button>
        <b-modal id="modal-1" :title="modalTitle" @ok="handleOk" @hidden="resetModal">
          <p class="my-4">Please update the fields below as required!</p>
          <form ref="form" @submit.stop.prevent="handleSubmit">
            <b-form-group label="JCSD" label-for="JCSD-input" invalid-feedback="JCSD is required">
              <b-form-input
                id="JCSD-input"
                v-model="client.jcsd"
                type="number"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group label="Name" label-for="name-input" invalid-feedback="Name is required">
              <b-form-input id="name-input" v-model="client.name" :state="nameState" required></b-form-input>
            </b-form-group>
            <b-form-group
              label="Email"
              label-for="email-input"
              invalid-feedback="Email is required"
            >
              <b-form-input id="name-input" v-model="client.email" :state="nameState" required></b-form-input>
            </b-form-group>
            <b-form-group
              label="Account Balance"
              label-for="JCSD-input"
              invalid-feedback="Account Balance is required"
            >
              <b-form-input
                id="Account Balance-input"
                v-model="client.account_balance"
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
                v-model="client.open_orders"
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
      local_broker_clients: [],
      local_brokers: [],
      trading_accounts: [],
      client: {},
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
  watch: {},
  methods: {
    checkFormValidity() {
      const valid = this.$refs.form.checkValidity();
      this.nameState = valid;
      return valid;
    },

    async resetModal() {
      this.client = {};
      this.getClients();
    },

    async handleOk(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault();
      // Trigger submit handler
      // Exit when the form isn't valid
      if (this.checkFormValidity()) {
        this.$bvModal.hide("modal-1"); //Close the modal if it is open
        //Determine if a new client is being created or we are updating an existing client

        const isNew = !this.client.id;

        const result = await this.$swal.fire({
          title: `${isNew ? "Creating" : "Updating"} Client Account`,
          html: "One moment while we setup  a new Client Account",
          timerProgressBar: true,
          onBeforeOpen: () => {
            this.$swal.showLoading();
          }
        });

        const client = {
          id: this.client.id,
          local_broker_id: parseInt(this.$userId),
          open_orders: this.client.open_orders,
          account_balance: this.client.account_balance,
          name: this.client.name,
          email: this.client.email,
          jcsd: this.client.jcsd,
          status: "Unverified"
        };

        if (isNew) {
          client["id"] = null;
        }

        console.log("Storing Broker Client");

        this.$swal.fire({
          title: `${isNew ? "Creating" : "Updating"} Broker Client`,
          html: `One moment while we ${
            isNew ? "create" : "update"
          } the Account`,
          timerProgressBar: true,
          onBeforeOpen: () => {
            this.$swal.showLoading();
          }
        });

        try {
          await axios.post("store-broker-client", client);
          await this.$swal(`Account created`);
          this.$swal.close();
          //setTimeout(location.reload.bind(location), 1000);
        } catch (error) {
          console.error(error);
          if (
            error.response &&
            error.response.data &&
            error.response.data.message &&
            error.response.data.message.includes("Duplicate entry")
          ) {
            await this.$swal(
              `An Account with this email address already exists. Please try using a different email`
            );
          } else {
            this.$swal("Oops...", "Something went wrong!", "error");
          }
        }

        this.resetModal();
        this.$nextTick(() => {
          this.$bvModal.hide("modal-1");
        });
      }
    },

    async getClients() {
      try {
        const { data } = await axios.get("trading-accounts");
        console.log("get clients", data);
        var broker = data[0];
        this.local_broker_clients = broker.clients;
      } catch (error) {}
    },

    async brokerClientHandler(b) {
      this.modalTitle = "Client Account Update";
      this.client = b;
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

    addBrokerClient() {
      this.modalTitle = "Create Client Account";
      this.$bvModal.show("modal-1");
    },

    async destroy(id) {
      try {
        this.$swal.fire({
          title: `Broker Client`,
          html: "Deleting Client.......",
          timerProgressBar: true,
          onBeforeOpen: () => {
            this.$swal.showLoading();
          }
        });
        await axios.delete(`client-broker-delete/${id}`);
        await this.getClients();
        this.$swal("Deleted!", "Broker Client Has Been Removed.", "success");
      } catch (error) {
        console.error("destroy", error);
        this.$swal("Oops...", "Something went wrong!", "error");
      }
    }
  },

  async mounted() {
    console.log("this.broker_traders", this.broker_traders);
    const client_data = JSON.parse(this.broker_traders);
    this.local_broker_clients = client_data[0].clients;

    // this.getClients();
    const { data: local_brokers } = await axios.get("local-brokers");
    console.log("local brokers", local_brokers);
    this.local_brokers = local_brokers.map(broker => ({
      text: broker.name,
      value: broker.id
    }));
  }
};
</script>
