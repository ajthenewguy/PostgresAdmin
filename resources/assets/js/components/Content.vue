<template>
    <div class="container-fluid">
        <div class="row">
			<transition name="slide">
				<div :class="sidebarClass">
					<div class="input-group">
						<input v-model="tableQuery" id="tableFilter" class="form-control input-sm form-control-sm focus" placeholder="Search Tables">
						<div class="input-group-addon">
							<span id="searchclear" @click="tableQuery = ''" class="glyphicon glyphicon-remove-circle"></span>
						</div>
					</div>
					<list
						:schemas="schemas"
						:schema="schema"
						:selected-table="selectedTable"
						:tables="tables"
						:table="table"
						:query="tableQuery"
						@openTable="openTable"
						@changeSchema="changeSchema"
						@toggleDisplay="toggleListDisplay"
						@addStructureTab="addStructureTab"
						@refreshTables="refreshTables"
					/>
				</div>
			</transition>
            <div :class="mainViewClass">
				<button @click.blur="toggleListDisplay" class="btn btn-default btn-xs attach" :class="toggleDisplayWrapperClass" href="" title="Toggle Display" role="button">
					<span :class="toggleDisplayClass" aria-hidden="true"></span>
				</button>
                <tabs ref="tabs"
					  :loaded-tables="loadedTables"
                      @loaded="onTabChange"
                      @tabChanged="onTabChange"
                      @refresh="refreshTab"
					  @openTableRow="openTableRow"
                />
            </div>
        </div>

		<div class="content-mask" v-show="state.masked">
			<div class="form-horizontal" id="sessionRestoreLogin">
				<h2>Login</h2>
				<div v-show="loginError">
					{{ loginError }}
				</div>
				<div class="form-group">
					<label for="email" class="col-md-4 control-label">E-Mail Address</label>

					<div class="col-md-6">
						<input id="email" type="email" class="form-control" name="email" v-model="login.email" required autofocus>
					</div>
				</div>
				<div class="form-group">
					<label for="password" class="col-md-4 control-label">Password</label>

					<div class="col-md-6">
						<input id="password" type="password" class="form-control" name="password" v-model="login.password" required>
					</div>
				</div>
				<div class="form-group" v-show="login._token">
					<div class="col-md-8 col-md-offset-4">
						<button @click="postLogin" class="btn btn-primary">
							Login
						</button>
						<a class="btn btn-link" :href="server + '/password/reset'">
							Forgot Your Password?
						</a>
					</div>
				</div>
			</div>
		</div>
    </div>
</template>

