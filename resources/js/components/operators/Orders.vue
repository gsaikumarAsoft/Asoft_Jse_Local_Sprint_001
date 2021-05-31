<template v-slot:custom-foot="row">
  <div>
    <head-nav></head-nav>
    <div class="container-fluid" style="margin-top: 20px;">
      <div class="content">
        <b-card title="Current Orders" v-if="permissions.indexOf('read-broker-order') !== -1">
          <div class="float-right" style="margin-bottom: 15px; padding-bottom: 15px;">
            <b-input
              id="search_content"
              v-model="filter"
              type="text"
              placeholder="Filter Orders..."
              class="mb-2 mr-sm-2 mb-sm-0"
            ></b-input>
          </div>


        <b-pagination
          v-if="permissions.indexOf('read-broker-order') !== -1"
          v-model="currentPage"
          :total-rows="rows"
          :per-page="perPage"
          aria-controls="orders-table"
          style="padding-top:15px;"
        ></b-pagination>
        <b-button
          v-if="permissions.indexOf('create-broker-order') !== -1"
          v-b-modal.jse-new-order
          @click="add"
          >Create New Order</b-button>        
        <b-button @click="exportOrders">Export Orders (PDF)</b-button>

          <b-table
            responsive
            v-if="permissions.indexOf('read-broker-order') !== -1"
            ref="selectedOrder"
            :empty-text="'No orders found. Create an Order below.'"
            id="orders-table"
            :items="broker_client_orders"
            :per-page="perPage"
            :current-page="currentPage"
            :filterIncludedFields="filterOn"
            striped
            hover
            :fields="fields"
            v-model="visibleRows" 
            :filter="filter"
            @row-clicked="brokerOrderHandler"
            @filtered="onFiltered"
          >
            <!-- <template :v-if="data.item.max_floor" v-slot:cell(max_floor)="data">
              <p>Iceberg Order</p>
            </template>-->
          </b-table>
          <p v-if="permissions.indexOf('read-broker-order') == -1" class="lead">
            You currently do not have permisions to view orders within the
            system. Please speak with your Broker Admin to be granted access to this area
          </p>
        </b-card>
        <b-modal
          :hide-footer="!create"
          id="jse-new-order"
          size="xl"
          ref="modal"
          @ok="handleJSEOrder"
          @hidden="resetModal"
          :title="modalTitle"
        >
          <form ref="form">
            <b-container class="bv-example-row">
              <b-row>
                <b-col>
                  <b-form-group label="Trading Account" label-for="broker-input">
                    <b-form-select
                      v-model="order.trading_account"
                      :options="broker_trading_account_options"
                      class="mb-3"
                      :disabled="disabled == 1"
                      @change="currencyHandler(order.trading_account)"
                    >
                      <template v-slot:first>
                        <b-form-select-option :value="null">
                          -- Please select a Trading
                          Account--
                        </b-form-select-option>
                      </template>
                      <!-- <b-form-select-option
                        v-for="b in local_brokers_list"
                        :value="b.value"
                        :key="b.id"
                      >{{ b.text }}</b-form-select-option>-->
                    </b-form-select>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group label="Client Account" label-for="broker-input">
                    <b-form-select
                      v-model="order.client_trading_account"
                      :options="client_trading_account_options.trading_account"
                      class="mb-3"
                      :disabled="disabled == 1"
                    >
                      <template v-slot:first>
                        <b-form-select-option :value="null">
                          -- Please select a Client
                          Account--
                        </b-form-select-option>
                      </template>
                      <b-form-select-option
                        v-for="b in client_trading_account_options"
                        :value="b.id"
                        :key="b.id"
                      >
                        JCSD-{{ b.jcsd }} :
                        {{ b.name }} "Investor"
                      </b-form-select-option>
                    </b-form-select>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group
                    id="input-group-1-client-order-number"
                    label="Client Order Number"
                    label-for="input-1-client-order-number"
                  >
                    <b-form-input
                      id="input-10"
                      v-model="order.client_order_number"
                      type="text"
                      placeholder="Enter Client Order Number"
                      :disabled="1 || 3"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group
                    id="input-group-1-market-order-number"
                    label="Market Order Number"
                    label-for="input-1-market-order-number"
                  >
                    <b-form-input
                      id="input-1"
                      v-model="order.market_order_number"
                      type="text"
                      placeholder="Enter Market Order Number"
                      disabled
                    ></b-form-input>
                  </b-form-group>
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  <b-form-group label="Symbol" label-for="order-input">
                    <multiselect
                      placeholder="Select a symbol"
                      v-model="order.symbol"
                      label="text"
                      :options="symbols"
                      :disabled="disabled == 1"
                    ></multiselect>
                  </b-form-group>
                </b-col>

                <b-col>
                  <b-form-group label="Currency" label-for="broker-input">
                    <multiselect
                      placeholder="Select a currency"
                      v-model="order.currency"
                      label="text"
                      :options="currencies"
                      :disabled="1 || 3"
                    ></multiselect>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group label="Value" label-for="order-input">
                    <b-input-group size="md" prepend="$">
                      <b-form-input
                        id="value-input1"
                        v-model="order.value"
                        :state="nameState"
                        type="number"
                        :disabled="disabled == 1"
                      ></b-form-input>
                    </b-input-group>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group label="Stop Price" label-for="limit-input">
                    <b-input-group size="md" prepend="$">
                      <b-form-input
                        id="value-input"
                        v-model="order.stop_price"
                        :state="nameState"
                        type="number"
                        :disabled="disabled == 1"
                      ></b-form-input>
                    </b-input-group>
                  </b-form-group>
                </b-col>
                <!-- <b-col v-show="order.stop_price">
                  <b-form-group
                    label="StopPx"
                    label-for="limit-input"
                    invalid-feedback="Limit is required"
                  >
                    <b-input-group size="md" prepend="$">
                      <b-form-input
                        id="value-input"
                        v-model="order.stop_px"
                        :state="nameState"
                        type="number"
                      ></b-form-input>
                    </b-input-group>
                  </b-form-group>
                </b-col>-->
              </b-row>
            </b-container>
            <b-container class="bv-example-row">
              <b-row>
                <b-col>
                  <b-form-group label="Quantity" label-for="broker-input">
                    <b-input-group size="md">
                      <b-form-input
                        id="quantity-input"
                        v-model="order.quantity"
                        :state="nameState"
                        :disabled="disabled == 1"
                      ></b-form-input>
                    </b-input-group>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group label="Price" label-for="order-input">
                    <b-input-group size="md" prepend="$">
                      <b-form-input
                        id="price-input"
                        v-model="order.price"
                        :state="nameState"
                        type="number"
                        :disabled="disabled == 1"
                      ></b-form-input>
                    </b-input-group>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group label="Side" label-for="type-input">
                    <multiselect
                      placeholder="Select a Side"
                      v-model="order.side"
                      label="text"
                      :options="side_options"
                      :disabled="disabled == 1"
                    ></multiselect>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group label="Order Type" label-for="type-input">
                    <multiselect
                      placeholder="Select an Order Type"
                      v-model="order.order_type"
                      label="text"
                      :options="order_types"
                      disabled
                    ></multiselect>
                  </b-form-group>
                </b-col>
              </b-row>
            </b-container>
            <b-container class="bv-example-row">
              <b-row>
                <b-col>
                  <b-form-group label="Handling Instructions" label-for="broker-input">
                    <multiselect
                      placeholder="Select an Instruction"
                      v-model="order.handling_instructions"
                      label="text"
                      :options="handling_options"
                      disabled
                    ></multiselect>
                    <!-- <b-form-select
                      v-model="order.local_broker"
                      :options="local_brokers_list.name"
                      class="mb-3"
                    >
                      <template v-slot:first>
                        <b-form-select-option :value="null" disabled>
                          -- Please Select Handling Instructions
                          --
                        </b-form-select-option>
                      </template>
                      <b-form-select-option
                        v-for="b in local_brokers_list"
                        :value="b.handling_instructions"
                        :key="b.id"
                      >{{ b.text }}</b-form-select-option>
                    </b-form-select>-->
                  </b-form-group>
                </b-col>
                <!-- <b-col>
                  <b-form-group
                    label="Option Type"
                    label-for="broker-input"
                    invalid-feedback="Option Type is required"
                  >
                    <multiselect
                      placeholder="Select an Option Type"
                      v-model="order.option_type"
                      order="order_option_inputs[][option_type]"
                      label="text"
                      :options="option_types"
                    ></multiselect>
                  </b-form-group>
                </b-col>-->
                <b-col>
                  <b-form-group label="Time In Force" label-for="broker-input">
                    <multiselect
                      placeholder="Select a Time In Force"
                      v-model="order.time_in_force"
                      order="order_option_inputs[][option_type]"
                      label="text"
                      :options="time_in_force"
                      :disabled="disabled == 1"
                    ></multiselect>
                  </b-form-group>
                </b-col>
                <b-col v-show="expiration">
                  <label for="example-datepicker">Expiration Date</label>
                  <b-form-datepicker
                    id="example-datepicker"
                    v-model="order.expiration_date"
                    class="mb-2"
                    :disabled="disabled == 1"
                  ></b-form-datepicker>
                </b-col>
              </b-row>
            </b-container>
            <b-container class="bv-example-row">
              <label>Iceberg Options</label>
              <b-row>
                <b-col>
                  <!-- Iceberg Options -->
                  <!-- [ Display Range ] -->
                  <b-form-group label="Display Range" label-for="order-input">
                    <b-input-group size="md" prepend="+-">
                      <b-form-input
                        id="display_range-input"
                        v-model="order.display_range"
                        :state="nameState"
                        type="number"
                        :disabled="disabled == 1"
                      ></b-form-input>
                    </b-input-group>
                  </b-form-group>
                </b-col>
                <b-col>
                  <!-- Iceberg Options -->
                  <!-- [ Display Range ] -->
                  <b-form-group label="Max Floor" label-for="order-input">
                    <b-input-group size="md" prepend="^">
                      <b-form-input
                        id="max_floor-input"
                        v-model="order.max_floor"
                        :state="nameState"
                        type="number"
                        :disabled="disabled == 1"
                      ></b-form-input>
                    </b-input-group>
                  </b-form-group>
                </b-col>
                <b-col>
                  <!-- [ Max Floor ] -->
                  <!-- <b-card title="Order Options">
                    <p v-if="order_option_inputs.length < 1">
                      <b>Create New Option</b>
                      <b-icon-plus @click="addOption(k)" variant="success" font-scale="2"></b-icon-plus>
                    </p>
                    <b-container>
                      <b-row class="mb-2" v-for="(order, k) in order_option_inputs" :key="k">
                        <b-col cols="5">
                          <b-form-group
                            label="Option Type"
                            label-for="broker-input"
                            invalid-feedback="Option Type is required"
                          >
                            <multiselect
                              placeholder="Select an Option Type"
                              v-model="order.option_type"
                              order="order_option_inputs[][option_type]"
                              label="text"
                              :options="option_types"
                            ></multiselect>
                          </b-form-group>
                        </b-col>
                        <b-col cols="5">
                          <b-form-group
                            label="Option Value"
                            label-for="broker-input"
                            invalid-feedback="Option Value is required"
                          >
                            <multiselect
                              v-if="!order_option_input"
                              placeholder="Select an Option "
                              name="order_option_inputs[][option_value]"
                              v-model="order.option_value"
                              label="text"
                              :options="option_values"
                            ></multiselect>
                            <b-form-input
                              v-if="order_option_input"
                              v-model="order.option_value"
                              placeholder="Enter an option value"
                            ></b-form-input>
                          </b-form-group>
                        </b-col>
                        <b-col cols="2" class="text-center" style="margin-top: 30px">
                          
                          <b-icon-x @click="removeOption(k)" variant="danger" font-scale="2"></b-icon-x>
                          <b-icon-plus @click="addOption(k)" variant="success" font-scale="2"></b-icon-plus>
                          <b-icon icon="arrow-up" font-scale="2" @click="showOptionValueInput"></b-icon>
                          
                        </b-col>
                      </b-row>
                  </b-container>-->
                  <!-- <b-button
                      href="#"
                      v-if="order_option_inputs.length > 0"
                      @click="saveOrderToJSON"
                      variant="primary"
                    >Save Template</b-button>
                    <b-button
                      v-if="!template"
                      href="#"
                      @click="template = true"
                      variant="primary"
                    >Load Template</b-button>
                    <input
                      v-if="template"
                      type="file"
                      id="file"
                      ref="file"
                      accept="json/*"
                      @change="readJSONTemplate()"
                    />
                    <b-button
                      v-if="template"
                      href="#"
                      @click="importOrderFromJSON"
                      variant="primary"
                    >Upload</b-button>
                  </b-card>-->
                  <!-- <b-card class="mt-3" header="JSE Order Data Result">
                    <b-col>
                      <pre class="m-0">{{order}}</pre>
                    </b-col>
                    <b-col>
                      <pre class="m-0">{{order_option_inputs}}</pre>
                    </b-col>
                  </b-card>-->
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
import moment from "moment";
import saveAs from "file-saver";
import Multiselect from "vue-multiselect";
import axios from "axios";
import headNav from "./../partials/Nav.vue";
import currenciesMixin from "../../mixins/Currencies.js";
import checkErrorMixin from "../../mixins/CheckError.js";
// import jsonfile from 'jsonfile';
import jsPDF from "jspdf";
export default {
  props: ["orders", "client_accounts", "local_brokers", "foreign_brokers"],
  components: {
    headNav,
    Multiselect,
  },
  mixins: [currenciesMixin, checkErrorMixin],
  data() {
    return {
      new_order: false,
      filter: null,
      expiration: false,
      disabled: 0,
      modalTitle: "New Order",
      permissions: [],
      order_template_data: [],
      file: "",
      order_option_input: false,
      filterOn: ["clordid", "side", "jcsd", "client_name", "symbol", "order_date", "order_type.text", "time_in_force", "currency", "order_status", "price", "max_floor"],
      template: false,
      broker_trading_account_options: [],
      client_trading_account_options: [],
      order_option_inputs: [],
      local_brokers_list: [],
      foreign_brokers_list: [],
      local_broker: [],
      foreign_broker: [],
      selected: null,
      create: false,
      order: {},
      fields: [
        // { key: "handling_instructions", sortable: true, },
        { key: "order_date", sortable: true },
        { key: "client_name", label: "Client", sortable: true },
        { key: "jcsd", label: "JCSD#", sortable: true },
        { key: "clordid", label: "Client Order#", sortable: true },
        { key: "order_status",
          label: "Status",
          sortable: true,         
          filterByFormatted: true, 
          formatter: (value, key, item) => {
            // return value;
            if (value === "0") {
              return "NEW";
            } else if (value === "Submitted") {
              return "SUBMITTED";
            } else if (value === "Failed") {
              return "FAILED";
            } else if (value === "-1") {
              return "PENDING SENT";
            } else if (value === "1") {
              return "PARTIALLY FILLED";
            } else if (value === "2") {
              return "FILLED";
            } else if (value === "4") {
              return "CANCELLED";
            } else if (value === "6") {
              return "PENDING CANCEL";
            } else if (value === "5") {
              return "REPLACED";
            } else if (value === "C") {
              return "EXPIRED";
            } else if (value === "Z") {
              return "PRIVATE";
            } else if (value === "U") {
              return "UNPLACED";
            } else if (value === "x") {
              return "INACTIVE";
            } else if (value === "8") {
              return "REJECTED";
            } else {
              return value;
            }
          },
        },
        { key: "side",
          label: "Side",
          sortable: true,       
          filterByFormatted: true, 
          formatter: (value, key, item) => {
            if (value) {
              var data = JSON.parse(item.side) || {};
              var s = data;

              return s.text;
            } else {
              return "N/A";
            }
            // return symbol.text;
          },
        },
        {
          key: "symbol.text",
          label: "Symbol",
          sortable: true,       
          filterByFormatted: true, 
          formatter: (value, key, item) => {
            const data = JSON.parse(item.symbol) || {};
            return data.text ;
            // return symbol.text;
          },
        },
        { key: "order_quantity", label: "Qty", sortable: true },
        {
          key: "time_in_force",
          label: "Time In Force",
          sortable: true,       
          filterByFormatted: true, 
          formatter: (value, key, item) => {
            if (value) {
              //var data = JSON.parse(item.time_in_force);
              var data = JSON.parse(item.time_in_force) || {};
              return data.value;
            } else {
              return "N/A";
            }
            // return symbol.text;
          },
        },
        { key: "order_type.text",
          label: "Type",
          sortable: true,       
          filterByFormatted: true, 
          formatter: (value, key, item) => {
            var type = JSON.parse(item.order_type) || {};
            var order = JSON.parse(type) ||'';
            return order.text;
          },
        },
        { key: "currency",
          label: "Currency",
          sortable: true,       
          filterByFormatted: true, 
          formatter: (value, key, item) => {
            if (value) {
              var data = JSON.parse(item.currency) || {};
              var s = data;

              return s.value;
            } else {
              return "N/A";
            }
            // return symbol.text;
          },
        },
        { key: "price", sortable: true },
        { key: "remaining", label: "Remainder Held", sortable: true },
        {
          key: "max_floor",
          label: "Visibility",
          sortable: true,       
          filterByFormatted: true, 
          formatter: (value, key, item) => {
            if (value) {
              return "Iceberg Order";
            } else {
              return "Full Order";
            }
          },
        },
        
        { key: "created_by", label: "Trader", sortable: true },
        // { key: "foreign_broker", sortable: true }
      ],
      visibleRows: [],
      broker_client_orders: [],
      totalRows: 0,
      broker: {},
      perPage: 5,
      currentPage: 1,
      usePagination: true,
      handling_options: [
        {
          text: "Automated execution order, private, no Broker intervention",
          value: "Automated execution order, private, no Broker intervention",
          fix_value: "1",
        },
      ],
      jason_order: [],
      option_values: [
        {
          text: "TimeInForce",
          value: "TimeInForce",
          type: "Date: when to expire",
        },
        {
          text: "Exection Destination",
          value: "Exection Destination",
          type: "optional",
        },
        { text: "Exuction Instruction", value: "Exection Instruction" },
      ],
      option_types: [
        { text: "ClOrdID", value: "ClOrdID" },
        { text: "ExecInst", value: "ExecInst" },
        { text: "HandlInst", value: "HandlInst" },
        { text: "OptionType", value: "OptionType" },
        { text: "OrderQty", value: "OrderQty" },
        { text: "OrdType", value: "OrdType" },
        { text: "Price", value: "Price" },
        { text: "Rule80A", value: "Rule80A" },
        { text: "Side", value: "Side" },
        { text: "StopPx", value: "StopPx" },
        { text: "TimeInForce", value: "TimeInForce" },
        { text: "TransactTime", value: "TransactTime" },
      ],
      time_in_force: [
        { text: "Day", value: "Day", fix_value: "0" },
        {
          text: "Good Till Cancel (GTC)",
          value: "Good Till Cancel (GTC)",
          fix_value: "1",
        },
        {
          text: "Good Till Date (GTD)",
          value: "Good Till Date (GTD)",
          fix_value: "6",
        },
        // {
        //   text: "At the Opening (OPG)",
        //   value: "At the Opening (OPG)",
        //   fix_value: "2"
        // },
        {
          text: "Immediate or Cancel (IOC)",
          value: "Immediate or Cancel (IOC)",
          fix_value: "3",
        },
        // {
        //   text: "Fill or Kill (FOK)",
        //   value: "Fill or Kill (FOK)",
        //   fix_value: "4"
        // }
        // {
        //   text: "Good Till Crossing (GTX)",
        //   value: "Good Till Crossing (GTX)",
        //   fix_value: "5"
        // }
      ],
      side_options: [
        { text: "Buy", value: "Buy", fix_value: "1" },
        { text: "Sell", value: "Sell", fix_value: "2" },
        // { text: "Buy minus", value: "Buy minus", fix_value: "3" },
        // { text: "Sell plus", value: "Sell plus", fix_value: "4" },
        // { text: "Sell short", value: "Sell short", fix_value: "5" },
        // { text: "Sell short exempt", value: "Sell short ", fix_value: "6" },
        // { text: "Undisclosed", value: "Undisclosed", fix_value: "7" },
        // { text: "Cross", value: "Cross", fix_value: "8" },
        // { text: "Cross short", value: "Cross short", fix_value: "9" }
      ],
      order_types: [
        // { text: "Market", value: "Market", fix_value: "1" },
        { text: "Limit", value: "Limit", fix_value: "2" },
        // { text: "Stop", value: "Stop", fix_value: "3" },
        // { text: "Stop limit", value: "Stop limit", fix_value: "4" },
        // { text: "Market on close", value: "Market on close", fix_value: "5" },
        // { text: "With or without", value: "With or without", fix_value: "6" },
        // { text: "Limit or better", value: "Limit or better", fix_value: "7" },
        // {
        //   text: "Limit with or without",
        //   value: "Limit with or without",
        //   fix_value: "8"
        // },
        // { text: "On basis", value: "On basis", fix_value: "9" },
        // { text: "On close", value: "On close", fix_value: "A" },
        // { text: "Limit on close", value: "Limit on close", fix_value: "B" },
        // { text: "Forex - Market", value: "Forex - Market", fix_value: "C" },
        // {
        //   text: "Previously quoted",
        //   value: "Previously quoted",
        //   fix_value: "D"
        // },
        // {
        //   text: "Previously indicated",
        //   value: "Previously indicated",
        //   fix_value: "E"
        // },
        // { text: "Forex - Limit", value: "Forex - Limit", fix_value: "F" },
        // { text: "Forex - Swap", value: "Forex - Swap", fix_value: "G" },
        // {
        //   text: "Forex - Previously Quoted",
        //   value: "Forex - Previously Quoted",
        //   fix_value: "H"
        // },
        // {
        //   text:
        //     "Funari (Limit Day Order with unexecuted portion handled as Market On Close. e.g. Japan)",
        //   value:
        //     "Funari (Limit Day Order with unexecuted portion handled as Market On Close. e.g. Japan)",
        //   fix_value: "I"
        // },
        // { text: "Pegged", value: "Pegged", fix_value: "J" }
      ],
      symbols: [],
      nameState: null,
      disabled: false,
    };
  },
  computed: {
    rows() {      
      if (!this.filter) {
        return this.broker_client_orders.length;
      } else {
        return this.broker_client_orders.length;
      }    
    },
  },
  watch: {
    "order.time_in_force": function (d) {
      // if (d.fix_value) {
      // //console.log("d", d);
      var fix_value = d.fix_value;
      this.expiration = false;
      if (fix_value === "6") {
        this.expiration = true;
      }
      // //console.log(this.expiration);
      // }
    },
  },
  methods: {
    onFiltered(filteredItems) {
        // Trigger pagination to update the number of buttons/pages due to filtering
        this.filteredItemsCount = filteredItems.length
        this.currentPage = 1
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
    search(nameKey, myArray) {
      for (var i = 0; i < myArray.length; i++) {
        if (myArray[i].value === nameKey) {
          return myArray[i];
        }
      }
    },
    currencyHandler() {
      this.order.currency = {};
      var data = this.broker_trading_account_options[0].data;
      var currency = data.currency;
      var resultObject = this.search(currency, this.currencies);
      this.order.currency = resultObject;
    },
    showOptionValueInput() {
      if (this.order_option_input === false) {
        this.order_option_input = true;
      } else {
        this.order_option_input = false;
      }
    },
    async brokerOrderHandler(o) {
      this.disabled = 1;
      this.order = {};
      this.order = o;

      var clients = this.client_trading_account_options;
      var trading = this.broker_trading_account_options;
      let i, j;
      for (i = 0; i < clients.length; i++) {
        if (o.broker_client_id === clients[i].id) {
          this.order.client_trading_account = clients[i].id;
        }
      }
      // //console.log(trading);
      for (j = 0; j < trading.length; j++) {
        // //console.log(trading[j].id);
        if (parseInt(o.trading_account_id) === trading[j].value) {
          this.order.trading_account = trading[j].value;
        }
      }
      // // =============================================================
      //Check if we already parsed to json if we didnt do so now.
      if (typeof o.time_in_force === "string") {
        // Parse stringified data from database back to json for viewing in the multiselect dropdown
        // let handling = JSON.parse(o.handling_instructions);
        this.order.handling_instructions = JSON.parse(o.handling_instructions);
        this.order.symbol = JSON.parse(o.symbol);
        this.order.currency = JSON.parse(o.currency);
        this.order.side = JSON.parse(o.side);
        this.order.order_type = JSON.parse(JSON.parse(o.order_type));
        this.order.time_in_force = JSON.parse(o.time_in_force);
      }
      this.$refs.selectedOrder.clearSelected();
      // ==============================================
      const result = await this.$swal({
        title: o.clordid,
        text: "The Options for the current order are.",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "View Order",
        cancelButtonText: "Cancel Order",
        footer: "<a href='orders' >Exit</a>",
      }); //.then(result => {
      if (result.value) {
        if (this.permissions.indexOf("read-broker-order") !== -1) {
          this.$bvModal.show("jse-new-order");
          this.modalTitle = `Viewing Order ${o.clordid}`;
        } else {
          await this.$swal(
            "Oops!",
            "Please request update permissions from your Admin to proceed",
            "error"
          );
        }
      }
      if (result.dismiss === "cancel") {
        if (this.permissions.indexOf("delete-broker-order") !== -1) {
          //console.log("Destruction");
          this.destroy(o.clordid);
          //broker Or;
        } else {
          this.$swal(
            "Oops!",
            "Please request delete permissions from your Admin",
            "error"
          );
        }
      }
    },
    readJSONTemplate(e) {
      //  let files = this.$refs.file.files[0];
      const files = this.$refs.file.files[0];
      // //console.log(this.files);
      const fr = new FileReader();
      const self = this;
      fr.onload = (e) => {
        //const result = JSON.parse(e.target.result);
        self.order_template_data = e.target.result;
      };

      this.order_template_data = fr.readAsText(files);
    },
    importOrderFromJSON() {
      //  this.order = this.file;
      // //console.log(this.order_template_data);
      this.order = {};
      this.order = this.order_template_data.order_standard;
      this.order_option_inputs = this.order_template_data.order_options;
      this.template = false;
    },

    async saveOrderToJSON() {
      let order_data = {
        order_standard: this.order,
        order_options: this.order_option_inputs,
      };

      delete order_data.order_standard["trading_account"];
      //  Hide new order modal to allow inserting of new template name
      this.$bvModal.hide("jse-new-order"); //Close the modal if it is open

      //Allow the user to create a file name before saving the file to their machine
      const result = await this.$swal({
        title:
          "Filename: Untitled.json, please insert a name for your file below.",
        input: "text",
        inputAttributes: {
          autocapitalize: "off",
        },
        confirmButtontext: "Create File",
        showLoaderOnConfirm: true,
        preConfirm: (request) => {
          // once the user is complete giving the file a name, show them the order modal
          var Filename = request;
          var blob = new Blob(
            [
              JSON.stringify(order_data),
              //   JSON.stringify(this.order_option_inputs)
            ],
            {
              type: "application/json",
            }
          );
          saveAs(blob, Filename + ".json");
        },
        allowOutsideClick: () => !this.$swal.isLoading(),
      });
      if (result.value) {
        //Re Open Modal and allow user to continue their function
        this.$bvModal.show("jse-new-order");
      }
    },
    async tradingAccounts() {
      const { data } = await axios.get("broker-trading-accounts"); //.then(response => {
      // //console.log("Right Here");
      // //console.log(data);
      for (let i = 0; i < data.length; i++) {
        ////console.log(data[i]);
        this.broker_trading_account_options.push({
          text:
            data[i].foreign_broker +
            " : " +
            data[i].bank +
            "-" +
            data[i].trading_account_number +
            " : " +
            data[i].account,
          value: data[i].id,
          data: data[i],
        });
      }
    },
    checkFormValidity() {
      const valid = this.$refs.form.checkValidity();
      this.nameState = valid;
      return valid;
    },
    async getBrokers() {
      // axios.get("broker-list").then(response => {
      //   let data = response.data;
      //   let i;
      //   for (i = 0; i < data.length; i++) {
      //     this.local_broker.push({
      //       text:data[i].name,
      //        value:data[i].id
      //     });
      //   }
      //   // this.broker_client_orders = data;
      // });
      // axios.get("foreign-broker-list").then(fresponse => {
      //   let fdata = fresponse.data;
      //   let j;
      //   for (j = 0; j < fdata.length; j++) {
      //     this.foreign_broker.push({
      //       text:fdata[j].name,
      //        value:fdata[j].id
      //     });
      //   }
      // });
    },
    async createBrokerClientOrder(broker) {
      //Notes:

      this.$swal
        .fire({
          title: "Creating Client Order",
          html: "One moment while we validate your order",
          timerProgressBar: true,
          allowOutsideClick: false,
          onBeforeOpen: () => {
            this.$swal.showLoading();
          },
        })
        .then((result) => {});

      // •	The “Price” indicates the highest price to be used to buy the stocks.
      // •	The “Account” represents the “JCSD #” from the “Client Account” for the order.
      // •	The “ClientID” represents the “Trader Number” from the “Trading Account” selected for the order.
      if (!broker.trading_account || !broker.client_trading_account) {
        this.$swal(
          "You need to select a Trading Account & Client Account to continue"
        );
      } else {
        try {
          const { data } = await axios.post(
            "store-operator-client-order",
            broker
          );
          //.then(response => {
          let valid = data.isvalid;
          // //console.log(data);
          if (valid) {
            this.notify("Order Created", data.errors, "success", true);
          } else {
            this.notify("Order Failed", data.errors, "warning", false);
          }
        } catch (error) {
          var s = error.response.data.message;
          var field = s.match(/'([^']+)'/)[1];
          if (error.response.data.message.includes("cannot be null")) {
            this.$swal(
              `When creating an order ${field} cannot be null. Please try creating the order again.`
            );
          }
        }
      }
    },
    async callFix() {
      let order_sample = {
        BeginString: "FIX.4.2",
        TargetCompID: "CIBC_TST",
        SenderCompID: "JSE_TST",
        SenderSubID: "JMMB",
        Host: "10.246.7.212",
        Port: 27102,
        UserName: "FC4",
        Password: "password",
        OrderID: "JMMB000004",
        BuyorSell: "1",
        OrdType: "4",
        OrderQty: "2",
        TimeInForce: "6",
        Symbol: "AAPL",
        Account: "1466267",
        Price: "224.99",
        Side: "5",
        Strategy: 1000,
        StopPx: 230.0,
        ExDestination: "CNQ",
        ClientID: "JMMB_TRADER1",
      };

      // // //console.log(order_sample);

      // Fix Wrapper
      const { status } = await axios.post(
        "https://cors-anywhere.herokuapp.com/" +
          this.$fixApi +
          "api/OrderManagement/NewOrderSingle",
        order_sample
        //{ crossDomain: true }
      );

      if (status === 200) {
        await this.messageDownload(order_sample);
      }
    },

    async messageDownload(order_sample) {
      const response = await axios.post(
        "https://cors-anywhere.herokuapp.com/" +
          this.$fixApi +
          "api/messagedownload/download",
        order_sample
        //{ crossDomain: true }
      );
      ///.then(response => {
      // //console.log(response);
      ///});
    },
    add() {
      this.disabled = "0";
      this.modalTitle = "New Order";
      this.create = true;
      // The “OrderID” must be unique per request submitted.
      var d = new Date();
      var formatteddatestr = moment(d).format("YMMDhhmmss");

      var dt = new Date();
      this.order.client_order_number =
        formatteddatestr + ("" + Math.random()).substring(2, 5);
      this.order.order_type = this.order_types[0]; //Preselect the order type by default
      this.order.handling_instructions = this.handling_options[0];
      // ===============================================/
      // ===============================================/
    },
    addOption(index) {
      // this.order_option_inputs.push({ option_type: "", option:_ value:"" });
      //  this.order_option_inputs.push(Vue.util.extend({}, this.order_option_inputs));
    },
    removeOption(index) {
      this.order_option_inputs.splice(index, 1);
    },
    async destroy(id) {
      this.$swal.fire({
        title: `${id}`,
        html:
          "Please wait while we validate your cancel request for order #" + id,
        timerProgressBar: true,
        showCancelButton: false,
        allowOutsideClick: false,
        onBeforeOpen: () => {
          this.$swal.showLoading();
        },
      });
      const { data } = await axios.delete(`destroy-broker-client-order/${id}`);
      let valid = data;
      if (valid.isvalid) {
        this.notify("Request Sent", data.errors, "success", true);
      } else {
        this.notify("Warning", data.errors, "warning", false);
      }
    },
    async handleJSEOrder() {
      // Exit when the form isn't valid
      // if (!this.checkFormValidity()) {
      // } else {
      this.$bvModal.hide("jse-new-order"); //Close the m  odal if it is open
      var new_order = {};
      this.order["handling_instructions"] = JSON.stringify(
        this.order.handling_instructions
      );
      this.order["symbol"] = JSON.stringify(this.order.symbol);
      this.order["currency"] = JSON.stringify(this.order.currency);
      this.order["order_type"] = JSON.stringify(this.order.order_type);
      this.order["side"] = JSON.stringify(this.order.side);
      this.order["time_in_force"] = JSON.stringify(this.order.time_in_force);
      this.order["option_type"] = JSON.stringify(this.order.option_type);
      this.order["order_type"] = JSON.stringify(this.order.order_type);
      await this.createBrokerClientOrder(this.order);
      // }
    },    
    exportOrders() {
      ////console.log("Filtered Orders");
      this.perPage=0;
      this.$refs.selectedOrder.refresh();
      //console.log(this.visibleRows);
      const tableData = this.visibleRows.map((r) =>
        //for (var i = 0; i < this.report_data.length; i++) {
        //tableData.push([
        [
          r.order_date.trim(),
          r.jcsd,
          r.clordid,          
          this.statusFilter(r.order_status),
          JSON.parse(r.side)["text"],
          (JSON.parse(r.symbol) || {})["text"],
          r.quantity,
          (JSON.parse(r.currency) || {})["value"],
          r.price,
          r.remaining
        ]
      );

      var doc = new jsPDF('l', 'in', [612, 792]);
      //   // It can parse html:
      //   doc.autoTable({ html: "#foreign-brokers" });
      // Or use javascript directly:
      doc.autoTable({
        head: [
          [
            "Order Date",
            "JCSD#",
            "Client Order#",
            "Status",
            "Side",
            "Symbol",
            "Quantity",
            "Currency",
            "Price",
            "Remainder"
          ],
        ],
        body: tableData,
      });
      doc.save("DMA-ORDERS-REPORT.pdf");
      this.perPage = 5;
      this.$refs.selectedOrder.refresh();
    },
    resetModal() {
      this.create = false;
      this.$refs.selectedOrder.clearSelected();
      this.order = {};
    },
    notify(title, message, type, confirm) {
      this.$swal({
        title: title,
        text: message,
        type: type,
        // showConfirmButton: confirm,
      }).then(function () {
        window.location.reload();
      });
    },
    handleSubmit() {},
    async getSymbols() {
      ({ data: this.symbols } = await axios.get("/apis/symbols.json"));
    },
  },
  async mounted() {
    await Promise.all([
      this.getBrokers(),
      this.getSymbols(),
      this.tradingAccounts(),
    ]);

    var client_accounts_data = JSON.parse(this.client_accounts);
    this.client_trading_account_options = client_accounts_data;

    // //Define Permission On Front And Back End
    let p = JSON.parse(this.$userPermissions);
    this.permissions = [];
    // Looop through and identify all permission to validate against actions
    for (let i = 0; i < p.length; i++) {
      this.permissions.push(p[i].name);
    }

    this.broker_client_orders = JSON.parse(this.orders);
    // //console.log(this.broker_client_orders);
  },
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
