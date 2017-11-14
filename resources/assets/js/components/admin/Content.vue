<template>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-3 col-md-2 sidebar">
                <div class="input-group">
                    <input v-model="tableQuery" class="form-control input-sm" placeholder="Search Tables">
                    <div class="input-group-addon">
                        <span id="searchclear" @click="tableQuery = ''" class="glyphicon glyphicon-remove-circle"></span>
                    </div>
                </div>
                <list
                        :tables="tables"
                        :table="table"
                        :query="tableQuery"
                        @openTable="openTable"
                        @addStructureTab="addStructureTab"
                        @refreshTables="refreshTables"
                />
            </div>
            <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
                <tabs ref="tabs"
                      @loaded="onTabChange"
                      @tabChanged="onTabChange"
                      @refresh="refreshTab"
                />
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: [ 'selectedDatabase', 'loadedTables' ],
        data() {
            return {
                bus: window.bus,
                store: window.store,
                state: window.store.state,
                util: window.util,
                table: null,
                tables: this.loadedTables,
                editingRow: null,
                filter: null,
                insertingRow: false,
                records: [],
                recordsCustom: [],
                requestTimes: {},
                tableQuery: '',
                order: null,
                where: null,
                tabs: []
            }
        },
        mixins: [
            require( '../../mixins/PostgresMixin.vue')
        ],
        components: {
            'list': require('./TableList'),
            'content-filter': require('./Filter'),
            'query': require('./Query'),
            'results-table': require('./ResultsTable'),
            'structure-table': require('./StructureTable'),
            'indices-table': require('./IndicesTable')
        },
        created() {
            let $this = this
            this.bus.$on('databaseConnected', function() {
                $this.refreshTables().then(() => {
                    $this.addDefaultTab()
                })
            })
        },
        mounted() {
            let $this = this
            $(window).on('load', function () {
                window.addEventListener('resize', $this.onWindowResize)
                if ($this.state.connection) {
                    $this.addDefaultTab()
                }
            })
        },
        watch: {
            state: {
                handler() {
                    this.tabs = this.state.tabs
                },
                deep: true
            }
        },
        methods: {
            addDefaultTab() {
                if (this.$refs.tabs.countTabs() < 1) {
                    this.newTab("query")
                }
            },
            addTableTab(table, type) {
                let tab = this.$refs.tabs.getTab({ "table": { "name": table }, "type": type })
                this.clearTable()
                this.table = table
                if (tab) {
                    this.changeTab(tab.id)
                } else {
                    this.loadTable(table).then(config => {
                        this.newTab(type, table, config)
                    })
                }
            },
            addStructureTab(table) {
                this.addTableTab(table, "structure")
            },
            activeTab() {
                return this.$refs.tabs.activeTab()
            },
            changeTab(id) {
                this.$refs.tabs.changeTab(id)
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
            newTab(type, title, table) {
                let tabId = this.$refs.tabs.newTab(...arguments)
                this.changeTab(tabId)
            },
            onTabChange(index) {
                this.resizeTabContent()
            },
            onWindowResize() {
                this.resizeTabContent()
            },
            openTable(table) {
                this.addTableTab(table, "content")
            },
            refreshTab() {
                let tab = this.activeTab()
                let table = tab.table
                if (typeof table === "object" && table.hasOwnProperty('name')) {
                    table = table.name
                }
                this.loadTable(tab.table, true).then(config => {
                    this.bus.$emit('tabRefreshed', config)
                })
            },
            refreshTables() {
                return axios.post(this.server + '/tables').then(response => {
                    this.tables = response.data
                }).catch(error => {
                    this.queryError(error)
                })
            },
            resizeTabContent() {
                let sidebarHeight = 0
                let contentHeight = 0
                setTimeout(function () {
                    sidebarHeight = $(".sidebar").height()
                    contentHeight = sidebarHeight - 3
                    if ($('#primaryTabContainer').length) {
                        contentHeight -= $('#primaryTabContainer').height() + 10 // 54
                    }
                    if ($('#contentFilter').length) {
                        contentHeight -= $('#contentFilter').height() // 35
                    }
                    if ($('#tabFooter .el-pagination').length) {
                        contentHeight -= $('#tabFooter .el-pagination').height() // 28
                    } else {
                        contentHeight -= 22
                    }
                    $(".tab-pane-content").height(contentHeight)
                }, 50)
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
    .rowButtons {
        width: 90px;
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
        text-align: center;
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
    }
    @media (min-width: 768px) {
        .sidebar {
            position: fixed;
            top: 42px;
            bottom: 0;
            left: 0;
            z-index: 1000;
            display: block;
            padding: 5px;
            overflow-x: hidden;
            overflow-y: auto; /* Scrollable contents if viewport is shorter than content. */
            background-color: #f5f5f5;
            border-right: 1px solid #eee;
        }
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
        padding: 20px 20px 10px 20px;
    }
    .main .page-header {
        margin-top: 0;
    }
</style>