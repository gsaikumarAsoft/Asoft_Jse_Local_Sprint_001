<template>
  <div>
    <head-nav></head-nav>
    <div class="container">
      <div class="content">
        <div v-if="!create">
          <table style="margin-top:50px;">
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Delete</th>
            </tr>
            <tr style="cursor:pointer" v-for="b in local_brokers" :key="b.id">
              <td># {{ b.id }}</td>
              <td>{{ b.name }}</td>
              <td>{{ b.email }}</td>
              <td @click="destroy(b.id)">
                <strong>-</strong>
              </td>
            </tr>
          </table>
        </div>
        <div v-if="create">
          <input v-model="broker.name" type="text" name="name" placeholder="NCB" />
          <input v-model="broker.email" type="email" name="email" placeholder="broker@ncb.com" />
        </div>

        <button v-if="!create" class="pull-right" @click.prevent="create = true">Add Local Broker</button>
        <button v-if="create" class="pull-right" @click.prevent="storeLocalBroker()">Save</button>
      </div>
    </div>
  </div>
</template>
<script lang="ts">
import axios from "axios";
import headNav from "./../partials/Nav.vue";
export default {
  components: {
    headNav
  },
  data() {
    return {
      create: false,
      local_brokers: [],
      broker: {}
    };
  },
  methods: {
    async getBrokers() {
      ({ data: this.local_brokers } = await axios.get("/broker-list")); //.then(response => {
    },
    async storeLocalBroker() {
      try {
        const response = await axios.post("/store-local-broker", this.broker);
        //.then(response => {
        ////console.log(response);
        await this.getBrokers();
        this.create = false;
      } catch (error) {}
    },
    add() {
      this.create = true;
    },
    async destroy(id) {
      await axios.delete(`/local-broker-delete/${id}`);
      await this.getBrokers();
    }
  },
  async mounted() {
    await this.getBrokers();
  }
};
</script>
