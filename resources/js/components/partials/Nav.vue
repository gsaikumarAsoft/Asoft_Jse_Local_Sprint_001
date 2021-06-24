<template>
  <b-navbar toggleable="lg" type="dark" variant="info">
    <b-navbar-brand href="#">JSE</b-navbar-brand>

    <b-navbar-toggle target="nav-collapse"></b-navbar-toggle>

    <b-collapse id="nav-collapse" is-nav>
      <b-navbar-nav v-if="user_role === 'AGTS'">
        <b-nav-item href="/settlement-agent">Home New</b-nav-item>
        <b-nav-item href="#">Transactions</b-nav-item>
      </b-navbar-nav>
      <b-navbar-nav v-if="user_role === 'ADMD'">
        <b-nav-item href="/jse-admin">Home</b-nav-item>
        <b-nav-item href="/jse-admin/foreign-broker-list">Foreign Broker List</b-nav-item>
        <b-nav-item href="/jse-admin/local-broker-list ">Local Broker List</b-nav-item>
        <b-nav-item href="/jse-admin/settlements/">Settlement Accounts</b-nav-item>
        <b-nav-item href="/jse-admin/broker-2-broker">Trading Accounts</b-nav-item>
        <b-nav-item-dropdown
          id="order-management-dropdown-menu"
          text="Order Management"
          toggle-class="nav-link-custom"
          right
          >
            <b-dropdown-item href="/jse-admin/order-execution">Order Execution Reports</b-dropdown-item >
            <b-dropdown-item href="/jse-admin/expiring-buy-orders">Cancel Expiring Buy Orders (Manually)</b-dropdown-item >
            <b-dropdown-item href="/jse-admin/expired-buy-orders">Cancel Expired Buy Orders (Manually)</b-dropdown-item >
            <b-dropdown-item href="/jse-admin/fill-expired-buy-orders">Fill Expired Buy Orders (Manually)</b-dropdown-item >
        </b-nav-item-dropdown> 
        <b-nav-item-dropdown
          id="reports-dropdown-menu"
          text="Reports"
          toggle-class="nav-link-custom"
          right
          >
        <b-dropdown-item href="/jse-admin/dma-trade-report">DMA Trades Report</b-dropdown-item > 
        </b-nav-item-dropdown>
      </b-navbar-nav> 

      <b-navbar-nav v-if="user_role === 'ADMB'">
        <b-nav-item href="/broker">Home</b-nav-item>
        <!-- <b-nav-item href="/broker/company/">Company</b-nav-item> -->
        <b-nav-item href="/broker/users">Users</b-nav-item>
        <b-nav-item href="/broker/settlements">Settlement Accounts</b-nav-item>
        <b-nav-item href="/broker/traders">Client Accounts</b-nav-item>
        <b-nav-item href="/broker/orders">Orders</b-nav-item>
        <b-nav-item href="/broker/execution">Order Execution Reports</b-nav-item>
         <b-nav-item-dropdown
          id="reports-dropdown-menu"
          text="Reports"
          toggle-class="nav-link-custom"
          right
          >
        <b-dropdown-item href="/broker/dma-trade-report">DMA Trades Report</b-dropdown-item > 
        </b-nav-item-dropdown>
        <!-- <b-nav-item href="/broker/spprovals">Approval Request List</b-nav-item> -->
        <!-- <b-nav-item href="/broker/log">Audit Log List</b-nav-item> -->
      </b-navbar-nav>

      <b-navbar-nav v-if="user_role === 'OPRB'">
        <b-nav-item href="/operator">Home</b-nav-item>
        <!-- <b-nav-item href="/operator/users">User Management</b-nav-item> -->
        <b-nav-item href="/operator/clients">Client Management</b-nav-item>
        <b-nav-item href="/operator/orders">Orders</b-nav-item>
        <b-nav-item href="/operator/execution">Order Execution Reports</b-nav-item>
        <!-- <b-nav-item href="/jse-admin/settlements/">Broker Settlement Account</b-nav-item> -->
      </b-navbar-nav>

      <b-navbar-nav v-if="user_role === 'TRDB'">
        <b-nav-item href="/trader-broker">Home</b-nav-item>
        <b-nav-item href="/trader-broker/orders">Orders</b-nav-item>
        <!-- <b-nav-item href="/jse-admin/settlements/">Broker Settlement Account</b-nav-item> -->
      </b-navbar-nav>

      <b-navbar-nav v-show="user_role === 'BRKF'">
        <b-nav-item href="/foreign-broker">Home</b-nav-item>
        <b-nav-item href="/foreign-broker/settlements">Settlement Accounts</b-nav-item>
        <b-nav-item href="/foreign-broker/tradings">Trading Accounts</b-nav-item>
        <!-- <b-nav-item href="/jse-admin/settlements/">Broker Settlement Account</b-nav-item> -->
      </b-navbar-nav>

      <!-- Right aligned nav items -->
      <b-navbar-nav class="ml-auto">
        <b-nav-item-dropdown right>
          <!-- Using 'button-content' slot -->
          <template v-slot:button-content>
            <em>{{ user_name }}</em>
          </template>
          <b-dropdown-item href="/profile">Profile</b-dropdown-item>
          <b-dropdown-item href="/logout">Sign Out</b-dropdown-item>
        </b-nav-item-dropdown>
      </b-navbar-nav>
    </b-collapse>
  </b-navbar>
</template>

<script>
import permissionMixin from "./../../mixins/Permissions";
import axios from "axios";
export default {
  mixins: [permissionMixin],
  data() {
    return {
      permissions: [],
      user_data: [],
      user_name: [],
      user_role: []
    };
  },
  mounted() {
    // let p = JSON.parse(this.$userPermissions);
    // console.log(p);

    axios.put(`/nv9w8yp8rbwg4t`).then(response => {
      let data = response.data;
      let role = data.roles[0];
      this.user_name = data.name;
      this.user_role = role.name;
    });
  },
  methods: {}
};
</script>
