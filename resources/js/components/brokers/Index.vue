<template>
  <div>
    <toplinks></toplinks>
    <div class="container-fluid" style="margin-top: 100px;">
      <h4>Welcome Local Broker Admin</h4>
      <p>Use the menus available here to perform the following functions:</p>
      <li>Manage your USERS</li>
      <li>Manage your SETTLEMENT ACCOUNTS</li>
      <li>Manage your CLIENT ACCOUNTS</li>
      <li>Manage your FOREIGN MARKET ORDERS</li>
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
