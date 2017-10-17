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
                <list :tables="tables" :table="table" :query="tableQuery" @openTable="openTable" />
                <select
                        id="database-switcher"
                        class="form-control input-sm"
                        v-model="database"
                        placeholder="Database"
                        @change="openDatabase()"
                >
                    <option
                            v-for="item in databases"
                            :key="item"
                            :label="item"
                            :value="item"
                    />
                </select>
            </div>
            <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
                <ul class="nav nav-tabs" id="primaryTabContainer">
                    <li :class="{ active: tab === 'query' }">
                        <a href="#query" @click="changeTab('query')" data-toggle="pill">
                            <span class="glyphicon glyphicon-search" aria-hidden="true"></span> Query
                        </a>
                    </li>
                    <li :class="{ active: tab === 'structure' }">
                        <a href="#structure" @click="changeTab('structure')" data-toggle="pill">
                            <span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> Structure
                        </a>
                    </li>
                    <li :class="{ active: tab === 'content' }">
                        <a href="#content" @click="changeTab('content')" data-toggle="pill">
                            <span class="glyphicon glyphicon-th-list" aria-hidden="true"></span> Content
                        </a>
                    </li>
                </ul>
                <div class="tab-content">

                    <!-- QUERY TAB -->
                    <div class="tab-pane" :class="{ active: tab === 'query' }" id="query">
                        <query
                                v-if="!processing || customQuery"
                                :sql="customQuery"
                                :history="history"
                                @beforeQuery="beforeQuery"
                                @customQuery="beforeCustomQuery"
                                @success="querySuccess"
                                @afterQuery="afterCustomQuery"
                                @error="queryError"
                        />
                        <results-table
                                :tab="tab"
                                :table="table"
                                :table-primary-key="false"
                                :records="recordsCustom"
                                :processing="processing"
                                :editing-row="false"
                                :custom-query="customQuery"
                                @editingRow="setEditingRow"
                                @updateRow="updateRow"
                                @deleteRow="deleteRow"
                        />
                        <div class="row">
                            <div class="col-sm-2">
                                <span v-if="requestTime[tab] && (records || customRecords)">
                                    {{ requestTimeStr(tab) }}
                                </span>
                                <span v-if="processing">
                                    <span class="glyphicon glyphicon-refresh spinning"></span>
                                </span>
                            </div>
                            <div class="col-sm-10 text-right">
                                <!-- no pagination -->
                            </div>
                        </div>
                    </div>

                    <!-- STRUCTURE TAB -->
                    <div class="tab-pane" :class="{ active: tab === 'structure' }" id="structure">
                        <div v-if="table && schema">
                            <structure-table
                                    :table="table"
                                    :schema="schema"
                                    :processing="processing"
                            />
                            <indices-table
                                    :table="table"
                                    :table-foreign-keys="tableForeignKeys"
                                    :processing="processing"
                            />
                        </div>
                        <div v-else>
                            <div v-if="!processing" class="empty">
                                No table selected
                            </div>
                        </div>
                    </div>

                    <!-- CONTENT TAB -->
                    <div class="tab-pane" :class="{ active: tab === 'content' }" id="content">
                        <content-filter
                                v-if="(records && records.length > 0) || where"
                                @filterWhere="filterWhere"
                        />
                        <results-table
                                :tab="tab"
                                :table="table"
                                :order="order"
                                :schema="schema"
                                :table-primary-key="tablePrimaryKey"
                                :records="records"
                                :processing="processing"
                                :inserting-row="insertingRow"
                                :editing-row="editingRow"
                                :custom-query="customQuery"
                                @sortColumn="sortColumn"
                                @insertingRow="setInsertingRow"
                                @editingRow="setEditingRow"
                                @insertRow="insertRow"
                                @updateRow="updateRow"
                                @deleteRow="deleteRow"
                        />
                        <div class="row">
                            <div class="col-sm-2">
                                <span v-if="processing">
                                    <span class="glyphicon glyphicon-refresh spinning"></span>
                                </span>
                                <span v-else-if="table">
                                    <a @click.prevent="refresh" href="#"><span class="glyphicon glyphicon-refresh"></span></a>
                                </span>
                                <span v-if="requestTime[tab]" class="request-time">
                                    {{ requestTimeStr(tab) }}
                                </span>
                            </div>
                            <div class="col-sm-10 text-right">
                                <el-pagination
                                        v-if="records && records.length"
                                        layout="total, prev, pager, next"
                                        :currentPage="pagination.current_page"
                                        :total="pagination.total"
                                        :pageCount="pagination.last_page"
                                        :pageSize="pagination.per_page"
                                        @current-change="getRecords"
                                />
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: [  'databases', 'selectedDatabase', 'loadedTables' ],
        data() {
            return {
                database: this.selectedDatabase,
                table: null,
                tables: this.loadedTables,
                editingRow: null,
                filter: null,
                insertingRow: false,
                records: [],
                recordsCustom: [],
                tab: 'query',
                tableQuery: '',
                order: null,
                where: null,
                pagination: {
                    current_page: 1,
                    first_page_url: '',
                    from: null,
                    last_page: null,
                    last_page_url: '',
                    next_page_url: '',
                    path: '',
                    per_page: null,
                    prev_page_url: '',
                    to: null,
                    total: 0
                }
            }
        },
        mixins: [ require( '../../mixins/PostgresMixin.vue') ],
        components: {
            'list': require('./TableList'),
            'content-filter': require('./Filter'),
            'query': require('./Query'),
            'results-table': require('./ResultsTable'),
            'structure-table': require('./StructureTable'),
            'indices-table': require('./IndicesTable')
        },
        watch: {
            order: function(column) {
                this.getRecords()
            }
        },
        methods: {
            recordCount() {
                return this.records ? this.records.length : 0
            },
            openDatabase() {
                this.tableQuery = ''
                return axios.post(this.server + '/switch-database', { database: this.database }).then(response => {
                    this.tables = response.data.tables
                }).catch(error => {
                    this.result = null
                    this.queryError(error)
                })
            },
            openTable(table) {
                this.clearTable()
                this.customQuery = false
                this.setInsertingRow(false)
                this.setEditingRow(null)
                this.table = table
                if (this.tab === "query") {
                    this.tab = 'content'
                }
                this.getPrimaryKey(this.table).then(() => {
                    this.order = this.primaryKey
                    this.getSchema(this.table).then(() => {
                        this.getRecords()
                    })
                })
            },
            changeTab(tab) {
                this.tab = tab
                this.setInsertingRow(false)
                this.setEditingRow(null)
                if (tab === "content" && (this.table && this.recordCount() < 1 && !this.processing)) {
                    this.getRecords()
                }
            },
            currentPage() {
                return this.pagination.current_page
            },
            sortColumn(column) {
                if (typeof this.order === "string" && this.order === column) {
                    this.order = [column, 'DESC']
                } else if (this.order && this.order.constructor === Array && this.order[0] === column) {
                    this.order = this.primaryKey
                } else {
                    this.order = column
                }
            },
            refresh() {
                this.getRecords()
            },
            filterWhere(where) {
                this.where = where
                this.getRecords()
            },
            getRecords(page) {
                let sql = this.makeSelect(this.table, this.where, null, this.order)
                if (typeof page === "undefined") {
                    page = this.currentPage()
                }
                return this.selectQuery(sql, page).then(response => {
                    if (this.tab === 'content') {
                        this.records = this.result
                    } else {
                        this.recordsCustom = this.result
                    }
                })
            },
            setInsertingRow(inserting) {
                this.editingRow = false
                this.insertingRow = !! inserting
            },
            setEditingRow(row) {
                this.editingRow = row
            },
            insertRow(data) {
                return this.insertQuery(this.makeInsert(this.table, data), data).then(() => {
                    this.setInsertingRow(false)
                    this.getRecords()
                })
            },
            updateRow(payload) {
                let where = {}
                where[this.tablePrimaryKey] = payload.primaryKey
                return this.updateQuery(this.makeUpdate(this.table, payload.data, where), payload.data).then(() => {
                    this.setEditingRow(null)
                    this.getRecords()
                })
            },
            deleteRow(primaryKey) {
                if (confirm('Delete this row?')) {
                    let where = {}
                    where[this.tablePrimaryKey] = primaryKey

                    // eslint-disable-next-line
                    console.log(this.table, where)

                    this.deleteQuery(this.makeDelete(this.table, where), where).then(() => {
                        this.getRecords(this.currentPage()).then(() => {
                            this.editingRow = null
                            this.processing = false
                        })
                    })
                }
            },
            beforeCustomQuery(sql) {
                if (sql) {
                    this.customQuery = true
                } else {
                    this.recordsCustom = []
                }
            },
            afterCustomQuery() {
                if (this.result) {
                    this.recordsCustom = this.result
                }
            },
            clearTable() {
                this.table = null
                this.schema = null
                this.tablePrimaryKey = ''
                this.tablePrimaryKeyFormat = ''
                this.tableForeignKeys = null
                this.order = null
                this.filter = null
                this.records = []
            },
            titleCase(string) {
                return string.replace(/_/g, ' ').replace(/(^[a-z])|(\s+[a-z])/g, txt => txt.toUpperCase())
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
    .list-group-item {
        padding: 3px 10px
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

    .request-time {
        margin-left: 5px;
    }


    html {
        min-height: 100%;
        position: relative;
    }

    /* Move down content because we have a fixed navbar that is 50px tall */
    body {
        margin-bottom: 32px;
        padding-top: 50px;
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
            top: 51px;
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
        padding: 20px;
    }
    .main .page-header {
        margin-top: 0;
    }
</style>