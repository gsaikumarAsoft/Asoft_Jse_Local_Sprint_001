/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import Vue from 'vue';
import BootstrapVue from 'bootstrap-vue';
import VueSweetalert2 from 'vue-sweetalert2';
import jsPDF from 'jspdf';
import { BootstrapVueIcons } from 'bootstrap-vue';
import Multiselect from 'vue-multiselect';
window.Vue = require('vue');

Vue.use(BootstrapVue);
Vue.use(Multiselect);
Vue.use(BootstrapVueIcons); 
Vue.use(VueSweetalert2);
Vue.use(jsPDF);

Vue.prototype.$userPermissions = document.querySelector("meta[name='user-permissions']").getAttribute('content');
Vue.prototype.$userId = document.querySelector("meta[name='user-id']").getAttribute('content');


Vue.component('jse-home', require('./components/Index.vue').default);
Vue.component('foreign', require('./components/ForeignBrokerList.vue').default);
Vue.component('local-brokers', require('./components/LocalBrokerList.vue').default);
Vue.component('settlements', require('./components/Settlements.vue').default);

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

Vue.component('broker-home', require('./components/brokers/Index.vue').default);
Vue.component('broker-company', require('./components/brokers/Companies.vue').default);
Vue.component('broker-user', require('./components/brokers/Users.vue').default);
Vue.component('broker-trader', require('./components/brokers/Clients.vue').default);
Vue.component('broker-order', require('./components/brokers/Orders.vue').default);
Vue.component('broker-request', require('./components/brokers/Requests.vue').default);
Vue.component('broker-2-broker', require('./components/B2b.vue').default);
Vue.component('broker-settlements', require('./components/brokers/SettlementAccounts').default);



Vue.component('operator-home', require('./components/operators/Index.vue').default);
Vue.component('operator-client', require('./components/operators/Client-Management.vue').default);
Vue.component('operator-order', require('./components/operators/Orders.vue').default);



Vue.component('trader-home', require('./components/trader/Index.vue').default);

Vue.component('outbound-home', require('./components/outbound/Index.vue').default);

Vue.component('account-profile', require('./components/profile/Index').default);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
});
