<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid" style="margin-top: 20px;">
      <!-- <pre>{{report_data}}</pre> -->
      <div class="content table-responsive">
        <b-card title="Order Execution Reports">
          <div class="float-right" style="margin-bottom: 15px">
            <b-input
              id="search_content"
              v-model="filter"
              type="text"
              placeholder="Filter Orders..."
              class="mb-2 mr-sm-2 mb-sm-0"
            ></b-input>
          </div>
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
            v-model="visibleRows" 
            :filterIncludedFields="filterOn"
            :filter="filter"
          ></b-table>
          <!-- <b-button v-b-modal.jse-new-order @click="create = true">Create New Order</b-button> -->
          <br />
          <b-pagination
            v-model="currentPage"
            :total-rows="rows"
            :per-page="perPage"
            aria-controls="orders-table"
          ></b-pagination>
          <b-button @click="exportReports">Export Execution Reports (PDF)</b-button>
        </b-card>
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import moment from "moment";
import saveAs from "file-saver";
import Multiselect from "vue-multiselect";
import axios from "axios";
import headNav from "./../partials/Nav.vue";
import jsPDF from "jspdf";
export default {
  props: ["execution_reports"],
  components: {
    headNav,
    Multiselect,
  },
  data() {
    return {
      filterOn: ["clordid", "qTradeacc", "messageDate", "buyorSell", "status", "settlement_account_number", "text"],
      filter: null,
      fields: [
        { key: "clordid", sortable: true, label: "Order Number" },
        { key: "qTradeacc", sortable: true, label: "Client Account" },
        { key: "status",
          sortable: true,  
          label: "STATUS : Details" ,     
          filterByFormatted: true, 
          formatter: (value, key, item) => {
            // return value;
            //return statusFilter(value) + (item.text || "No details");
            
            if (value === "0") {
              return "NEW : "+ (item.text || "No details");
            }
            if (value === "-1") {
              return "PENDING : "+ (item.text || "No details");
            }
            if (value === "1") {
              return "PARTIALLY FILLED : "+ (item.text || "No details");
            }
            if (value === "2") {
              return "FILLED : "+ (item.text || "No details");
            }
            if (value === "4") {
              return "CANCELLED : "+ (item.text || "No details");
            }
            if (value === "6") {
              return "PENDING CANCEL : "+ (item.text || "No details");
            }
            if (value === "5") {
              return "REPLACED : "+ (item.text || "No details");
            }
            if (value === "C") {
              return "EXPIRED :"+ (item.text || "No details");
            }
            if (value === "Z") {
              return "PRIVATE : "+ (item.text || "No details");
            }
            if (value === "U") {
              return "UNPLACED : "+ (item.text || "No details");
            }
            if (value === "x") {
              return "INACTIVE : "+ (item.text || "No details");  //; Stop Limit is waiting for its triggering conditions to be met (Nasdaq Defined)";
            }
            if (value === "8") {
              return "REJECTED : "+ (item.text || "No details");
            }
            if (value === "Submitted") {
              return "SUBMITTED : "+ (item.text || "No details");
            }
            if (value === "Failed") {
              return "FAILED : "+ (item.text || "No details");
            }
            if (value === "Cancel Submitted") {
              return "CANCEL SUBMITTED : "+ (item.text || "No details");
            }
            return "STATUS["+value+"] : "+ (item.text || "No details");
            
          },
        },
        /*
        { key: "text", sortable: true, 
          label: "Description",
          filterByFormatted: true, 
          formatter: (value, key, item) => {
            // return value;
            //return value == null ? "(No details)" : (value || "");
            return item.status;
            //return value == null ? "Status: " + this.status : (value || 0);
          }
        },
        */
        {
          key: "buyorSell",
          sortable: true,       
          filterByFormatted: true, 
          formatter: (value, key, item) => {
            // return value;
            if (value === "1") {
              return "Buy";
            }
            if (value === "2") {
              return "Sell";
            }
            if (value === "5") {
              return "Sell Short";
            }
            if (value === "6") {
              return "Sell Short Exempt";
            }
            if (value === "8") {
              return "Cross";
            }
            if (value === "9") {
              return "Short Cross";
            }
            if (value === "X") {
              return "BuyCross (Fill for the Buy side of a Cross)";
            }
            if (value === "Y") {
              return "SellCross (Fill for the Sell side of a Cross)";
            }
          },
        },
        {
          key: "settlement_account_number",       
          filterByFormatted: true, 
          formatter: (value, key, item) => {
            var agent = item.settlement_agent;
            return agent + "-" + value;
          },
        },
        {
          key: "messageDate",
          sortable: true,       
          filterByFormatted: true, 
          formatter: (value, key, item) => {
            return moment(String(value));
          },
        },
      ],
      visibleRows: [],
      broker_client_orders: [],
      broker: {},
      perPage: 5,
      currentPage: 1,
      nameState: null,
    };
  },
  computed: {
    report_data() {
      const data = JSON.parse(this.execution_reports);
      this.execution_reports = data;
      return data;
    },
    rows() {
      return this.report_data.length;
    },
  },
  watch: {
    "filter": function(filt){
        //if(filt) {  
          this.currentPage = 1;
          //this.perPage = 0
        //} else { 
        //  this.perPage = 5
        //}
    },
  },
  methods: {
    statusFilter(data) {
      if (data === "0") {
        return "NEW";
      } else if (data === "-1") {
        return "PENDING";
      } else if (data === "1") {
        return "PARTIALLY FILLED";
      } else if (data === "2") {
        return "FILLED";
      } else if (data === "4") {
        return "CANCELLED";
      } else if (data === "5") {
        return "REPLACED";
      } else if (data === "C") {
        return "EXPIRED";
      } else if (data === "Z") {
        return "PRIVATE";
      } else if (data === "U") {
        return "UNPLACED";
      } else if (data === "x") {
        return "INACTIVE";
      } else if (data === "8") {
        return "REJECTED";
      } else if (data === "Submitted") {
        return "SUBMITTED";
      } else if (data === "Failed") {
        return "FAILED";
      } else if (data === "Cancel Submitted") {
        return "CANCEL SUBMITTED";
      } else {
        return "["+data+"]";
      }
    },
    exportReports() {
      //console.log(this.visibleRows);      
      const tableData = this.visibleRows.map((r) =>
      //const tableData = this.report_data.map((r) =>
        //for (var i = 0; i < this.report_data.length; i++) {
        //tableData.push([
        [
          r.clordid,
          r.qTradeacc,
          this.statusFilter(r.status)+" : "+ r.text,
          r.buyorSell == 1 ? "BUY" : "SELL",
          r.settlement_agent + "-" + r.settlement_account_number,
          r.messageDate,
        ]
      );

      // //console.log(this.broker_settlement_account[i])
      // tableData.push(this.broker_settlement_account[i]);

      var doc = new jsPDF('l', 'in', [612, 792]);
      //   // It can parse html:
      //   doc.autoTable({ html: "#foreign-brokers" });

      // Or use javascript directly:
      doc.autoTable({
        head: [
          [
            "Order Number",
            "Client Account",
            "Status : Description",
            "Side",
            "Settlement Account",
            "Messsage Date",
          ],
        ],
        body: tableData,
      });
      doc.save("DMA-ORDER-EXECUTION-REPORTS.pdf");
    },
  },
  mounted() {
    //console.log(this);
  },
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
