<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid" style="margin-top: 20px">
      <div class="content">
        <b-card title="Users">
          <div class="float-right" style="margin-bottom: 15px">
            <b-input
              id="search_content"
              v-model="filter"
              type="text"
              placeholder="Filter Users..."
              class="mb-2 mr-sm-2 mb-sm-0"
            ></b-input>
          </div>
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
            :filterIncludedFields="filterOn"
            :filter="filter"
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
          <b-button v-b-modal.modal-1 @click="create = true"
            >Create User</b-button
          >
        </b-card>
        <b-modal
          id="modal-1"
          :title="modalTitle"
          @ok="handleOk"
          @hidden="resetModal"
        >
          <p class="my-4">Please update the fields below as required!</p>
          <form ref="form">
            <b-form-group
              label="Name"
              label-for="name-input"
              invalid-feedback="Name is required"
            >
              <b-form-input
                id="name-input"
                v-model="broker.name"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="Email"
              label-for="email-input"
              invalid-feedback="Email is required"
              or
            >
              <b-form-input
                id="name-input"
                v-model="broker.email"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              sm
              label="Trading Account"
              label-for="name-input"
              invalid-feedback="Name is required"
            >
              <b-form-select
                id="trading-input"
                label="Trading Account"
                label-for="Trading Account-input"
                invalid-feedback="A Trading is required"
                sm
                v-model="broker.broker_trading_account_id"
                :options="broker_trading_account_options"
              ></b-form-select> 
            </b-form-group>
            
              <!--b-button type="reset" size="sm" >Reset</b-button-->

            <!-- <b-form-group label="User Permissions:">
              <b-form-checkbox-group
                id="checkbox-group-1"
                v-model="broker.selected_permissions"
                :options="broker_user_options"
                name="flavour-1"
              ></b-form-checkbox-group>
            </b-form-group>-->
            <b-form-group label="Client Account Permissions:">
              <b-form-checkbox-group
                v-model="broker.selected_client_permissions"
                id="checkboxes-r"
              >
                <b-form-checkbox value="create-broker-client"
                  >Create</b-form-checkbox
                >
                <b-form-checkbox value="read-broker-client"
                  >Read</b-form-checkbox
                >
                <b-form-checkbox value="update-broker-client"
                  >Update</b-form-checkbox
                >
                <!-- <b-form-checkbox value="delete-broker-client">Delete</b-form-checkbox> -->
                <b-form-checkbox value="approve-broker-client"
                  >Approve</b-form-checkbox
                >
              </b-form-checkbox-group>
            </b-form-group>
            <b-form-group label="Order Permissions:">
              <b-form-checkbox-group
                v-model="broker.selected_broker_permissions"
                id="checkboxes-4"
              >
                <b-form-checkbox value="create-broker-order"
                  >Create</b-form-checkbox
                >
                <b-form-checkbox value="read-broker-order"
                  >Read</b-form-checkbox
                >
                <!-- <b-form-checkbox value="update-broker-order">Update</b-form-checkbox> -->
                <b-form-checkbox value="delete-broker-order"
                  >Cancel</b-form-checkbox
                >
                <!-- <b-form-checkbox value="approve-broker-order">Approve</b-form-checkbox> -->
              </b-form-checkbox-group>
            </b-form-group>
          </form>
        </b-modal>
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import permissionMixin from "./../../../js/mixins/Permissions.js";
import axios from "axios";
import headNav from "./../partials/Nav.vue";
import checkErrorMixin from "../../mixins/CheckError.js";
export default {
  mixins: [permissionMixin, checkErrorMixin],
  components: {
    headNav,
  },
  data() {
    return {
      filter: null,
      filterOn: ["name","email","status", "types"],
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
        { text: "Approve", value: "approve-broker-user" },
      ],
      broker_client_options: [
        { text: "Create", value: "create-broker-client" },
        { text: "Read", value: "read-broker-client" },
        { text: "Update", value: "update-broker-client" },
        { text: "Delete", value: "delete-broker-client" },
        { text: "Approve", value: "approve-broker-client" },
      ],
      broker_order_options: [
        { text: "Create", value: "create-broker-order" },
        { text: "Read", value: "read-broker-order" },
        { text: "Update", value: "update-broker-order" },
        { text: "Delete", value: "delete-broker-order" },
        { text: "Approve", value: "approve-broker-order" },
      ],
      permission_target: [],
      fields: [
        {
          key: "name",
          label: "User",
          sortable: true,
        },
        {
          key: "email",
          sortable: true,
          label: "email",
        },
        {
          key: "status",
          label: "Status",
        },
        {
          key: "types",
          label: "Access Permissions",
        },
      ],
      modalTitle: "User Update",
      nameState: null,
    };
  },
  computed: {
    rows() {
      return this.local_broker_users.length;
    },
  },
  watch: {
    create: function (data) {
      if (data) {
        this.modalTitle = "Create User";
      } else {
        this.modalTitle = "User Update";
      }
    },
  },
  methods: {
    async getUserRole() {
      const { data } = await axios.put(`/nv9w8yp8rbwg4t`);
      //console.log("getUserRole data", data);
      let role = data.roles[0];
      let broker_account = data.broker;
      // //console.log(broker_account);
      this.user_role = role.name;
      this.user_name = data.name;

      // //console.log(this.permissions.indexOf("create") !== -1);

      if (
        this.permissions.indexOf("create-broker-user") !== -1 &&
        broker_account.length > 0
      ) {
        this.permission_target.push({
          role: "ADMB",
          text: "Broker User",
          value: "broker-user",
        });
      } else {
        this.permission_target = [
          {
            role: "ADMB",
            text: "Broker User",
            value: "broker-user",
          },
          {
            role: "",
            text: "Broker Client",
            value: "broker-client",
          },
          {
            role: "",
            text: "Broker Accounts",
            value: "broker-accounts",
          },
          {
            role: "",
            text: "Broker Orders",
            value: "broker-order",
          },
        ];
      }

      if (this.permissions.indexOf("create-broker-client") !== -1) {
        this.permission_target = [
          {
            role: "ADMB",
            text: "Broker Client",
            value: "broker-client",
          },
        ];
      }
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
    resetTradingAccount() {
      this.broker.broker_trading_account_id = -1;
      this.broker_trading_account_id = -1;
    },
    async handleOk(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault();
      // //console.log(this.broker);
      // Exit when the form isn't valid
      if (!this.checkFormValidity()) return;

      if (this.broker.selected_client_permissions == null) {
        this.broker.selected_client_permissions = [];
      }
      if (this.broker.selected_broker_permissions == null) {
        this.broker.selected_broker_permissions = [];
      }
      const account = {
        local_broker_id: parseInt(this.$userId),
        broker_trading_account_id: this.broker.broker_trading_account_id,
        name: this.broker.name,
        email: this.broker.email,
        status: "Unverified",
        permissions: this.broker.selected_client_permissions.concat(
          this.broker.selected_broker_permissions
        ),
        target: this.broker.target,
      };

      //Determine if a new user is being created or we are updating an existing user
      if (this.create) {
        //Exclude ID
        account["id"] = null;
      } else {
        //Include ID
        account["id"] = this.broker.id;
      }
      this.$swal.fire({
        title: `${this.create ? "Creating" : "Updating"} User Account`,
        html: `One moment while we ${
          this.create ? "setup" : "update"
        } the User Account`,
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        },
      });
      //console.log("", account);
      try {
        await axios.post("store-broker", account);
        await this.getBrokerUsers();
        //location.reload();
        this.$swal(
          `Account ${this.create ? "Created" : "Updated"} for ${
            this.broker.email
          }`
        );
        this.resetModal();
        await this.$nextTick();
        this.$bvModal.hide("modal-1");
      } catch (error) {
        this.checkDuplicateError(error);
      }
    },

    async brokerUserHandler(b) {
      this.broker = b;
      this.broker.selected_permissions = this.broker.types;
      // //console.log(this.broker);
      const result = await this.$swal({
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
        cancelButtonAriaLabel: "cancel",
      }); //.then(result => {
      if (result.value) {
        this.$bvModal.show("modal-1");
      }
      if (result.dismiss === "cancel") {
        await this.destroy(b.id);
      }
      //});
    },
    async getBrokerUsers() {
      try {
        ({ data: this.local_broker_users } = await axios.get("broker-users"));
        //console.log("this.local_broker_users", this.local_broker_users);
        //Handle Permissions
        for (let i = 0; i < this.local_broker_users.length; i++) {
          // //console.log(this.local_broker_users[i].permissions)
          this.local_broker_users[i].types = [];
          this.local_broker_users[i].selected_client_permissions = [];
          this.local_broker_users[i].selected_broker_permissions = [];

          const user_permissions = this.local_broker_users[i].permissions;
          for (let k = 0; k < user_permissions.length; k++) {
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
      } catch (error) {
        console.error("getBrokerUsers", error);
        throw error;
      }
    },

    async tradingAccounts() {
      const { data } = await axios.get("broker-trading-accounts");
      //console.log("tradingAccounts", data);
      data.push([]);
      this.broker_trading_account_options = data.map((x) => ({
        text: `${x.foreign_broker||"Any"} : ${x.bank||"Any"}-${x.trading_account_number||"Any"} : ${x.account||"Any"}`,
        value: x.id||0,
      }));
    },

    add() {
      this.create = true;
    },
    async destroy(id) {
      this.$swal.fire({
        title: "Deleting User Account",
        html: "One moment while we delete the User Account",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        },
      });
      try {
        await axios.delete(`user-broker-delete/${id}`);
        this.getBrokerUsers();
        this.$swal.close();
      } catch (error) {
        this.checkDeleteError(error);
      }
    },
    async getLocalBrokers() {
      const { data } = await axios.get("local-brokers");
      //console.log("local_brokers", data);
      this.local_brokers = data.map((x) => ({
        text: x.name,
        value: x.id,
      }));
    },
  },
  async mounted() {
    this.$swal.fire({
      title: "Loading User Data..",
      html: "One moment while we load user data",
      timerProgressBar: true,
      onBeforeOpen: () => {
        this.$swal.showLoading();
      },
    });
    await Promise.all([
      this.getBrokerUsers(),
      this.tradingAccounts(),
      this.getUserRole(),
      this.getLocalBrokers(),
    ]);
    this.$swal.close();
    //console.log("permissions", this.permissions);
  },
};
</script>
