import axios from "axios";
export default {
  methods: {
    async permissionMixin() {
      const { body } = await axios.get("api/user"); //.then(response => {
      //console.log("permissionMixin", body);
      // });
    }
  }
};