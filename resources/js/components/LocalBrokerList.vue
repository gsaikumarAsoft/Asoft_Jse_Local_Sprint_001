<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid">
      <div class="content">
        <b-table
          striped
          hover
          show-empty
          :empty-text="'No Local Brokers  have been Created. Create a Local Broker below.'"
          id="local-brokers"
          :items="local_brokers"
          :fields="fields"
          :per-page="perPage"
          :current-page="currentPage"
          @row-clicked="localBrokerHandler"
        >
          <template slot="index" slot-scope="row">{{ row }}</template>
        </b-table>
        <b-pagination
          v-model="currentPage"
          :total-rows="rows"
          :per-page="perPage"
          aria-controls="local-brokers"
        ></b-pagination>
        <b-button v-b-modal.modal-1 @click="create = true">Create Local Broker</b-button>
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
          </form>
        </b-modal>
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import axios from "axios";
import headNav from "./partials/Nav.vue";
export default {
  components: {
    headNav
  },
  data() {
    return {
      create: false,
      local_brokers: [],
      broker: {},
      perPage: 5,
      currentPage: 1,
      fields: [
        {
          key: "user.name",
          sortable: true,
          label: "Name"
        },
        {
          key: "user.email",
          sortable: true,
          label: "Email"
        },
        {
          key: "user.status",
          sortable: true,
          label: "Account Status"
        }
      ],
      modalTitle: "Local Broker Update",
      nameState: null
    };
  },
  computed: {
    rows() {
      return this.local_brokers.length;
    }
  },
  watch: {
    create: function(data) {
      if (data) {
        this.modalTitle = "Create Local Broker";
      } else {
        this.modalTitle = "Local Broker Update";
      }
    }
  },
  methods: {
    checkFormValidity() {
      const valid = this.$refs.form.checkValidity();
      this.nameState = valid;
      return valid;
    },
    async resetModal() {
      this.create = false;
      this.broker = {};
      await this.getBrokers();
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
        //Determine if a new user is being created or we are updating an existing user
        if (this.create) {
          //Exclude ID
          await this.storeLocalBroker({
            name: this.broker.name,
            email: this.broker.email
          });
          this.$swal(`Account created for ${this.broker.email}`);
        } else {
          //Include ID
          await this.storeLocalBroker({
            id: this.broker.id,
            name: this.broker.name,
            email: this.broker.email
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
    async localBrokerHandler(b) {
      this.broker = b.user;
      const result = await this.$swal({
        title: "",
        icon: "info",
        html: `Would you like to Edit Or Delete the following Local Broker <b>(${b.user.name})</b> `,
        showCloseButton: true,
        showCancelButton: true,
        // focusConfirm: true,
        cancelButtonColor: "#DD6B55",
        confirmButtonText: "Edit",
        confirmButtonAriaLabel: "delete",
        cancelButtonText: "Delete",
        cancelButtonAriaLabel: "cancel"
      }); //.then(result => {
      if (result.value) {
        this.$bvModal.show("modal-1");
      }
      if (result.dismiss === "cancel") {
        await this.destroy(b.id);
        this.$swal("Deleted!", "Local Broker Has Been Removed.", "success");
      }
    },
    async getBrokers() {
      ({ data: this.local_brokers } = await axios.get("local-brokers")); //.then(response => {
      console.log("this.local_brokers", this.local_brokers);
    },
    async storeLocalBroker(broker) {
      try {
        await axios.post("store-local-broker", broker);
        await this.getBrokers();
      } catch (error) {}
    },
    add() {
      this.create = true;
    },
    async destroy(id) {
      await axios.delete(`local-broker-delete/${id}`);
      await this.getBrokers();
    }
  },
  async mounted() {
    await this.getBrokers();
  }
};
</script>
