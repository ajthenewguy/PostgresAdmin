<template>
    <div class="container-fluid">
        <div class="row">
            <div :class="classSidePanel">
                <div class="panel panel-default">
                    <div class="panel-heading">Tables</div>
                    <div class="panel-body input-group">
                        <!--<div class="input-group-addon">?</div>-->
                        <input v-model="tableQuery" class="form-control input-sm" placeholder="Search Tables">
                        <div class="input-group-addon">
                            <span id="searchclear" @click="tableQuery = ''" class="glyphicon glyphicon-remove-circle"></span>
                        </div>

                    </div>
                    <List :tables="tables" :table="table" :query="tableQuery" @openTable="openTable"></List>
                </div>
            </div>
            <div :class="classMainPanel">
                <div class="panel panel-default">
                    <div class="panel-heading">{{ primaryPanelTitle }}</div>
                    <div class="panel-body">
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
                        <div class="results table-responsive">
                            <transition name="fade">
                                <table v-if="records && records.length" class="table table-hover table-condensed" :class="{ processing }">
                                    <thead>
                                        <tr>
                                            <th v-for="(value, name) in records[0]"><span v-if="name === tablePrimaryKey" class="glyphicon glyphicon-star" aria-hidden="true"></span>{{ name }}</th>
                                            <th v-if="table && tablePrimaryKey"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="fields in records" :class="{ active: editingRow === fields[tablePrimaryKey] }">
                                            <td v-for="(value, name) in fields">{{ value }}</td>
                                            <td v-if="table && tablePrimaryKey" class="rowButtons">
                                                <transition>
                                                    <span v-if="editingRow == fields[tablePrimaryKey]">
                                                        <button key="cancel" @click="editingRow = null" type="button" class="btn btn-default btn-xs">
                                                            <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                                                        </button>
                                                        <button key="save" @click="updateRow(fields[tablePrimaryKey])" type="button" class="btn btn-default btn-xs">
                                                            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                                                        </button>
                                                        <button key="delete" @click="deleteRow(fields[tablePrimaryKey])" type="button" class="btn btn-default btn-xs">
                                                            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                                                        </button>
                                                    </span>
                                                        <span v-else>
                                                        <button key="edit" @click="editingRow = fields[tablePrimaryKey]" type="button" class="btn btn-default btn-xs">
                                                            <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                                        </button>
                                                    </span>
                                                </transition>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div v-else>
                                    <div v-if="(table || customQuery) && !processing" class="empty">
                                        No records found
                                    </div>
                                </div>
                            </transition>
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

                        <Query v-if="!processing || customQuery" @beforeQuery="beforeQuery" @customQuery="beforeCustomQuery" @success="querySuccess" @afterQuery="afterCustomQuery" @error="queryError"></Query>
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
                schema: null,
                tablePrimaryKey: '',
                tablePrimaryKeyFormat: '',
                editingRow: null,
                records: [],
                sql: '',
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
                processing: false
            }
        },
        mounted() {
//            this.getTables()
        },
        components: {
            List: require('./TableList'),
            Query: require('./Query')
        },
        computed: {
            classMainPanel: function() {
//                console.log(this.recordCount())
                return {
                    'col-md-9': true
                }
            },
            classSidePanel: function() {
//                console.log(this.recordCount())
                return {
                    'col-md-3': true,//this.recordCount() > 0 || this.processing,
                    'col-md-12': false//this.recordCount() < 1 && ! this.processing
                }
            }
        },
        methods: {
            recordCount() {
                console.log(this.records)
                return this.records ? this.records.length : 0
            },
            openTable(table) {
                this.clearTable()
                this.customQuery = false
                this.table = table
                this.sql = 'SELECT * FROM ' + this.table
                this.getPrimaryKey(this.table).then(() => {
                    this.getSchema(this.table).then(() => {
                        this.getRecords()
                    })
                })
            },
            getPrimaryKey(table) {
                let sql = "SELECT \
                pg_attribute.attname, \
                    format_type(pg_attribute.atttypid, pg_attribute.atttypmod) \
                FROM pg_index, pg_class, pg_attribute, pg_namespace \
                WHERE \
                pg_class.oid = '" + (table || this.table) + "'::regclass AND \
                indrelid = pg_class.oid AND \
                nspname = 'public' AND \
                pg_class.relnamespace = pg_namespace.oid AND \
                pg_attribute.attrelid = pg_class.oid AND \
                pg_attribute.attnum = any(pg_index.indkey) \
                AND indisprimary"
                return this.executeQuery(sql)
                    .then(response => {
                        this.processing = false
                        let data = this.parseResponse(response)
                        if (data.length) {
                            console.log(data)
                            this.tablePrimaryKey = data[0].attname || 'id'
                            this.tablePrimaryKeyFormat = data[0].format_type || ''
                        }
                    })
            },
            getSchema(table) {
                let sql = "SELECT " +
                    "column_name, table_name, data_type, udt_name, is_nullable, is_updatable " +
                    "FROM INFORMATION_SCHEMA.COLUMNS WHERE " +
                    "table_name = '" + (table || this.table) + "'"
                return this.executeQuery(sql)
                    .then(response => {
                        this.processing = false
                        this.schema = this.parseResponse(response)
                    })

            },
            executeQuery(sql, page) {
                this.processing = true
                let data = {
                    input: sql || this.sql
                }
                if (page) {
                    data.page = page
                }
                return axios.post('http://postgres:5433/query', data)
                    .catch(error => {
                        this.queryError(error)
                    })
            },
//            getTables() {
//                axios.post('http://postgres:5433/query', {
//                    input: 'SELECT * FROM pg_catalog.pg_tables WHERE schemaname = \'public\'',
//                    pluck: 'tablename'
//                }).then(response => {
//                    this.results = response.data
//                }).catch(error => {
//                    console.log(error)
//                })
//            },
            currentPage() {
                return this.pagination.current_page
            },
            getRecords(page) {
                this.processing = true
                if (typeof page === "undefined") {
                    page = this.currentPage()
                }

                this.primaryPanelTitle = 'Records in table "' + this.table + '"'

//                this.records = []
                return this.executeQuery(null, page).then(response => {
                    this.querySuccess(response, 'Records in table "' + this.table + '"')
                })
            },
            updateRow(primaryKey) {
                this.processing = true
                console.log('NOT IMPLEMENTED: UPDATE ' + this.table + ' WHERE ' + this.tablePrimaryKey + ' = ' + primaryKey)

                // get edited fields

                // submit patch request

                // refresh table

                this.editingRow = null
            },
            deleteRow(primaryKey) {
                this.processing = true
                let sql = 'DELETE FROM ' + this.table + ' WHERE ' + this.tablePrimaryKey + ' = ' + primaryKey;
                this.executeQuery(sql).then(() => {
                    this.getRecords(this.currentPage()).then(() => {
                        this.editingRow = null
                        this.processing = false
                    })
                })
            },
            beforeCustomQuery(sql) {
                this.customQuery = true
                this.primaryPanelTitle = sql
                this.clearTable()
            },
            beforeQuery(sql) {
                this.sql = sql
                this.processing = true
//                this.records = []
            },
            afterCustomQuery() {
//                this.customQuery = false
            },
            parseResponse(response) {
                let data = null
                if (response.data && response.data.data) {
                    data = response.data.data
                    if (data !== Array) {
                        data = Object.values(data)
                    }
                }
                return data
            },
            querySuccess(response, table) {
                this.processing = false
//                this.primaryPanelTitle = table || 'User Query'
                this.records = this.parseResponse(response)
                this.pagination = this.$parent.parsePagination(response)
            },
            queryError(error) {
                this.processing = false
                let message = this.$parent.handleError(error)
                alert(message)
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

<style>
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
        right: 21px;
        top: 0;
        bottom: 0;
        height: 14px;
        margin: auto;
        font-size: 14px;
        cursor: pointer;
        color: #bbb;
    }
</style>