<template>
    <div class="container">
        <div class="content"Func>
            <div class="links">
                <a href="/ ">Local Broker List |</a>
                <!-- <a href="/">Foreign Broker Details</a> -->
                <a href="/foreign-broker-list">Foreign Broker List |</a>
                <!-- <a href="/">Local Broker Details</a> -->
                <a href="/">Broker Settlement Account</a>
            </div>

            <div v-if="!create">
                <table style="margin-top:50px;">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Delete</th>
                    </tr>
                    <tr
                        style="cursor:pointer"
                        v-for="b in local_brokers"
                        :key="b.id"
                    >
                        <td># {{ b.id }}</td>
                        <td>{{ b.name }}</td>
                        <td>{{ b.email }}</td>
                        <td @click="destroy(b.id)"><strong>-</strong></td>
                    </tr>
                </table>
            </div>
            <div v-if="create">
                <input
                    v-model="broker.name"
                    type="text"
                    name="name"
                    placeholder="NCB"
                />
                <input
                    v-model="broker.email"
                    type="email"
                    name="email"
                    placeholder="broker@ncb.com"
                />
            </div>

            <button
                v-if="!create"
                class="pull-right"
                @click.prevent="create = true"
            >
                Add Local Broker
            </button>
            <button
                v-if="create"
                class="pull-right"
                @click.prevent="storeLocalBroker()"
            >
                Save
            </button>
        </div>
        
    </div>
</template>
<script lang="ts">
import axios from "axios";
export default {
    data() {
        return {
            create: false,
            local_brokers: [],
            broker: {}
        };
    },
    methods: {
        getBrokers() {
            axios.get("/broker-list").then(response => {
                let data = response.data;
                this.local_brokers = data;
            });
        },
        storeLocalBroker() {
            axios
                .post("/store-local-broker", this.broker)
                .then(response => {
                    this.getBrokers();
                    //////console.log(response);
                    this.create = false;
                    this.getBrokers();
                })
                .catch(error => {});
        },
        add() {
            this.create = true; 
        },
        destroy(id) {
            axios.delete(`/local-broker-delete/${id}`).then(response => {
                this.getBrokers();
            });
        }
    },
    mounted() {
        this.getBrokers();
    }
};
</script>
