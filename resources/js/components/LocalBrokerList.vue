<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid" style="margin-top: 20px;">
      <div class="content">
        <b-card title="Local Brokers">
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
          <b-button 
            v-b-modal.modal-1
            @click="create = true">Create Local Broker</b-button>
        </b-card>
        <b-modal id="modal-1" 
          :title="modalTitle" 
          @ok="handleOk"           
          :no-close-on-backdrop=true
          :no-close-on-esc=true           
          @hidden="resetModal">
          <p class="my-4">Please update the fields below as required!</p>
          <form ref="form" @submit.stop.prevent="handleSubmit">
            <b-form-group label="Name" label-for="name-input" invalid-feedback="Name is required">
              <b-form-input 
                id="name-input" 
                v-model="broker.name" 
                :state="nameState" 
                :disabled="!this.formReady"
                required></b-form-input>
            </b-form-group>

            <b-form-group
              label="Email"
              label-for="email-input"
              invalid-feedback="A valid Email is required"
            >
              <b-form-input 
                id="email-input" 
                type="email"
                v-model="broker.email" 
                :state="nameState"                 
                :disabled="!this.formReady"
                required></b-form-input>
            </b-form-group>
            
            <b-form-group
              sm
              label="Admin Can Trade"
              label-for="admin-can-trade-input"
              invalid-feedback="Admin Can Trade is required"
            >
              <b-form-select                
                v-model="broker.admin_can_trade" 
                :options="can_trade_options"                
                :state="nameState"
                :disabled="!this.formReady"
                required
              ></b-form-select>
            </b-form-group>
            <!-- -->
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
      local_brokers: [],
      can_trade_options: [        
          { text: 'Yes', value: 'Yes' },
          { text: 'No', value: 'No' },
      ],
      broker: { admin_can_trade: 'No'},
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
          key: "admin_can_trade",
          sortable: true,
          label: "Admin Can Trade"
        },
        {
          key: "user.status",
          sortable: true,
          label: "Account Status"
        }
         /*
        {
          key: "local_broker_id",
          sortable: true,
          label: "Local Broker ID"
        }
        //*/
      ],
      modalTitle: "Local Broker Update",
      formReady: true,
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
    async handleOk(bvModalEvt) {
      if (!this.formReady) {        
        this.$swal.fire({
          title: `${this.create ? "Creating" : "Updating"} Local Broker Account`,
          html: "An update is already beoong processed. Please wait while we setup the Account",
          timerProgressBar: true,
          onBeforeOpen: () => {
            this.$swal.showLoading();
          }
        });
        return;
      }
      this.formReady = false;

      // Prevent modal from closing
      bvModalEvt.preventDefault();
      // Trigger submit handler

      // Exit when the form isn't valid
      if (!this.checkFormValidity()) return;

      this.$swal.fire({
        title: `${this.create ? "Creating" : "Updating"} Local Broker Account`,
        html: "One moment while we setup the Account",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        }
      });

      try {
        //console.log("CREATE/UPDATE this.broker", this.broker);
        await axios.post("store-local-broker", this.broker);
        await this.getBrokers();
        this.$swal(
          `Account ${this.created ? "Created" : "Updated"} ${this.broker.email ? "for" : ""} 
            ${this.broker.email ? this.broker.email : ""}`
        );
        await this.resetModal();
        await this.$nextTick();
        this.$bvModal.hide("modal-1");
      } catch (error) {
        this.checkDuplicateError(error);
      }
      this.formReady = true;

      // Push the name to submitted names
      // this.submittedNames.push(this.name);
      // Hide the modal manually
      // this.$nextTick(() => {
      //   this.$bvModal.hide("modal-1");
      // });
    },
    async localBrokerHandler(b) {
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
        //console.log("b", b);
        ////console.log("b.admin_can_trade", b.admin_can_trade);
        this.broker = b.user;
        Object.assign(this.broker, {admin_can_trade: b.admin_can_trade});        
        ////console.log("selected this.broker", this.broker);      
        this.$bvModal.show("modal-1");
      }
      if (result.dismiss === "cancel") {
        await this.destroy(b.id);
      }
    },
    async getBrokers() {
      ({ data: this.local_brokers } = await axios.get("local-brokers")); //.then(response => {
      ////console.log("this.local_brokers", this.local_brokers);
    },

    add() {
      this.create = true;
    },
    async destroy(id) {
      this.$swal.fire({
        title: `Deleting Local Broker Account`,
        html: "One moment while we delete the Account",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        }
      });
      try {
        await axios.delete(`local-broker-delete/${id}`);
        await this.getBrokers();
        this.$swal("Deleted!", "Local Broker Has Been Removed.", "success");
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
