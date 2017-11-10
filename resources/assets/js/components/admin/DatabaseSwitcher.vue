<template>
    <div class="navbar-right">

        <!--
        <div class="form-group" style="display:inline-block; margin:0;">
            <label class="control-label">Database:</label>
            <el-select
                    id="database-switcher"
                    v-model="database"
                    placeholder="Database"
                    class="dark"
                    @change="openDatabase()"
            >
                <el-option
                        v-for="item in databases"
                        :key="item"
                        :label="item"
                        :value="item"
                />
            </el-select>
        </div>-->

        <div style="display: inline-block; padding:10px 15px;">
            <v-button @click="showModal = true" size="xs" class="dark">Connections</v-button>
        </div>
        <!-- Connections in modal -->
        <modal v-show="showModal" @close="showModal = false" class="wide">
            <h3 slot="header">{{ modalHeader }}</h3>
            <template slot="body">
                <connections
                    @connectionsLoaded="onConnectionsLoaded"
                    @connect="onConnect"
                />
            </template>
            <template slot="footer">
                <v-button @click.prevent="showModal = false" text="Close"></v-button>
            </template>
        </modal>
    </div>

</template>

<script>
    export default {
        props: ['databases', 'selectedDatabase'],
        components: {
            'connections': require('../Connections')
        },
        data() {
            return {
                bus: window.bus,
                store: window.store,
                state: window.store.state,
                util: window.util,
                connection: null,
                connections: null,
                database: this.selectedDatabase,
                showModal: false,
                modalHeader: 'Connections'
            }
        },
        methods: {
            onConnectionsLoaded(data) {
                this.connection = data.connection
                this.connections = data.connections

                // eslint-disable-next-line
                console.log('this.connection:',this.connection,'this.connections:',this.connections)

                if (! this.connection && this.connections !== null) {
                    this.modalHeader = 'Please Select a Connection'
                    this.showModal = true
                }
            },
            onConnect(connection) {
                this.bus.$emit('databaseConnected', name)
                this.modalHeader = 'Connections'
                this.showModal = false
                this.connection = connection
            },
            openDatabase() {
                window.location = "/" + this.database
            }
        }
    }
</script>
<style>
    #database-switcher input {
        height: 26px;
    }
    .navbar {
        min-height: 40px;
    }
    .navbar-brand {
        height: 40px;
        padding: 10px 12px;
    }
    @media (min-width: 768px) {
        .navbar-nav > li > a {
            padding-top: 10px;
            padding-bottom: 10px;
        }
    }
</style>