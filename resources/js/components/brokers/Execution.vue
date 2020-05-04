<template>
  <div>
    <head-nav></head-nav>
    <div class="container" style="margin-top: 100px;">
      <h1>Execution Order Reports</h1>
      <div class="content">
        <b-table
          ref="selectedOrder"
          :empty-text="'No Orders have been Created. Create an Order below.'"
          id="orders-table"
          :items="report_data"
          :per-page="perPage"
          :current-page="currentPage"
          striped
          hover
          :fields="fields"
          @row-clicked="brokerOrderHandler"
        ></b-table>
        <b-pagination
          v-model="currentPage"
          :total-rows="rows"
          :per-page="perPage"
          aria-controls="orders-table"
        ></b-pagination>
        <!-- <b-button v-b-modal.jse-new-order @click="create = true">Create New Order</b-button> -->
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import saveAs from "file-saver";
import Multiselect from "vue-multiselect";
import axios from "axios";
import headNav from "./../partials/Nav";
// import jsonfile from 'jsonfile';
export default {
  props: ["execution_reports"],
  components: {
    headNav,
    Multiselect
  },
  data() {
    return {
      report_data: JSON.parse(this.execution_reports),
      fields: [
        // { ket: "handling_instructions", sortable: true },
        // { key: "order_date", sortable: true },
        // {
        //   key: "order_type.text",
        //   label: "Order Type",
        //   sortable: true,
        //   formatter: (value, key, item) => {
        //     var type = JSON.parse(item.order_type);
        //     var order = JSON.parse(type);
        //     return order.text;
        //   }
        // },
        // {
        //   key: "symbol.text",
        //   label: "Symbol",
        //   sortable: true,
        //   formatter: (value, key, item) => {
        //     var data = JSON.parse(item.symbol);
        //     var s = data;

        //     return s.text;
        //     // return symbol.text;
        //   }
        // },
        // {
        //   key: "time_in_force.text",
        //   label: "Time In Force",
        //   sortable: true,
        //   formatter: (value, key, item) => {
        //     var data = JSON.parse(item.time_in_force);
        //     var s = data;

        //     return s.text;
        //     // return symbol.text;
        //   }
        // },
        // {
        //   key: "currency.text",
        //   label: "Currency",
        //   sortable: true,
        //   formatter: (value, key, item) => {
        //     var data = JSON.parse(item.currency);
        //     var s = data;

        //     return s.text;
        //     // return symbol.text;
        //   }
        // },
        // {
        //   key: "side.text",
        //   label: "Side",
        //   sortable: true,
        //   formatter: (value, key, item) => {
        //     var data = JSON.parse(item.side);
        //     var s = data;

        //     return s.text;
        //     // return symbol.text;
        //   }
        // },
        // { key: "order_quantity", sortable: true },
        // { key: "price", sortable: true },
        // { key: "order_status", sortable: true }
        // // { key: "foreign_broker", sortable: true }
      ],
      broker_client_orders: [],
      broker: {},
      perPage: 5,
      currentPage: 1,
      nameState: null
    };
  },
  computed: {
    rows() {
      return this.report_data.length;
    }
  },
  methods: {},
  mounted() {
  }
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
