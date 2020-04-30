<template>
  <div>
    <head-nav></head-nav>
    <div class="container">
      <h1>Current Orders</h1>
      <div class="content">
        <b-table
          show-empty
          :empty-text="'No Orders have been Created. Create an Order below.'"
          id="my-table"
          :items="broker_client_orders"
          :per-page="perPage"
          :current-page="currentPage"
          striped
          hover
          :fields="fields"
        ></b-table>
        <div v-if="!create"></div>
        <b-modal id="jse-new-order" ref="modal" @ok="handleJSEOrder" title="New Order">
          <form ref="form">
            <b-form-group
              label="Local Broker"
              label-for="broker-input"
              invalid-feedback="Local Broker is required"
            >
              <b-form-select v-model="order.local_broker" :options="local_broker.name" class="mb-3">
                <!-- This slot appears above the options from 'options' prop -->
                <template v-slot:first>
                  <b-form-select-option :value="null" disabled>-- Please select a Local Broker --</b-form-select-option>
                </template>
                <b-form-select-option
                  v-for="b in local_broker"
                  :value="b.value"
                  :key="b.id"
                >{{ b.text }}</b-form-select-option>
              </b-form-select>
            </b-form-group>
            <b-form-group
              label="Foreign Broker"
              label-for="foreign-broker-input"
              invalid-feedback="Foreign Broker is required"
            >
              <b-form-select v-model="order.foreign_broker" :options="foreign_broker" class="mb-3">
                <!-- This slot appears above the options from 'options' prop -->
                <template v-slot:first>
                  <b-form-select-option :value="null" disabled>-- Please select a Foreign Broker --</b-form-select-option>
                </template>
                <b-form-select-option
                  v-for="f in foreign_broker"
                  :value="f.id"
                  :key="f.id"
                >{{ f.name }}</b-form-select-option>
              </b-form-select>
            </b-form-group>
            <b-form-group
              label="Order Type"
              label-for="type-input"
              invalid-feedback="Order Type is required"
            >
              <b-form-select v-model="order.type" class="mb-3">
                <!-- This slot appears above the options from 'options' prop -->
                <template v-slot:first>
                  <b-form-select-option v-model="order.type" disabled>-- Choose an Order Type --</b-form-select-option>
                </template>

                <!-- These options will appear after the ones from 'options' prop -->
                <b-form-select-option value="BUY">BUY</b-form-select-option>
                <b-form-select-option value="SELL">SELL</b-form-select-option>
              </b-form-select>
            </b-form-group>
            <b-form-group
              label="Symbol"
              label-for="order-input"
              invalid-feedback="Symbol is required"
            >
              <b-form-select v-model="order.symbol" class="mb-3">
                <!-- This slot appears above the options from 'options' prop -->
                <template v-slot:first>
                  <b-form-select-option :value="null" disabled>-- Please select a Symbol --</b-form-select-option>
                </template>

                <!-- These options will appear after the ones from 'options' prop -->
                <b-form-select-option value="JMMB">JMMB</b-form-select-option>
                <b-form-select-option value="138LSR">138LSR</b-form-select-option>
              </b-form-select>
            </b-form-group>
            <b-form-group
              label="Price"
              label-for="order-input"
              invalid-feedback="Price is required"
            >
              <b-input-group size="md" prepend="$" append=".00">
                <b-form-input id="price-input" v-model="order.price" :state="nameState" required></b-form-input>
              </b-input-group>
            </b-form-group>
          </form>
        </b-modal>
        <b-pagination
          v-model="currentPage"
          :total-rows="rows"
          :per-page="perPage"
          aria-controls="my-table"
        ></b-pagination>
        <b-button v-b-modal.jse-new-order @click="create = true">Create New Order</b-button>
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import axios from "axios";
import headNav from "./../partials/Nav";
export default {
  props: ["orders", "local_brokers"],
  components: {
    headNav
  },
  data() {
    return {
      local_broker: JSON.parse(this.local_brokers),
      foreign_broker: [],
      selected: null,
      create: false,
      order: {},
      fields: [
        { key: "local_broker", sortable: true },
        { key: "order_date", sortable: true },
        { key: "order_type", sortable: true },
        { key: "symbol", sortable: true },
        { key: "order_quantity", sortable: true },
        { key: "price", sortable: true },
        { key: "order_status", sortable: true },
        { key: "foreign_broker", sortable: true }
      ],
      broker_client_orders: JSON.parse(this.orders),
      broker: {},
      perPage: 3,
      currentPage: 1,
      new_order_fields: {
        Name: "",
        Account: "",
        ClOrdID: "",
        ExecInst: "",
        HandlInst: "",
        OrderQty: "",
        OrdType: "",
        Price: "",
        Side: "",
        Symbol: "",
        TimeInForce: "",
        TransactTime: "",
        FuttSettDate: "",
        SymbolSfx: "",
        StopPx: "",
        ExDestination: "",
        ClientID: "", // When puchasing on the foreign markets the UMIR of the BUY SELL Must be mapped to this field
        MinQty: "",
        MaxFloor: "",
        LocateReqd: "",
        PegOffset: "",
        Country: "",
        ExpireDate: "",
        Text: "",
        TCMConstraints: "",
        Bypass: "",
        NCIB: "",
        MinInteractionSize: "",
        AccountSell: "",
        AccountType: "",
        AccountTypeSell: "",
        ProgramTrade: "",
        BasketTrade: "",
        CrossType: "",
        Jitney: "",
        Anonymous: "",
        RegulationId: "",
        RegulationIdSell: "",
        SettlementTerms: "",
        ItemNumber: "",
        NonResident: "",
        NoTradeFeat: "",
        NoTradeKey: "",
        PostOnlyPegType: "",
        ShortMarkingExem: "",
        SeekDarkLiquidity: "",
        LongLife: "",
        DisplayRange: "",
        MAQMatchType: "",
        MatchingStateParticipation: "",
        AggressiveStrategy: "",
        QuotePrioritizationStrategy: "",
        DirectRoute: "",
        RouteInterlisted: "",
        DarkRouting: "",
        PrimaryBookOnly: ""
      },
      nameState: null
    };
  },
  computed: {
    rows() {
      return this.broker_client_orders.length;
    }
  },
  methods: {
    checkFormValidity() {
      const valid = this.$refs.form.checkValidity();
      this.nameState = valid;
      return valid;
    },
    getBrokers() {
      axios.get("broker-list").then(response => {
        let data = response.data;
        let i;
        for (i = 0; i < data.length; i++) {
          this.local_broker.push({
            text: data[i].name,
            value: data[i].id
          });
        }
        // this.broker_client_orders = data;
      });
      axios.get("foreign-broker-list").then(fresponse => {
        let fdata = fresponse.data;
        let j;
        for (j = 0; j < fdata.length; j++) {
          this.foreign_broker.push({
            text: fdata[j].name,
            value: fdata[j].id
          });
        }
      });
    },
    createBrokerClientOrder() {
         this.$swal("Processing your order..");
      axios
        .post(
          "http://172.26.0.184:8020/api/OrderManagement/NewOrderSingle",
          this.order
        )
        .then(response => {
          this.$swal("Order Sent");
          console.log(response);
        })
        .catch(error => {
          this.$swal("Order Failed" + error);
        });
    },
    add() {
      this.create = true;
    },
    destroy(id) {
      axios.delete(`local-broker-delete/${id}`).then(response => {
        this.getBrokers();
      });
    },
    handleJSEOrder() {
      // Exit when the form isn't valid
      if (!this.checkFormValidity()) {
      } else {
        this.$bvModal.hide("jse-new-order"); //Close the modal if it is open
        this.createBrokerClientOrder(this.order);
        this.resetModal();
      }
    },
    resetModal() {
      this.create = false;
    },
    handleSubmit() {}
  },
  mounted() {
      console.log(this);
    this.getBrokers();
  }
};
</script>
