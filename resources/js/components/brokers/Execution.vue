<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid" style="margin-top: 20px;">
<b-container class="bv-example-row">
            <b-row>
              <b-col width="0px">
                <template>
                    <label for="display-expiry-date" >Reported Since Date: </label>
                      <b-form-input
                        type="date"                        
                        id="execution-date" 
                        v-model="execution_date"                         
                      >
                      </b-form-input>                   
                </template>
              </b-col>
              <b-col>                  
                  <div style = "width:180;">
                  <b-form-group label="Foreign Broker:" label-for="broker-input">
                  <b-form-select 
                        v-model="selected_foreign_broker"
                        :options="foreign_broker_options"
                        class="mb-3"
                        @change="selectForeignBroker()"
                      ></b-form-select>
                 </b-form-group>
                 </div>                 
              </b-col>
              <b-col>
                <label for="fetch-reports" >Click to retrieve execution reports: </label></br>
                <b-button 
                  id="fetch-reports"
                  @click="fetchReports" >Fetch Reports</b-button>
              </b-col>
            </b-row>
      </b-container>

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
      execution_date: today.getFullYear().toString()+"-"+('0' + (today.getMonth()+1)).slice(-2)+"-"+('0' + (today.getDate())).slice(-2),
      //              today.getFullYear().toString()+"-"+('0' + (today.getMonth()+1)).slice(-2)+"-"+('0' + (today.getDate()+1)).slice(-2),
      foreign_broker_options: [],
      execution_reports: [],
      report_data: [],
      selected_foreign_broker: "0",
      fields: [
        { key: "clordid", sortable: true, label: "Client Order #" },
        { key: "orderID", sortable: true, label: "Market Order #" },
        { key: "local_broker", sortable: true, label: "Local Broker" },
        { key: "foreign_broker", sortable: true, label: "Foreign Broker" },
        {
          key: "settlement_account_number",       
          filterByFormatted: true, 
          formatter: (value, key, item) => {
            var agent = item.settlement_agent;
            return agent + "-" + value;
          },
        },
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
        { key: "qTradeacc", sortable: true, label: "JCSD" },
        {
          key: "messageDate",
          sortable: true,       
          filterByFormatted: true, 
          formatter: (value, key, item) => {
            return moment(String(value));
          },
        },
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
    async getReports() {
      this.execution_reports  = [];
      //this.execution_date
      ({ data: this.execution_reports } = await axios.get("execution-list-for/"+this.selected_foreign_broker+"/"+this.execution_date)); //.then(response => {
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
      this.getReports(),      
    ]);
    
          //console.log(r);
  },
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