<script>
    export default {
        props: [ 'csrfToken', 'selectedDatabase', 'loadedTables' ],
        data() {
            return {
                bus: window.bus,
                util: window.util,
                table: null,
                tables: this.loadedTables,
                displayTableList: true,
                editingRow: null,
                filter: null,
                insertingRow: false,
                records: [],
                recordsCustom: [],
                requestTimes: {},
                schema: null,
                schemas: [],
                selectedTable: null,
                tableQuery: '',
                order: null,
                where: null,
				login: {
					_token: '',
					email: '',
					password: ''
				},
				loginError: ''
            }
        },
        mixins: [
            require( '../mixins/PostgresMixin')
        ],
        components: {
            'list': require('./admin/TableList'),
            'query': require('./admin/Query'),
            'results-table': require('./admin/ResultsTable'),
            'structure-table': require('./admin/StructureTable'),
            'indices-table': require('./admin/IndicesTable')
        },
        created() {
            this.bus.$on('Connections.databaseSelected', this.onDatabaseConnect)
            this.bus.$on('Connections.databaseConencted', this.loadTabs)
            window.addEventListener('keyup', this.registerKeyListener)
            window.addEventListener('keydown', this.registerKeyListener)
        },
        mounted() {
			this.bus.$on('expiredSession', () => {
				this.state.masked = true
				this.refreshToken()
			})
        },
        computed: {
            sidebarClass: function() {
                let className = ''
                if (this.displayTableList) {
                    className = 'col-sm-3 col-md-2 sidebar'
                } else {
                    className = 'col-sm-3 col-md-2 collapsed sidebar'
                }
                return className
            },
            mainViewClass: function() {
                let className = ''
                if (this.displayTableList) {
                    className = 'col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main'
                } else {
                    className = 'col-md-12 main'
                }
                return className
            },
            toggleDisplayClass: function() {
                let className = ''
                if (this.displayTableList) {
                    className = this.util.icon('menu-left')
                } else {
                    className = this.util.icon('menu-right')
                }
                return className
            },
            toggleDisplayWrapperClass: function() {
                let className = ''
                if (! this.displayTableList) {
                    className = 'pull-out'
                }
                return className
            }
        },
        watch: {
            state: {
                handler() {
                    let tab = null
                    let active = this.state.activeTab

					if (active === null) {
                        this.selectedTable = null
                    } else if (this.$refs.tabs) {
						tab = this.$refs.tabs.tabs[active]
						if (tab && tab.table) {
							this.selectedTable = tab.table
						}
					}
                },
                deep: true
            }
        },
        methods: {
            registerKeyListener(e) {
                this.state.keyMap[e.keyCode] = e.type === 'keydown'

                // (Cmd || Ctrl) + F
                if ((this.state.keyMap[91] || this.state.keyMap[17]) && this.state.keyMap[70]) {
                    //
                }
                // \ Toggle sidebar
                if (this.state.keyMap[220]) {
                    let focusedElement = document.activeElement.tagName.toLowerCase()
                    if (focusedElement !== 'input' && focusedElement !== 'textarea' && focusedElement !== 'select') {
                        this.toggleListDisplay()
					}
                }
                // / Focus table filter/search
                if (this.state.keyMap[191]) {
                    let focusedElement = document.activeElement.tagName.toLowerCase()
                    if (focusedElement !== 'input' && focusedElement !== 'textarea' && focusedElement !== 'select') {
                        this.focusTableFilter(e)
                    }
                }

            },
            loadTabs() {
                let connection = this.state.connection
                // load selected tab
                this.$refs.tabs.loadTabs().then(() => {
					window.session.get(connection+'.selectedTab').then(selectedTabId => {
					    if (selectedTabId) {
                            if (this.$refs.tabs.tabExists(selectedTabId)) {
                                this.changeTab(selectedTabId)
                            } else {
                                let tab = this.$refs.tabs.getTab('connection', connection)
                                this.changeTab(tab.id)
                            }
						}
					}).catch(() => {
                        if (this.$refs.tabs.countTabs() < 1) {
                            this.changeTab(this.newTab("query"))
                        }
					}).then(() => {
                        this.bus.$emit('App.tabsLoaded')
					})
                })
			},
            addTableTab(type, table, where, pos) {
                let title = table || this.titleCase(type)
				let tab = {}
				if (typeof where === "undefined") {
                    tab = this.$refs.tabs.getTab({ "table": table, "type": type })
				} else {
                    tab = this.$refs.tabs.getTab({ "table": table, "type": type, "where": where })
				}
                this.clearTable()
                this.table = table
                if (tab) {
                    this.changeTab(tab.id, where)
                } else {
                    if (typeof pos === "undefined") {
                        tab = this.$refs.tabs.getTab({ "table": table })
						if (tab) {
                            if (tab.type === "content" && type === "structure") {
                                pos = tab.index
							} else if (tab.type === "structure" && type === "content") {
                                pos = tab.index + 1
							}
						}
					}
                    this.loadTable(table).then(config => {
                        this.newTab(type, title, table, where, pos)
                    })
                }
            },
            addStructureTab(table) {
                this.addTableTab("structure", table)
            },
            activeTab() {
                return this.$refs.tabs.activeTab()
            },
            changeTab(id, where) {
                this.$refs.tabs.changeTab(id, where)
            },
            closeTab(id) {
                return this.$refs.tabs.closeTab(id)
            },
            clearTable() {
                this.table = null
                this.schema = null
                this.order = null
                this.filter = null
                this.records = []
            },
            newTab(type, title, table, where, pos) {
                let tabId = this.$refs.tabs.newTab(...arguments)
                this.changeTab(tabId, where)
				return tabId
            },
            onTabChange(tab) {
                //
            },
            onWindowResize() {
                //
            },
            changeSchema(schema) {
                // console.log('CHANGE SCHEMA:', schema)
                this.refreshTables()
			},
			onDatabaseConnect() {
                this.loadSchemas().then(schemas => {
					this.schemas = schemas
					if (schemas.length === 1) {
						this.schema = schemas[0]
					} else if (schemas.indexOf('public') > -1) {
						let publicIndex = schemas.indexOf('public')
						this.schema = schemas[publicIndex]
					}
					this.refreshTables()
                    this.loadTabs()
                })
			},
            openTable(table) {
                this.addTableTab("content", table)
            },
            openTableRow(event) {
				console.log(event)
                this.addTableTab("content", event.table, event.where, event.pos)
            },
            refreshTab(index) {
                if (typeof index === "undefined") {
                    index = this.$refs.tabs.activeTabIndex()
                }
                this.$refs.tabs.refreshTab(index)
            },
            refreshTables(connection) {
				return axios.post(this.server + '/tables', { schema: this.schema }).then(response => {
					this.tables = response.data
					let tableCount = response.data.length
					for (let i = 0; i < tableCount; i++) {
						let table = response.data[i]
						this.state.tables[table] = this.initTableObject(table)
					}
					this.bus.$emit('App.databaseTablesRefreshed')
				}).catch(error => {
					console.log('databaseConnectError', error)
					this.bus.$emit('databaseConnectError')
					this.$notify.error({
						title: 'Connection Error',
						message: this.parseError(error),
						type: 'success'
					})
				})
            },
			refreshToken() {
				axios.get(this.server + '/token').then(response => {
                    this.login._token = response.data
					window.Laravel.csrfToken = response.data
					window.axios.defaults.headers.common = {
					    'X-CSRF-TOKEN': response.data,
					    'X-Requested-With': 'XMLHttpRequest'
					}
                }).catch(error => {
					console.log('tokenFetchError')
                    this.bus.$emit('tokenFetchError')
					this.$notify.error({
						title: 'Token Fetch Error',
						message: this.parseError(error),
						type: 'success'
					})
                })
			},
			postLogin() {
				return axios.post(this.server + '/login', this.login).then(() => {
					this.state.masked = false
                    this.bus.$emit('loggedIn')
                }).catch(error => {
					console.log('loginError', error)
                    this.bus.$emit('loginError')
					if (error) {
	                    errorText = error
	                    if (error.response) {
	                        errorText = error.response.statusText
	                        if (error.response.data) {
	                            errorText = error.response.data
	                            if (error.response.data.message) {
	                                errorText = error.response.data.message
	                            }
	                        }
							if (error.response.status === 422) {
								this.bus.$emit('invalidLogin')
								this.loginError = errorText
	                        }
	                    }
	                }
                })
			},
            titleCase(string) {
                return string.replace(/_/g, ' ').replace(/(^[a-z])|(\s+[a-z])/g, txt => txt.toUpperCase())
            },
            toggleListDisplay() {
                this.displayTableList = ! this.displayTableList
			},
			focusTableFilter(e) {
                if (!this.displayTableList) {
                    this.displayTableList = true
				}
                $('#tableFilter').focus()
                e.preventDefault()
                e.stopPropagation()
                return false
			}
        }
    }
