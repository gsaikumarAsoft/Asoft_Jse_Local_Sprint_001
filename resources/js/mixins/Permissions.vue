<script>
import axios from "axios";
export default {
  methods: {
    permissionMixin: function() {
      axios.get("api/user").then(response => {
        console.log(response.body);
      });
    }
  }
};
</script>