<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid">
      <div class="content">
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
          @row-clicked="displayForeignBroker"
        >
          <template slot="index" slot-scope="row">{{ row }}</template>
        </b-table>
        <b-pagination
          v-model="currentPage"
          :total-rows="rows"
          :per-page="perPage"
          aria-controls="foreign-brokers"
        ></b-pagination>
        <b-button v-b-modal.modal-1 @click="addNewBroker">Create Foreign Broker</b-button>
        <b-modal id="modal-1" :title="modalTitle" @ok="handleModalOK" @hidden="resetModal">
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
export default {
  components: {
    headNav
  },
  data() {
    return {
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
  watch: {},
  methods: {
    checkFormValidity() {
      const valid = this.$refs.form.checkValidity();
      this.nameState = valid;
      return valid;
    },
    async resetModal() {
      this.broker = {};
      await this.getBrokers();
    },
    async handleModalOK(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault();
      // Trigger submit handler

      // Exit when the form isn't valid
      if (!this.checkFormValidity()) {
      } else {
        this.$bvModal.hide("modal-1"); //Close the modal if it is open

        const storeType = !this.broker.id ? "Created" : "Updated";
        const titleType = !this.broker.id ? "Creating" : "Updating";
        const contentType = !this.broker.id ? "create" : "update";
        //Determine if a new user is being created or we are updating an existing user

        try {
          this.$swal.fire({
            title: `${titleType} Foreign Broker Account`,
            html: `One moment while we ${contentType} the Account`,
            timerProgressBar: true,
            onBeforeOpen: () => {
              this.$swal.showLoading();
            }
          });

          await axios.post("store-foreign-broker", this.broker);
          await this.getBrokers();
          this.$swal(`Account ${storeType} for ${this.broker.email}`);

          this.resetModal();
          this.$nextTick(() => {
            this.$bvModal.hide("modal-1");
          });
          this.$swal.close();
        } catch (error) {
          console.error("store", error);
          this.$swal(
            "Oops...",
            "Something went wrong! This Email Address may already be assigned.",
            "error"
          );
        }
      }
    },

    async displayForeignBroker(b) {
      this.broker = b.user;
      this.modalTitle = "Foreign Broker Update";
      const result = await this.$swal({
        title: "",
        icon: "info",
        html: `Foreign Broker <b>(${b.user.name})</b> `,
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
      ({ data: this.foreign_brokers } = await axios.get("foreign-brokers")); //.then(response => {
    },

    addNewBroker() {
      this.modalTitle = "Create Foreign Broker";
      this.$bvModal.show("modal-1");
    },

    async destroy(id) {
      try {
        this.$swal.fire({
          title: `Foreign Broker`,
          html: "Deleting Broker.......",
          timerProgressBar: true,
          onBeforeOpen: () => {
            this.$swal.showLoading();
          }
        });
        await axios.delete(`foreign-broker-delete/${id}`); //.then(response => {
        await this.getBrokers();
        this.$swal("Deleted!", "Foreign Broker Has Been Removed.", "success");
      } catch (error) {
        console.error("destroy", error);
        this.$swal("Oops...", "Something went wrong!", "error");
      }
    }
  },
  async mounted() {
    await this.getBrokers();
  }
};
</script>
