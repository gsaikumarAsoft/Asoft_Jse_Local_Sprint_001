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
        <b-card title="Fillable Expired BUY Orders">
          <b-form inline>
            <b-pagination
              v-model="currentPage"
              :total-rows="rows"
              :per-page="perPage"
              aria-controls="expired-orders"
              style="margin-bottom:0;"
            ></b-pagination>
          </b-form>  
        </b-card>
        <b-table
            responsive            
            striped
            hover
            show-empty
            :empty-text="'No Fillable Expired Buy Orders were found. Try fetching orders for another expiration date and foreign broker.'"
            id="expired-orders"
            :items="expiring_orders"
            :fields="fields"
            :per-page="perPage"
            :current-page="currentPage"
            @row-clicked="brokerOrderHandler"
          >
            <template slot="index" slot-scope="row">
              {{ row }}              
            </template>   
        </b-table>
        <b-modal id="modal-1" 
          :title="modalTitle" 
          ok-title="Fill Now" 
          :no-close-on-backdrop=true
          :no-close-on-esc=true 
          @hidden="resetModal" 
          @ok="confirmFillOrderHandler"
          >          
          <form ref="form" @submit.stop.prevent="handleSubmit">
            <b-container class="bv-example-row">
              <b-row>
                <b-col>
                  <b-form-group label="Order#:" label-for="order-number" invalid-feedback="Symbol is required">
                    <b-form-input 
                      id="order-number" 
                      v-model="selected_order_id" 
                      :state="nameState" 
                      :disabled="true"
                      required></b-form-input>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group label="Fill Date (UTC): " label-for="order-id" invalid-feedback="Fill date is invalid">
                    <b-form-input
                      type="date"                        
                      id="expiry-datepicker" 
                      v-model="order_fill_date" 
                      @change="selectExpiryDate()"
                      required
                      >
                    </b-form-input> 
                  </b-form-group>
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  <b-form-group label="Symbol:" label-for="order-symbol" invalid-feedback="Symbol is required">
                    <b-form-input 
                      id="order-symbol" 
                      v-model="selected_order_symbol" 
                      :state="nameState" 
                      :disabled="true"
                      required></b-form-input>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group label="Quantity:" label-for="order-quantity" invalid-feedback="Quanity is required">
                    <b-form-input 
                      id="order-quantity" 
                      v-model="selected_order_quantity" 
                      :state="nameState" 
                      :disabled="!this.formReady"
                      required></b-form-input>
                  </b-form-group>
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  <b-form-group label="Funds Remaining:" label-for="order-remaining">
                    <b-form-input 
                      id="order-remaining" 
                      v-model="selected_order_remaining" 
                      :state="nameState" 
                      :disabled="true" 
                      required></b-form-input>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group label="Price:" label-for="order-price" invalid-feedback="Price is required">
                    <b-form-input 
                      id="order-price" 
                      v-model="selected_order_price" 
                      :state="nameState" 
                      :disabled="!this.formReady"
                      required></b-form-input>
                  </b-form-group>
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  <b-form-group label="Manual Fill Comment:" label-for="order-comment" invalid-feedback="Price is required">
                    <b-form-input 
                      id="order-comment" 
                      v-model="order_fill_comment" 
                      :state="nameState" 
                      :disabled="!this.formReady"
                      required></b-form-input>
                  </b-form-group>
                </b-col>
              </b-row>
            </b-container>
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
      expiring_orders_date: today.getFullYear().toString()+"-"+('0' + (today.getMonth()+1)).slice(-2)+"-"+('0' + (today.getDate())).slice(-2),
      //expiring_orders_date: "2021-03-23",
      selected_orders: [],
      selected_order_id: "",
      selected_order_symbol: "",
      selected_order_quantity: 0,
      selected_order_orig_quantity: 0,
      selected_order_remaining: 0.00,
      selected_order_price: 0.00,
      order_fill_date: today.getFullYear().toString()+"-"+('0' + (today.getMonth()+1)).slice(-2)+"-"+('0' + (today.getDate())).slice(-2),
      order_fill_comment: "",
      selected_order_currency: "", 
      current_order: "",
      cancel_requested_orders: [],
      order_batch: [],
      cancel_requested_order: "",
      can_trade_options: [        
          { text: 'Yes', value: 'Yes' },
          { text: 'No', value: 'No' },
      ],
      order_fill: {},
      perPage: 3,
      currentPage: 1,
      fields: [
        /*{
          key: "selected",
          sortable: true,
          label: "Selected"
        },*/
        
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
          key: "price",
          sortable: true,
          label: "Price"
        },
        {
          key: "jcsd",
          sortable: true,
          label: "JCSD"
        },
        {
          key: "remaining",
          sortable: true,
          label: "Remaining"
        }
        /*,
        {
          key: "order_type",
          sortable: true,
          label: "Type"
        }
        */
      ],
      modalTitle: "",
      formReady: true,
      nameState: null
    };
  },
  computed: {
    rows() {
      //return this.local_brokers.length;
      return this.expiring_orders.length;
    }
  },
  watch: {
  },
  methods: {   
      async brokerOrderHandler(o) {
        
        var customBtnActions = (btnId) => {
          this.$swal.close();
        };
        const result = await this.$swal({
          title: `Order #: ${o.client_order_id}`,
          html: `This order expired on <b>${o.expiration_date}</b>. Please choose an option:`,
          icon: "question",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Fill Expired Order",
          cancelButtonText: "Exit",
        });

      if (result.value) {
        //console.log("You chose to FILL order", o.client_order_id);
        this.selected_order_id = o.client_order_id;
        this.selected_order_symbol = o.order_symbol;
        this.selected_order_quantity = o.quantity;
        this.selected_order_orig_quantity = o.order_quantity;
        this.selected_order_remaining = o.remaining;
        this.selected_order_price = o.price;
        this.order_fill_date = o.expiration_date;
        this.order_fill_comment = "";
        this.selected_order_currency = o.currency;
        this.modalTitle = "Fill Expired Order "+this.selected_order_id;
        this.$bvModal.show("modal-1");
      }
      if (result.dismiss === "cancel") {
        //this.destroy(o.clordid);
        //console.log("You chose to exit the options screen for Order:", o.client_order_id);
      }
    },
      getTodayFormatted() {
        return this.today.toLowerCase();
      },
    onPreviewClick(value, index, item) {
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
      //console.log("selected_expiry_date", this.expiring_orders_date);
    },
    selectOrder(o, index, value) {
      if (o.selected=="Yes") {
        o.selected = "No";
        this.selected_orders.pop(o.client_order_id);
      } else {
        o.selected = "Yes";
        this.selected_orders.push(o.client_order_id);
      }
      //console.log("selected orders", this.selected_orders);
    },
    selectAll() {
      this.selected_orders = [];
      this.expiring_orders.forEach(order => order.selected ="Yes");
      this.expiring_orders.forEach( order => this.selected_orders.push(order.client_order_id));
      //console.log("selected orders", this.selected_orders);
    },
    unSelectAll() {
      this.selected_orders = [];
      this.expiring_orders.forEach(order => order.selected ="No");
      //console.log("selected orders", this.selected_orders);
    },  
    async getForeignBrokers() {
      ({ data: this.foreign_brokers } = await axios.get("foreign-brokers"));
      //this.foreign_brokers.forEach(foreign_broker => this.foreign_broker_options.push({value: foreign_broker.user.id, text: foreign_broker.user.name })); 
      this.foreign_brokers.forEach(foreign_broker => 
        {
          this.foreign_broker_options.push({value: foreign_broker.user.id, text: foreign_broker.user.name });
          if(this.selected_foreign_broker == "") {
            this.selected_foreign_broker = foreign_broker.user.id;
          }
        }
      );
      await this.fetchOrders();
    },
    async confirmFillOrderHandler() {
      const result = await this.$swal({
        title: "",
        icon: "info",
        html: `Are you sure you want to send <b>Cancel Requests</b> for this batch of <b>(${this.selected_orders.length})</b> orders? `,
        showCloseButton: true,
        showCancelButton: true,
        cancelButtonColor: "#DD6B55",
        confirmButtonText: "Proceed",
        confirmButtonAriaLabel: "proceed",
        cancelButtonText: "Cancel",
        cancelButtonAriaLabel: "cancel"
      }); 
      this.fillOrder();
    },
    checkFormValidity() {
      const valid = this.$refs.form.checkValidity();
      this.nameState = valid;
      return valid;
    },
    async resetModal() {
      //this.create = false;
    },
    async fetchOrders() {
      await this.getOrders();
    },
    async getOrders() {
      this.expiring_orders = [];
      this.selected_orders = [];
      ({ data: this.expiring_orders } = await axios.get("expiring-orders-for/"+this.selected_foreign_broker+"/"+this.expiring_orders_date)); 
    },
    async getExpiredOrders() {
      ({ data: this.expiring_orders } = await axios.get("expiring-orders")); //.then(response => {
      this.expiring_orders.forEach( order => this.selected_orders.push(order.client_order_id));
    },
    async fillOrder() {
      this.$swal.fire({
        title: `Filling order [${this.selected_order_id}] which expired on [${this.expiring_orders_date}]`,
        html: "Updating order status, please wait...",
        timerProgressBar: true,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        }
      });
      //this.order_fill;
      Object.assign(this.order_fill, {order_id: this.selected_order_id}); 
      Object.assign(this.order_fill, {order_symbol: this.selected_order_symbol}); 
      Object.assign(this.order_fill, {quantity: this.selected_order_quantity}); 
      Object.assign(this.order_fill, {order_quantity: this.selected_order_orig_quantity}); 
      Object.assign(this.order_fill, {order_price: this.selected_order_price}); 
      Object.assign(this.order_fill, {fill_date: this.order_fill_date}); 
      Object.assign(this.order_fill, {comment: this.order_fill_comment}); 
      try {
        await axios.post("fill-expired-order", this.order_fill);
        //await this.getExpiredOrders();
        this.$swal("Expired order #:"+this.selected_order_id, " successfully FILLED!", "success");
      } catch (error) {
        this.checkManualFillError(error);
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
