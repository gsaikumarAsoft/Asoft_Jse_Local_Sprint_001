<template>
  <div>
    <head-nav></head-nav>
    <div class="container-fluid">
      <div class="content">
        <b-table
          responsive
          striped
          hover
          show-empty
          :empty-text="'No Settlement Accounts have been Created. Create a Settlement Account below.'"
          id="foreign-brokers"
          :items="broker_settlement_account"
          :fields="fields"
          :per-page="perPage"
          :current-page="currentPage"
          @row-clicked="settlmentAccountHandler"
        >
          <template slot="index" slot-scope="row">{{ row }}</template>
        </b-table>
        <b-pagination
          v-model="currentPage"
          :total-rows="rows"
          :per-page="perPage"
          aria-controls="foreign-brokers"
        ></b-pagination>
        <b-button v-b-modal.modal-1 @click="create = true">Create Settlement Account</b-button>
        <b-button @click="importAccounts">Import Accounts</b-button>
        <b-button @click="exportBalances">Export Balances</b-button>
        <b-modal id="modal-1" :title="modalTitle" @ok="handleOk" @hidden="resetModal">
          <p class="my-4">Please update the fields below as required!</p>
          <form ref="form" @submit.stop.prevent="handleSubmit">
            <b-form-group
              sm
              label="Local Broker"
              label-for="name-input"
              invalid-feedback="Name is required"
            >
              <b-form-select
                label="Local Broker"
                label-for="localBroker-input"
                invalid-feedback="A Local Broker is required"
                sm
                v-model="settlement_account.local_broker_id"
                :options="local_brokers"
              ></b-form-select>
            </b-form-group>
            <b-form-group
              label="Foreign Broker"
              label-for="foreign-input"
              invalid-feedback="Foreign Broker is required"
            >
              <b-form-select
                v-model="settlement_account.foreign_broker_id"
                :options="foreign_brokers"
              ></b-form-select>
            </b-form-group>
            <b-form-group label="Bank" label-for="bank-input" invalid-feedback=" Bank is required">
              <b-form-input
                id="bank-input"
                v-model="settlement_account.bank_name"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="Account Number"
              label-for="account-input"
              invalid-feedback=" Account is required"
            >
              <b-form-input
                id="account-input"
                v-model="settlement_account.account"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="Settlement Agent Email"
              label-for="email-input"
              invalid-feedback=" Email is required"
            >
              <b-form-input
                id="email-input"
                v-model="settlement_account.email"
                :state="nameState"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              sm
              label="Account Currency"
              label-for="currency-input"
              invalid-feedback="currency is required"
            >
              <b-form-select
                label="Currency"
                label-for="Currency-input"
                invalid-feedback="A currency is required"
                sm
                v-model="settlement_account.currency"
                :options="currencies"
              ></b-form-select>
            </b-form-group>
            <b-form-group
              label="Account Balance "
              label-for="umir-input"
              invalid-feedback=" Account Balance  is required"
            >
              <b-form-input
                id="balance-input"
                v-model="settlement_account.account_balance"
                :state="nameState"
                type="number"
                required
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label="Amount Allocated "
              label-for="umir-input"
              invalid-feedback=" Amount Allocated  is required"
            >
              <b-form-input
                id="allocated-input"
                v-model="settlement_account.amount_allocated"
                :state="nameState"
                type="number"
                required
              ></b-form-input>
            </b-form-group>
          </form>
        </b-modal>
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import axios from "axios";
import headNav from "./partials/Nav";
import currenciesMixin from "./../mixins/Currencies";
export default {
  mixins: [currenciesMixin],
  components: {
    "head-nav": headNav
  },
  data() {
    return {
      currencies: [
        { value: "AFN", text: "AFN:  Afghan Afghani" },
        { value: "ALL", text: "ALL:  Albanian Lek" },
        { value: "AMD", text: "AMD:  Armenian Dram" },
        { value: "ANG", text: "ANG:  Netherlands Antillean Guilder" },
        { value: "AOA", text: "AOA:  Angolan Kwanza" },
        { value: "ARS", text: "ARS:  Argentine Peso" },
        { value: "AUD", text: "AUD:  Australian Dollar" },
        { value: "AWG", text: "AWG:  Aruban Florin" },
        { value: "AZN", text: "AZN:  Azerbaijani Manat" },
        { value: "BAM", text: "BAM:  Bosnia-Herzegovina Convertible Mark" },
        { value: "BBD", text: "BBD:  Barbadian Dollar" },
        { value: "BDT", text: "BDT:  Bangladeshi Taka" },
        { value: "BGN", text: "BGN:  Bulgarian Lev" },
        { value: "BHD", text: "BHD:  Bahraini Dinar" },
        { value: "BIF", text: "BIF:  Burundian Franc" },
        { value: "BMD", text: "BMD:  Bermudan Dollar" },
        { value: "BND", text: "BND:  Brunei Dollar" },
        { value: "BOB", text: "BOB:  Bolivian Boliviano" },
        { value: "BRL", text: "BRL:  Brazilian Real" },
        { value: "BSD", text: "BSD:  Bahamian Dollar" },
        { value: "BTC", text: "BTC:  Bitcoin" },
        { value: "BTN", text: "BTN:  Bhutanese Ngultrum" },
        { value: "BWP", text: "BWP:  Botswanan Pula" },
        { value: "BYN", text: "BYN:  Belarusian Ruble" },
        { value: "BZD", text: "BZD:  Belize Dollar" },
        { value: "CAD", text: "CAD:  Canadian Dollar" },
        { value: "CDF", text: "CDF:  Congolese Franc" },
        { value: "CHF", text: "CHF:  Swiss Franc" },
        { value: "CLF", text: "CLF:  Chilean Unit of Account (UF)" },
        { value: "CLP", text: "CLP:  Chilean Peso" },
        { value: "CNH", text: "CNH:  Chinese Yuan (Offshore)" },
        { value: "CNY", text: "CNY:  Chinese Yuan" },
        { value: "COP", text: "COP:  Colombian Peso" },
        { value: "CRC", text: "CRC:  Costa Rican Colón" },
        { value: "CUC", text: "CUC:  Cuban Convertible Peso" },
        { value: "CUP", text: "CUP:  Cuban Peso" },
        { value: "CVE", text: "CVE:  Cape Verdean Escudo" },
        { value: "CZK", text: "CZK:  Czech Republic Koruna" },
        { value: "DJF", text: "DJF:  Djiboutian Franc" },
        { value: "DKK", text: "DKK:  Danish Krone" },
        { value: "DOP", text: "DOP:  Dominican Peso" },
        { value: "DZD", text: "DZD:  Algerian Dinar" },
        { value: "EGP", text: "EGP:  Egyptian Pound" },
        { value: "ERN", text: "ERN:  Eritrean Nakfa" },
        { value: "ETB", text: "ETB:  Ethiopian Birr" },
        { value: "EUR", text: "EUR:  Euro" },
        { value: "FJD", text: "FJD:  Fijian Dollar" },
        { value: "FKP", text: "FKP:  Falkland Islands Pound" },
        { value: "GBP", text: "GBP:  British Pound Sterling" },
        { value: "GEL", text: "GEL:  Georgian Lari" },
        { value: "GGP", text: "GGP:  Guernsey Pound" },
        { value: "GHS", text: "GHS:  Ghanaian Cedi" },
        { value: "GIP", text: "GIP:  Gibraltar Pound" },
        { value: "GMD", text: "GMD:  Gambian Dalasi" },
        { value: "GNF", text: "GNF:  Guinean Franc" },
        { value: "GTQ", text: "GTQ:  Guatemalan Quetzal" },
        { value: "GYD", text: "GYD:  Guyanaese Dollar" },
        { value: "HKD", text: "HKD:  Hong Kong Dollar" },
        { value: "HNL", text: "HNL:  Honduran Lempira" },
        { value: "HRK", text: "HRK:  Croatian Kuna" },
        { value: "HTG", text: "HTG:  Haitian Gourde" },
        { value: "HUF", text: "HUF:  Hungarian Forint" },
        { value: "IDR", text: "IDR:  Indonesian Rupiah" },
        { value: "ILS", text: "ILS:  Israeli New Sheqel" },
        { value: "IMP", text: "IMP:  Manx pound" },
        { value: "INR", text: "INR:  Indian Rupee" },
        { value: "IQD", text: "IQD:  Iraqi Dinar" },
        { value: "IRR", text: "IRR:  Iranian Rial" },
        { value: "ISK", text: "ISK:  Icelandic Króna" },
        { value: "JEP", text: "JEP:  Jersey Pound" },
        { value: "JMD", text: "JMD:  Jamaican Dollar" },
        { value: "JOD", text: "JOD:  Jordanian Dinar" },
        { value: "JPY", text: "JPY:  Japanese Yen" },
        { value: "KES", text: "KES:  Kenyan Shilling" },
        { value: "KGS", text: "KGS:  Kyrgystani Som" },
        { value: "KHR", text: "KHR:  Cambodian Riel" },
        { value: "KMF", text: "KMF:  Comorian Franc" },
        { value: "KPW", text: "KPW:  North Korean Won" },
        { value: "KRW", text: "KRW:  South Korean Won" },
        { value: "KWD", text: "KWD:  Kuwaiti Dinar" },
        { value: "KYD", text: "KYD:  Cayman Islands Dollar" },
        { value: "KZT", text: "KZT:  Kazakhstani Tenge" },
        { value: "LAK", text: "LAK:  Laotian Kip" },
        { value: "LBP", text: "LBP:  Lebanese Pound" },
        { value: "LKR", text: "LKR:  Sri Lankan Rupee" },
        { value: "LRD", text: "LRD:  Liberian Dollar" },
        { value: "LSL", text: "LSL:  Lesotho Loti" },
        { value: "LYD", text: "LYD:  Libyan Dinar" },
        { value: "MAD", text: "MAD:  Moroccan Dirham" },
        { value: "MDL", text: "MDL:  Moldovan Leu" },
        { value: "MGA", text: "MGA:  Malagasy Ariary" },
        { value: "MKD", text: "MKD:  Macedonian Denar" },
        { value: "MMK", text: "MMK:  Myanma Kyat" },
        { value: "MNT", text: "MNT:  Mongolian Tugrik" },
        { value: "MOP", text: "MOP:  Macanese Pataca" },
        { value: "MRO", text: "MRO:  Mauritanian Ouguiya (pre-2018)" },
        { value: "MRU", text: "MRU:  Mauritanian Ouguiya" },
        { value: "MUR", text: "MUR:  Mauritian Rupee" },
        { value: "MVR", text: "MVR:  Maldivian Rufiyaa" },
        { value: "MWK", text: "MWK:  Malawian Kwacha" },
        { value: "MXN", text: "MXN:  Mexican Peso" },
        { value: "MYR", text: "MYR:  Malaysian Ringgit" },
        { value: "MZN", text: "MZN:  Mozambican Metical" },
        { value: "NAD", text: "NAD:  Namibian Dollar" },
        { value: "NGN", text: "NGN:  Nigerian Naira" },
        { value: "NIO", text: "NIO:  Nicaraguan Córdoba" },
        { value: "NOK", text: "NOK:  Norwegian Krone" },
        { value: "NPR", text: "NPR:  Nepalese Rupee" },
        { value: "NZD", text: "NZD:  New Zealand Dollar" },
        { value: "OMR", text: "OMR:  Omani Rial" },
        { value: "PAB", text: "PAB:  Panamanian Balboa" },
        { value: "PEN", text: "PEN:  Peruvian Nuevo Sol" },
        { value: "PGK", text: "PGK:  Papua New Guinean Kina" },
        { value: "PHP", text: "PHP:  Philippine Peso" },
        { value: "PKR", text: "PKR:  Pakistani Rupee" },
        { value: "PLN", text: "PLN:  Polish Zloty" },
        { value: "PYG", text: "PYG:  Paraguayan Guarani" },
        { value: "QAR", text: "QAR:  Qatari Rial" },
        { value: "RON", text: "RON:  Romanian Leu" },
        { value: "RSD", text: "RSD:  Serbian Dinar" },
        { value: "RUB", text: "RUB:  Russian Ruble" },
        { value: "RWF", text: "RWF:  Rwandan Franc" },
        { value: "SAR", text: "SAR:  Saudi Riyal" },
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
      create: false,
      broker_settlement_account: [],
      settlement_account: {},
      local_brokers: [],
      foreign_brokers: [],
      perPage: 5,
      currentPage: 1,
      fields: [
        {
          key: `local_broker.name`,
          label: "Local Broker",
          sortable: true
        },
        {
          key: "foreign_broker.name",
          label: "Foreign Broker",
          sortable: true
        },
        {
          key: "bank_name",
          label: "Settlement Agent",
          sortable: true
        },
        {
          key: "account",
          sortable: true
        },
        {
          key: "email",
          label: "Settlement Agent Email",
          sortable: true
        },
        {
          key: "account_balance",
          sortable: true
        },
        {
          key: "amount_allocated",
          sortable: true
        },
        {
          key: "currency",
          sortable: true
        },
        {
          key: "settlement_agent_status",
          label: 'Status',
          sortable: true
        }
      ],
      modalTitle: "Create Broker Settlement Account",
      nameState: null
    };
  },
  computed: {
    rows() {
      return this.broker_settlement_account.length;
    }
  },
  methods: {
    importAccounts(){
            this.$swal
        .fire({
          title: "Importing",
          html: "One moment while we import new settlement accounts.",
          timerProgressBar: true,
          onBeforeOpen: () => {
            this.$swal.showLoading();
          }
        })
        .then(result => {});
    },
    exportBalances() {
      const tableData = [];
      for (var i = 0; i < this.broker_settlement_account.length; i++) {
        tableData.push([
          this.broker_settlement_account[i].local_broker["name"],
          this.broker_settlement_account[i].foreign_broker["name"],
          this.broker_settlement_account[i].bank_name,
          this.broker_settlement_account[i].account,
          this.broker_settlement_account[i].email,
          this.broker_settlement_account[i].account_balance,
          this.broker_settlement_account[i].amount_allocated
        ]);
      }

      // console.log(this.broker_settlement_account[i])
      // tableData.push(this.broker_settlement_account[i]);

      var doc = new jsPDF();
      //   // It can parse html:
      //   doc.autoTable({ html: "#foreign-brokers" });

      // Or use javascript directly:
      doc.autoTable({
        head: [
          [
            "Local Broker",
            "Foreign Broker",
            "Bank",
            "Account",
            "Email",
            "Account Balance",
            "Amount Allocated"
          ]
        ],
        body: tableData
      });
      doc.save("BrokerSettlementReport.pdf");
    },
    checkFormValidity() {
      const valid = this.$refs.form.checkValidity();
      this.nameState = valid;
      return valid;
    },
    resetModal() {
      this.create = false;
      this.settlement_account = {};
    },
    handleOk(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault();
      // Trigger submit handler
      this.handleSubmit();
    },
    handleSubmit() {
      // Exit when the form isn't valid
      if (!this.checkFormValidity()) {
      } else {
        this.$bvModal.hide("modal-1"); //Close the modal if it is open
        //Determine if a new user is being created or we are updating an existing user
        if (this.create) {
          //Exclude ID
          this.storeBrokerSettlementAccount({
            id: null,
            currency: this.settlement_account.currency,
            account: this.settlement_account.account,
            account_balance: this.settlement_account.account_balance,
            amount_allocated: this.settlement_account.amount_allocated,
            bank_name: this.settlement_account.bank_name,
            email: this.settlement_account.email,
            foreign_broker_id: this.settlement_account.foreign_broker_id,
            local_broker_id: this.settlement_account.local_broker_id,
            status: "Unverified",
            hash: this.settlement_account.hash
          });
          this.$swal(`Account created for ${this.settlement_account.email}`);
        } else {
          //Include ID
          console.log(this.settlement_account);
          this.storeBrokerSettlementAccount(this.settlement_account);
        }

        this.resetModal();
        this.$nextTick(() => {
          this.$bvModal.hide("modal-1");
        });
      }

      this.nameState = null;
    },
    settlmentAccountHandler(b) {
      // console.log(b);
      this.settlement_account = {};
      console.log(b);
      this.settlement_account = b;
      this.$swal({
        title: "",
        icon: "info",
        html: `Would you like to Edit Or Delete the following Settlement Account</b> `,
        // showCloseButton: true,
        showCancelButton: true,
        focusConfirm: true,
        cancelButtonColor: "#DD6B55",
        confirmButtonText: "Edit",
        confirmButtonAriaLabel: "delete",
        cancelButtonText: "Delete",
        cancelButtonAriaLabel: "cancel"
      }).then(result => {
        if (result.value) {
          this.$bvModal.show("modal-1");
        }
        if (result.dismiss === "cancel") {
          this.destroy(b.id);
          this.$swal(
            "Deleted!",
            "Settlement Account Has Been Removed.",
            "success"
          );
        }
      });
    },
    setLocalBroker() {
      // console.log(this);
    },
    getSettlementList() {
      axios.get("../settlement-list").then(response => {
        let broker_settlement_accounts = response.data;
        this.broker_settlement_account = [];
        this.broker_settlement_account = broker_settlement_accounts;
        // console.log(this.broker_settlement_account);
      });
    },
    storeBrokerSettlementAccount(account) {
      // console.log(account);
      axios
        .post("../store-settlement-broker", account)
        .then(response => {
          this.getSettlementList();
          this.create = false;
        })
        .catch(error => {
          // console.log(error);
        });
    },
    add() {
      this.create = true;
    },
    destroy(id) {
      axios.delete(`../settlement-account-delete/${id}`).then(response => {
        this.getSettlementList();
      });
    }
  },
  mounted() {
    axios.get("../local-brokers").then(response => {
      let local_brokers = response.data;
      let i;
      for (i = 0; i < local_brokers.length; i++) {
        this.local_brokers.push({
          text: local_brokers[i].user.name,
          value: local_brokers[i].user.id
        });
      }
    });
    axios.get("../foreign-brokers").then(response => {
      let foreign_brokers = response.data;
      let i;
      for (i = 0; i < foreign_brokers.length; i++) {
        // console.log(foreign_brokers[i].user );
        let data = foreign_brokers[i].user;
        this.foreign_brokers.push({
          text: data.name,
          value: data.id
        });
      }
    });

    this.getSettlementList();
  }
};
</script>
