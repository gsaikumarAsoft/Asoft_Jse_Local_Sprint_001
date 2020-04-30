<template>
  <div>
    <toplinks></toplinks>
    <div class="container" style="margin-top: 100px;">
      <h4>Welcome JSE Admin</h4>
      <p>This DMA Administration user interface provides menu options to functions that will allow you to perform any of the following functions:</p>
      <li>Manage LOCAL BROKERS</li>
      <li>Manage SETTLEMENT ACCOUNTS</li>
      <li>Manage BROKER TO BROKER SETTINGS</li>
      <b-img src="/images/DMAValueProposition.png" fluid alt="Responsive image"></b-img>
    </div>
  </div>
</template>
<script>
import toplinks from "./partials/Nav";
export default {
  components: {
    toplinks
  }
};
</script>
