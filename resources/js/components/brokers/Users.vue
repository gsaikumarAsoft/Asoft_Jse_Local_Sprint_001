<template>
  <div>
    <head-nav></head-nav>
    <div class="container">
      <div class="content">
        <b-table
        responsive
          show-empty
          :empty-text="'No Users have been Created. Create a user below.'"
          striped
          hover
          id="local-brokers"
          :items="local_broker_users"
          :fields="fields"
          :per-page="perPage"
          :current-page="currentPage"
          @row-clicked="brokerUserHandler"
        >
          <template slot="index" slot-scope="row">{{ row }}</template>
        </b-table>
        <b-pagination
          v-model="currentPage"
          :total-rows="rows"
          :per-page="perPage"
          aria-controls="local-brokers"
        ></b-pagination>
        <b-button v-b-modal.modal-1 @click="create = true">Create User</b-button>
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
              sm
              label="Trading Account"
              label-for="name-input"
              invalid-feedback="Name is required"
            >
              <b-form-select
                label="Trading Account"
                label-for="Trading Account-input"
                invalid-feedback="A Trading is required"
                sm
                v-model="broker.broker_trading_account_id"
                :options="broker_trading_account_options"
              ></b-form-select>
            </b-form-group>
            <!-- <b-form-group label="User Permissions:">
              <b-form-checkbox-group
                id="checkbox-group-1"
                v-model="broker.selected_permissions"
                :options="broker_user_options"
                name="flavour-1"
              ></b-form-checkbox-group>
            </b-form-group>-->
            <b-form-group label="Client Account Permissions:">
              <b-form-checkbox-group v-model="broker.selected_client_permissions" id="checkboxes-r">
                <b-form-checkbox value="create-broker-client">Create</b-form-checkbox>
                <b-form-checkbox value="read-broker-client">Read</b-form-checkbox>
                <b-form-checkbox value="update-broker-client">Update</b-form-checkbox>
                <b-form-checkbox value="delete-broker-client">Delete</b-form-checkbox>
                <b-form-checkbox value="approve-broker-client">Approve</b-form-checkbox>
              </b-form-checkbox-group>
            </b-form-group>
            <b-form-group label="Order Permissions:">
              <b-form-checkbox-group v-model="broker.selected_broker_permissions" id="checkboxes-4">
                <b-form-checkbox value="create-broker-order">Create</b-form-checkbox>
                <b-form-checkbox value="read-broker-order">Read</b-form-checkbox>
                <b-form-checkbox value="update-broker-order">Update</b-form-checkbox>
                <b-form-checkbox value="delete-broker-order">Delete</b-form-checkbox>
                <b-form-checkbox value="approve-broker-order">Approve</b-form-checkbox>
              </b-form-checkbox-group>
            </b-form-group>
          </form>
        </b-modal>
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import permissionMixin from "./../../mixins/Permissions";
import axios from "axios";
import headNav from "./../partials/Nav";
export default {
  mixins: [permissionMixin],
  components: {
    headNav
  },
  data() {
    return {
      trading_accounts: [],
      permissions: [],
      user_role: [],
      create: false,
      local_brokers: [],
      local_broker_users: [{ type: [] }],
      broker: {},
      perPage: 5,
      currentPage: 1,
      broker_trading_account_options: [],
      broker_user_options: [
        { text: "Create", value: "create-broker-user" },
        { text: "Read", value: "read-broker-user" },
        { text: "Update", value: "update-broker-user" },
        { text: "Delete", value: "delete-broker-user" },
        { text: "Approve", value: "approve-broker-user" }
      ],
      broker_client_options: [
        { text: "Create", value: "create-broker-client" },
        { text: "Read", value: "read-broker-client" },
        { text: "Update", value: "update-broker-client" },
        { text: "Delete", value: "delete-broker-client" },
        { text: "Approve", value: "approve-broker-client" }
      ],
      broker_order_options: [
        { text: "Create", value: "create-broker-order" },
        { text: "Read", value: "read-broker-order" },
        { text: "Update", value: "update-broker-order" },
        { text: "Delete", value: "delete-broker-order" },
        { text: "Approve", value: "approve-broker-order" }
      ],
      permission_target: [],
      fields: [
        {
          key: "name",
          label: "User",
          sortable: true
        },
        {
          key: "email",
          sortable: true,
          label: "email"
        },
        {
          key: "status",
          label: "Status"
        },
        {
          key: "types",
          label: "Access Permissions"
        }
      ],
      modalTitle: "User Update",
      nameState: null
    };
  },
  computed: {
    rows() {
      return this.local_broker_users.length;
    }
  },
  watch: {
    create: function(data) {
      if (data) {
        this.modalTitle = "Create User";
      } else {
        this.modalTitle = "User Update";
      }
    }
  },
  methods: {
    getUserRole() {
      axios.put(`/nv9w8yp8rbwg4t`).then(response => {
        let data = response.data;
        let role = data.roles[0];
        let broker_account = data.broker;
        // console.log(broker_account);
        this.user_role = role.name;
        this.user_name = data.name;

        // console.log(this.permissions.indexOf("create") !== -1);

        if (
          this.permissions.indexOf("create-broker-user") !== -1 &&
          broker_account.length > 0
        ) {
          this.permission_target.push({
            role: "ADMB",
            text: "Broker User",
            value: "broker-user"
          });
        } else {
          this.permission_target = [
            {
              role: "ADMB",
              text: "Broker User",
              value: "broker-user"
            },
            {
              role: "",
              text: "Broker Client",
              value: "broker-client"
            },
            {
              role: "",
              text: "Broker Accounts",
              value: "broker-accounts"
            },
            {
              role: "",
              text: "Broker Orders",
              value: "broker-order"
            }
          ];
        }

        if (this.permissions.indexOf("create-broker-client") !== -1) {
          this.permission_target = [
            {
              role: "ADMB",
              text: "Broker Client",
              value: "broker-client"
            }
          ];
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
      this.broker = {};
    },
    handleOk(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault();
      // Trigger submit handler
      this.handleSubmit();
    },
    handleSubmit() {
      // console.log(this.broker);
      // Exit when the form isn't valid
      if (!this.checkFormValidity()) {
      } else {
        this.$bvModal.hide("modal-1"); //Close the modal if it is open

        //Determine if a new user is being created or we are updating an existing user
        if (this.create) {
          //Exclude ID
          this.storeBrokerUser({
            local_broker_id: parseInt(this.$userId),
            broker_trading_account_id: this.broker.broker_trading_account_id,
            name: this.broker.name,
            email: this.broker.email,
            status: "Unverified",
            permissions: this.broker.selected_client_permissions.concat(
              this.broker.selected_broker_permissions
            ),
            target: this.broker.target
          });
        } else {
          //Include ID
          this.storeBrokerUser({
            id: this.broker.id,
            local_broker_id: parseInt(this.$userId),
            broker_trading_account_id: this.broker.broker_trading_account_id,
            name: this.broker.name,
            email: this.broker.email,
            status: "Unverified",
            permissions: this.broker.selected_client_permissions.concat(
              this.broker.selected_broker_permissions
            ),
            target: this.broker.target
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
      this.$nextTick(() => {
        this.$bvModal.hide("modal-1");
      });
    },
    brokerUserHandler(b) {
      this.broker = b;
      this.broker.selected_permissions = this.broker.types;
      // console.log(this.broker);
      this.$swal({
        title: "",
        icon: "info",
        html: `Would you like to View Or Delete the following User <b>(${b.name})</b> `,
        showCloseButton: true,
        showCancelButton: true,
        // focusConfirm: true,
        cancelButtonColor: "#DD6B55",
        confirmButtonText: "View",
        confirmButtonAriaLabel: "delete",
        cancelButtonText: "Delete",
        cancelButtonAriaLabel: "cancel"
      }).then(result => {
        if (result.value) {
          this.$bvModal.show("modal-1");
        }
        if (result.dismiss === "cancel") {
          this.destroy(b.id);
          this.$swal("Deleted!", "User Has Been Removed.", "success");
        }
      });
    },
    getBrokers() {
      axios.get("broker-users").then(response => {
        // console.log(response);
        let data = response.data;
        let user_permissions = [{}];
        this.local_broker_users = data;

        //Handle Permissions
        let i, j, k;
        for (i = 0; i < this.local_broker_users.length; i++) {
          // console.log(this.local_broker_users[i].permissions)
          this.local_broker_users[i].types = [];
          this.local_broker_users[i].selected_client_permissions = [];
          this.local_broker_users[i].selected_broker_permissions = [];

          user_permissions = this.local_broker_users[i].permissions;
          for (k = 0; k < user_permissions.length; k++) {
            var specific_permission = user_permissions[k].name;
            this.local_broker_users[i].types.push(specific_permission);

            if (specific_permission.includes("client")) {
              this.local_broker_users[i].selected_client_permissions.push(
                specific_permission
              );
            }

            if (specific_permission.includes("order")) {
              this.local_broker_users[i].selected_broker_permissions.push(
                specific_permission
              );
            }
          }
        }

        this.broker.selected_permissions = this.local_broker_users.types;
      });
    },
    tradingAccounts() {
      axios.get("broker-trading-accounts").then(response => {
        let data = response.data;
        let i;
        for (i = 0; i < data.length; i++) {
          // console.log(data[i]);
          this.broker_trading_account_options.push({
            text:
              data[i].foreign_broker +
              " : " +
              data[i].bank +
              "-" +
              data[i].trading_account_number +
              " : " +
              data[i].account,
            value: data[i].id
          });
        }
      });
    },
    storeBrokerUser(broker) {
      this.$swal
        .fire({
          title: "Creating User Account",
          html: "One moment while we setup the User Account",
          timerProgressBar: true,
          onBeforeOpen: () => {
            this.$swal.showLoading();
          }
        })
        .then(result => {});
      axios
        .post("store-broker", broker)
        .then(response => {
          this.$swal(`Account created for ${broker.email}`);
          // setTimeout(location.reload.bind(location), 2000);
          this.getBrokers();
        })
        .catch(error => {
          if (error.response.data.message.includes("Duplicate entry")) {
            this.$swal(
              `An Account with this email address already exists. Please try using a different email`
            );
          }
        });
    },
    add() {
      this.create = true;
    },
    destroy(id) {
      axios.delete(`user-broker-delete/${id}`).then(response => {
        // this.getBrokers();
        window.location.reload();
      });
    }
  },
  mounted() {
    this.getUserRole();

    // //Define Permission On Front And Back End
    // let p = JSON.parse(this.$userPermissions);
    // //Looop through and identify all permission to validate against actions
    // for (let i = 0; i < p.length; i++) {
    //   this.permissions.push(p[i].name);
    //   // console.log(p[i].name);
    // }
    axios.get("local-brokers").then(response => {
      let local_brokers = response.data;
      // console.log(response);
      let i;
      for (i = 0; i < local_brokers.length; i++) {
        this.local_brokers.push({
          text: local_brokers[i].name,
          value: local_brokers[i].id
        });
      }
    });
    this.getBrokers();
    this.tradingAccounts();

    console.log(this.permissions);
  }
};
</script>
