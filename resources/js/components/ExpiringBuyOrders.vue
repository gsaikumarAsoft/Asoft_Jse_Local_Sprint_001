<template>
  <div>
    <head-nav></head-nav>

    <div class="container-fluid" style="margin-top: 10px;">
      <b-container class="bv-example-row">
            <b-row>
              <b-col width="0px">
                <template>
                    <label for="display-expiry-date" >Expiration Date (UTC): </label>
                      <b-form-input
                        type="date"                        
                        id="expiry-datepicker" 
                        v-model="expiring_orders_date" 
                        @change="selectExpiryDate()"
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
                <label for="fetch-orders" >Click to retrieve order: </label></br>
                <b-button 
                  id="fetch-orders"
                  @click="fetchOrders" >Fetch Orders</b-button>
              </b-col>
            </b-row>
      </b-container>
    </div> 
    <div class="container-fluid" style="margin-top: 10px;">
      <div class="content">

        <b-card title="Expiring BUY Orders">
          <b-form inline>
            <b-pagination
              v-model="currentPage"
              :total-rows="rows"
              :per-page="perPage"
              aria-controls="local-brokers"
              style="margin-bottom:0;"
            ></b-pagination>
            <b-button 
              @click="selectAll" >Select All</b-button>
            <b-button 
              @click="unSelectAll" >Clear Selection</b-button>
            <b-button 
              v-b-modal.modal-1
              @click="create = true">Batch Cancel Request</b-button>
          </b-form>  
        </b-card>
          <b-table
            striped
            hover
            show-empty
            :empty-text="'No Expiring Orders were found. Try fetching orders for another expiration date and foreign Broker.'"
            id="expiring-orders"
            :items="expiring_orders"
            :fields="fields"
            :per-page="perPage"
            :current-page="currentPage"
            @row-clicked="selectOrder"
          >

            <template slot="index" slot-scope="row">
              {{ row }}              
            </template>
            
          </b-table>

        <b-modal id="modal-1" 
          :title="modalTitle" 
          ok-only
          ok-title="Close"
          :no-close-on-backdrop=true
          :no-close-on-esc=true 
          >
          
          <form ref="form" @submit.stop.prevent="handleSubmit">
            <p class="my-4">Orders selected for Batch Cancel Request processing ({{ selected_orders.length }})</p>
            <template>
              <div>
                <b-form-select v-model="current_order" :options="selected_orders" :select-size="4"></b-form-select>
                <div class="mt-3">Selected Order: <strong>{{ current_order }}</strong></div>
              </div>
            </template>
            <b-button @click="confirmCancelBatchHandler" >Request Cancellation(s) Now</b-button>
            <div class="text-center" style="float:right;" >
            </div>
            <p class="my-4">({{ cancel_requested_orders.length }}) Cancel Requests Sent </p>
            <template>
              <div>
                <b-form-select v-model="cancel_requested_order" :options="cancel_requested_orders" :select-size="4"></b-form-select>
              </div>
            </template>
          </form>
        </b-modal>
        
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import axios from "axios";
import headNav from "./partials/Nav.vue";
import checkErrorMixin from "./../mixins/CheckError.js";
export default {
  components: {
    headNav
  },
   
  mixins: [checkErrorMixin],
   
  data() {    
      const now = new Date();
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    return {
      create: false,
      expiring_orders: [],
      foreign_brokers: [],
      foreign_broker_options: [],      
      selected_foreign_broker: "",
      selected_foreign_broker_name: "",
      process_status: 0,
      expiring_orders_date: today.getFullYear().toString()+"-"+('0' + (today.getMonth()+1)).slice(-2)+"-"+('0' + (today.getDate())).slice(-2),
      selected_orders: [],
      current_order: "",
      selected_orders_count: 0,
      processed_orders_count: 0,
      cancel_requested_orders: [],
      order_batch: [],
      cancel_requested_order: "",
      can_trade_options: [        
          { text: 'Yes', value: 'Yes' },
          { text: 'No', value: 'No' },
      ],
      broker: { admin_can_trade: 'No'},
      perPage: 3,
      currentPage: 1,
      fields: [
        {
          key: "selected",
          sortable: true,
          label: "Selected"
        },
        {
          key: "order_status",
          sortable: true,
          label: "Status"
        },
        {
          key: "order_date",
          sortable: true,
          label: "Order Date"
        },
        {
          key: "order_time_in_force",
          sortable: true,
          label: "Time-In-Force"
        },
        {
          key: "expiration_date",
          sortable: true,
          label: "Expires On"
        },
        {
          key: "local_broker",
          sortable: true,
          label: "From Broker"
        },        
        {
          key: "client_order_id",
          sortable: true,
          label: "Client Order #"
        },
        {
          key: "market_order_number",
          sortable: true,
          label: "Market Order#"
        },
        {
          key: "order_symbol",
          sortable: true,
          label: "Symbol"
        },
        {
          key: "order_quantity",
          sortable: true,
          label: "Order Quantiy"
        },
        {
          key: "jcsd",
          sortable: true,
          label: "JCSD"
        },
        {
          key: "order_type",
          sortable: true,
          label: "Type"
        }
      ],
      modalTitle: "Cancel Expiring Order",
      formReady: true,
      nameState: null
    };
  },
  computed: {
    rows() {
      return this.expiring_orders.length;
    }
  },
  watch: {
    create: function(data) {
      if (data) {
        this.modalTitle = "Batch Cancel Expiring Orders";
      } else {
        this.modalTitle = "View Cancel Request Batch Ststua";
      }
    },
    processed_orders_count: function(data) {
      if (this.process_status==1) {
        console.log(data +"/"+ this.selected_orders_count +" orders processed");
        if (data == this.selected_orders_count) {
            this.$swal("Cancel Request Batch Completed!", "Expiring Order Cancel Requests Sent!", "success");
            this.process_status=0;
        }
      }
    }
  },
  methods: {
      getTodayFormatted() {
        return this.today.toLowerCase()
      },
    selectForeignBroker() {
      this.foreign_broker_options.forEach(broker => 
            {
               if(broker.value == this.selected_foreign_broker) 
                {this.selected_foreign_broker_name= broker.text;};
            }
          );     
    },
    selectExpiryDate() {
    },
    selectOrder(o, index, value) {
      if (o.selected=="Yes") {
        o.selected = "No";
        this.selected_orders.pop(o.client_order_id);
      } else {
        o.selected = "Yes";
        this.selected_orders.push(o.client_order_id);
      }
    },
    selectAll() {
      this.selected_orders = [];
      this.expiring_orders.forEach( order => 
        {
          this.selected_orders.push(order.client_order_id);
          order.selected ="Yes"
        });
    },
    unSelectAll() {
      this.selected_orders = [];
      this.expiring_orders.forEach(order => order.selected ="No");
    }, 
    async getForeignBrokers() {
      ({ data: this.foreign_brokers } = await axios.get("foreign-brokers"));
      this.selected_orders = [];
      this.foreign_brokers.forEach(foreign_broker => 
        {
          this.foreign_broker_options.push({value: foreign_broker.user.id, text: foreign_broker.user.name });
          if(this.selected_foreign_broker == "") {
            this.selected_foreign_broker = foreign_broker.user.id;
            this.selected_foreign_broker_name= foreign_broker.user.name;
          }
        }
      );
      await this.fetchOrders();
    },
    async confirmCancelBatchHandler() {
      const result = await this.$swal({
        title: "",
        icon: "info",
        html: `Are you sure you want to send <b>Cancel Requests</b> for this batch of <b>(${this.selected_orders.length})</b> orders? `,
        showCloseButton: true,
        showCancelButton: true, 
        cancelButtonColor: "#DD6B55",
        confirmButtonText: "Proceed",
        confirmButtonAriaLabel: "proceed",
        cancelButtonText: "Go back!",
        cancelButtonAriaLabel: "cancel",
      }); 
        this.cancel_requested_orders = [];
        this.order_batch = [];
        this.selected_orders_count = this.selected_orders.length;
        this.processed_orders_count = 0;
        this.process_status = 1;
        await this.processCancelRequestBatch();
    },
    async processCancelRequestBatch() {      
      this.$swal.fire({
        title: `Requesting Cancellation for [${this.selected_foreign_broker_name}] orders expiring on [${this.expiring_orders_date}]`,
        html: "Please wait while we process your Cancel Request Batch...",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        }
      });      
      this.selected_orders.forEach(order_id => this.order_batch.push(order_id));
      this.order_batch.forEach(order_id => this.sendCancelRequest(order_id));
    },
    sendCancelRequest(order_id) {
      //console.log('Cancelling ['+ this.selected_broker_id + '] Orders Expiring on ', this.expiring_orders_date);
      this.cancelOrder(order_id);
      this.selected_orders.pop(order_id);
    },
    checkFormValidity() {
      const valid = this.$refs.form.checkValidity();
      this.nameState = valid;
      return valid;
    },
    async resetModal() {
      this.create = false;
      this.broker = {};
    },
    async fetchOrders() {
      await this.getOrders();
    },
    async getOrders() {
      this.expiring_orders = [];
      this.selected_orders = [];
      ({ data: this.expiring_orders } = await axios.get("expiring-orders-for/"+this.selected_foreign_broker+"/"+this.expiring_orders_date)); 
    },
    add() {
      this.create = true;
    },
    async cancelOrder(id) {      
      this.$swal.fire({
        title: `Sending Cancel Requests for [${this.selected_foreign_broker_name}] orders that expire on [${this.expiring_orders_date}]`,
        html: "Submitting cancel requests, please wait...",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        }
      });
      try {
        await axios.delete(`destroy-broker-client-order/${id}`);
        this.cancel_requested_orders.push(id + " Submitted");
        this.processed_orders_count = this.cancel_requested_orders.length;
      } catch (error) {
        this.checkCancelRequestError('Failure submitting Cancel Request for Order '+id+': ' +error);
      }
    }
  },
  async mounted() {
    //console.log("date", this.expiring_orders_date.toString());
    await Promise.all([
      this.getForeignBrokers(),
    ]);
  }
};
</script>
