<template>
    <li :class="{ dropdown: ui.connections.dropdown  }">
        <a href="#" @click.prevent="showModal = true" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
            {{ connection }} <span v-if="ui.connections.dropdown" class="caret"></span>
        </a>
        <ul v-if="ui.connections.dropdown" class="dropdown-menu" role="menu">
            <li>
                <a href="" @click.prevent="showModal = true">Connections</a>
            </li>
        </ul>
        <!-- Connections in modal -->
        <modal v-show="showModal" @close="showModal = false" class="wide">
            <h3 slot="header">{{ modalHeader }}</h3>
            <template slot="body">
                <div class="row">
                    <div :class="sidebarClass">
                        <connection-list
                                :route="route"
                                :connection="connection"
                                :connections="connections"
                                @connect="onConnect"
                                @editConnection="showEditConnection"
                                @showAddConnection="showAddConnection"
                                @refreshConnections="loadConnections"
                        />
                    </div>
                    <div :class="contentClass" v-if="route === 'add'">
                        <div class="panel panel-default">
                            <div class="panel-heading">Connection Setup</div>
                            <div class="panel-body">
                                <connection-form
                                        :route="route"
                                        @input="onInput"
                                        @submit="onSubmit"
                                        @cancel="cancelAddConnection"
                                />
                            </div>
                        </div>
                    </div>
                    <div :class="contentClass" v-if="route === 'edit'">
                        <div class="panel panel-default">
                            <div class="panel-heading">Connection Setup</div>
                            <div class="panel-body">
                                <connection-form
                                        :route="route"
                                        :connection="newConnection"
                                        @input="onInput"
                                        @submit="onSubmit"
                                        @cancel="cancelAddConnection"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template slot="footer">
                <v-button @click.prevent="showModal = false" text="Close"></v-button>
            </template>
        </modal>
        <!-- /modal -->
    </li>
</template>

<script>
    export default {
        props: ['defaultConnection'],
        components: {
            'connection-list': require('./ConnectionList'),
            'connection-form': require('./forms/Connection')
        },
        data() {
            return {
                bus: window.bus,
                store: window.store,
                state: window.store.state,
                util: window.util,
                route: 'index',
                connections: [],
                connection: null,
                modalHeader: 'Connections',
                newConnection: {},
                showModal: false,
                ui: {
                    connections: {
                        dropdown: false
                    }
                }
            }
        },
        computed: {
            sidebarClass: function () {
                return {
                    'col-md-3': this.route !== 'index',
                    'col-md-6': this.route === 'index'
                }
            },
            contentClass: function () {
                return {
                    'col-md-9': this.route !== 'index',
                    'col-md-6': this.route === 'index'
                }
            },
            currentConnection: function () {
                return _.find(this.connections, [ 'name', this.connection ])
            }
        },
        mounted() {
            let connection = null
            this.loadConnections().then(value => {
                if (this.connections.length < 1) {
                    this.route = 'add'
                } else {
                    if (connection = _.find(this.connections, [ 'name', this.defaultConnection ])) {
                        this.connection = connection.name
                    }
                }
            })
        },
        methods: {
            cancelAddConnection() {
                this.route = 'index'
            },
            loadConnections() {
                return this.settings.get([ 'connection', 'connections' ]).then(values => {
                    this.connection = values.connection
                    this.connections = values.connections
                    if (this.connection) {
                        this.state.connection = this.connection
                        this.bus.$emit('databaseConnected', this.connection)
                    }
                    if (! this.connection && this.connections !== null) {
                        this.modalHeader = 'Please Select a Connection'
                        this.showModal = true
                    }
                }).catch(reason => {
                    let message = 'Error retrieving connections: ' + reason
                    console.error(message)
                    if (reason !== 'Setting "connections" not found') {
                        alert(message)
                    }
                })
            },
            onInput(e) {
                this.newConnection[e.target.name] = e.target.value
            },
            onSubmit(e) {
                let index = null
                let connection = {}
                let connectionName = this.newConnection.name
                if (connection = _.find(this.connections, [ 'name', connectionName ])) {
                    index = _.indexOf(this.connections, connection)
                    this.connections[index] = this.newConnection
                } else {
                    this.connections.push(this.newConnection)
                }
                this.settings.set('connections', this.connections)
                this.route = 'index'
            },
            onConnect(name) {
                let connection = null
                if (connection = _.find(this.connections, [ 'name', name ])) {
                    this.setConnection(connection.name)
                }
            },
            setConnection(connection) {
                return this.settings.set('connection', connection).then(() => {
                    // eslint-disable-next-line
                    console.log('setConnection()', connection)
                    this.state.connection = connection
                    this.bus.$emit('databaseConnected', connection)
                    this.connection = connection
                    this.route = 'index'
                }).then(() => {
                    this.modalHeader = 'Connections'
                    this.promptConnection = false
                    this.showModal = false
                })
            },
            showAddConnection() {
                this.route = 'add'
            },
            showEditConnection(name) {
                // eslint-disable-next-line
                console.log('edit', name)
                this.route = 'edit'
                this.newConnection = _.find(this.connections, [ 'name', name ])
                this.newConnection.password = ''
            }
        }
    }
</script>
