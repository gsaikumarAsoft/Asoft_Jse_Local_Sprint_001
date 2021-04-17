<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid" style="margin-top:100px">
      <div class="content">
        <b-card title="Client Accounts">
          <b-table
            v-if="permissions.indexOf('read-broker-client') !== -1"
            striped
            hover
            show-empty
            :empty-text="'No Clients have been Created. Create a Client below.'"
            id="local-brokers"
            :items="clients"
            :fields="fields"
            :per-page="perPage"
            :current-page="currentPage"
            @row-clicked="brokerClientHandler"
          >
            <template slot="index" slot-scope="row">{{ row }}</template>
          </b-table>
          <p
            v-if="permissions.indexOf('read-broker-client') == -1"
            class="lead"
          >You currently do not have permisions to view Broker Client accounts within the system. Please speak with your Broker Admin to have the Permissions activated on your account</p>
          <b-pagination
            v-if="permissions.indexOf('read-broker-client') !== -1"
            v-model="currentPage"
            :total-rows="rows"
            :per-page="perPage"
            aria-controls="local-brokers"
          ></b-pagination>
          <b-button
            v-if="permissions.indexOf('create-broker-client') !== -1"
            v-b-modal.modal-1
            @click="create = true"
          >Create Client</b-button>
          <b-button v-if="clients.length > 0" @click="exportClients">Export Clients</b-button>
          <b-modal id="modal-1" :title="modalTitle" @ok="handleOk" @hidden="resetModal">
            <p class="my-4">Please update the fields below as required!</p>
            <form ref="form" @submit.stop.prevent="handleSubmit">
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
                label="JCSD Number"
                label-for="jcsd-input"
                invalid-feedback="Name is required"
              >
                <b-form-input
                  id="jcsd-input"
                  v-model="broker.jcsd"
                  type="number"
                  :state="nameState"
                  required
                ></b-form-input>
              </b-form-group>
              <b-form-group
                sm
                label="Account Balance"
                label-for="name-input"
                invalid-feedback="Name is required"
              >
                <b-form-input
                  id="account-balance-input"
                  v-model="broker.account_balance"
                  type="number"
                  :state="nameState"
                  required
                ></b-form-input>
              </b-form-group>
              <b-form-group
                v-if="permissions.indexOf('approve-broker-client') !== -1"
                label="Client Status"
                label-for="status-input"
                invalid-feedback="Client status is required"
              >
                <b-form-select v-model="broker.operator_status" :options="client_status"></b-form-select>
              </b-form-group>
              <!-- <b-form-group label="Access Permissions:">
                <b-form-checkbox-group
                  id="checkbox-group-1"
                  v-model="selected_permissions"
                  :options="options"
                  name="flavour-1"
                ></b-form-checkbox-group>
              </b-form-group>-->
            </form>
          </b-modal>
        </b-card>
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import jsPDF from "jspdf";
import permissionMixin from "./../../../js/mixins/Permissions.js";
import axios from "axios";
import headNav from "./../partials/Nav.vue";
export default {
  mixins: [permissionMixin],
  components: {
    headNav,
  },
  data() {
    return {
      broker: { status: "Un-Verified" },
      permissions: [],
      selected_permissions: [],
      create: false,
      clients: [],
      local_brokers: [],
      perPage: 5,
      currentPage: 1,
      client_status: [
        { value: null, text: "Please select a status" },
        { value: "Verified", text: "Verified" },
        { value: "Un-verified", text: "Rejected" },
        { value: "Pending", text: "Pending" },
      ],
      options: [
        { text: "Create", value: "Create" },
        { text: "Read", value: "Read" },
        { text: "Update", value: "Update" },
        { text: "Delete", value: "Delete" },
        { text: "Approve", value: "Approve" },
      ],
      status: [
        { key: 1, text: "Approved", value: "Approved" },
        { key: 2, text: "Denied", value: "Denied" },
        { key: 3, text: "Un-Verified", value: "Un-Verified" },
      ],
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
            var nf = Intl.NumberFormat();
            var cal = item.open_orders;
            return nf.format(cal);
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

            var nf = Intl.NumberFormat();
            var cal = Number(item.filled_orders);
            return nf.format(cal);
          },
        },
        {
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
    rows() {
      return this.clients.length;
    },
  },
  watch: {
    create: function (data) {
      if (data) {
        this.modalTitle = "Create Client";
      } else {
        this.modalTitle = "Client Update";
      }
    },
  },
  methods: {
    exportClients() {
      const tableData = [];
      for (var i = 0; i < this.clients.length; i++) {
        tableData.push([
          this.clients[i].name,
          this.clients[i].email,
          this.clients[i].status,
          this.clients[i].account_balance,
          this.clients[i].open_orders,
          this.clients[i].filled_orders,
          this.clients[i].jcsd,
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
      doc.save("Broker Operator Clients.pdf");
    },
    checkFormValidity() {
      const valid = this.$refs.form.checkValidity();
      //3.81.122.101/
      http: this.nameState = valid;
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
    async handleSubmit(b) {
      // Exit when the form isn't valid
      if (!this.checkFormValidity()) {
      } else {
        this.$bvModal.hide("modal-1"); //Close the modal if it is open

        //Determine if a new client is being created or we are updating an existing client
        if (this.create) {
          //Exclude ID
          this.storeClient({
            name: this.broker.name,
            local_broker_id: parseInt(this.$userId),
            email: this.broker.email,
            jcsd: this.broker.jcsd,
            status: this.broker.status,
            operator_status: this.broker.operator_status,
            permission: this.selected_permissions,
            account_balance: this.broker.account_balance,
          });
          this.$swal(`Account created for ${this.broker.email}`);
        } else {
          //Include ID
          this.storeClient({
            id: this.broker.id,
            local_broker_id: parseInt(this.$userId),
            name: this.broker.name,
            email: this.broker.email,
            jcsd: this.broker.jcsd,
            status: this.broker.status,
            operator_status: this.broker.operator_status,
            permission: this.selected_permissions,
            account_balance: this.broker.account_balance,
          });
          this.$swal(`Account Updated for ${this.broker.email}`);
        }

        this.resetModal();
        this.$nextTick(() => {
          this.$bvModal.hide("modal-1");
        });
      }
      // Push the name to submitted names
      // this.submittedNames.push(this.name);
      // Hide the modal manually
      // this.$nextTick(() => {
      //   this.$bvModal.hide("modal-1");
      // });
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
        cancelButtonAriaLabel: "cancel",
      }); //.then(result => {
      if (result.value) {
        if (this.permissions.indexOf("update-broker-client") !== -1) {
          this.$bvModal.show("modal-1");
        } else {
          this.$swal(
            "Oops!",
            "Please request update permissions from your Admin",
            "error"
          );
        }
      }
      if (result.dismiss === "cancel") {
        if (this.permissions.indexOf("delete-broker-client") !== -1) {
          await this.destroy(b.id);
          this.$swal("Deleted!", "Client Has Been Removed.", "success");
        } else {
          this.$swal(
            "Oops!",
            "Please request delete permissions from your Admin",
            "error"
          );
        }
      }
    },
    async getClients() {
      ({ data: this.clients } = await axios.get("operator-clients")); //.then(response => {
      let user_permissions = [];
      //console.log("getClients", this.clients);
      //Handle Permissions
      let i, j, k;
      for (i = 0; i < this.clients.length; i++) {
        this.clients[i].types = [];
        user_permissions = this.clients[i].permission;
        // //console.log(user_permissions);
        for (k = 0; k < user_permissions.length; k++) {
          var specific_permission = user_permissions[k].permission;
          this.clients[i].types.push(specific_permission);
        }
      }
    },
    async storeClient(broker) {
      //console.log("storeClient", broker);
      try {
        await axios.post("store-broker-trader", broker);
        await this.getClients();
      } catch (error) {}
    },
    add() {
      this.create = true;
    },
    async destroy(id) {
      await axios.delete(`client-broker-delete/${id}`);
      await this.getClients();
    },
  },
  async mounted() {
    // //Define Permission On Front And Back End
    let p = JSON.parse(this.$userPermissions);
    //Looop through and identify all permission to validate against actions
    for (let i = 0; i < p.length; i++) {
      this.permissions.push(p[i].name);
    }

    const { data: local_brokers } = await axios.get("trading-accounts"); //.then(response => {

    let i;
    for (i = 0; i < local_brokers.length; i++) {
      this.local_brokers.push({
        text: local_brokers[i].name,
        value: local_brokers[i].id,
      });
    }

    await this.getClients();
  },
};
</script>
