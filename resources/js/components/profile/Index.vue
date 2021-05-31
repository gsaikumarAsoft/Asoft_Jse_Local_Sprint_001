<template>
  <div>
    <toplinks></toplinks>
    <div class="container-fluid" style="margin-top: 20px;">
      <h4>User Profile</h4>
      <p v-if="role === 'ADMD'">You may update and validate your account details below</p>
      <p v-if="role != 'ADMD'">You may update your account password below</p>
      <b-form @submit="onSubmit" @reset="onReset" v-if="show">
        <div v-if="role === 'ADMD'">
          <b-form-group
            id="input-group-1"
            label="Email address:"
            label-for="input-1"
            description="We'll never share your email with anyone else."
          >
            <b-form-input
              id="input-1"
              v-model="user.email"
              type="email"
              required
              placeholder="Enter email"
            ></b-form-input>
          </b-form-group>

          <b-form-group id="input-group-2" label="Your Name:" label-for="input-2">
            <b-form-input id="input-2" v-model="user.name" required placeholder="Enter name"></b-form-input>
          </b-form-group>
        </div>
        <b-form-group
          v-if="role != 'ADMD'"
          id="input-group-3"
          label="Your Password:"
          label-for="input-3"
        >
          <b-form-input
            id="input-3"
            type="password"
            v-model="user.password"
            required
            placeholder="Enter new password"
          ></b-form-input>
        </b-form-group>

        <!-- <b-form-group id="input-group-4">
          <b-form-checkbox-group v-model="form.checked" id="checkboxes-4">
            <b-form-checkbox value="me">Check me out</b-form-checkbox>
            <b-form-checkbox value="that">Check that out</b-form-checkbox>
          </b-form-checkbox-group>
        </b-form-group>-->

        <b-button v-if="role === 'ADMD'" type="submit" variant="primary">Update Details</b-button>
        <b-button v-if="role != 'ADMD'" type="submit" variant="danger">Reset Pasword</b-button>
      </b-form>
      <!-- <b-card class="mt-3" header="Form Data Result">
        <pre class="m-0">{{ form }}</pre>
      </b-card>-->
    </div>
  </div>
</template>
<script>
import permissionMixin from "./../../mixins/Permissions";
import toplinks from "../partials/Nav";
import axios from "axios";
export default {
  mixins: [permissionMixin],
  props: ["user_account", "role"],
  components: {
    toplinks
  },
  data() {
    return {
      permissions: JSON.parse(this.$userPermissions),
      user: JSON.parse(this.user_account),
      form: {
        email: "",
        name: "",
        password: null,
        checked: []
      },
      passwords: [
        { text: "Select One", value: null },
        "Carrots",
        "Beans",
        "Tomatoes",
        "Corn"
      ],
      show: true
    };
  },
  methods: {
    updateAccount(user) {
      axios
        .post("profile/store", user)
        .then(response => {
          ////console.log(response);
          this.$swal(`Account details Updated`);
          setTimeout(function() {
            window.location.reload();
          }, 2000);
        })
        .catch(error => {});
    },
    onSubmit(evt) {
      evt.preventDefault();
      this.updateAccount(this.user);
    },
    onReset(evt) {
      evt.preventDefault();
      // Reset our form values
      this.form.email = "";
      this.form.name = "";
      this.form.password = null;
      this.form.checked = [];
      // Trick to reset/clear native browser form validation state
      this.show = false;
      this.$nextTick(() => {
        this.show = true;
      });
    }
  },
  mounted() {}
};
</script>