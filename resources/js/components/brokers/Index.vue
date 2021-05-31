<template>
  <div>
    <toplinks></toplinks>
    <div class="container-fluid" style="margin-top: 20px;">
      <h4>Welcome Local Broker Admin</h4>
      <p>Use the menus available here to perform the following functions:</p>
      <li>Manage <a href="/broker/users">USERS</a></li>
      <li>Manage <a href="/broker/settlements">SETTLEMENT BALANCES</a></li>
      <li>Manage <a href="/broker/traders">CLIENT ACCOUNTS</a></li>
      <li>Manage <a href="/broker/orders">FOREIGN MARKET ORDERS</a></li>
      <li>Manage <a href="/profile/">PROFILE</a></li>
    </div>
  </div>
</template>
<script>
import toplinks from "./../partials/Nav";
export default {
  components: {
    toplinks
  },
  mounted() {
    //console.log("In Local Broker");
  }
};
</script>
