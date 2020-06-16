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
import headNav from "./partials/Nav";
export default {
  components: {
    headNav
  },
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
    resetModal() {
      this.create = false;
      this.broker = {};
      this.getBrokers();
    },
    handleOk(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault();
      // Trigger submit handler
      this.handleSubmit();
    },
    handleSubmit() {
      // Exit when the form isn't valid
      if (!this.checkFormValidity()) {
      } else {
        this.$bvModal.hide("modal-1"); //Close the modal if it is open

        //Determine if a new user is being created or we are updating an existing user
        if (this.create) {
          //Exclude ID
          this.storeForeignBroker({
            name: this.broker.name,
            email: this.broker.email
          });
          this.$swal(`Account created for ${this.broker.email}`);
        } else {
          //Include ID
          this.storeForeignBroker({
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
    foreignBrokerHandler(b) {
      this.broker = b.user;
      this.$swal({
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
      }).then(result => {
        if (result.value) {
          this.$bvModal.show("modal-1");
        }
        if (result.dismiss === "cancel") {
          this.destroy(b.id);
          this.$swal("Deleted!", "Foreign Broker Has Been Removed.", "success");
        }
      });
    },
    getBrokers() {
      axios.get("foreign-brokers").then(response => {
        let data = response.data;
        this.foreign_brokers = data;
      });
    },
    storeForeignBroker(broker) {
      this.$swal
        .fire({
          title: "Creating Foreign Broker Account",
          html: "One moment while we setup the Account",
          timerProgressBar: true,
          onBeforeOpen: () => {
            this.$swal.showLoading();
            axios
              .post("store-foreign-broker", broker)
              .then(response => {
                this.getBrokers();
              })
              .catch(error => {
                this.$swal(
                  "Account Created!",
                  // "Foreign Broker Has Been Removed.",
                  "success"
                );
              });
          }
        })
        .then(result => {});
    },
    add() {
      this.create = true;
    },
    destroy(id) {
      axios.delete(`foreign-broker-delete/${id}`).then(response => {
        this.getBrokers();
      });
    }
  },
  mounted() {
    this.getBrokers();
  }
};
</script>
