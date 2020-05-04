<template>
  <div>
    <head-nav></head-nav>
    <div class="container" style="margin-top: 100px;">
      <h1>Execution Order Reports</h1>
      <div class="content table-responsive">
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
        <!-- <b-button v-b-modal.jse-new-order @click="create = true">Create New Order</b-button> -->
      </div>
      <b-pagination
        v-model="currentPage"
        :total-rows="rows"
        :per-page="perPage"
        aria-controls="orders-table"
      ></b-pagination>
      <b-button @click="exportBalances">Export Balances</b-button>
    </div>
  </div>
</template>
<script lang="ts">
import saveAs from "file-saver";
import Multiselect from "vue-multiselect";
import axios from "axios";
import headNav from "./../partials/Nav";
import jsPDF from "jspdf";
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
  methods: {
    exportBalances() {
      const tableData = [];
      for (var i = 0; i < this.report_data.length; i++) {
        tableData.push([
          // this.report_data[i].clOrdID,
          this.report_data[i].orderID,
          // this.report_data[i].text,
          // this.report_data[i].ordRejRes,
          // this.report_data[i].status,
          this.report_data[i].buyorSell,
          this.report_data[i].securitySubType,
          // this.report_data[i].time,
          this.report_data[i].ordType,
          this.report_data[i].orderQty,
          this.report_data[i].timeInForce,
          this.report_data[i].symbol,
          // this.report_data[i].qTradeacc,
          this.report_data[i].price,
          this.report_data[i].stopPx,
          // this.report_data[i].execType,
          this.report_data[i].senderSub,
          this.report_data[i].seqNum
          // this.report_data[i].sendingTime,
          // this.report_data[i].messageDate
        ]);
      }

      // console.log(this.broker_settlement_account[i])
      // tableData.push(this.broker_settlement_account[i]);

      var doc = new jsPDF();
      //   // It can parse html:
      //   doc.autoTable({ html: "#foreign-brokers" });

      // Or use javascript directly:
      doc.autoTable({
        head: [
          [
            // "clOrdID",
            "orderID",
            // "text",
            // "ordRejRes",
            // "status",
            "buyorSell",
            "securitySubType",
            // "time",
            "ordType",
            "orderQty",
            "timeInForce",
            "symbol",
            // "qTradeacc",
            "price",
            "stopPx",
            // "execType",
            "senderSubID",
            "seqNum"
            // "sendingTime",
            // "messageDate"
          ]
        ],
        body: tableData
      });
      doc.save("JSE-ORDER-EXECUTION-REPORT.pdf");
    }
  },
  mounted() {}
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
