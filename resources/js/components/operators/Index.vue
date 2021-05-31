<template>
  <div>
    <toplinks></toplinks>
    <div class="container-fluid" style="margin-top: 20px;">
      <h4>Welcome Local Broker Operator</h4>
      <p>Use the menus available here to perform the following functions:</p>
      <li>Manage CLIENT ACCOUNTS</li>
      <li>Manage FOREIGN MARKET ORDERS</li>
      <li>Manage your PROFILE</li>
    </div>
  </div>
</template>
<script>
import toplinks from "./../partials/Nav";
export default {
  components: {
    toplinks
  }
};
</script>
