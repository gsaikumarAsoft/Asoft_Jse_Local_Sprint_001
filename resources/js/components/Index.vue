<template>
  <div>
    <toplinks></toplinks>
    <div class="container-fluid" style="margin-top: 20px;">
      <h4>Welcome JSE Admin</h4>
      <p>This DMA Administration user interface provides menu options to functions that will allow you to perform any of the following functions:</p>
            <li>
        <a href="/jse-admin/foreign-broker-list">MANAGE FOREIGN BROKERS</a>
      </li>
      <li>
        <a href="/jse-admin/local-broker-list">MANAGE LOCAL BROKERS</a>
      </li>
      <li>
        <a href="/jse-admin/local-brokers-order-list">MANAGE LOCAL BROKERS ORDERS</a>
      </li>
      <li>
        <a href="/jse-admin/settlements">MANAGE SETTLEMENT ACCOUNTS</a>
      </li>
      <li>
        <a href="/jse-admin/broker-2-broker">MANAGE TRADING ACCOUNTS</a>
      </li>
      <li>
        <a href="/jse-admin/expiring-buy-orders">CANCEL EXPIRING BUY ORDERS</a>
      </li>
      <li>
        <a href="/jse-admin/expired-buy-orders">CANCEL EXPIRED BUY ORDERS</a>
      </li>
      <b-img src="/images/DMAValueProposition.png" fluid alt="Responsive image"></b-img>
    </div>
  </div>
</template>
<script>
import toplinks from "./partials/Nav";
export default {
  components: {
    toplinks,
  },
};
</script>
