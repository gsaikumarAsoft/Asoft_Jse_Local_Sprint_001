<template>
  <div>
    <toplinks></toplinks>
    <div class="container-fluid" style="margin-top: 20px;">
      <h4>Welcome Outbound Broker</h4>
      <p>This DMA Administration user interface provides menu options to functions that will allow you to perform any of the following functions:</p>
      <li>Manage SETTLEMENT ACCOUNTS</li>
      <li>Manage TRADING ACCOUNT SETTINGS</li>
      <b-img src="/images/DMAValueProposition.png" fluid alt="Responsive image"></b-img>
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
