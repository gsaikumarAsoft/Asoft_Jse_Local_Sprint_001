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
                  <div style = "width:180;">
                  <b-form-group label="Local Broker:" label-for="local-broker-input">
                  <b-form-select 
                        v-model="selected_local_broker"
                        :options="local_broker_options"
                        class="mb-3"
                        @change="selectLocalBroker()"
                      ></b-form-select>
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
  <b-row class="float-right" style="margin-bottom: 15px">
         
         <b-col style="margin-top: -9%">
           <b-form-group label="Side:" label-for="side-dropdown">
            <b-form-select 
                  v-model="selected_side"
                  :options="side_options"
                  class="mb-3" 
                  @change="getSide()"
                ></b-form-select>
            </b-form-group>
            </b-col>
            <b-col>
              <b-input
              id="search_content"
              v-model="filter"
              type="text"
              placeholder="Filter Orders..."
              class="mb-2 mr-sm-2 mb-sm-0"
            ></b-input>
            </b-col>
         </b-row> 
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
           <b-button @click="exportExcel">Export Execution Reports (Excel)</b-button>
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
import XLSX from 'xlsx';
export default {
  components: {
    headNav,
    Multiselect,
  },
  data() {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      
    return {
      filterOn: ["orderDate", "tradeDate", "clientName", "JCSD", "client_order_number", "mrktOrdNo", "symbol","currency"],
      filter: null,
      current_date: today.getFullYear().toString()+"-"+('0' + (today.getMonth()+1)).slice(-2)+"-"+('0' + (today.getDate())).slice(-2),
      from_date: today.getFullYear().toString()+"-"+('0' + (today.getMonth()+1)).slice(-2)+"-"+('0' + (today.getDate())).slice(-2),
      to_date: today.getFullYear().toString()+"-"+('0' + (today.getMonth()+1)).slice(-2)+"-"+('0' + (today.getDate())).slice(-2),
      foreign_broker_options: [],
      local_broker_options: [], 
      dma_trade_reports: [],  
      side_options: [],  
      report_data: [],
      user_name1: [],
      selected_foreign_broker: "0", 
      selected_local_broker: "0",
      selected_foreign_broker_name: '',
      selected_local_broker_name:'',
      ExcelExportData:[],
      selected_side: "0",  
      fields: [
        { key: "orderDate", sortable: true, label: "Order Date" },
        { key: "tradeDate", sortable: true, label: "Trade Date" },
        { key: "clientName", sortable: true, label: "Client" },
        { key: "JCSD", sortable: true, label: "JCSD#" },
        { key: "client_order_number", sortable: true, label: "Client Order#" },
        { key: "mrktOrdNo", sortable: true, label: "Market Order#" },
        { key: "comments", sortable: true, label: "Comment(text)" },
        { key: "side", sortable: true, label: "Side" },
        { key: "symbol", sortable: true, label: "Symbol" },
        { key: "FillQty", sortable: true, label: "Fill Qty" },
        { key: "currency", sortable: true, label: "Currency" },
        { key: "FillPrice", sortable: true, label: "Fill Price" }, 
        { key: "FilledValue", sortable: true, label: "Fill Value" },  
        
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
       
      //console.log(this.foreign_brokers);

      this.selected_foreign_broker = "0";
      this.foreign_brokers.forEach(foreign_broker => 
        {
          this.foreign_broker_options.push({value: foreign_broker.id, text: foreign_broker.user.name });
          if(this.selected_foreign_broker == "0") {
            this.selected_foreign_broker = foreign_broker.id;
            this.selected_foreign_broker_name=foreign_broker.user.name;
          }
        }
      );
 
      this.fetchReports();
       
    },
    selectForeignBroker() {
      
      this.foreign_broker_options.forEach(broker => 
            {if(broker.value == this.selected_foreign_broker) 
            {this.selected_foreign_broker_name= broker.text;};}
          );
      this.fetchReports();
      
    },

    //local broker dropdown

     async getLocalBrokers() {

      ({ data: this.local_brokers } = await axios.get("local-brokers"));
      
      this.selected_local_broker = "0";
      this.local_brokers.forEach(local_broker => 
        {
          this.local_broker_options.push({value: local_broker.id, text: local_broker.user.name });
          if(this.selected_local_broker == "0") {
            this.selected_local_broker = local_broker.id;
             this.selected_local_broker_name= local_broker.user.name;
          }
        }
      ); 

      this.fetchReports();
      
    },
    selectLocalBroker() {
     
      this.local_broker_options.forEach(broker => 
            {if(broker.value == this.selected_local_broker) 
            {this.selected_local_broker_name= broker.text;};}
          );
      this.fetchReports();
         
     },

     async getSideDetails() {

      ({ data: this.side_options} = await axios.get("sidelist")); 

      this.selected_side= "0";
      this.side_options.forEach(local_side => 
        { 
          if(this.selected_side == "0") {
            this.selected_side = local_side.value;
          }
        }
      );
     },
     getSide() {
      
      this.side_options.forEach(sideopt => 
            {if(sideopt.value == this.selected_side) 
            {this.selected_side_name= sideopt.text;};}
          );

      this.fetchReports(); 
        
    },

    async getReports() {
      this.dma_trade_reports  = []; 
      ({ data: this.dma_trade_reports} = await axios.get("dma-trade-report-list/"+this.from_date+"/"+this.to_date+"/"+this.selected_foreign_broker+"/"+this.selected_local_broker+"/"+this.selected_side)); //.then(response => {
      this.report_data = this.dma_trade_reports;
    }, 
    onFiltered(filteredItems) {
        // Trigger pagination to update the number of buttons/pages due to filtering
        this.filteredItemsCount = filteredItems.length
        this.currentPage = 1
      },
    exportReports() {

      this.perPage=0;
      const ClientOrders = [];
      const Symbols = [];
      const Currency = [];
 
      const tableData = this.visibleRows.map((r) =>
       [
          r.orderDate,
          r.tradeDate,    
          r.clientName,
          r.JCSD,
          r.client_order_number,   
          r.mrktOrdNo,
          r.comments,
          r.side,   
          r.symbol,
          r.FillQty,
          r.currency,   
          r.FillPrice,
          r.FilledValue
        ]
      );
       
      const countTabelData = [];
      var sumFQty=0;var sumFPrice=0;var sumFValue=0;
      for (var i = 0; i < tableData.length; i++) {([
         ClientOrders.push(tableData[i][4]),
         Symbols.push(tableData[i][8]),
         Currency.push(tableData[i][10]),
         sumFQty = sumFQty + parseInt(tableData[i][9]),
         sumFPrice = sumFPrice + parseInt(tableData[i][11]),
         sumFValue = sumFValue + parseInt(tableData[i][12]),
      ]);
      }
 
        var doc = new jsPDF('l', 'pt');

//Filters
        var Filtercolumns = ['Filter Values',''];
        var Filtercolumns1 = ['From Date:','To Date:','Foreign Broker:','Local Broker:','Side:','Report Date:','Generated By:'];
        var FiltercolumnsVal = [this.from_date,this.to_date, this.selected_foreign_broker_name,  this.selected_local_broker_name,this.selected_side,this.current_date,this.user_name1];
        const FiltertableData = [];
              for (var i = 0; i < Filtercolumns1.length; i++) {
                FiltertableData.push([
                  Filtercolumns1[i],
                  FiltercolumnsVal[i],
                  Filtercolumns1[i],
                  FiltercolumnsVal[i],
                  Filtercolumns1[i],
                  FiltercolumnsVal[i],
                  Filtercolumns1[i],
                  FiltercolumnsVal[i],
                  Filtercolumns1[i],
                  FiltercolumnsVal[i],
                ]);
              }
              doc.autoTable(Filtercolumns, FiltertableData, {margin: {top: 80}});
      
//header
              var header = function(data) {
              doc.setFontSize(18);
              doc.setTextColor(40);
              doc.setFontStyle('normal'); 
              doc.text("DMA Trades (Filled Orders) Report", data.settings.margin.right, 50); 
              //doc.text("Date", data.settings.margin.right, 55); 

                };
  
          var options = {
            beforePageContent: header,
            margin: {
              top: 80
            }, 
          };
          var columns = ['Order Date','Trade Date','Client','JCSD#','Client Order#','Market Order#','Comment(text)','Side','Symbol','Fill Qty','Currency','Fill Price','Fill Value'];
          doc.autoTable(columns,tableData,options); 
          countTabelData.push([
          tableData.length + ' Trades', '','','',tableData.length + ' Clients',[...new Set(ClientOrders)].length +' Orders','','','',[...new Set(Symbols)].length + ' Symbol',sumFQty+ ' ',[...new Set(Currency)],sumFPrice+ ' ', sumFValue+ ' ',
          ]);

          var columns1 = ['Totals','','','','','','','','','Symbol','Fill Qty','Currency','Fill Price','Fill Value'];
          doc.autoTable(columns1,countTabelData,options); 
          doc.save("DMA-TRADE-FILL-REPORTS.pdf");
    },
    exportExcel()
    { 
        
       const tableData = this.visibleRows.map((r) =>
      
       this.ExcelExportData.push( {
 
          "Order Date": r.orderDate,
          "Trade Date": r.tradeDate, 
          "Client": r.clientName,
          "JCSD#":  r.JCSD,
          "Client Order#": r.client_order_number,  
          "Market Order#": r.mrktOrdNo,
          "Comment (Text)":r.comments,
          "Side":  r.side, 
          "Symbol": r.symbol,
          "Fill Qty": r.FillQty,
          "Currency": r.currency,   
          "Fill Price": r.FillPrice,
          "Fill Value": r.FilledValue
          },
          ) 
      );

       var worksheet_name = "DMA TRADE REPORT";
       var excelFileData = XLSX.utils.json_to_sheet(this.ExcelExportData);          
       var wb = XLSX.utils.book_new();    
       XLSX.utils.book_append_sheet(wb, excelFileData,worksheet_name);   
       XLSX.writeFile(wb,'DMA-TRADE-REPORT.xlsx'); 
        
   }
   },
   async mounted() {
    //console.log(this.execution_reports);
    await Promise.all([
      this.getForeignBrokers(),
      this.getLocalBrokers(),
      this.getReports(),  
      this.getSideDetails(),    
    ]);

    axios.put(`/nv9w8yp8rbwg4t`).then(response => {
      let data = response.data; 
      this.user_name1 = data.name; 
    });
    
          //console.log(r);
  },
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
