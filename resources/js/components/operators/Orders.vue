<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid">
      <h1>Current Orders</h1>
      <div class="content">
        <b-table
          responsiveS
          v-if="permissions.indexOf('read-broker-order') !== -1"
          ref="selectedOrder"
          :empty-text="'No Orders have been Created. Create an Order below.'"
          id="orders-table"
          :items="broker_client_orders"
          :per-page="perPage"
          :current-page="currentPage"
          striped
          hover
          :fields="fields"
          @row-clicked="brokerOrderHandler"
        ></b-table>
        <!-- <pre>{{broker_client_orders}}</pre> -->
        <div v-if="!create"></div>
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
                  <b-form-group
                    label="Trading Account"
                    label-for="broker-input"
                  >
                    <b-form-select
                      v-model="order.trading_account"
                      :options="broker_trading_account_options"
                      class="mb-3"
                      :disabled="disabled == 1"
                      @change="currencyHandler(order.trading_account)"
                    >
                      <template v-slot:first>
                        <b-form-select-option :value="null">
                          -- Please select a Trading Account--
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
                          -- Please select a Client Account--
                        </b-form-select-option>
                      </template>
                      <b-form-select-option
                        v-for="b in client_trading_account_options"
                        :value="b.id"
                        :key="b.id"
                      >
                        JCSD-{{ b.jcsd }} : {{ b.name }} "Investor"
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
                      :disabled="disabled == 1"
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
                      :disabled="disabled == 1"
                    ></multiselect>
                  </b-form-group>
                </b-col>
              </b-row>
            </b-container>
            <b-container class="bv-example-row">
              <b-row>
                <b-col>
                  <b-form-group
                    label="Handling Instructions"
                    label-for="broker-input"
                  >
                    <multiselect
                      placeholder="Select an Instruction"
                      v-model="order.handling_instructions"
                      label="text"
                      :options="handling_options"
                      :disabled="disabled == 1"
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
                    </b-container>
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
        <b-pagination
          v-model="currentPage"
          :total-rows="rows"
          :per-page="perPage"
          aria-controls="orders-table"
        ></b-pagination>
        <b-button
          v-if="permissions.indexOf('create-broker-order') !== -1"
          v-b-modal.jse-new-order
          @click="add"
          >Create New Order</b-button
        >
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
  props: ["orders", "client_accounts"],
  components: {
    headNav,
    Multiselect,
  },
  data() {
    return {
      expiration: false,
      disabled: 0,
      modalTitle: "New Order",
      permissions: [],
      order_template_data: [],
      file: "",
      order_option_input: false,
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
        { key: "clordid", label: "Order#", sortable: true },
        {
          key: "order_type",
          label: "Order Type",
          sortable: true,
          formatter: (value, key, item) => {
            var type = JSON.parse(item.order_type);
            var order = JSON.parse(value);
            var d = JSON.parse(order);
            return d["text"];
          },
        },
        {
          key: "symbol",
          label: "Symbol",
          sortable: true,
          formatter: (value, key, item) => {
            var data = JSON.parse(item.symbol);
            var s = data;

            return s.text;
            // return symbol.text;
          },
        },
        {
          key: "time_in_force",
          label: "Time In Force",
          sortable: true,
          formatter: (value, key, item) => {
            if (value) {
              var data = JSON.parse(item.time_in_force);
              var s = data;

              return s.text;
            } else {
              return "N/A";
            }
            // return symbol.text;
          },
        },
        {
          key: "currency",
          label: "Currency",
          sortable: true,
          formatter: (value, key, item) => {
            if (value) {
              var data = JSON.parse(item.currency);
              var s = data;

              return s.text;
            } else {
              return "N/A";
            }
            // return symbol.text;
          },
        },
        {
          key: "side",
          label: "Side",
          sortable: true,
          formatter: (value, key, item) => {
            if (value) {
              var data = JSON.parse(item.side);
              var s = data;

              return s.text;
            } else {
              return "N/A";
            }
            // return symbol.text;
          },
        },
        { key: "order_quantity", sortable: true },
        { key: "price", sortable: true },
        {
          key: "order_status",
          sortable: true,
          formatter: (value, key, item) => {
            // return value;
            if (value === "0") {
              return "New";
            } else if (value === "1") {
              return "Partially Filled";
            } else if (value === "2") {
              return "Filled";
            } else if (value === "4") {
              return "Cancelled";
            } else if (value === "5") {
              return "Replaced";
            } else if (value === "C") {
              return "Expired";
            } else if (value === "Z") {
              return "Private";
            } else if (value === "U") {
              return "Unplaced; order is not in the orderbook (Nasdaq defined)";
            } else if (value === "x") {
              return "Inactive Trigger; Stop Limit is waiting for its triggering conditions to be met (Nasdaq Defined)";
            } else if (value === "8") {
              return "Rejected";
            } else {
              return value;
            }
          },
        },
        // { key: "foreign_broker", sortable: true }
      ],
      broker_client_orders: "",
      broker: {},
      perPage: 5,
      currentPage: 1,
      handling_options: [
        {
          text: "Automated execution order, private, no Broker intervention",
          value: "Automated execution order, private, no Broker intervention",
          fix_value: "1",
        },
        {
          text: "Automated execution order, public, Broker intervention OK",
          value: "Automated execution order, public, Broker intervention OK",
          fix_value: "2",
        },
        {
          text: "Manual order, best execution",
          value: "Manual order, best execution",
          fix_value: "3",
        },
        {
          text: "Automated execution order, private, no Broker intervention",
          value: "Automated execution order, private, no Broker intervention",
          fix_value: "4",
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
        {
          text: "Fill or Kill (FOK)",
          value: "Fill or Kill (FOK)",
          fix_value: "4",
        },
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
        { text: "Stop limit", value: "Stop limit", fix_value: "4" },
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
      currencies: [
        { value: "AFN", text: "AFN:  Afghan Afghani" },
        { value: "ALL", text: "ALL:  Albanian Lek" },
        { value: "AMD", text: "AMD:  Armenian Dram" },
        { value: "ANG", text: "ANG:  Netherlands Antillean Guilder" },
        { value: "AOA", text: "AOA:  Angolan Kwanza" },
        { value: "ARS", text: "ARS:  Argentine Peso" },
        { value: "AUD", text: "AUD:  Australian Dollar" },
        { value: "AWG", text: "A WG:  Aruban Florin" },
        { value: "AZN", text: "AZN:  Azerbaijani Manat" },
        { value: "BAM", text: "BAM:  Bosnia-Herzegovina Convertible Mark" },
        { value: "BBD", text: "BBD:  Barbadian Dollar" },
        { value: "BDT", text: "BDT:  Bangladeshi Taka" },
        { value: "BGN", text: "BGN:  Bulgar  ian Lev" },
        { value: "BHD", text: "BHD:  Bahraini Dinar" },
        { value: "BIF", text: "BIF:  Burundian Franc" },
        { value: "BMD", text: "BMD:  Bermud  an Dollar" },
        { value: "BND", text: "BND:  Brunei Dollar" },
        { value: "BOB", text: "BOB:  Bolivian Boliviano" },
        { value: "BRL", text: "BRL:  Br   azilian Real" },
        { value: "BSD", text: "BSD:  Bahamian Dollar" },
        { value: "BTC", text: "BTC:  Bitcoin" },
        { value: "BTN", text: "BTN:  Bh   utanese Ngultrum" },
        { value: "BWP", text: "BWP:  Botswanan Pula" },
        { value: "BYN", text: "BYN:  Belarusian Ruble" },
        { value: "BZD", text: "BZ   D:  Belize Dollar" },
        { value: "CAD", text: "CAD:  Canadian Dollar" },
        { value: "CDF", text: "CDF:  Congolese Franc" },
        { value: "CHF", text: "CHF:  Swiss Franc   " },
        { value: "CLF", text: "CLF:  Chilean Unit of Account (UF)" },
        { value: "CLP", text: "CLP:  Chilean Peso" },
        { value: "CNH", text: "CNH:  Chinese Yuan (Offshore)" },
        { value: "CNY", text: "CNY:  Chinese Yuan" },
        { value: "COP", text: "COP:  Colombi an Peso" },
        { value: "CRC", text: "CRC:  Costa Rican Colón" },
        { value: "CUC", text: "CUC:  Cuban Convertible Peso" },
        { value: "CUP", text: "CUP:  Cuban Peso" },
        { value: "CVE", text: "CVE:  Cape Verdean Escudo" },
        { value: "CZK", text: "CZK:  Czech Republic Koruna" },
        { value: "DJF", text: "DJF:  Djiboutian Franc" },
        { value: "DKK", text: "DKK:  Danish Krone" },
        { value: "DOP", text: "DOP:  Dominican Peso   " },
        { value: "DZD", text: "DZD:  Algerian Dinar" },
        { value: "EGP", text: "EG   P:  Egyptian Pound" },
        { value: "ERN", text: "ERN:  Eritrean Nakfa" },
        { value: "ETB", text: "ETB:  Ethiopian Birr" },
        { value: "EUR", text: "EUR:  Eu   ro" },
        { value: "FJD", text: "FJD:  Fijian Dollar" },
        { value: "   FKP", text: "FKP:  Falkland Islands Pound" },
        { value: "GBP", text: "GBP:  Bri  tish Pound Sterling" },
        { value: "GEL", text: "GEL:  Georgian Lari" },
        { value: "GGP", text: "GGP:  Guernsey Pound" },
        { value: "GHS", text: "GHS:  Ghanaian Cedi" },
        { value: "GIP", text: "GIP:  Gibraltar Pou nd" },
        { value: "GMD", text: "GMD:  Gambian Dalasi" },
        { value: "G  NF", text: "GNF:  Guinean Franc" },
        { value: "GTQ", text: "GTQ:  Guatemala  n Quetzal" },
        { value: "GYD", text: "GYD:  Guyanaese Dollar" },
        { value: "HKD", text: "HKD:  Hong Kong Dollar" },
        { value: "HNL", text: "HNL:  Hondura n Lempira" },
        { value: "HRK", text: "HRK:  Croatian Kuna" },
        { value: "HTG", text: "HTG:  Haitian Gourde" },
        { value: "HUF", text: "HUF:    Hungarian Forint" },
        { value: "IDR", text: "IDR:  Indonesian Rupiah" },
        { value: "ILS", text: "ILS:  Israeli New Sheqel" },
        { value: "IM P", text: "IMP:  Manx pound" },
        { value: "INR", text: "INR:  Indian Rupe   e" },
        { value: "IQD", text: "IQD:  Iraqi Dinar" },
        { value: "IRR", text: "IRR:  Iranian Rial" },
        { value: "ISK", text: "ISK:  Icelandic Króna" },
        { value: "JEP", text: "JEP:  Jersey Pound" },
        { value: "JMD", text: "JMD:     Jamaican Dollar" },
        { value: "JOD", text: "JOD:  Jordanian Dinar" },
        { value: "JPY", text: "JPY:  Japanese Yen" },
        { value: "KES", text: "KES:  Kenyan Shilling" },
        { value: "KGS", text: "KGS:  Kyrgystani Som" },
        { value: "KHR", text: "KHR:  Cambodian Riel" },
        { value: "KMF", text: "KMF:  Comorian Franc" },
        { value: "KPW", text: "KPW:  North Korean Won " },
        { value: "KRW", text: "KRW:  South Korean Won" },
        { value: "KWD", text: "KWD:  Kuwaiti Dinar" },
        { value: "KYD", text: "KYD:  Cayman Island s Dollar" },
        { value: "KZT", text: "KZT:  Kazakhstani Tenge" },
        { value: "LAK", text: "LAK:  Laotian Kip" },
        { value: "LBP", text: "LBP:  Lebane  se Pound" },
        { value: "LKR", text: "LKR:  Sri Lankan Rupee" },
        { value: "LRD", text: "LRD:  Liberian Dollar" },
        { value: "LSL", text: "L SL:  Lesotho Loti" },
        { value: "LYD", text: "LYD:  Libyan Dinar" },
        { value: "MAD", text: "MAD:  Moroccan Dirham" },
        { value: "MDL", text: "MDL:  Moldovan Leu" },
        { value: "MGA", text: "MGA:  Malagasy Ariary" },
        { value: "MKD", text: "MKD:  Macedonian Denar" },
        { value: "MMK", text: "MMK:  M yanma Kyat" },
        { value: "MNT", text: "MNT:  Mongolian Tugrik" },
        { value: "MOP", text: "MOP:  Macanese Pataca" },
        { value: "MRO", text: "M RO:  Mauritanian Ouguiya (pre-2018)" },
        { value: "MRU", text: "MRU:  Mau  ritanian Ouguiya" },
        { value: "MUR", text: "MUR:  Mauritian Rupee" },
        { value: "MVR", text: "MVR:  Maldivian Rufiyaa" },
        { value: "MWK", text: "MWK:  Malawian Kwacha" },
        { value: "MXN", text: "MXN:  Mexican Peso" },
        { value: "MYR", text: "MYR:  Malaysian Ringgit" },
        { value: "MZN", text: "MZN:  Mozambican Metical" },
        { value: "NAD", text: "NAD:    Namibian Dollar" },
        { value: "NGN", text: "NGN:  Nigerian Naira" },
        { value: "NIO", text: "NIO:  Nicaraguan Córdoba" },
        { value: "NOK", text: "NOK:  Norwegian Krone" },
        { value: "NPR", text: "NPR:  Nepalese Rupee" },
        { value: "NZD", text: "NZD:  New Zealand Dollar" },
        { value: "OMR", text: "OMR:  Omani Rial" },
        { value: "PAB", text: "PAB:  Panamanian Balboa" },
        { value: "PEN", text: "PEN:  Peruvian Nuevo Sol" },
        { value: "PG K", text: "PGK:  Papua New Guinean Kina" },
        { value: "PHP", text: "PHP:     Philippine Peso" },
        { value: "PKR", text: "PKR:  Pakistani Rupee" },
        { value: "PLN", text: "PLN:  Polish Zloty" },
        { value: "PYG", text: "PYG  :  Paraguayan Guarani" },
        { value: "QAR", text: "QAR:  Qatari Rial" },
        { value: "RON", text: "RON:  Romanian Leu" },
        { value: "RSD", text: "  RSD:  Serbian Dinar" },
        { value: "RUB", text: "RUB:  Russian Ruble" },
        { value: "RWF", text: "RWF:  Rwandan Franc" },
        { value: "SAR", text: "SAR  :  Saudi Riyal" },
        { value: "SBD", text: "SBD:  Solomon Islands Dollar" },
        { value: "SCR", text: "SCR:  Seychellois Rupee" },
        { value: "SDG", text: "SDG:  Sudanese Pound" },
        { value: "SEK", text: "SEK:  Swedish Krona" },
        { value: "SGD", text: "SGD:  Singapore Dollar" },
        { value: "SHP", text: "SHP:  Saint Helena Pound" },
        { value: "SLL", text: "SLL:  Sierra Leonean Leone" },
        { value: "SOS", text: "SOS:  Somali Shilling" },
        { value: "SRD", text: "SRD:  Surinamese Dollar" },
        { value: "SSP", text: "SSP:  South Sudanese Pound" },
        { value: "STD", text: "STD:  São Tomé and Príncipe Dobra (pre-20,18)" },
        { value: "STN", text: "STN:  São Tomé and Príncipe Dobra" },
        { value: "SVC", text: "SVC:  Salvadoran Colón" },
        { value: "SYP", text: "SYP:  Syrian Pound" },
        { value: "SZL", text: "SZL:  Swazi Lilangeni" },
        { value: "THB", text: "THB:  Thai Baht" },
        { value: "TJS", text: "TJS:  Tajikistani Somoni" },
        { value: "TMT", text: "TMT:  Turkmenistani Manat" },
        { value: "TND", text: "TND:  Tunisian Dinar" },
        { value: "TOP", text: "TOP:  Tongan Pa'anga" },
        { value: "TRY", text: "TRY:  Turkish Lira" },
        { value: "TTD", text: "TTD:  Trinidad and Tobago Dollar" },
        { value: "TWD", text: "TWD:  New Taiwan Dollar" },
        { value: "TZS", text: "TZS:  Tanzanian Shilling" },
        { value: "UAH", text: "UAH:  Ukrainian Hryvnia" },
        { value: "UGX", text: "UGX:  Ugandan Shilling" },
        { value: "USD", text: "USD:  United States Dollar" },
        { value: "UYU", text: "UYU:  Uruguayan Peso" },
        { value: "UZS", text: "UZS:  Uzbekistan Som" },
        { value: "VEF", text: "VEF:  Venezuelan Bolívar Fuerte (Old)" },
        { value: "VES", text: "VES:  Venezuelan Bolívar Soberano" },
        { value: "VND", text: "VND:  Vietnamese Dong" },
        { value: "VUV", text: "VUV:  Vanuatu Vatu" },
        { value: "WST", text: "WST:  Samoan Tala" },
        { value: "XAF", text: "XAF:  CFA Franc BEAC" },
        { value: "XAG", text: "XAG:  Silver Ounce" },
        { value: "XAU", text: "XAU:  Gold Ounce" },
        { value: "XCD", text: "XCD:  East Caribbean Dollar" },
        { value: "XDR", text: "XDR:  Special Drawing Rights" },
        { value: "XOF", text: "XOF:  CFA Franc BCEAO" },
        { value: "XPD", text: "XPD:  Palladium Ounce" },
        { value: "XPF", text: "XPF:  CFP Franc" },
        { value: "XPT", text: "XPT:  Platinum Ounce" },
        { value: "YER", text: "YER:  Yemeni Rial" },
        { value: "ZAR", text: "ZAR:  South African Rand" },
        { value: "ZMW", text: "ZMW:  Zambian Kwacha" },
        { value: "ZWL", text: "ZWL:  Zimbabwean Dollar" },
      ],
      nameState: null,
    };
  },
  computed: {
    rows() {
      return this.broker_client_orders.length;
    },
  },
  methods: {
    search(nameKey, myArray) {
      for (var i = 0; i < myArray.length; i++) {
        if (myArray[i].value === nameKey) {
          return myArray[i];
        }
      }
    },
    currencyHandler() {
      this.order.currency = {};
      var data = this.broker_trading_account_options[0].data
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
    brokerOrderHandler(o) {
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
      console.log(trading);
      for (j = 0; j < trading.length; j++) {
        // console.log(trading[j].id);
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
      this.$swal({
        title: o.clordid,
        text: "The Options for the current order are.",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "View Order",
        cancelButtonText: "Cancel Order",
        footer: "<a href='orders' >Exit</a>",
      }).then((result) => {
        if (result.value) {
          if (this.permissions.indexOf("update-broker-order") !== -1) {
            this.$bvModal.show("jse-new-order");
            this.modalTitle = `Viewing Order ${o.clordid}`;
          } else {
            this.$swal(
              "Oops!",
              "Please request update permissions from your Admin to proceed",
              "error"
            );
          }
        }
        if (result.dismiss === "cancel") {
          if (this.permissions.indexOf("delete-broker-order") !== -1) {
            this.destroy(o.id);
            brokerOr;
          } else {
            this.$swal(
              "Oops!",
              "Please request delete permissions from your Admin",
              "error"
            );
          }
        }
      });
    },
    readJSONTemplate(e) {
      //  let files = this.$refs.file.files[0];
      const files = this.$refs.file.files[0];
      // console.log(this.files);
      const fr = new FileReader();
      const self = this;
      fr.onload = (e) => {
        const result = JSON.parse(e.target.result);
        self.order_template_data = result;
      };

      this.order_template_data = fr.readAsText(files);
    },
    importOrderFromJSON() {
      //  this.order = this.file;
      console.log(this.order_template_data);
      this.order = {};
      this.order = this.order_template_data.order_standard;
      this.order_option_inputs = this.order_template_data.order_options;
      this.template = false;
    },
    saveOrderToJSON() {
      let order_data = {
        order_standard: this.order,
        order_options: this.order_option_inputs,
      };

      delete order_data.order_standard["trading_account"];
      //  Hide new order modal to allow inserting of new template name
      this.$bvModal.hide("jse-new-order"); //Close the modal if it is open

      //Allow the user to create a file name before saving the file to their machine
      this.$swal({
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
      }).then((result) => {
        if (result.value) {
          //Re Open Modal and allow user to continue their function
          this.$bvModal.show("jse-new-order");
        }
      });
    },
    tradingAccounts() {
      axios.get("broker-trading-accounts").then((response) => {
        let data = response.data;
        console.log("Right Here");
        console.log(data);
        let i;
        for (i = 0; i < data.length; i++) {
          //console.log(data[i]);
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
            data: data[i]
          });
        }
      });
    },
    checkFormValidity() {
      const valid = this.$refs.form.checkValidity();
      this.nameState = valid;
      return valid;
    },
    getBrokers() {
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
    createBrokerClientOrder(broker) {
      //Notes:

      // this.$swal
      //   .fire({
      //     title: "Creating Client Order",
      //     html: "One moment while we setup the current order",
      //     timerProgressBar: true,
      //     onBeforeOpen: () => {
      //       this.$swal.showLoading();
      //     },
      //   })
      //   .then((result) => {});

      // •	The “Price” indicates the highest price to be used to buy the stocks.
      // •	The “Account” represents the “JCSD #” from the “Client Account” for the order.
      // •	The “ClientID” represents the “Trader Number” from the “Trading Account” selected for the order.
      if (!broker.trading_account || !broker.client_trading_account) {
        this.$swal(
          "You need to select a Trading Account & Client Account to continue"
        );
      } else {
        axios
          .post("store-operator-client-order", broker)
          .then((response) => {
            let data = response.data;
            let valid = data.isvalid;
            console.log(data);
            if (valid) {
              console.log(data);
              this.$swal(data.errors);
              setTimeout(location.reload.bind(location), 2000);
            } else {
              this.$swal(data.errors);
              setTimeout(location.reload.bind(location), 2000);
            }
          })
          .catch((error) => {
            var s = error.response.data.message;
            var field = s.match(/'([^']+)'/)[1];
            if (error.response.data.message.includes("cannot be null")) {
              this.$swal(
                `When creating an order ${field} cannot be null. Please try creating the order again.`
              );
            }
          });
      }
    },
    callFix() {
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

      console.log(order_sample);

      // Fix Wrapper
      axios
        .post(
          "https://cors-anywhere.herokuapp.com/" +
            this.$fixApi +
            "api/OrderManagement/NewOrderSingle",
          order_sample,
          { crossDomain: true }
        )
        .then((response) => {
          let status = response.status;
          if (status === 200) {
            this.messageDownload(order_sample);
          }
        });
    },
    messageDownload(order_sample) {
      axios
        .post(
          "https://cors-anywhere.herokuapp.com/" +
            this.$fixApi +
            "api/messagedownload/download",
          order_sample,
          { crossDomain: true }
        )
        .then((response) => {
          console.log(response);
        });
    },
    add() {
      this.disabled = "0";
      this.modalTitle = "New Order";
      this.create = true;
      var dt = new Date();
      // The “OrderID” must be unique per request submitted.
      this.order.client_order_number =
        "ORD" +
        dt.getFullYear() +
        "" +
        (dt.getMonth() + 1).toString().padStart(2, "0") +
        "" +
        dt
          .getDate()
          .toString()
          .padStart(2, "0") +
        "N" +
        ("" + Math.random()).substring(2, 5);
      // ===============================================/
    },
    addOption(index) {
      // this.order_option_inputs.push({ option_type: "", option:_ value:"" });
      //  this.order_option_inputs.push(Vue.util.extend({}, this.order_option_inputs));
    },
    removeOption(index) {
      this.order_option_inputs.splice(index, 1);
    },
    destroy(id) {
      // this.$swal("Proccessing Order Cancellation");
      // // Fix Wrapper
      // axios
      //   .post(
      //     "https://cors-anywhere.herokuapp.com/" +
      //      this.$fixApi+"api/OrderManagement/OrderCancelRequest",
      //     order_sample,
      //     { crossDomain: true }
      //   )
      //   .then(response => {
      //     this.$swal("Order Cancelled");
      //   });
    },
    handleJSEOrder() {
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
      this.createBrokerClientOrder(this.order);
      // }
    },
    resetModal() {
      this.create = false;
      this.$refs.selectedOrder.clearSelected();
      this.order = {};
    },
    handleSubmit() {},
    getSymbols() {
      axios.get("/apis/symbols.json").then((response) => {
        this.symbols = response.data;
      });
    },
  },
  mounted() {
    this.getBrokers();
    this.getSymbols();
    this.tradingAccounts();
    console.log(this);
    var client_accounts_data = JSON.parse(this.client_accounts);
    this.client_trading_account_options = client_accounts_data;

    // //Define Permission On Front And Back End
    let p = JSON.parse(this.$userPermissions);
    this.permissions = [];
    // Looop through and identify all permission to validate against actions
    for (let i = 0; i < p.length; i++) {
      this.permissions.push(p[i].name);
    }

    // if there are orders present
    // if(JSON.parse(this.orders).length >= 1){
    this.broker_client_orders = JSON.parse(this.orders);
    console.log(this.broker_client_orders);
    // }
  },
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
