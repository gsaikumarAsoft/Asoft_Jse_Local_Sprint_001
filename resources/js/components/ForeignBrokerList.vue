<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid" style="margin-top: 20px;">
      <div class="content">
        <b-card title="Foreign Brokers">
          <b-table
            striped
            hover
            show-empty
            :empty-text="'No Foreign Brokers have been Created. Create a Foreign Broker below.'"
            id="foreign-brokers"
            :items="foreign_brokers"
            :fields="fields"
            :per-page="perPage"
            :current-page="currentPage"
            @row-clicked="foreignBrokerHandler"
          >
            <template slot="index" slot-scope="row">{{ row }}</template>
          </b-table>
          <b-pagination
            v-model="currentPage"
            :total-rows="rows"
            :per-page="perPage"
            aria-controls="foreign-brokers"
          ></b-pagination>
          <b-button v-b-modal.modal-1 @click="create = true">Create Foreign Broker</b-button>
        </b-card>
        <b-modal id="modal-1" :title="modalTitle" @ok="handleOk" @hidden="resetModal">
          <p class="my-4">Please update the fields below as required!</p>
          <form ref="form">
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
import checkErrorMixin from "./../mixins/CheckError.js";
export default {
  components: {
    headNav
  },
   mixins: [checkErrorMixin],
  data() {
    return {
      create: false,
      foreign_brokers: [],
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
          label: "Status"
        }
      ],
      modalTitle: "Foreign Broker Update",
      nameState: null
    };
  },
  computed: {
    rows() {
      return this.foreign_brokers.length;
    }
  },
  watch: {
    create: function(data) {
      if (data) {
        this.modalTitle = "Create Foreign Broker";
      } else {
        this.modalTitle = "Foreign Broker Update";
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

    async handleOk(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault();
      // Trigger submit handler

      // Exit when the form isn't valid
      if (!this.checkFormValidity()) return;

      this.$bvModal.hide("modal-1"); //Close the modal if it is open

      //Determine if a new user is being created or we are updating an existing user
      this.$swal.fire({
        title: `${
          this.create ? "Creating" : "Updating"
        } Foreign Broker Account`,
        html: "One moment while we setup the Account",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        }
      });

      try {
        await axios.post("store-foreign-broker", this.broker);
        await this.getBrokers();
        this.$swal(
          `Account ${this.created ? "Created" : "Updated"}!`,
          "success"
        );
        await this.resetModal();
        await this.$nextTick();
        this.$bvModal.hide("modal-1");
      } catch (error) {
        this.checkDuplicateError(error);
      }

      // Push the name to submitted names
      // this.submittedNames.push(this.name);
      // Hide the modal manually
      // this.$nextTick(() => {
      //   this.$bvModal.hide("modal-1");
      // });
    },
    async foreignBrokerHandler(b) {
      this.broker = b.user;
      const result = await this.$swal({
        title: "",
        icon: "info",
        html: `Would you like to Edit Or Delete the following Foreign Broker <b>(${b.user.name})</b> `,
        // showCloseButton: true,
        showCancelButton: true,
        focusConfirm: true,
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
      }
    },
    async getBrokers() {
      ({ data: this.foreign_brokers } = await axios.get("foreign-brokers"));
    },

    add() {
      this.create = true;
    },
    async destroy(id) {
      this.$swal.fire({
        title: `Deleting Foreign Broker Account`,
        html: "One moment while we delete the Account",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        }
      });
      try {
        await axios.delete(`foreign-broker-delete/${id}`); //.then(response => {
        await this.getBrokers();
        this.$swal("Deleted!", "Foreign Broker Has Been Removed.", "success");
      } catch (error) {
        this.checkDeleteError(error);
      }
    }
  },
  async mounted() {
    await this.getBrokers();
  }
};
</script>