</script>

<style lang="scss">
    .fade-enter-active, .fade-leave-active {
        transition: opacity .5s
    }
    .fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
        opacity: 0
    }
    .glyphicon-star, .glyphicon-star-empty {
        margin-right: 2px;
    }
    .glyphicon.spinning {
        animation: spin 1s infinite linear;
        -webkit-animation: spin2 1s infinite linear;
    }
    @keyframes spin {
        from { transform: scale(1) rotate(0deg); }
        to { transform: scale(1) rotate(360deg); }
    }
    @-webkit-keyframes spin2 {
        from { -webkit-transform: rotate(0deg); }
        to { -webkit-transform: rotate(360deg); }
    }
    .table.processing tbody {
        opacity: 0.8;
    }

    .closeTab {
        margin-left: 5px;
        visibility: hidden;
    }
    .nav-tab-item-a:hover .closeTab {
        visibility: visible;
    }
    .empty {
        color: #bcbcbc;
        font-size: 1.5em;
        padding: 15px;
    }
    #searchinput {
        width: 200px;
    }
    #searchclear {
        position: absolute;
        right: 7px;
        top: 0;
        bottom: 0;
        height: 14px;
        margin: auto;
        font-size: 14px;
        cursor: pointer;
        color: #bbb;
    }
    .row-no-padding {
        [class*="col-"] {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
    }


    html {
        min-height: 100%;
        position: relative;
    }

    /* Move down content because we have a fixed navbar that is 50px tall */
    body {
        padding-top: 30px;
    }

    /*
     * Global add-ons
     */

    .sub-header {
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

	.padded {
		padding: 3px;
	}

    /*
     * Top navigation
     * Hide default border to remove 1px line.
     */
    .navbar-fixed-top {
        border: 0;
    }

    /*
     * Sidebar
     */

    /* Hide for mobile, show later */
    .sidebar {
        display: none;
		border-right: 1px solid #eee;
		padding: 0;

		> .input-group {
			margin: 3px;
		}
    }
    @media (min-width: 768px) {
        .sidebar {
            position: fixed;
            top: 42px;
            bottom: 0;
            left: 0;
            z-index: 1000;
            display: block;
            overflow-x: hidden;
            overflow-y: auto; /* Scrollable contents if viewport is shorter than content. */
            background-color: #f9f9f9;
            /*border-right: 1px solid #eee;*/
			transition: 0.25s;
        }
    }
	.collapsed.sidebar {
		left: -16.66666667%;
	}

    /* Sidebar navigation */
    .nav-sidebar {
        margin-right: -21px; /* 20px padding + 1px border */
        margin-bottom: 20px;
        margin-left: -20px;
    }
    .nav-sidebar > li > a {
        padding-right: 20px;
        padding-left: 20px;
    }
    .nav-sidebar > .active > a,
    .nav-sidebar > .active > a:hover,
    .nav-sidebar > .active > a:focus {
        color: #fff;
        background-color: #428bca;
    }


    /*
     * Main content
     */

    .main {
        height: 100%;
        padding: 20px 0 0 0;
		transition: 0.25s;
    }
    .main .page-header {
        margin-top: 0;
    }
	.main .btn {
		height: auto;
	}

	.btn.attach {
		bottom: 5px;
		left: -30px;
		outline: none;
		position: absolute;
		z-index: 1000;
	}
	.btn.attach.pull-out {
		left: 5px;
	}

	.content-mask {
		position: fixed;
		z-index: 9999;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		/*background-color: #000;*/
		background: linear-gradient(135deg, #000, #3c3c3c);
		transition: opacity .3s ease;
	}
	.content-mask > * {
		z-index: 10000;
	}
</style>
