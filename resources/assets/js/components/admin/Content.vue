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
            </div>
            <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
                <ul class="nav nav-tabs" id="primaryTabContainer">
                    <li :class="{ active: tab === 'query' }">
                        <a href="#query" @click="changeTab('query')" data-toggle="pill">Query</a>
                    </li>
                    <li :class="{ active: tab === 'structure' }">
                        <a href="#structure" @click="changeTab('structure')" data-toggle="pill">Structure</a>
                    </li>
                    <li :class="{ active: tab === 'content' }">
                        <a href="#content" @click="changeTab('content')" data-toggle="pill">Content</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane" :class="{ active: tab === 'structure' }" id="structure">
                        <structure-table
                                :table="table"
                                :schema="schema"
                                :processing="processing"
                        />
                    </div>
                    <div class="tab-pane" :class="{ active: tab === 'content' }" id="content">
                        <div class="results table-responsive">
                            <results-table
                                    :tab="tab"
                                    :table="table"
                                    :schema="schema"
                                    :table-primary-key="tablePrimaryKey"
                                    :records="records"
                                    :processing="processing"
                                    :editing-row="editingRow"
                                    :custom-query="customQuery"
                                    @editingRow="setEditingRow"
                                    @updateRow="updateRow"
                                    @deleteRow="deleteRow"
                            />
                        </div>
                        <div class="row">
                            <div class="col-sm-12 text-right">
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
                        <span v-if="processing">
                            <span class="glyphicon glyphicon-refresh spinning"></span>
                        </span>
                    </div>
                    <div class="tab-pane" :class="{ active: tab === 'query' }" id="query">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <query
                                        v-if="!processing || customQuery"
                                        :sql="customQuery"
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
        props: [ 'tables' ],
        data() {
            return {
                customQuery: false,
                primaryPanelTitle: '',
                table: null,
//                schema: null,
                tablePrimaryKey: '',
                tablePrimaryKeyFormat: '',
                editingRow: null,
                records: [],
                recordsCustom: [],
//                sql: '',
                tab: 'query',
                tableQuery: '',
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
                },
//                processing: false
            }
        },
        mixins: [ require( '../../mixins/PostgresMixin.vue') ],
        components: {
            'list': require('./TableList'),
            'query': require('./Query'),
            'structure-table': require('./StructureTable'),
            'results-table': require('./ResultsTable')
        },
        methods: {
            recordCount() {
                return this.records ? this.records.length : 0
            },
            openTable(table) {
                this.clearTable()
                this.customQuery = false
                this.setEditingRow(null)
                this.table = table
                if (this.tab === "query") {
                    this.tab = 'content'
                }
                this.sql = 'SELECT * FROM ' + this.table
                this.getPrimaryKey(this.table).then(() => {
                    this.getSchema(this.table).then(() => {
                        this.getRecords()
                    })
                })
            },
//            executeQuery(sql, page) {
//                this.processing = true
//                let data = {
//                    sql: sql || this.sql
//                }
//                if (page) {
//                    data.page = page
//                }
//                if (bindings) {
//                    data.bindings = bindings
//                }
//                return axios.post('http://postgres:5433/select', data)
//                    .catch(error => {
//                        this.queryError(error)
//                    })
//            },
            changeTab(tab) {
                this.tab = tab
                if (tab === "content" && (this.table && this.recordCount() < 1)) {
                    this.getRecords()
                }
            },
            currentPage() {
                return this.pagination.current_page
            },
            getRecords(page) {
                let sql = this.makeSelect(this.table)
                if (typeof page === "undefined") {
                    page = this.currentPage()
                }
                this.primaryPanelTitle = 'Records in table "' + this.table + '"'
                return this.selectQuery(sql, page).then(response => {
                    if (this.tab === 'content') {
                        this.records = this.result
                    } else {
                        this.recordsCustom = this.result
                    }
                })
            },
            setEditingRow(row) {
                this.editingRow = row
            },
            updateRow(payload) {
                let where = {}
                where[this.tablePrimaryKey] = payload.primaryKey
                return this.updateQuery(this.makeUpdate(this.table, payload.data, where), payload.data).then(() => {
                    this.editingRow = null
                    this.getRecords()
                })
            },
            deleteRow(primaryKey) {
                if (confirm('Delete this row?')) {
                    let where = {}
                    where[this.tablePrimaryKey] = primaryKey
                    this.deleteQuery(this.makeDelete(this.table, where)).then(() => {
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
                    this.primaryPanelTitle = sql
//                    this.clearTable()
                } else {
                    this.recordsCustom = []
                }
            },
            afterCustomQuery() {
//                this.customQuery = false
            },
            clearTable() {
                this.table = null
                this.schema = null
                this.tablePrimaryKey = ''
                this.tablePrimaryKeyFormat = ''
                this.records = []
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
    @media (min-width: 768px) {
        .main {
            padding-right: 40px;
            padding-left: 40px;
        }
    }
    .main .page-header {
        margin-top: 0;
    }
</style>