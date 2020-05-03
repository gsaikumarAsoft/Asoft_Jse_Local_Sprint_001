<template>
  <div>
    <head-nav></head-nav>
    <div class="container">
      <h1>Current Orders</h1>
      <div class="content">
        <b-table
         v-if='permissions.indexOf("read-broker-order") !== -1'
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
        <div v-if="!create"></div>
        <b-modal
          id="jse-new-order"
          size="xl"
          ref="modal"
          @ok="handleJSEOrder"
          @hidden="resetModal"
          title="New Order"
        >
          <form ref="form">
            <b-container class="bv-example-row">
              <b-row>
                <b-col>
                  <b-form-group
                    label="Trading Account"
                    label-for="broker-input"
                    invalid-feedback="Trading Account is required"
                  >
                    <b-form-select
                      v-model="order.trading_account"
                      :options="broker_trading_account_options"
                      class="mb-3"
                    >
                      <template v-slot:first>
                        <b-form-select-option :value="null" disabled>
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
                  <b-form-group
                    label="Client Account"
                    label-for="broker-input"
                    invalid-feedback="Client Account is required"
                  >
                    <b-form-select
                      v-model="order.client_trading_account"
                      :options="client_trading_account_options.trading_account"
                      class="mb-3"
                    >
                      <template v-slot:first>
                        <b-form-select-option :value="null" disabled>
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
                      required
                      placeholder="Enter Client Order Number"
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
                      required
                      placeholder="Enter Market Order Number"
                    ></b-form-input>
                  </b-form-group>
                </b-col>
              </b-row>
              <b-row>
                <b-col>
                  <b-form-group
                    label="Symbol"
                    label-for="order-input"
                    invalid-feedback="Symbol is required"
                  >
                    <multiselect
                      placeholder="Select a symbol"
                      v-model="order.symbol"
                      label="text"
                      :options="symbols"
                    ></multiselect>
                  </b-form-group>
                </b-col>

                <b-col>
                  <b-form-group
                    label="Currency"
                    label-for="broker-input"
                    invalid-feedback="Currency is required"
                  >
                    <multiselect
                      placeholder="Select a currency"
                      v-model="order.currency"
                      label="text"
                      :options="currencies"
                      required
                    ></multiselect>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group
                    label="Value"
                    label-for="order-input"
                    invalid-feedback="value is required"
                  >
                    <b-input-group size="md" prepend="$">
                      <b-form-input
                        id="value-input1"
                        v-model="order.value"
                        :state="nameState"
                        type="number"
                        required
                      ></b-form-input>
                    </b-input-group>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group
                    label="Stop Price"
                    label-for="limit-input"
                    invalid-feedback="Limit is required"
                  >
                    <b-input-group size="md" prepend="$">
                      <b-form-input
                        id="value-input"
                        v-model="order.stop_price"
                        :state="nameState"
                        type="number"
                        required
                      ></b-form-input>
                    </b-input-group>
                  </b-form-group>
                </b-col>
              </b-row>
            </b-container>
            <b-container class="bv-example-row">
              <b-row>
                <b-col>
                  <b-form-group
                    label="Quantity"
                    label-for="broker-input"
                    invalid-feedback="Quantity is required"
                  >
                    <b-input-group size="md">
                      <b-form-input
                        id="quantity-input"
                        v-model="order.quantity"
                        :state="nameState"
                        required
                      ></b-form-input>
                    </b-input-group>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group
                    label="Price"
                    label-for="order-input"
                    invalid-feedback="Price is required"
                  >
                    <b-input-group size="md" prepend="$">
                      <b-form-input
                        id="price-input"
                        v-model="order.price"
                        :state="nameState"
                        type="number"
                        required
                      ></b-form-input>
                    </b-input-group>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group
                    label="Side"
                    label-for="type-input"
                    invalid-feedback="Side is required"
                  >
                    <multiselect
                      placeholder="Select a Side"
                      v-model="order.side"
                      label="text"
                      :options="side_options"
                    ></multiselect>
                  </b-form-group>
                </b-col>
                <b-col>
                  <b-form-group
                    label="Order Type"
                    label-for="type-input"
                    invalid-feedback="Order Type is required"
                  >
                    <multiselect
                      placeholder="Select an Order Type"
                      v-model="order.order_type"
                      label="text"
                      :options="order_types"
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
                    invalid-feedback="Handling Instructions is required"
                  >
                    <multiselect
                      placeholder="Select an Instruction"
                      v-model="order.handling_instructions"
                      label="text"
                      :options="handling_options"
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
                  <label for="example-datepicker">Expiration Date</label>
                  <b-form-datepicker
                    id="example-datepicker"
                    v-model="order.expiration_date"
                    class="mb-2"
                  ></b-form-datepicker>
                </b-col>
                <b-col>
                  <b-form-group
                    label="Time In Force"
                    label-for="broker-input"
                    invalid-feedback="Option Type is required"
                  >
                    <multiselect
                      placeholder="Select a Time In Force"
                      v-model="order.time_in_force"
                      order="order_option_inputs[][option_type]"
                      label="text"
                      :options="time_in_force"
                    ></multiselect>
                  </b-form-group>
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
          v-if='permissions.indexOf("create-broker-order") !== -1'
          v-b-modal.jse-new-order
          @click="create = true"
        >Create New Order</b-button>
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
    Multiselect
  },
  data() {
    return {
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
        { ket: "handling_instructions", sortable: true },
        { key: "order_date", sortable: true },
        {
          key: "order_type.text",
          label: "Order Type",
          sortable: true,
          formatter: (value, key, item) => {
            var type = JSON.parse(item.order_type);
            var order = JSON.parse(type);
            return order.text;
          }
        },
        {
          key: "symbol.text",
          label: "Symbol",
          sortable: true,
          formatter: (value, key, item) => {
            var data = JSON.parse(item.symbol);
            var s = data;

            return s.text;
            // return symbol.text;
          }
        },
        {
          key: "time_in_force.text",
          label: "Time In Force",
          sortable: true,
          formatter: (value, key, item) => {
            var data = JSON.parse(item.time_in_force);
            var s = data;

            return s.text;
            // return symbol.text;
          }
        },
        {
          key: "currency.text",
          label: "Currency",
          sortable: true,
          formatter: (value, key, item) => {
            var data = JSON.parse(item.currency);
            var s = data;

            return s.text;
            // return symbol.text;
          }
        },
        {
          key: "side.text",
          label: "Side",
          sortable: true,
          formatter: (value, key, item) => {
            var data = JSON.parse(item.side);
            var s = data;

            return s.text;
            // return symbol.text;
          }
        },
        { key: "order_quantity", sortable: true },
        { key: "price", sortable: true },
        { key: "order_status", sortable: true }
        // { key: "foreign_broker", sortable: true }
      ],
      broker_client_orders: JSON.parse(this.orders),
      broker: {},
      perPage: 5,
      currentPage: 1,
      handling_options: [
        {
          text: "Automated execution order, private, no Broker intervention",
          value: "Automated execution order, private, no Broker intervention",
          fix_value: "1"
        },
        {
          text: "Automated execution order, public, Broker intervention OK",
          value: "Automated execution order, public, Broker intervention OK",
          fix_value: "2"
        },
        {
          text: "Manual order, best execution",
          value: "Manual order, best execution",
          fix_value: "3"
        },
        {
          text: "Automated execution order, private, no Broker intervention",
          value: "Automated execution order, private, no Broker intervention",
          fix_value: "4"
        }
      ],
      jason_order: [],
      option_values: [
        {
          text: "TimeInForce",
          value: "TimeInForce",
          type: "Date: when to expire"
        },
        {
          text: "Exection Destination",
          value: "Exection Destination",
          type: "optional"
        },
        { text: "Exuction Instruction", value: "Exection Instruction" }
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
        { text: "TransactTime", value: "TransactTime" }
      ],
      time_in_force: [
        { text: "Day", value: "Day", fix_value: "0" },
        {
          text: "Good Till Cancel (GTC)",
          value: "Good Till Cancel (GTC)",
          fix_value: "1"
        },
        {
          text: "At the Opening (OPG)",
          value: "At the Opening (OPG)",
          fix_value: "2"
        },
        {
          text: "Immediate or Cancel (IOC)",
          value: "Immediate or Cancel (IOC), fix_value: '3'"
        },
        {
          text: "Fill or Kill (FOK)",
          value: "Fill or Kill (FOK)",
          fix_value: "4"
        },
        {
          text: "Good Till Crossing (GTX)",
          value: "Good Till Crossing (GTX)",
          fix_value: "5"
        },
        { text: "Good Till Date", value: "Good Till Date", fix_value: "6" }
      ],
      side_options: [
        { text: "Buy", value: "Buy", fix_value: "1" },
        { text: "Sell", value: "Sell", fix_value: "2" },
        { text: "Buy minus", value: "Buy minus", fix_value: "3" },
        { text: "Sell plus", value: "Sell plus", fix_value: "4" },
        { text: "Sell short", value: "Sell short", fix_value: "5" },
        { text: "Sell short exempt", value: "Sell short ", fix_value: "6" },
        { text: "Undisclosed", value: "Undisclosed", fix_value: "7" },
        { text: "Cross", value: "Cross", fix_value: "8" },
        { text: "Cross short", value: "Cross short", fix_value: "9" }
      ],
      order_types: [
        { text: "Market", value: "Market", fix_value: "1" },
        { text: "Limit", value: "Limit", fix_value: "2" },
        { text: "Stop", value: "Stop", fix_value: "3" },
        { text: "Stop limit", value: "Stop limit", fix_value: "4" },
        { text: "Market on close", value: "Market on close", fix_value: "5" },
        { text: "With or without", value: "With or without", fix_value: "6" },
        { text: "Limit or better", value: "Limit or better", fix_value: "7" },
        {
          text: "Limit with or without",
          value: "Limit with or without",
          fix_value: "8"
        },
        { text: "On basis", value: "On basis", fix_value: "9" },
        { text: "On close", value: "On close", fix_value: "A" },
        { text: "Limit on close", value: "Limit on close", fix_value: "B" },
        { text: "Forex - Market", value: "Forex - Market", fix_value: "C" },
        {
          text: "Previously quoted",
          value: "Previously quoted",
          fix_value: "D"
        },
        {
          text: "Previously indicated",
          value: "Previously indicated",
          fix_value: "E"
        },
        { text: "Forex - Limit", value: "Forex - Limit", fix_value: "F" },
        { text: "Forex - Swap", value: "Forex - Swap", fix_value: "G" },
        {
          text: "Forex - Previously Quoted",
          value: "Forex - Previously Quoted",
          fix_value: "H"
        },
        {
          text:
            "Funari (Limit Day Order with unexecuted portion handled as Market On Close. e.g. Japan)",
          value:
            "Funari (Limit Day Order with unexecuted portion handled as Market On Close. e.g. Japan)",
          fix_value: "I"
        },
        { text: "Pegged", value: "Pegged", fix_value: "J" }
      ],
      symbols: [
        {
          text: "AAB  Aberdeen International Inc",
          value: "AAB Aberdeen International Inc"
        },
        {
          text: "AAV Advantage Oil & Gas Ltd",
          value: "AAV Advantage Oil & Gas Ltd"
        },
        {
          text: "ABT Absolute Software Corp",
          value: "ABT Absolute Software Corp"
        },
        { text: "ABX Barrick Gold Corp", value: "ABX Barrick Gold Corp" },
        { text: "AC Air Canada", value: "AC Air Canada" },
        { text: "ACB Aurora Cannabis Inc", value: "ACB Aurora Cannabis Inc" },
        {
          text: "ACB.WT Aurora Cannabis Inc WT",
          value: "ACB.WT Aurora Cannabis Inc WT"
        },
        { text: "ACD Accord Financial", value: "ACD Accord Financial" },
        {
          text: "ACD.DB Accord Financial Corp 7.00 Pct Debs",
          value: "ACD.DB Accord Financial Corp 7.00 Pct Debs"
        },
        { text: "ACI Altagas Canada Inc", value: "ACI Altagas Canada Inc" },
        { text: "ACO.X Atco Ltd Cl.I NV", value: "ACO.X Atco Ltd Cl.I NV" },
        { text: "ACO.Y Atco Ltd Cl II", value: "ACO.Y Atco Ltd Cl II" },
        { text: "ACQ Autocanada Inc", value: "ACQ Autocanada Inc" },
        {
          text: "ACZ Middlefield American Core Dividend ETF",
          value: "ACZ Middlefield American Core Dividend ETF"
        },
        { text: "AD Alaris Royalty Corp", value: "AD Alaris Royalty Corp" },
        {
          text: "AD.DB Alaris Royalty Corp 5.50 Pct Debs",
          value: "AD.DB Alaris Royalty Corp 5.50 Pct Debs"
        },
        { text: "ADN Acadian Timber Corp", value: "ADN Acadian Timber Corp" },
        { text: "ADVZ Advanz Pharma Corp", value: "ADVZ Advanz Pharma Corp" },
        {
          text: "ADW.A Andrew Peller Limited Cl.A",
          value: "ADW.A Andrew Peller Limited Cl.A"
        },
        {
          text: "ADW.B Andrew Peller Limited Cl.B",
          value: "ADW.B Andrew Peller Limited Cl.B"
        },
        {
          text: "AEF Acasta Enterprises Inc",
          value: "AEF Acasta Enterprises Inc"
        },
        {
          text: "AEF.WT Acasta Enterprises Inc WT",
          value: "AEF.WT Acasta Enterprises Inc WT"
        },
        {
          text: "AEM Agnico Eagle Mines Limited",
          value: "AEM Agnico Eagle Mines Limited"
        },
        {
          text: "AEZS Aeterna Zentaris Inc",
          value: "AEZS Aeterna Zentaris Inc"
        },
        {
          text: "AFN Ag Growth International Inc",
          value: "AFN Ag Growth International Inc"
        },
        {
          text: "AFN.DB.D Ag Growth Intl Inc 4.85 Pct Debs",
          value: "AFN.DB.D Ag Growth Intl Inc 4.85 Pct Debs"
        },
        {
          text: "AFN.DB.E Ag Growth Intl Inc 4.50 Pct Debs",
          value: "AFN.DB.E Ag Growth Intl Inc 4.50 Pct Debs"
        },
        {
          text: "AFN.DB.F Ag Growth Intl Inc 5.4 Pct Debs",
          value: "AFN.DB.F Ag Growth Intl Inc 5.4 Pct Debs"
        },
        {
          text: "AFN.DB.G Ag Growth Intl Inc 5.25 Pct Debs",
          value: "AFN.DB.G Ag Growth Intl Inc 5.25 Pct Debs"
        },
        {
          text: "AFN.DB.H Ag Growth Intl Inc 5.25 Pct Debs",
          value: "AFN.DB.H Ag Growth Intl Inc 5.25 Pct Debs"
        },
        {
          text: "AGF.B AGF Management Ltd Cl.B NV",
          value: "AGF.B AGF Management Ltd Cl.B NV"
        },
        {
          text: "AGI Alamos Gold Inc Cls A",
          value: "AGI Alamos Gold Inc Cls A"
        },
        {
          text: "AI Atrium Mortgage Investment Corp",
          value: "AI Atrium Mortgage Investment Corp"
        },
        {
          text: "AI.DB Atrium Mortgage Inv Corp 5.25 Pct Debs",
          value: "AI.DB Atrium Mortgage Inv Corp 5.25 Pct Debs"
        },
        {
          text: "AI.DB.B Atrium Mortgage Inv Corp 5.50 Pct Debs",
          value: "AI.DB.B Atrium Mortgage Inv Corp 5.50 Pct Debs"
        },
        {
          text: "AI.DB.C Atrium Mortgage Inv Corp 5.30 Pct Debs",
          value: "AI.DB.C Atrium Mortgage Inv Corp 5.30 Pct Debs"
        },
        {
          text: "AI.DB.D Atrium Mortgage Inv Corp 5.50 Pct Debs",
          value: "AI.DB.D Atrium Mortgage Inv Corp 5.50 Pct Debs"
        },
        {
          text: "AI.DB.E Atrium Mortgage Inv Corp 5.60 Pct Debs",
          value: "AI.DB.E Atrium Mortgage Inv Corp 5.60 Pct Debs"
        },
        { text: "AIF Altus Group Limited", value: "AIF Altus Group Limited" },
        {
          text: "AII Almonty Industries Inc",
          value: "AII Almonty Industries Inc"
        },
        { text: "AIM Aimia Inc", value: "AIM Aimia Inc" },
        {
          text: "AIM.PR.A Aimia Inc Pref Ser 1",
          value: "AIM.PR.A Aimia Inc Pref Ser 1"
        },
        {
          text: "AIM.PR.C Aimia Inc Pref Ser 3",
          value: "AIM.PR.C Aimia Inc Pref Ser 3"
        },
        { text: "AJX Agjunction Inc", value: "AJX Agjunction Inc" },
        { text: "AKG Asanko Gold Inc", value: "AKG Asanko Gold Inc" },
        {
          text: "AKT.A Akita Drilling Ltd Cl.A NV",
          value: "AKT.A Akita Drilling Ltd Cl.A NV"
        },
        { text: "AKT.B Akita Cl B", value: "AKT.B Akita Cl B" },
        { text: "AKU Akumin Inc", value: "AKU Akumin Inc" },
        { text: "AKU.U Akumin Inc", value: "AKU.U Akumin Inc" },
        { text: "ALA Altagas Ltd", value: "ALA Altagas Ltd" },
        {
          text: "ALA.PR.A Altagas Ltd Pref A",
          value: "ALA.PR.A Altagas Ltd Pref A"
        },
        {
          text: "ALA.PR.B Altagas Ltd Pref Ser B",
          value: "ALA.PR.B Altagas Ltd Pref Ser B"
        },
        {
          text: "ALA.PR.E Altagas Ltd Pref Ser E",
          value: "ALA.PR.E Altagas Ltd Pref Ser E"
        },
        {
          text: "ALA.PR.G Altagas Ltd Pref G",
          value: "ALA.PR.G Altagas Ltd Pref G"
        },
        {
          text: "ALA.PR.H Altagas Ltd Pref H",
          value: "ALA.PR.H Altagas Ltd Pref H"
        },
        {
          text: "ALA.PR.I Altagas Ltd Pref Ser I",
          value: "ALA.PR.I Altagas Ltd Pref Ser I"
        },
        {
          text: "ALA.PR.K Altagas Ltd Pref Ser K",
          value: "ALA.PR.K Altagas Ltd Pref Ser K"
        },
        {
          text: "ALA.PR.U Altagas Ltd Pref Ser C",
          value: "ALA.PR.U Altagas Ltd Pref Ser C"
        },
        {
          text: "ALB Allbanc Split Banc Corp II",
          value: "ALB Allbanc Split Banc Corp II"
        },
        {
          text: "ALB.PR.C Allbanc Split Corp II Pref Ser 2",
          value: "ALB.PR.C Allbanc Split Corp II Pref Ser 2"
        },
        { text: "ALC Algoma Central", value: "ALC Algoma Central" },
        {
          text: "ALC.DB.A Algoma Central Corp 5.25 Pct Debs",
          value: "ALC.DB.A Algoma Central Corp 5.25 Pct Debs"
        },
        { text: "ALEF Aleafia Health Inc", value: "ALEF Aleafia Health Inc" },
        {
          text: "ALEF.DB Aleafia Health Inc 8.50 Pct Debs",
          value: "ALEF.DB Aleafia Health Inc 8.50 Pct Debs"
        },
        {
          text: "ALEF.WT Aleafia Health Inc WT",
          value: "ALEF.WT Aleafia Health Inc WT"
        },
        { text: "ALO Alio Gold Inc", value: "ALO Alio Gold Inc" },
        { text: "ALS Altius Minerals Corp", value: "ALS Altius Minerals Corp" },
        { text: "ALYA Alithya Group", value: "ALYA Alithya Group" },
        { text: "AMM Almaden Minerals Ltd", value: "AMM Almaden Minerals Ltd" },
        {
          text: "AND Andlauer Healthcare Group Inc",
          value: "AND Andlauer Healthcare Group Inc"
        },
        { text: "ANX Anaconda Mining Inc", value: "ANX Anaconda Mining Inc" },
        { text: "AOI Africa Oil Corp", value: "AOI Africa Oil Corp" },
        { text: "AOT Ascot Resources Ltd", value: "AOT Ascot Resources Ltd" },
        {
          text: "AP.UN Allied Properties Real Estate Inv Trust",
          value: "AP.UN Allied Properties Real Estate Inv Trust"
        },
        { text: "APHA Aphria Inc", value: "APHA Aphria Inc" },
        {
          text: "APR.UN Automotive Properties REIT",
          value: "APR.UN Automotive Properties REIT"
        },
        {
          text: "APS Aptose Biosciences Inc",
          value: "APS Aptose Biosciences Inc"
        },
        {
          text: "APY Anglo Pacific Group Plc",
          value: "APY Anglo Pacific Group Plc"
        },
        { text: "AQA Aquila Resources Inc", value: "AQA Aquila Resources Inc" },
        {
          text: "AQN Algonquin Power and Utilities Corp",
          value: "AQN Algonquin Power and Utilities Corp"
        },
        {
          text: "AQN.PR.A Algonquin Power and Utilities Pref A",
          value: "AQN.PR.A Algonquin Power and Utilities Pref A"
        },
        {
          text: "AQN.PR.D Algonquin Power and Utilities Pref D",
          value: "AQN.PR.D Algonquin Power and Utilities Pref D"
        },
        { text: "AR Argonaut Gold Inc", value: "AR Argonaut Gold Inc" },
        {
          text: "ARB Accelerate Arbitrage Fund ETF",
          value: "ARB Accelerate Arbitrage Fund ETF"
        },
        { text: "ARE Aecon Group Inc", value: "ARE Aecon Group Inc" },
        {
          text: "ARE.DB.C Aecon Group Inc 5.0 Pct Debs",
          value: "ARE.DB.C Aecon Group Inc 5.0 Pct Debs"
        },
        {
          text: "ARG Amerigo Resources Ltd",
          value: "ARG Amerigo Resources Ltd"
        },
        { text: "ARX Arc Resources Ltd", value: "ARX Arc Resources Ltd" },
        {
          text: "ASM Avino Silver and Gold Mines Ltd",
          value: "ASM Avino Silver and Gold Mines Ltd"
        },
        {
          text: "ASND Ascendant Resources Inc",
          value: "ASND Ascendant Resources Inc"
        },
        {
          text: "ASND.WT Ascendant Resources Inc WT",
          value: "ASND.WT Ascendant Resources Inc WT"
        },
        {
          text: "ASP Acerus Pharmaceuticals Corp",
          value: "ASP Acerus Pharmaceuticals Corp"
        },
        { text: "ASR Alacer Gold Corp", value: "ASR Alacer Gold Corp" },
        {
          text: "AT Acuityads Holdings Inc",
          value: "AT Acuityads Holdings Inc"
        },
        { text: "ATA Ats Automation", value: "ATA Ats Automation" },
        {
          text: "ATD.A Alimentation Couche-Tard Inc Cl A Mv",
          value: "ATD.A Alimentation Couche-Tard Inc Cl A Mv"
        },
        {
          text: "ATD.B Alimentation Couche-Tard Inc Cl B Sv",
          value: "ATD.B Alimentation Couche-Tard Inc Cl B Sv"
        },
        { text: "ATH Athabasca Oil Corp", value: "ATH Athabasca Oil Corp" },
        { text: "ATP Atlantic Power Corp", value: "ATP Atlantic Power Corp" },
        {
          text: "ATP.DB.E Atlantic Power Corporation 6.00 Pct Debs",
          value: "ATP.DB.E Atlantic Power Corporation 6.00 Pct Debs"
        },
        { text: "ATZ Aritzia Inc", value: "ATZ Aritzia Inc" },
        { text: "AUG Auryn Resources Inc", value: "AUG Auryn Resources Inc" },
        {
          text: "AUGB.F First Trst CBOE Vest US Eqty Buffer ETF",
          value: "AUGB.F First Trst CBOE Vest US Eqty Buffer ETF"
        },
        {
          text: "AUMN Golden Minerals Company",
          value: "AUMN Golden Minerals Company"
        },
        {
          text: "AUP Aurinia Pharmaceuticals Inc",
          value: "AUP Aurinia Pharmaceuticals Inc"
        },
        { text: "AVCN Avicanna Inc", value: "AVCN Avicanna Inc" },
        {
          text: "AVL Avalon Advanced Materials Inc",
          value: "AVL Avalon Advanced Materials Inc"
        },
        {
          text: "AVP Avcorp Industries Inc",
          value: "AVP Avcorp Industries Inc"
        },
        {
          text: "AW.UN A&W Revenue Royalties Income Fund",
          value: "AW.UN A&W Revenue Royalties Income Fund"
        },
        {
          text: "AX.PR.A Artis REIT Pref Ser A",
          value: "AX.PR.A Artis REIT Pref Ser A"
        },
        {
          text: "AX.PR.E Artis REIT Pref Ser E",
          value: "AX.PR.E Artis REIT Pref Ser E"
        },
        {
          text: "AX.PR.I Artis REIT Pref Series I",
          value: "AX.PR.I Artis REIT Pref Series I"
        },
        {
          text: "AX.UN Artis Real Estate Investment Trust Units",
          value: "AX.UN Artis Real Estate Investment Trust Units"
        },
        { text: "AXU Alexco Resource Corp", value: "AXU Alexco Resource Corp" },
        { text: "AYM Atalaya Mining Plc", value: "AYM Atalaya Mining Plc" },
        {
          text: "AZP.PR.A Atlantic Power Pref Equity Ser 1",
          value: "AZP.PR.A Atlantic Power Pref Equity Ser 1"
        },
        {
          text: "AZP.PR.B Atlantic Power Pref Equity Ser 2",
          value: "AZP.PR.B Atlantic Power Pref Equity Ser 2"
        },
        {
          text: "AZP.PR.C Atlantic Power Pref Equity Ser 3",
          value: "AZP.PR.C Atlantic Power Pref Equity Ser 3"
        },
        { text: "AZZ Azarga Uranium Corp", value: "AZZ Azarga Uranium Corp" },
        { text: "Code Name", value: "Code Name" },
        {
          text: "BAD Badger Daylighting Ltd",
          value: "BAD Badger Daylighting Ltd"
        },
        {
          text: "BAM.A Brookfield Asset Management Inc Cl.A Lv",
          value: "BAM.A Brookfield Asset Management Inc Cl.A Lv"
        },
        {
          text: "BAM.PF.A Brookfield Asset Mgmt Inc Pref Ser 32",
          value: "BAM.PF.A Brookfield Asset Mgmt Inc Pref Ser 32"
        },
        {
          text: "BAM.PF.B Brookfield Asset Mgmt Inc Pref Ser 34",
          value: "BAM.PF.B Brookfield Asset Mgmt Inc Pref Ser 34"
        },
        {
          text: "BAM.PF.C Brookfield Asset Mgmt Inc Pref Ser 36",
          value: "BAM.PF.C Brookfield Asset Mgmt Inc Pref Ser 36"
        },
        {
          text: "BAM.PF.D Brookfield Asset Mgmt Inc Pr Ser 37",
          value: "BAM.PF.D Brookfield Asset Mgmt Inc Pr Ser 37"
        },
        {
          text: "BAM.PF.E Brookfield Asset Mgmt Pref Ser 38",
          value: "BAM.PF.E Brookfield Asset Mgmt Pref Ser 38"
        },
        {
          text: "BAM.PF.F Brookfield Asset Mgmt Inc Pref Ser 40",
          value: "BAM.PF.F Brookfield Asset Mgmt Inc Pref Ser 40"
        },
        {
          text: "BAM.PF.G Brookfield Asset Mgmt Inc Pref Ser 42",
          value: "BAM.PF.G Brookfield Asset Mgmt Inc Pref Ser 42"
        },
        {
          text: "BAM.PF.H Brookfield Asset MGT Inc Pref Ser 44",
          value: "BAM.PF.H Brookfield Asset MGT Inc Pref Ser 44"
        },
        {
          text: "BAM.PF.I Brookfield Asset Mgmt Inc Pref Ser 46",
          value: "BAM.PF.I Brookfield Asset Mgmt Inc Pref Ser 46"
        },
        {
          text: "BAM.PF.J Brookfield Asset Mgmt Inc Pref Ser 48",
          value: "BAM.PF.J Brookfield Asset Mgmt Inc Pref Ser 48"
        },
        {
          text: "BAM.PR.B Brookfield Asset Mgmt Inc Pr. Ser 2",
          value: "BAM.PR.B Brookfield Asset Mgmt Inc Pr. Ser 2"
        },
        {
          text: "BAM.PR.C Brookfield Asset Mgmt Inc Pr. Ser 4",
          value: "BAM.PR.C Brookfield Asset Mgmt Inc Pr. Ser 4"
        },
        {
          text: "BAM.PR.E Brookfield Asset Mgmt Inc Pr. Ser 8",
          value: "BAM.PR.E Brookfield Asset Mgmt Inc Pr. Ser 8"
        },
        {
          text: "BAM.PR.G Brookfield Asset Mgmt Inc Pr Ser 9",
          value: "BAM.PR.G Brookfield Asset Mgmt Inc Pr Ser 9"
        },
        {
          text: "BAM.PR.K Brookfield Asset Mgmt Inc Pr. Ser 13",
          value: "BAM.PR.K Brookfield Asset Mgmt Inc Pr. Ser 13"
        },
        {
          text: "BAM.PR.M Brookfield Asset Mgmt Inc Prf A Ser 17",
          value: "BAM.PR.M Brookfield Asset Mgmt Inc Prf A Ser 17"
        },
        {
          text: "BAM.PR.N Brookfield Asset Mgmt Pref Shs Ser 18",
          value: "BAM.PR.N Brookfield Asset Mgmt Pref Shs Ser 18"
        },
        {
          text: "BAM.PR.R Brookfield Asset Mgmt Pref Ser 24",
          value: "BAM.PR.R Brookfield Asset Mgmt Pref Ser 24"
        },
        {
          text: "BAM.PR.S Brookfield Asset Mgmt Inc Pref Ser 25",
          value: "BAM.PR.S Brookfield Asset Mgmt Inc Pref Ser 25"
        },
        {
          text: "BAM.PR.T Brookfield Asset Mgmt Pref Ser 26",
          value: "BAM.PR.T Brookfield Asset Mgmt Pref Ser 26"
        },
        {
          text: "BAM.PR.X Brookfield Am Pref Ser 28",
          value: "BAM.PR.X Brookfield Am Pref Ser 28"
        },
        {
          text: "BAM.PR.Z Brookfield Asset Mgmt Inc Pr Ser 30",
          value: "BAM.PR.Z Brookfield Asset Mgmt Inc Pr Ser 30"
        },
        {
          text: "BAR Balmoral Resources Ltd",
          value: "BAR Balmoral Resources Ltd"
        },
        {
          text: "BASE Evolve Glob Matls Mining Enh Yld Idx ETF",
          value: "BASE Evolve Glob Matls Mining Enh Yld Idx ETF"
        },
        {
          text: "BASE.B Evolve Glob Matls Mining Enh Yld Unhgd",
          value: "BASE.B Evolve Glob Matls Mining Enh Yld Unhgd"
        },
        { text: "BB Blackberry Limited", value: "BB Blackberry Limited" },
        {
          text: "BB.DB.V Blackberry Limited 3.75 Pct Debs USD",
          value: "BB.DB.V Blackberry Limited 3.75 Pct Debs USD"
        },
        {
          text: "BBD.A Bombardier Inc Cl A Mv",
          value: "BBD.A Bombardier Inc Cl A Mv"
        },
        {
          text: "BBD.B Bombardier Inc Cl B Sv",
          value: "BBD.B Bombardier Inc Cl B Sv"
        },
        { text: "BBD.PR.B Bombardier 2 Pr", value: "BBD.PR.B Bombardier 2 Pr" },
        {
          text: "BBD.PR.C Bombardier Inc Pref Class C",
          value: "BBD.PR.C Bombardier Inc Pref Class C"
        },
        {
          text: "BBD.PR.D Bombardier Inc Pref Ser 3",
          value: "BBD.PR.D Bombardier Inc Pref Ser 3"
        },
        {
          text: "BBL.A Brampton Brick Ltd Cl.A Sv",
          value: "BBL.A Brampton Brick Ltd Cl.A Sv"
        },
        {
          text: "BBU.UN Brookfield Business Partners LP",
          value: "BBU.UN Brookfield Business Partners LP"
        },
        {
          text: "BC.U Bespoke Capital Acquisition Class A USD",
          value: "BC.U Bespoke Capital Acquisition Class A USD"
        },
        {
          text: "BC.WT.U Bespoke Capital Acquisition Corp WT",
          value: "BC.WT.U Bespoke Capital Acquisition Corp WT"
        },
        { text: "BCE BCE Inc", value: "BCE BCE Inc" },
        {
          text: "BCE.PR.A BCE First Pr Shares Series Aa",
          value: "BCE.PR.A BCE First Pr Shares Series Aa"
        },
        {
          text: "BCE.PR.B BCE Inc First Pr Shares Series Ab",
          value: "BCE.PR.B BCE Inc First Pr Shares Series Ab"
        },
        {
          text: "BCE.PR.C BCE Inc Pr Shares Series AC",
          value: "BCE.PR.C BCE Inc Pr Shares Series AC"
        },
        {
          text: "BCE.PR.D BCE Inc Pref Shares Series Ad",
          value: "BCE.PR.D BCE Inc Pref Shares Series Ad"
        },
        {
          text: "BCE.PR.E BCE 1st Pref Shares Series Ae",
          value: "BCE.PR.E BCE 1st Pref Shares Series Ae"
        },
        {
          text: "BCE.PR.F BCE 1st Pref Shares Series Af",
          value: "BCE.PR.F BCE 1st Pref Shares Series Af"
        },
        {
          text: "BCE.PR.G BCE 1st Pref Shares Series Ag",
          value: "BCE.PR.G BCE 1st Pref Shares Series Ag"
        },
        {
          text: "BCE.PR.H BCE 1st Pref Shares Series Ah",
          value: "BCE.PR.H BCE 1st Pref Shares Series Ah"
        },
        {
          text: "BCE.PR.I BCE 1st Pref Shares Series Ai",
          value: "BCE.PR.I BCE 1st Pref Shares Series Ai"
        },
        {
          text: "BCE.PR.J BCE Inc Pref Sh Series Aj",
          value: "BCE.PR.J BCE Inc Pref Sh Series Aj"
        },
        {
          text: "BCE.PR.K BCE Inc Pref Ser AK",
          value: "BCE.PR.K BCE Inc Pref Ser AK"
        },
        {
          text: "BCE.PR.L BCE Inc Pref Ser AL",
          value: "BCE.PR.L BCE Inc Pref Ser AL"
        },
        {
          text: "BCE.PR.M BCE Inc Pref Shares Series Am",
          value: "BCE.PR.M BCE Inc Pref Shares Series Am"
        },
        {
          text: "BCE.PR.N BCE Inc Pref Shares Series An",
          value: "BCE.PR.N BCE Inc Pref Shares Series An"
        },
        {
          text: "BCE.PR.O BCE Inc Pref Shares Series Ao",
          value: "BCE.PR.O BCE Inc Pref Shares Series Ao"
        },
        {
          text: "BCE.PR.Q BCE Inc Pref Shares Series Aq",
          value: "BCE.PR.Q BCE Inc Pref Shares Series Aq"
        },
        { text: "BCE.PR.R BCE Inc Ser R", value: "BCE.PR.R BCE Inc Ser R" },
        { text: "BCE.PR.S BCE Inc Ser S", value: "BCE.PR.S BCE Inc Ser S" },
        {
          text: "BCE.PR.T BCE Inc First Pref Series T",
          value: "BCE.PR.T BCE Inc First Pref Series T"
        },
        {
          text: "BCE.PR.Y BCE Inc Ser Y Pr",
          value: "BCE.PR.Y BCE Inc Ser Y Pr"
        },
        {
          text: "BCE.PR.Z BCE Inc Series Z",
          value: "BCE.PR.Z BCE Inc Series Z"
        },
        {
          text: "BCI New Look Vision Group Inc",
          value: "BCI New Look Vision Group Inc"
        },
        {
          text: "BDI Black Diamond Group Ltd",
          value: "BDI Black Diamond Group Ltd"
        },
        {
          text: "BDIV Brompton Global Dividend Growth ETF",
          value: "BDIV Brompton Global Dividend Growth ETF"
        },
        {
          text: "BDT Bird Construction Inc",
          value: "BDT Bird Construction Inc"
        },
        {
          text: "BEI.UN Boardwalk Real Estate Investment Trust",
          value: "BEI.UN Boardwalk Real Estate Investment Trust"
        },
        {
          text: "BEK.B Becker Milk Company Ltd Cl.B NV",
          value: "BEK.B Becker Milk Company Ltd Cl.B NV"
        },
        {
          text: "BEP.PR.E Brookfield Renewable LP Pref Ser 5",
          value: "BEP.PR.E Brookfield Renewable LP Pref Ser 5"
        },
        {
          text: "BEP.PR.G Brookfield Renewable LP Pref Ser 7",
          value: "BEP.PR.G Brookfield Renewable LP Pref Ser 7"
        },
        {
          text: "BEP.PR.I Brookfield Renewable Pref Ser 9",
          value: "BEP.PR.I Brookfield Renewable Pref Ser 9"
        },
        {
          text: "BEP.PR.K Brookfield Renewable LP Pref Ser 11",
          value: "BEP.PR.K Brookfield Renewable LP Pref Ser 11"
        },
        {
          text: "BEP.PR.M Brookfield Renewable LP Pref Ser 13",
          value: "BEP.PR.M Brookfield Renewable LP Pref Ser 13"
        },
        {
          text: "BEP.PR.O Brookfield Renewable Partners L.P.",
          value: "BEP.PR.O Brookfield Renewable Partners L.P."
        },
        {
          text: "BEP.UN Brookfield Renewable Partners LP",
          value: "BEP.UN Brookfield Renewable Partners LP"
        },
        {
          text: "BFIN Brompton Na Financials Dividend ETF",
          value: "BFIN Brompton Na Financials Dividend ETF"
        },
        {
          text: "BFIN.U Brompton Na Financials Dividend ETF USD",
          value: "BFIN.U Brompton Na Financials Dividend ETF USD"
        },
        {
          text: "BGC Bristol Gate Concentrated CDN Eqty ETF",
          value: "BGC Bristol Gate Concentrated CDN Eqty ETF"
        },
        {
          text: "BGI.UN Brookfield Glbl Infras Sec Inc Fd",
          value: "BGI.UN Brookfield Glbl Infras Sec Inc Fd"
        },
        {
          text: "BGU Bristol Gate Concentrated US Eqty ETF",
          value: "BGU Bristol Gate Concentrated US Eqty ETF"
        },
        {
          text: "BGU.U Bristol Gate Conc US Eqty ETF USD",
          value: "BGU.U Bristol Gate Conc US Eqty ETF USD"
        },
        {
          text: "BHC Bausch Health Companies Inc",
          value: "BHC Bausch Health Companies Inc"
        },
        {
          text: "BIK.PR.A Bip Investment Corp Pref Ser 1",
          value: "BIK.PR.A Bip Investment Corp Pref Ser 1"
        },
        {
          text: "BIP.PR.A Brookfield Infra Partners LP Pref Ser 1",
          value: "BIP.PR.A Brookfield Infra Partners LP Pref Ser 1"
        },
        {
          text: "BIP.PR.B Brookfield Infra Partners LP Pref Ser 3",
          value: "BIP.PR.B Brookfield Infra Partners LP Pref Ser 3"
        },
        {
          text: "BIP.PR.C Brookfield Infra Partners LP Pref Ser 5",
          value: "BIP.PR.C Brookfield Infra Partners LP Pref Ser 5"
        },
        {
          text: "BIP.PR.D Brookfield Infra Partners LP Pref Ser 7",
          value: "BIP.PR.D Brookfield Infra Partners LP Pref Ser 7"
        },
        {
          text: "BIP.PR.E Brookfield Infra Partners LP Pref Ser 9",
          value: "BIP.PR.E Brookfield Infra Partners LP Pref Ser 9"
        },
        {
          text: "BIP.PR.F Brookfield Infra Partners LP Pref Ser 11",
          value: "BIP.PR.F Brookfield Infra Partners LP Pref Ser 11"
        },
        {
          text: "BIP.UN Brookfield Infra Partners LP Units",
          value: "BIP.UN Brookfield Infra Partners LP Units"
        },
        {
          text: "BIPC Brookfield Infrastructure Corporation",
          value: "BIPC Brookfield Infrastructure Corporation"
        },
        {
          text: "BIR Birchcliff Energy Ltd",
          value: "BIR Birchcliff Energy Ltd"
        },
        {
          text: "BIR.PR.A Birchcliff Energy Ltd Pref Sh A",
          value: "BIR.PR.A Birchcliff Energy Ltd Pref Sh A"
        },
        {
          text: "BIR.PR.C Birchcliff Energy Ltd Pref Ser C",
          value: "BIR.PR.C Birchcliff Energy Ltd Pref Ser C"
        },
        { text: "BK Canadian Banc Corp", value: "BK Canadian Banc Corp" },
        {
          text: "BK.PR.A Canadian Banc Corp Pref A",
          value: "BK.PR.A Canadian Banc Corp Pref A"
        },
        { text: "BKI Black Iron Inc", value: "BKI Black Iron Inc" },
        {
          text: "BKL.C Invesco Senior Loan Index ETF",
          value: "BKL.C Invesco Senior Loan Index ETF"
        },
        {
          text: "BKL.F Powershares Senior Loan Index ETF",
          value: "BKL.F Powershares Senior Loan Index ETF"
        },
        {
          text: "BKL.U Invesco Senior Loan Index USD ETF",
          value: "BKL.U Invesco Senior Loan Index USD ETF"
        },
        { text: "BKX Bnk Petroleum Inc", value: "BKX Bnk Petroleum Inc" },
        {
          text: "BL.UN Global Innovation Dividend Fund",
          value: "BL.UN Global Innovation Dividend Fund"
        },
        {
          text: "BLB.UN Bloom Select Income Fund",
          value: "BLB.UN Bloom Select Income Fund"
        },
        {
          text: "BLCK First Trust Indxx Innov Trans Proc ETF",
          value: "BLCK First Trust Indxx Innov Trans Proc ETF"
        },
        {
          text: "BLDP Ballard Power Systems Inc",
          value: "BLDP Ballard Power Systems Inc"
        },
        { text: "BLU Bellus Health Inc", value: "BLU Bellus Health Inc" },
        { text: "BLX Boralex Inc", value: "BLX Boralex Inc" },
        { text: "BMO Bank of Montreal", value: "BMO Bank of Montreal" },
        {
          text: "BMO.PR.A BMO Pref Shares Series 26",
          value: "BMO.PR.A BMO Pref Shares Series 26"
        },
        {
          text: "BMO.PR.B BMO Pref Shares Series 38",
          value: "BMO.PR.B BMO Pref Shares Series 38"
        },
        {
          text: "BMO.PR.C BMO Pref Shares Series 40",
          value: "BMO.PR.C BMO Pref Shares Series 40"
        },
        {
          text: "BMO.PR.D BMO Pref Shares Series 42",
          value: "BMO.PR.D BMO Pref Shares Series 42"
        },
        {
          text: "BMO.PR.E BMO Pref Shares Series 44",
          value: "BMO.PR.E BMO Pref Shares Series 44"
        },
        {
          text: "BMO.PR.F BMO Pref Shares Series 46",
          value: "BMO.PR.F BMO Pref Shares Series 46"
        },
        {
          text: "BMO.PR.Q Bank of Montreal B Pref Sh Ser 25",
          value: "BMO.PR.Q Bank of Montreal B Pref Sh Ser 25"
        },
        {
          text: "BMO.PR.S BMO Cl B Pref Shares Ser 27",
          value: "BMO.PR.S BMO Cl B Pref Shares Ser 27"
        },
        {
          text: "BMO.PR.T BMO Non Cum Cl B Prf Share Series 29",
          value: "BMO.PR.T BMO Non Cum Cl B Prf Share Series 29"
        },
        {
          text: "BMO.PR.W BMO Cl B Pref Shares Ser 31",
          value: "BMO.PR.W BMO Cl B Pref Shares Ser 31"
        },
        {
          text: "BMO.PR.Y Bank of Montreal Pref Ser 33",
          value: "BMO.PR.Y Bank of Montreal Pref Ser 33"
        },
        {
          text: "BMO.PR.Z Bank of Montreal Pref Ser 35",
          value: "BMO.PR.Z Bank of Montreal Pref Ser 35"
        },
        {
          text: "BNC Purpose CDN Financial Income Fund ETF",
          value: "BNC Purpose CDN Financial Income Fund ETF"
        },
        {
          text: "BND Purpose Tactical Inv Grade Bond ETF",
          value: "BND Purpose Tactical Inv Grade Bond ETF"
        },
        { text: "BNE Bonterra Energy Corp", value: "BNE Bonterra Energy Corp" },
        { text: "BNG Bengal Energy Ltd", value: "BNG Bengal Energy Ltd" },
        {
          text: "BNP Bonavista Energy Corp",
          value: "BNP Bonavista Energy Corp"
        },
        { text: "BNS Bank of Nova Scotia", value: "BNS Bank of Nova Scotia" },
        {
          text: "BNS.PR.D Bns Pref Shares Series 31",
          value: "BNS.PR.D Bns Pref Shares Series 31"
        },
        {
          text: "BNS.PR.E Bns Pref Shares Series 34",
          value: "BNS.PR.E Bns Pref Shares Series 34"
        },
        {
          text: "BNS.PR.F Bns Pref Shares Series 33",
          value: "BNS.PR.F Bns Pref Shares Series 33"
        },
        {
          text: "BNS.PR.G Bns Pref Shares Series 36",
          value: "BNS.PR.G Bns Pref Shares Series 36"
        },
        {
          text: "BNS.PR.H Bns Pref Shares Series 38",
          value: "BNS.PR.H Bns Pref Shares Series 38"
        },
        {
          text: "BNS.PR.I Bns Preferred Shares Series 40",
          value: "BNS.PR.I Bns Preferred Shares Series 40"
        },
        {
          text: "BNS.PR.Y Bank of Nova Scotia 5 Yr Pref Ser 30",
          value: "BNS.PR.Y Bank of Nova Scotia 5 Yr Pref Ser 30"
        },
        { text: "BNS.PR.Z Bns Pref Ser 32", value: "BNS.PR.Z Bns Pref Ser 32" },
        { text: "BOS Airboss America J", value: "BOS Airboss America J" },
        {
          text: "BOY Boyuan Construction Group Inc",
          value: "BOY Boyuan Construction Group Inc"
        },
        {
          text: "BPF.UN Boston Pizza Royalties Income Fund",
          value: "BPF.UN Boston Pizza Royalties Income Fund"
        },
        {
          text: "BPO.PR.A Brookfield Office Properties Pref Ser Aa",
          value: "BPO.PR.A Brookfield Office Properties Pref Ser Aa"
        },
        {
          text: "BPO.PR.C Brookfield Office Properties Pref Ser CC",
          value: "BPO.PR.C Brookfield Office Properties Pref Ser CC"
        },
        {
          text: "BPO.PR.E Brookfield Office Properties Pref Ser Ee",
          value: "BPO.PR.E Brookfield Office Properties Pref Ser Ee"
        },
        {
          text: "BPO.PR.G Brookfield Office Properties Pref Ser G",
          value: "BPO.PR.G Brookfield Office Properties Pref Ser G"
        },
        {
          text: "BPO.PR.I Brookfield Office Properties Pref Ser II",
          value: "BPO.PR.I Brookfield Office Properties Pref Ser II"
        },
        {
          text: "BPO.PR.N Brookfield Office Properties Pref Ser N",
          value: "BPO.PR.N Brookfield Office Properties Pref Ser N"
        },
        {
          text: "BPO.PR.P Brookfield Office Properties Pref Ser P",
          value: "BPO.PR.P Brookfield Office Properties Pref Ser P"
        },
        {
          text: "BPO.PR.R Brookfield Office Properties Pref Ser R",
          value: "BPO.PR.R Brookfield Office Properties Pref Ser R"
        },
        {
          text: "BPO.PR.S Brookfield Office Properties Pref Ser S",
          value: "BPO.PR.S Brookfield Office Properties Pref Ser S"
        },
        {
          text: "BPO.PR.T Brookfield Office Properties Pref Ser T",
          value: "BPO.PR.T Brookfield Office Properties Pref Ser T"
        },
        {
          text: "BPO.PR.W Brookfield Office Properties Pref Ser W",
          value: "BPO.PR.W Brookfield Office Properties Pref Ser W"
        },
        {
          text: "BPO.PR.X Brookfield Office Properties Pref Ser V",
          value: "BPO.PR.X Brookfield Office Properties Pref Ser V"
        },
        {
          text: "BPO.PR.Y Brookfield Office Properties Pref Ser Y",
          value: "BPO.PR.Y Brookfield Office Properties Pref Ser Y"
        },
        {
          text: "BPRF Bf and Crumrine Invest Grade Pref ETF",
          value: "BPRF Bf and Crumrine Invest Grade Pref ETF"
        },
        {
          text: "BPRF.U Bf and Crumrine Invgrade Pref ETF USD",
          value: "BPRF.U Bf and Crumrine Invgrade Pref ETF USD"
        },
        {
          text: "BPS.PR.A Brookfield Property Split Pref Ser 2",
          value: "BPS.PR.A Brookfield Property Split Pref Ser 2"
        },
        {
          text: "BPS.PR.B Brookfield Property Split Pref Ser 3",
          value: "BPS.PR.B Brookfield Property Split Pref Ser 3"
        },
        {
          text: "BPS.PR.C Brookfield Property Split Pref Ser 4",
          value: "BPS.PR.C Brookfield Property Split Pref Ser 4"
        },
        {
          text: "BPS.PR.U Brookfield Property Split Pref Ser 1",
          value: "BPS.PR.U Brookfield Property Split Pref Ser 1"
        },
        {
          text: "BPY.UN Brookfield Property Partners LP",
          value: "BPY.UN Brookfield Property Partners LP"
        },
        { text: "BR Big Rock Brewery Inc", value: "BR Big Rock Brewery Inc" },
        {
          text: "BRE Brookfield Real Estate Services Inc",
          value: "BRE Brookfield Real Estate Services Inc"
        },
        {
          text: "BRF.PR.A Brookfield Renewable Pref Cl A Ser 1",
          value: "BRF.PR.A Brookfield Renewable Pref Cl A Ser 1"
        },
        {
          text: "BRF.PR.B Brookfield Renewable Pref Cl A Ser 2",
          value: "BRF.PR.B Brookfield Renewable Pref Cl A Ser 2"
        },
        {
          text: "BRF.PR.C Brookfield Renewable Power Pref Eqty Inc",
          value: "BRF.PR.C Brookfield Renewable Power Pref Eqty Inc"
        },
        {
          text: "BRF.PR.E Brookfield Renewable Power Pref Eqt Sr 5",
          value: "BRF.PR.E Brookfield Renewable Power Pref Eqt Sr 5"
        },
        {
          text: "BRF.PR.F Brookfield Renewable Power Pref Eqt Sr 6",
          value: "BRF.PR.F Brookfield Renewable Power Pref Eqt Sr 6"
        },
        { text: "BRY Bri Chem Corp", value: "BRY Bri Chem Corp" },
        { text: "BSC Bns Split Corp II", value: "BSC Bns Split Corp II" },
        {
          text: "BSC.PR.C Bns Split Corp II Pref B Series 2",
          value: "BSC.PR.C Bns Split Corp II Pref B Series 2"
        },
        {
          text: "BSO.UN Brookfield Select Opportunities Inc Fund",
          value: "BSO.UN Brookfield Select Opportunities Inc Fund"
        },
        { text: "BSX Belo Sun Mining Corp", value: "BSX Belo Sun Mining Corp" },
        {
          text: "BTB.DB.F Btb REIT 7.15 Pct Debs",
          value: "BTB.DB.F Btb REIT 7.15 Pct Debs"
        },
        {
          text: "BTB.DB.G Btb REIT 6.0 Pct Debs",
          value: "BTB.DB.G Btb REIT 6.0 Pct Debs"
        },
        { text: "BTB.UN Btb REIT Units", value: "BTB.UN Btb REIT Units" },
        { text: "BTE Baytex Energy Corp", value: "BTE Baytex Energy Corp" },
        { text: "BTO B2Gold Corp", value: "BTO B2Gold Corp" },
        {
          text: "BU Burcon Nutrascience Corp",
          value: "BU Burcon Nutrascience Corp"
        },
        {
          text: "BUA.UN Bloom US Income and Growth Fund",
          value: "BUA.UN Bloom US Income and Growth Fund"
        },
        { text: "BUI Buhler Ind", value: "BUI Buhler Ind" },
        {
          text: "BXF CI First Asset 1 To 5 Yr Lad Gov Stp Bd",
          value: "BXF CI First Asset 1 To 5 Yr Lad Gov Stp Bd"
        },
        {
          text: "BYD Boyd Group Services Inc",
          value: "BYD Boyd Group Services Inc"
        },
        {
          text: "BYL Baylin Technologies Inc",
          value: "BYL Baylin Technologies Inc"
        },
        {
          text: "BYL.DB Baylin Technologies Inc 6.5 Pct Debs",
          value: "BYL.DB Baylin Technologies Inc 6.5 Pct Debs"
        },
        { text: "A B", value: "A B" },
        {
          text: "CACB CIBC Active Invst Grade Corp Bond ETF",
          value: "CACB CIBC Active Invst Grade Corp Bond ETF"
        },
        { text: "CAE Cae Inc", value: "CAE Cae Inc" },
        {
          text: "CAGG Wisdomtree Yld Enh CDN Agrt Bondetf",
          value: "CAGG Wisdomtree Yld Enh CDN Agrt Bondetf"
        },
        {
          text: "CAGS Wisdomtree Yld Enh CDN ST Agg Bdetf",
          value: "CAGS Wisdomtree Yld Enh CDN ST Agg Bdetf"
        },
        {
          text: "CAL Caledonia Mining Corporation Plc",
          value: "CAL Caledonia Mining Corporation Plc"
        },
        {
          text: "CALL Evolve US Banks Enhanced Yield ETF",
          value: "CALL Evolve US Banks Enhanced Yield ETF"
        },
        {
          text: "CALL.B Evolve US Banks Enhanced Yield Unheg",
          value: "CALL.B Evolve US Banks Enhanced Yield Unheg"
        },
        {
          text: "CALL.U Evolve US Banks Enhanced Yld Fd ETF USD",
          value: "CALL.U Evolve US Banks Enhanced Yld Fd ETF USD"
        },
        { text: "CAR.UN CDN Apartment Un", value: "CAR.UN CDN Apartment Un" },
        {
          text: "CARS Evolve Automobile Innovation Idx Hgd ETF",
          value: "CARS Evolve Automobile Innovation Idx Hgd ETF"
        },
        {
          text: "CARS.B Evolve Automobile Innovation Idx Uh ETF",
          value: "CARS.B Evolve Automobile Innovation Idx Uh ETF"
        },
        {
          text: "CARS.U Evolve Automobile Innovation Idx Fd USD",
          value: "CARS.U Evolve Automobile Innovation Idx Fd USD"
        },
        { text: "CAS Cascades Inc", value: "CAS Cascades Inc" },
        {
          text: "CBH Ishares 1-10 Yr Laddered Corp Bond ETF",
          value: "CBH Ishares 1-10 Yr Laddered Corp Bond ETF"
        },
        {
          text: "CBO Ishares 1-5Yr Laddered Corp Bond ETF",
          value: "CBO Ishares 1-5Yr Laddered Corp Bond ETF"
        },
        {
          text: "CBT.UN June 2020 Corporate Bond Trust",
          value: "CBT.UN June 2020 Corporate Bond Trust"
        },
        {
          text: "CCA Cogeco Communications Inc",
          value: "CCA Cogeco Communications Inc"
        },
        { text: "CCL.A Ccl Inc Cl A", value: "CCL.A Ccl Inc Cl A" },
        {
          text: "CCL.B Ccl Industries Inc Cl B NV",
          value: "CCL.B Ccl Industries Inc Cl B NV"
        },
        { text: "CCM Canarc Res J", value: "CCM Canarc Res J" },
        { text: "CCO Cameco Corp", value: "CCO Cameco Corp" },
        {
          text: "CCS.PR.C Co-Operators Gen Ins Cl E Prf",
          value: "CCS.PR.C Co-Operators Gen Ins Cl E Prf"
        },
        {
          text: "CCX Canadian Crude Oil Index ETF",
          value: "CCX Canadian Crude Oil Index ETF"
        },
        {
          text: "CDAY Ceridian Hcm Holdings Inc",
          value: "CDAY Ceridian Hcm Holdings Inc"
        },
        {
          text: "CDD.UN Core Canadian Dividend Trust",
          value: "CDD.UN Core Canadian Dividend Trust"
        },
        {
          text: "CDV Cardinal Resources Limited",
          value: "CDV Cardinal Resources Limited"
        },
        {
          text: "CDZ Ishares S&P TSX CDN Dividend ETF",
          value: "CDZ Ishares S&P TSX CDN Dividend ETF"
        },
        { text: "CEE Centamin Plc", value: "CEE Centamin Plc" },
        {
          text: "CEF Sprott Physical Gold Silver Trust",
          value: "CEF Sprott Physical Gold Silver Trust"
        },
        {
          text: "CEF.U Central Fund of Canada Ltd Cl.U NV",
          value: "CEF.U Central Fund of Canada Ltd Cl.U NV"
        },
        {
          text: "CERV Cervus Equipment Corp",
          value: "CERV Cervus Equipment Corp"
        },
        {
          text: "CET Cathedral Energy Services Ltd",
          value: "CET Cathedral Energy Services Ltd"
        },
        {
          text: "CEU Ces Energy Solutions Corp",
          value: "CEU Ces Energy Solutions Corp"
        },
        {
          text: "CEW Ishares Equal Weight Banc Lifeco ETF",
          value: "CEW Ishares Equal Weight Banc Lifeco ETF"
        },
        {
          text: "CF Canaccord Genuity Group Inc",
          value: "CF Canaccord Genuity Group Inc"
        },
        {
          text: "CF.DB.A Canaccord Genuity Group 6.25 Pct Debs",
          value: "CF.DB.A Canaccord Genuity Group 6.25 Pct Debs"
        },
        {
          text: "CF.PR.A Canaccord Genuity Group Inc Pref A",
          value: "CF.PR.A Canaccord Genuity Group Inc Pref A"
        },
        {
          text: "CF.PR.C Canaccord Genuity Group Inc Pref C",
          value: "CF.PR.C Canaccord Genuity Group Inc Pref C"
        },
        { text: "CFF Conifex Timber Inc", value: "CFF Conifex Timber Inc" },
        {
          text: "CFLX CIBC Flexible Yield ETF Hedged",
          value: "CFLX CIBC Flexible Yield ETF Hedged"
        },
        { text: "CFP Canfor Corp", value: "CFP Canfor Corp" },
        {
          text: "CFW Calfrac Well Services Ltd",
          value: "CFW Calfrac Well Services Ltd"
        },
        {
          text: "CFX Canfor Pulp Products Inc",
          value: "CFX Canfor Pulp Products Inc"
        },
        { text: "CG Centerra Gold Inc", value: "CG Centerra Gold Inc" },
        {
          text: "CGAA CI First Asset Glb Asset Allocation ETF",
          value: "CGAA CI First Asset Glb Asset Allocation ETF"
        },
        {
          text: "CGG China Gold Int Resources Corp",
          value: "CGG China Gold Int Resources Corp"
        },
        { text: "CGI CDN General Inv", value: "CGI CDN General Inv" },
        {
          text: "CGI.PR.D Canadian General Inv Ltd Pref Ser 4",
          value: "CGI.PR.D Canadian General Inv Ltd Pref Ser 4"
        },
        {
          text: "CGL Ishares Gold Bullion ETF Hdg",
          value: "CGL Ishares Gold Bullion ETF Hdg"
        },
        {
          text: "CGL.C Ishares Gold Bullion ETF Non Hdg",
          value: "CGL.C Ishares Gold Bullion ETF Non Hdg"
        },
        { text: "CGO Cogeco Inc Sv", value: "CGO Cogeco Inc Sv" },
        {
          text: "CGR Ishares Global Real Estate ETF",
          value: "CGR Ishares Global Real Estate ETF"
        },
        { text: "CGT Columbus Gold Corp", value: "CGT Columbus Gold Corp" },
        { text: "CGX Cineplex Inc", value: "CGX Cineplex Inc" },
        {
          text: "CGXF CI First Asset Gold Giants Cvr Call ETF",
          value: "CGXF CI First Asset Gold Giants Cvr Call ETF"
        },
        { text: "CGY Calian Group Ltd", value: "CGY Calian Group Ltd" },
        {
          text: "CHB Ishares US HY Fixed Income Index ETF",
          value: "CHB Ishares US HY Fixed Income Index ETF"
        },
        {
          text: "CHE.DB.B Chemtrade Logistics If 5.25 Pct Debs",
          value: "CHE.DB.B Chemtrade Logistics If 5.25 Pct Debs"
        },
        {
          text: "CHE.DB.C Chemtrade Logistics Income Fund 5.0 Debs",
          value: "CHE.DB.C Chemtrade Logistics Income Fund 5.0 Debs"
        },
        {
          text: "CHE.DB.D Chemtrade Logistics Inc Fd 4.75 Pct Debs",
          value: "CHE.DB.D Chemtrade Logistics Inc Fd 4.75 Pct Debs"
        },
        {
          text: "CHE.DB.E Chemtrade Logistics If 6.50 Pct Debs",
          value: "CHE.DB.E Chemtrade Logistics If 6.50 Pct Debs"
        },
        {
          text: "CHE.UN Chemtrade Logistics Income Fund",
          value: "CHE.UN Chemtrade Logistics Income Fund"
        },
        { text: "CHH Centric Health Corp", value: "CHH Centric Health Corp" },
        {
          text: "CHNA.B Wisdomtree Icbccs S&P China 500 Idx ETF",
          value: "CHNA.B Wisdomtree Icbccs S&P China 500 Idx ETF"
        },
        {
          text: "CHP.UN Choice Properties REIT",
          value: "CHP.UN Choice Properties REIT"
        },
        { text: "CHR Chorus Aviation Inc", value: "CHR Chorus Aviation Inc" },
        {
          text: "CHR.DB.A Chorus Aviation Inc 5.75 Pct Debs",
          value: "CHR.DB.A Chorus Aviation Inc 5.75 Pct Debs"
        },
        {
          text: "CHW Chesswood Group Limited",
          value: "CHW Chesswood Group Limited"
        },
        {
          text: "CIA Champion Iron Limited",
          value: "CIA Champion Iron Limited"
        },
        {
          text: "CIC CI First Asset Canbanc Income Class ETF",
          value: "CIC CI First Asset Canbanc Income Class ETF"
        },
        {
          text: "CIF Ishares Global Infrastructure Index ETF",
          value: "CIF Ishares Global Infrastructure Index ETF"
        },
        {
          text: "CIGI Colliers International Group Inc",
          value: "CIGI Colliers International Group Inc"
        },
        {
          text: "CIQ.UN Canadian High Income Equity Fund",
          value: "CIQ.UN Canadian High Income Equity Fund"
        },
        {
          text: "CIU.PR.A Cu Inc Pref Shares",
          value: "CIU.PR.A Cu Inc Pref Shares"
        },
        {
          text: "CIU.PR.C Cu Inc Pref Ser 4",
          value: "CIU.PR.C Cu Inc Pref Ser 4"
        },
        { text: "CIX CI Financial Corp", value: "CIX CI Financial Corp" },
        { text: "CJ Cardinal Energy Ltd", value: "CJ Cardinal Energy Ltd" },
        {
          text: "CJ.DB Cardinal Energy Ltd 5.50 Pct Debs",
          value: "CJ.DB Cardinal Energy Ltd 5.50 Pct Debs"
        },
        {
          text: "CJR.B Corus Entertainment Inc Cl.B NV",
          value: "CJR.B Corus Entertainment Inc Cl.B NV"
        },
        { text: "CJT Cargojet Inc", value: "CJT Cargojet Inc" },
        {
          text: "CJT.DB.D Cargojet Inc 5.75 Pct Debs",
          value: "CJT.DB.D Cargojet Inc 5.75 Pct Debs"
        },
        {
          text: "CJT.DB.E Cargojet Inc 5.75 Pct Debs",
          value: "CJT.DB.E Cargojet Inc 5.75 Pct Debs"
        },
        { text: "CKE Chinook Energy Inc", value: "CKE Chinook Energy Inc" },
        { text: "CKI Clarke Inc", value: "CKI Clarke Inc" },
        {
          text: "CKI.DB Clarke Inc 6.25 Pct Debs",
          value: "CKI.DB Clarke Inc 6.25 Pct Debs"
        },
        {
          text: "CLF Ishares 1-5 Yr Ladder Govt Bond ETF",
          value: "CLF Ishares 1-5 Yr Ladder Govt Bond ETF"
        },
        {
          text: "CLG Ishares 1-10 Yr Ladder Govt Bond ETF",
          value: "CLG Ishares 1-10 Yr Ladder Govt Bond ETF"
        },
        { text: "CLIQ Alcanna Inc", value: "CLIQ Alcanna Inc" },
        {
          text: "CLIQ.DB Alcanna Inc 4.70 Pct Debs",
          value: "CLIQ.DB Alcanna Inc 4.70 Pct Debs"
        },
        {
          text: "CLQ Clean Teq Holdings Limited",
          value: "CLQ Clean Teq Holdings Limited"
        },
        {
          text: "CLR Clearwater Seafoods Incorporated",
          value: "CLR Clearwater Seafoods Incorporated"
        },
        { text: "CLS Celestica Inc Sv", value: "CLS Celestica Inc Sv" },
        {
          text: "CM Canadian Imperial Bank of Commerce",
          value: "CM Canadian Imperial Bank of Commerce"
        },
        { text: "CM.PR.O CIBC Pref Ser 39", value: "CM.PR.O CIBC Pref Ser 39" },
        { text: "CM.PR.P CIBC Pref Ser 41", value: "CM.PR.P CIBC Pref Ser 41" },
        { text: "CM.PR.Q CIBC Pref Ser 43", value: "CM.PR.Q CIBC Pref Ser 43" },
        { text: "CM.PR.R CIBC Pref Ser 45", value: "CM.PR.R CIBC Pref Ser 45" },
        {
          text: "CM.PR.S CIBC Pref Series 47",
          value: "CM.PR.S CIBC Pref Series 47"
        },
        {
          text: "CM.PR.T CIBC Pref Series 49",
          value: "CM.PR.T CIBC Pref Series 49"
        },
        {
          text: "CM.PR.Y CIBC Pref Series 51",
          value: "CM.PR.Y CIBC Pref Series 51"
        },
        {
          text: "CMAG CI Munro Alt Global Growth ETF",
          value: "CMAG CI Munro Alt Global Growth ETF"
        },
        {
          text: "CMAR CI Marret Alt Absolute Return Bnd ETF",
          value: "CMAR CI Marret Alt Absolute Return Bnd ETF"
        },
        {
          text: "CMAR.U CI Marret Alt Absolute Rtrn Bnd ETF USD",
          value: "CMAR.U CI Marret Alt Absolute Rtrn Bnd ETF USD"
        },
        {
          text: "CMCE CIBC Multifactor Canadian Equity ETF",
          value: "CMCE CIBC Multifactor Canadian Equity ETF"
        },
        {
          text: "CMG Computer Modelling Group Ltd",
          value: "CMG Computer Modelling Group Ltd"
        },
        {
          text: "CMMC Copper Mountain Mining Corp",
          value: "CMMC Copper Mountain Mining Corp"
        },
        {
          text: "CMR Ishares Premium Money Market ETF",
          value: "CMR Ishares Premium Money Market ETF"
        },
        {
          text: "CMUE CIBC Multifactor US Equity ETF",
          value: "CMUE CIBC Multifactor US Equity ETF"
        },
        {
          text: "CMUE.F CIBC Multifactor US Equity ETF Hedged",
          value: "CMUE.F CIBC Multifactor US Equity ETF Hedged"
        },
        { text: "CNE Canacol Energy Ltd", value: "CNE Canacol Energy Ltd" },
        { text: "CNQ CDN Natural Res", value: "CNQ CDN Natural Res" },
        {
          text: "CNR Canadian National Railway Co.",
          value: "CNR Canadian National Railway Co."
        },
        {
          text: "CNT Century Global Commodities Corp",
          value: "CNT Century Global Commodities Corp"
        },
        { text: "CNU Cnooc Limited", value: "CNU Cnooc Limited" },
        { text: "COG Condor Gold Plc", value: "COG Condor Gold Plc" },
        {
          text: "COMM BMO Global Communications Index ETF",
          value: "COMM BMO Global Communications Index ETF"
        },
        { text: "COP Coro Mining Corp", value: "COP Coro Mining Corp" },
        {
          text: "CORP Exemplar Investment Grade Fund",
          value: "CORP Exemplar Investment Grade Fund"
        },
        {
          text: "CORV Correvio Pharma Corp",
          value: "CORV Correvio Pharma Corp"
        },
        {
          text: "COW Ishares Global Agri Index ETF",
          value: "COW Ishares Global Agri Index ETF"
        },
        {
          text: "CP Canadian Pacific Railway Limited",
          value: "CP Canadian Pacific Railway Limited"
        },
        {
          text: "CPD Ishares S&P TSX CDN Pref ETF",
          value: "CPD Ishares S&P TSX CDN Pref ETF"
        },
        {
          text: "CPG Crescent Point Energy Corp",
          value: "CPG Crescent Point Energy Corp"
        },
        {
          text: "CPH Cipher Pharmaceuticals Inc",
          value: "CPH Cipher Pharmaceuticals Inc"
        },
        { text: "CPI Condor Petroleum Inc", value: "CPI Condor Petroleum Inc" },
        { text: "CPX Capital Power Corp", value: "CPX Capital Power Corp" },
        {
          text: "CPX.PR.A Capital Power Corporation Pref Ser 1",
          value: "CPX.PR.A Capital Power Corporation Pref Ser 1"
        },
        {
          text: "CPX.PR.C Capital Power Corp Pref Ser 3",
          value: "CPX.PR.C Capital Power Corp Pref Ser 3"
        },
        {
          text: "CPX.PR.E Capital Power Corporation Pref Ser 5",
          value: "CPX.PR.E Capital Power Corporation Pref Ser 5"
        },
        {
          text: "CPX.PR.G Capital Power Corporation Pref Ser 7",
          value: "CPX.PR.G Capital Power Corporation Pref Ser 7"
        },
        {
          text: "CPX.PR.I Capital Power Corporation Pref Ser 9",
          value: "CPX.PR.I Capital Power Corporation Pref Ser 9"
        },
        {
          text: "CPX.PR.K Capital Power Corporation Pref Series 11",
          value: "CPX.PR.K Capital Power Corporation Pref Series 11"
        },
        { text: "CQE Cequence Energy Ltd", value: "CQE Cequence Energy Ltd" },
        { text: "CR Crew Energy Inc", value: "CR Crew Energy Inc" },
        {
          text: "CRDL Cardiol Therapeutics Inc",
          value: "CRDL Cardiol Therapeutics Inc"
        },
        {
          text: "CRDL.WT Cardiol Therapeutics Inc WT",
          value: "CRDL.WT Cardiol Therapeutics Inc WT"
        },
        {
          text: "CRED CI Lawrence Park Alt Invest Grd Crdt ETF",
          value: "CRED CI Lawrence Park Alt Invest Grd Crdt ETF"
        },
        {
          text: "CRED.U CI Lawrence Park Alt Inv Grd Crt ETF USD",
          value: "CRED.U CI Lawrence Park Alt Inv Grd Crt ETF USD"
        },
        { text: "CRH CRH Medical Corp", value: "CRH CRH Medical Corp" },
        { text: "CRON Cronos Group Inc", value: "CRON Cronos Group Inc" },
        { text: "CRP Ceres Global Ag Corp", value: "CRP Ceres Global Ag Corp" },
        {
          text: "CRR.UN Crombie Real Estate Investment Trust",
          value: "CRR.UN Crombie Real Estate Investment Trust"
        },
        {
          text: "CRT.UN CT Real Estate Investment Trust",
          value: "CRT.UN CT Real Estate Investment Trust"
        },
        {
          text: "CRWN Crown Capital Partners Inc",
          value: "CRWN Crown Capital Partners Inc"
        },
        {
          text: "CRWN.DB Crown Capital Partners Inc 6.0 Pct Debs",
          value: "CRWN.DB Crown Capital Partners Inc 6.0 Pct Debs"
        },
        { text: "CS Capstone Mining Corp", value: "CS Capstone Mining Corp" },
        {
          text: "CSAV CI First Asset High Interest Savings ETF",
          value: "CSAV CI First Asset High Interest Savings ETF"
        },
        {
          text: "CSD Ishares Short Dur HI Inc ETF CAD Hgd",
          value: "CSD Ishares Short Dur HI Inc ETF CAD Hgd"
        },
        {
          text: "CSE.PR.A Capstone Infrastructure Corp Pref A",
          value: "CSE.PR.A Capstone Infrastructure Corp Pref A"
        },
        {
          text: "CSH.UN Chartwell Retirement Residences",
          value: "CSH.UN Chartwell Retirement Residences"
        },
        {
          text: "CSM Clearstream Energy Services Inc",
          value: "CSM Clearstream Energy Services Inc"
        },
        {
          text: "CSU Constellation Software Inc",
          value: "CSU Constellation Software Inc"
        },
        {
          text: "CSU.DB Constellation Software Inc Debs Ser 1",
          value: "CSU.DB Constellation Software Inc Debs Ser 1"
        },
        {
          text: "CSW.A Corby Spirit and Wine Ltd Class A",
          value: "CSW.A Corby Spirit and Wine Ltd Class A"
        },
        {
          text: "CSW.B Corby Spirit and Wine Ltd Class B",
          value: "CSW.B Corby Spirit and Wine Ltd Class B"
        },
        {
          text: "CSY CI First Asset Core Can Equity Inc Class",
          value: "CSY CI First Asset Core Can Equity Inc Class"
        },
        {
          text: "CTC Canadian Tire Corporation Limited",
          value: "CTC Canadian Tire Corporation Limited"
        },
        {
          text: "CTC.A Canadian Tire Corporation Cl A NV",
          value: "CTC.A Canadian Tire Corporation Cl A NV"
        },
        {
          text: "CTF.UN Citadel Income Fund",
          value: "CTF.UN Citadel Income Fund"
        },
        {
          text: "CTX Crescita Therapeutics Inc",
          value: "CTX Crescita Therapeutics Inc"
        },
        {
          text: "CU Canadian Utilities Ltd Cl.A NV",
          value: "CU Canadian Utilities Ltd Cl.A NV"
        },
        {
          text: "CU.PR.C Canadian Utilities Limited Pref Ser C",
          value: "CU.PR.C Canadian Utilities Limited Pref Ser C"
        },
        {
          text: "CU.PR.D Canadian Utilities Ltd Pr Series Aa",
          value: "CU.PR.D Canadian Utilities Ltd Pr Series Aa"
        },
        {
          text: "CU.PR.E Canadian Utilities Ltd Pref Ser Bb",
          value: "CU.PR.E Canadian Utilities Ltd Pref Ser Bb"
        },
        {
          text: "CU.PR.F Canadian Utilities Ltd Pref Ser CC",
          value: "CU.PR.F Canadian Utilities Ltd Pref Ser CC"
        },
        {
          text: "CU.PR.G Canadian Utilities Ltd Pref Ser Dd",
          value: "CU.PR.G Canadian Utilities Ltd Pref Ser Dd"
        },
        {
          text: "CU.PR.H Canadian Utilities Ltd Pref Ser Ee",
          value: "CU.PR.H Canadian Utilities Ltd Pref Ser Ee"
        },
        {
          text: "CU.PR.I Canadian Utilities Ltd Pref Ser Ff",
          value: "CU.PR.I Canadian Utilities Ltd Pref Ser Ff"
        },
        { text: "CU.X CDN Util Cl B", value: "CU.X CDN Util Cl B" },
        {
          text: "CUD Ishares S&P US Div Growers ETF",
          value: "CUD Ishares S&P US Div Growers ETF"
        },
        { text: "CUF.UN Cominar R E Un", value: "CUF.UN Cominar R E Un" },
        { text: "CUP.U Caribbean Util US", value: "CUP.U Caribbean Util US" },
        {
          text: "CVD Ishares Convertible Bond Index ETF",
          value: "CVD Ishares Convertible Bond Index ETF"
        },
        { text: "CVE Cenovus Energy Inc", value: "CVE Cenovus Energy Inc" },
        { text: "CVG Clairvest Group", value: "CVG Clairvest Group" },
        { text: "CWB CDN Western Bank", value: "CWB CDN Western Bank" },
        {
          text: "CWB.PR.B Canadian Western Bank Pref Ser 5",
          value: "CWB.PR.B Canadian Western Bank Pref Ser 5"
        },
        {
          text: "CWB.PR.C Canadian Western Bank Pref Ser 7",
          value: "CWB.PR.C Canadian Western Bank Pref Ser 7"
        },
        {
          text: "CWB.PR.D Canadian Western Bank Pref Series 9",
          value: "CWB.PR.D Canadian Western Bank Pref Series 9"
        },
        {
          text: "CWEB Charlottes Web Holdings Inc",
          value: "CWEB Charlottes Web Holdings Inc"
        },
        {
          text: "CWEB.WT Charlottes Web Holdings Inc",
          value: "CWEB.WT Charlottes Web Holdings Inc"
        },
        {
          text: "CWL Caldwell Partners International Inc",
          value: "CWL Caldwell Partners International Inc"
        },
        {
          text: "CWW Ishares S&P Global Water ETF",
          value: "CWW Ishares S&P Global Water ETF"
        },
        {
          text: "CWX Canwel Building Materials Group Ltd",
          value: "CWX Canwel Building Materials Group Ltd"
        },
        {
          text: "CWX.NT.A Canwel Build Materials 6.375 Pct Notes",
          value: "CWX.NT.A Canwel Build Materials 6.375 Pct Notes"
        },
        { text: "CXB Calibre Mining Corp", value: "CXB Calibre Mining Corp" },
        {
          text: "CXF CI First Asset CDN Convert Bond ETF",
          value: "CXF CI First Asset CDN Convert Bond ETF"
        },
        {
          text: "CXI Currency Exchange International Corp",
          value: "CXI Currency Exchange International Corp"
        },
        {
          text: "CYB Cymbria Corporation Cl A",
          value: "CYB Cymbria Corporation Cl A"
        },
        {
          text: "CYBR Evolve Cyber Security Index Hgd ETF",
          value: "CYBR Evolve Cyber Security Index Hgd ETF"
        },
        {
          text: "CYBR.B Evolve Cyber Security Index Uh ETF",
          value: "CYBR.B Evolve Cyber Security Index Uh ETF"
        },
        {
          text: "CYBR.U Evolve Cyber Security Index Uh Fund USD",
          value: "CYBR.U Evolve Cyber Security Index Uh Fund USD"
        },
        {
          text: "CYH Ishares Global Monthly Dividend ETF",
          value: "CYH Ishares Global Monthly Dividend ETF"
        }
      ],
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
        { value: "ZWL", text: "ZWL:  Zimbabwean Dollar" }
      ],
      nameState: null
    };
  },
  computed: {
    rows() {
      return this.broker_client_orders.length;
    }
  },
  methods: {
    showOptionValueInput() {
      if (this.order_option_input === false) {
        this.order_option_input = true;
      } else {
        this.order_option_input = false;
      }
    },
    brokerOrderHandler(o) {
      this.order = {};
      this.order = o;
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
      // this.$refs.selectedOrder.clearSelected();
      // =============================================================================================
      this.$swal({
        title: "",
        icon: "info",
        html: `Select Ok to View Or Cancel the following order`,
        showCloseButton: true,
        showCancelButton: true,
        // focusConfirm: true,
        cancelButtonColor: "#DD6B55",
        confirmButtontext: "View",
        confirmButtonAriaLabel: "cancel",
        cancelButtontext: "Cancel Order",
        cancelButtonAriaLabel: "cancel"
      }).then(result => {
        if (result.value) {
          if (this.permissions.indexOf("update-broker-order") !== -1) {
            this.$bvModal.show("jse-new-order");
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
            // this.destroy(o.id);
            this.$swal("Cancelled!", "Client Order Has Been Cancelled.", "error");
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
      fr.onload = e => {
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
        order_options: this.order_option_inputs
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
          autocapitalize: "off"
        },
        confirmButtontext: "Create File",
        showLoaderOnConfirm: true,
        preConfirm: request => {
          // once the user is complete giving the file a name, show them the order modal

          var Filename = request;
          var blob = new Blob(
            [
              JSON.stringify(order_data)
              //   JSON.stringify(this.order_option_inputs)
            ],
            {
              type: "application/json"
            }
          );
          saveAs(blob, Filename + ".json");
        },
        allowOutsideClick: () => !this.$swal.isLoading()
      }).then(result => {
        if (result.value) {
          //Re Open Modal and allow user to continue their function
          this.$bvModal.show("jse-new-order");
        }
      });
    },
    tradingAccounts() {
      axios.get("broker-trading-accounts").then(response => {
        let data = response.data;
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
            value: data[i].id
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
      if (!broker.trading_account || !broker.client_trading_account) {
        this.$swal(
          "You need to select a Trading Account & Client Accont to continue"
        );
      } else {
        this.$swal("Processing your order..");
        axios.post("store-operator-client-order", broker).then(response => {
          let data = response.data;
          let valid = data.isvalid;
          if (valid) {
            this.$swal(data.errors);
            this.callFix();
            setTimeout(location.reload.bind(location), 2000);
          } else {
            this.$swal(data.errors);
            setTimeout(location.reload.bind(location), 2000);
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

        ClientID: "JMMB_TRADER1"
      };

      console.log(order_sample);

      // Fix Wrapper
      axios
        .post(
          "https://cors-anywhere.herokuapp.com/" +
            "http://35.155.69.248:8020/api/OrderManagement/NewOrderSingle",
          order_sample,
          { crossDomain: true }
        )
        .then(response => {
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
            "http://35.155.69.248:8020/api/messagedownload/download",
          order_sample,
          { crossDomain: true }
        )
        .then(response => {
          console.log(response);
        });
    },
    add() {
      this.create = true;
    },
    addOption(index) {
      // this.order_option_inputs.push({ option_type: "", option:_ value:"" });
      //  this.order_option_inputs.push(Vue.util.extend({}, this.order_option_inputs));
    },
    removeOption(index) {
      this.order_option_inputs.splice(index, 1);
    },
    destroy(id) {
      axios.delete(`destroy-broker-client-order/${id}`).then(response => {});
    },
    handleJSEOrder() {
      // Exit when the form isn't valid
      if (!this.checkFormValidity()) {
      } else {
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
      }
    },
    resetModal() {
      this.create = false;
      this.$refs.selectedOrder.clearSelected();
      this.order = {};
    },
    handleSubmit() {}
  },
  mounted() {
    this.getBrokers();
    this.tradingAccounts();
    // console.log(this.client_accounts);
    // var order_data = /
    var client_accounts_data = JSON.parse(this.client_accounts);
    this.client_trading_account_options = client_accounts_data;

    // //Define Permission On Front And Back End
    let p = JSON.parse(this.$userPermissions);
    //Looop through and identify all permission to validate against actions
    for (let i = 0; i < p.length; i++) {
      this.permissions.push(p[i].name);
    }

    // var local = JSON.parse(this.local_brokers);
    // let i;
    // for (i = 0; i < local.length; i++) {
    //   this.local_brokers_list.push({
    //     text:local[i].name,
    //      value:local[i].id
    //   });
    // }

    // var foreign = JSON.parse(this.foreign_brokers);
    // let f;
    // for (f = 0; f < foreign.length; f++) {
    //   this.foreign_brokers_list.push({
    //     text:foreign[f].name,
    //      value:foreign[f].id
    //   });
    // }
  }
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
