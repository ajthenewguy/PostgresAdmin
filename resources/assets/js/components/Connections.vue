<template>
    <li :class="{ dropdown: ui.connections.dropdown  }">
        <a href="#" @click.prevent="showConnectionsModal = true" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
            {{ connection }} <span v-if="ui.connections.dropdown" class="caret"></span>
        </a>
        <ul v-if="ui.connections.dropdown" class="dropdown-menu" role="menu">
            <li>
                <a href="" @click.prevent="showConnectionsModal = true">Connections</a>
            </li>
        </ul>
        <!-- Connections in modal -->
        <modal v-show="showConnectionsModal" @close="showConnectionsModal = false" class="wide">
            <h3 slot="header">{{ modalHeader }}</h3>
            <template slot="body">
                <div class="row">
                    <div :class="sidebarClass">
                        <connection-list
                                :route="route"
                                :connection="connection"
                                :connections="connections"
                                @attemptConnect="attemptConnect"
                                @editConnection="showEditConnection"
                                @deleteConnection="deleteConnection"
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
                            <div class="panel-heading">Edit Connection</div>
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
                <v-button @click.prevent="showConnectionsModal = false" text="Close"></v-button>
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
                editingConnection: null,
                connections: [],
                connection: null,
                connectionCache: null,
                modalHeader: 'Connections',
                newConnection: {},
                showConnectionsModal: false,
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
            }
        },
        mounted() {
            let connection = null
            this.loadConnections().then(value => {
				if (this.connections !== null && this.connections.length < 1) {
                    this.route = 'add'
                } else {
					if (this.defaultConnection) {
						this.attemptConnect(this.defaultConnection)
					} else {
						this.promptConnection()
					}
                }
            })
			this.bus.$on('App.databaseTablesLoaded', this.handleDatabaseConnect)
			this.bus.$on('databaseConnectError', this.handleDatabaseConnectError)
        },
        methods: {
			attemptConnect(name) {
                let connection = null
                if (connection = _.find(this.connections, [ 'name', name ])) {
					if (this.connection) {
						this.connectionCache = _.clone(this.connection)
					}
                    this.setConnection(connection.name).then(() => {
						this.connection = connection.name
						this.bus.$emit('Connections.databaseSelected', connection.name)
	                    this.route = 'index'
						if (this.modalOpen()) {
							this.closeModal()
						}
	                })
                }
            },
            cancelAddConnection() {
                this.newConnection = {}
                this.route = 'index'
            },
            setConnection(connection) {
                return this.settings.set('connection', connection)
            },
			closeModal() {
				this.showConnectionsModal = false
				this.modalHeader = 'Connections'
			},
            deleteConnection(name) {
				this.$confirm('This will permanently delete the connection "' + name + '". Continue?', 'Warning').then(() => {
					let index = _.findIndex(this.connections, [ 'name', name ])
                    this.connections.splice(index, 1)
                    this.settings.set('connections', this.connections).then(() => {
						this.$notify({
							title: 'Success',
							message: 'Database connection "' + name + '" deleted',
							type: 'success'
						})
					})
                    if (name === this.editingConnection) {
                        this.route = 'index'
                    }
				}).catch(() => {
					//
				})
            },
			handleDatabaseConnect() {
				this.connectionCache = null
				this.state.connection = this.connection
			},
			handleDatabaseConnectError() {
				this.connection = ''
				if (this.connectionCache) {
					this.attemptConnect(this.connectionCache)
				} else {
					this.settings.set('connection', null)
					this.promptConnection()
				}
			},
            loadConnections() {
                return this.settings.get('connections').then(connections => {
                    this.connections = connections
                }).catch(reason => {
                    let message = 'Error retrieving connections: ' + reason
                    console.error(message)
                    if (reason !== 'Setting "connections" not found') {
						this.$notify.error({
							title: 'Connection Error',
							message: message,
							type: 'success'
						})
                    }
                })
            },
			modalOpen() {
				return this.showConnectionsModal
			},
            onInput(e) {
                this.newConnection[e.target.name] = e.target.value
            },
            onSubmit(e) {
                let index = null
				let isNew = false
                let connection = {}
                let connectionName = this.newConnection.name
                if (connection = _.find(this.connections, [ 'name', connectionName ])) {
                    index = _.indexOf(this.connections, connection)
                    this.connections[index] = this.newConnection
                    this.editingConnection = null
                } else {
					isNew = true
                    this.connections.push(this.newConnection)
                }
                this.settings.set('connections', this.connections).then(() => {
					this.$notify({
						title: 'Success',
						message: 'Database connection ' + (isNew ? 'added' : 'updated'),
						type: 'success'
					})
				})
                this.newConnection = {}
                this.route = 'index'
            },
			promptConnection() {
				this.showModal('Please Select a Connection')
			},
            showAddConnection() {
                this.newConnection = {}
                this.route = 'add'
            },
            showEditConnection(name) {
                this.editingConnection = name
                this.route = 'edit'
                this.newConnection = _.find(this.connections, [ 'name', name ])
                this.newConnection.password = ''
            },
			showModal(title = null) {
				if (title) {
					this.modalHeader = title
				}
				this.showConnectionsModal = true
			}
        }
    }
</script>
