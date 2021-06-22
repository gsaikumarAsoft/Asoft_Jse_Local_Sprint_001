<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid" style="margin-top: 20px;">
<b-container class="bv-example-row">
            <b-row>
              <b-col width="0px">
                <template>
                    <label for="display-expiry-date" >From Date: </label>
                      <b-form-input
                        type="date"                        
                        id="from-date" 
                        v-model="from_date"                         
                      >
                      </b-form-input>                   
                </template>
              </b-col>
              <b-col width="0px">
                <template>
                    <label for="display-expiry-date" >To Date: </label>
                      <b-form-input
                        type="date"                        
                        id="to-date" 
                        v-model="to_date"                         
                      >
                      </b-form-input>                   
                </template>
              </b-col> 
               <b-col>                  
                  <div style = "width:180;">
                  <b-form-group label="Local Broker:" label-for="local-broker-input">
                  <b-form-select 
                        v-model="selected_local_broker"
                        :options="local_broker_options"
                        class="mb-3"
                        @change="selectLocalBroker()"
                      disabled></b-form-select>
                 </b-form-group>
                 </div>                 
              </b-col>
              <b-col>
                <label for="fetch-reports" style="font-size:12px;" >Click to retrieve execution reports: </label></br>
                <b-button 
                  id="fetch-reports"
                  @click="fetchReports" >Fetch Reports</b-button>
              </b-col>
            </b-row>
      </b-container>

      <!-- <pre>{{report_data}}</pre> -->
      <div class="content table-responsive">
        <b-card title="DMA Trades (Filled Orders) Report">
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
            id="dmatrade-table"
            :items="report_data"
            :per-page="perPage"
            :current-page="currentPage"
            striped
            hover
            :fields="fields"
            v-model="visibleRows" 
            :filterIncludedFields="filterOn"
            :filter="filter"
            @filtered="onFiltered"
          ></b-table>
          <!-- <b-button v-b-modal.jse-new-order @click="create = true">Create New Order</b-button> -->
          <br />
          <b-form inline>
            <b-pagination
              v-model="currentPage"
              :total-rows="rows"
              :per-page="perPage"
              aria-controls="orders-table"
                style="vertical-align: text-top; height: 20px;" 
                inline
            ></b-pagination>
                 - Rows per page: 
              <b-form-spinbutton id="rows-per-page" v-model="perPage" min="0" max="100" style="vertical-align: text-top;" inline></b-form-spinbutton>
               - <b-form-checkbox id="toggle-pagination" v-model="usePagination" inline> Use pages</b-form-checkbox>
          </b-form>
          <b-button @click="exportReports">Export Execution Reports (PDF)</b-button>
           <b-button @click="exportReports">Export Execution Reports (Excel)</b-button>
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
import headNav from "../partials/Nav.vue";
import jsPDF from "jspdf";
export default {
  components: {
    headNav,
    Multiselect,
  },
  data() {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      
    return {
      filterOn: ["clordid", "qTradeacc", "messageDate", "buyorSell", "status", "settlement_account_number", "text"],
      filter: null,
      from_date: today.getFullYear().toString()+"-"+('0' + (today.getMonth()+1)).slice(-2)+"-"+('0' + (today.getDate())).slice(-2),
      to_date: today.getFullYear().toString()+"-"+('0' + (today.getMonth()+1)).slice(-2)+"-"+('0' + (today.getDate())).slice(-2),
      foreign_broker_options: [],
      local_broker_options: [], 
      execution_reports: [],
      report_data: [],
      selected_foreign_broker: "0",
      selected_local_broker: "0",
      fields: [
        { key: "orderDate", sortable: true, label: "Order Date" },
        { key: "tradeDate", sortable: true, label: "Trade Date" },
        { key: "clientName", sortable: true, label: "Client" },
        { key: "jcsdId", sortable: true, label: "JCSD#" },
        { key: "clientOrderId", sortable: true, label: "Client Order#" },
        { key: "marketOrderId", sortable: true, label: "Market Order#" },
        { key: "comment", sortable: true, label: "Comment(text)" },
        { key: "slide", sortable: true, label: "Slide" },
        { key: "symbol", sortable: true, label: "Symbol" },
        { key: "fillQty", sortable: true, label: "Fill Qty" },
        { key: "currency", sortable: true, label: "Currency" },
        { key: "fillPrice", sortable: true, label: "Fill Price" },
        { key: "fillValue", sortable: true, label: "Fill Value" },
        { key: "trader", sortable: true, label: "Trader" },
        
      ],
      visibleRows: [],
      broker_client_orders: [],
      totalRows: 0,
      broker: {},
      perPage: 5,
      currentPage: 1,
      usePagination: true,
      nameState: null,
    };
  },
  computed: {
    rows() {
      return this.report_data.length;
    },
  },
  watch: {
    "filter": function(filt){
        //if(filt) {  
          this.currentPage = 1;
          this.totalRows = this.visibleRows.length;
          this.usePagination = true
          //this.perPage = 0
        //} else { 
        //  this.perPage = 5
        //}
    },
    "usePagination": function(usePages){
        if(usePages) {  
          this.perPage = 5
        } else { 
          this.perPage = 0
        }
    },
  },
  methods: {
   
    async fetchReports() {
      await this.getReports();
    },  
    async getForeignBrokers() {
      ({ data: this.foreign_brokers } = await axios.get("foreign-brokers"));
      
      this.selected_foreign_broker = "0";
      this.foreign_brokers.forEach(foreign_broker => 
        {
          this.foreign_broker_options.push({value: foreign_broker.user.id, text: foreign_broker.user.name });
          if(this.selected_foreign_broker == "0") {
            this.selected_foreign_broker = foreign_broker.user.id;
          }
        }
      );

      this.fetchReports();
      //console.log("foreing_brokers", this.foreign_broker_options  );
    },
    selectForeignBroker() {
      //console.log("selected_broker_id", this.selected_foreign_broker);
      //console.log("selected_expiry_date", this.expiring_orders_date);
      //for each this.foreign_broker_options 
      this.foreign_broker_options.forEach(broker => 
            {if(broker.value == this.selected_foreign_broker) 
            {this.selected_foreign_broker_name= broker.text;};}
          );
      this.fetchReports();
      //console.log("selected_broker_name", this.selected_foreign_broker_name );
        
    },

    //local broker dropdown

     async getLocalBrokers() {
      ({ data: this.local_brokers } = await axios.get("local-brokers"));
      
      this.selected_local_broker = "0";
      this.local_brokers.forEach(local_broker => 
        {
          this.local_broker_options.push({value: local_broker.user.id, text: local_broker.user.name });
          if(this.selected_local_broker == "0") {
            this.selected_local_broker = local_broker.user.id;
          }
        }
      );

      this.fetchReports();
     // console.log("local_brokers", this.local_broker_options);
    },
    selectLocalBroker() {
      //console.log("selected_broker_id", this.selected_foreign_broker);
      //console.log("selected_expiry_date", this.expiring_orders_date);
      //for each this.local_broker_options 
      this.local_broker_options.forEach(broker => 
            {if(broker.value == this.selected_local_broker) 
            {this.selected_local_broker_name= broker.text;};}
          );
      this.fetchReports();
      //console.log("selected_broker_name", this.selected_foreign_broker_name );
        
    },





    async getReports() {
      this.execution_reports  = [];
      //this.execution_date
      ({ data: this.execution_reports } = await axios.get("/dma-trade-reportList/"+this.from_date+"/"+this.to_date+"/"+this.selected_local_broker)); //.then(response => {
      this.report_data = this.execution_reports;
      //this.expiring_orders.forEach( order => this.selected_orders.push(order.client_order_id));
    },
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
    onFiltered(filteredItems) {
        // Trigger pagination to update the number of buttons/pages due to filtering
        this.filteredItemsCount = filteredItems.length
        this.currentPage = 1
      },
    exportReports() {
      this.perPage=0;
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
  async mounted() {
    //console.log(this.execution_reports);
    await Promise.all([
      this.getForeignBrokers(),
      this.getLocalBrokers(),
      this.getReports(),      
    ]);
    
          //console.log(r);
  },
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
