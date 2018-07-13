<template>
    <div class="tab-pane" :class="{ active: getTabIndex('id', id) === currentTab, query: type === 'query' }" :id="'tab-' + id">
        <template v-if="type === 'structure'">
            <structure-table
			 	v-if="table && schema()"
                :table="table"
                :schema="schema()"
				:indexes="indexes()"
                :primary-key="primaryKey()"
                :processing="processing"
                :foreign-keys="foreignKeys()"
                @refresh="$emit('refresh', $event)"
            />
            <div v-else>
                <div v-if="!processing" class="empty">
                    No table selected
                </div>
            </div>
        </template>
        <query
                ref="customQuery"
                v-if="type === 'query'"
                :sql="customQuery"
                :history="history"
                :loaded-tables="loadedTables"
                :processing="processing"
                @beforeQuery="beforeQuery"
                @customQuery="beforeCustomQuery"
                @success="success"
                @afterQuery="afterCustomQuery"
                @error="queryError"
        />
        <table v-if="(type === 'query' || type === 'content')" class="results-table-container">
            <content-filter
                    v-if="type === 'content'"
                    id="contentFilter"
                    :find="where"
                    @filterWhere="filterWhere"
                    :columns="columns"
            />
            <results-table
                    :class="{ 'tab-pane-content': type !== 'query' }"
                    v-show="type === 'query' || type === 'content'"
                    :tab="type"
                    :id="'results-table-' + id"
                    :table="table"
                    :table-config="state.tables[table]"
                    :order="order"
                    :processing="processing"
                    :records="records"
                    :editing-row="editingRow"
                    :inserting-row="insertingRow"
                    @sortColumn="sortColumn"
                    @insertingRow="setInsertingRow"
                    @editingRow="setEditingRow"
                    @insertRow="insertRow"
                    @updateRow="updateRow"
                    @deleteRow="deleteRow"
                    @openTableRow="$emit('openTableRow', $event)"
                    @refresh="$emit('refresh', $event)"
            />
            <results-footer
                    v-if="type === 'query' || type === 'content'"
                    id="tabFooter"
                    :executed="executed"
                    :records="records"
                    :pagination="(type === 'content' ? pagination : false)"
                    :processing="processing"
                    :request-time="requestTime"
                    :tab="type"
                    :table="(type === 'content' ? table : false)"
                    @refresh="refresh"
                    @changePage="getRecords"
                    @changePerPage="handleSizeChange"
            />
        </table>
    </div>
</template>
<script>
    export default {
        props: [ 'id', 'currentTab', 'type', 'table', 'loadedTables', 'find' ],
        mixins: [require('../../mixins/PostgresMixin.vue')],
        data() {
            return {
                bus: window.bus,
                // store: window.store,
                // state: window.store.state,
                editingRow: null,
                executed: false,
                filter: null,
                history: [],
                insertingRow: false,
                order: null,
                records: [],
                where: this.find,
                processing: false
            }
        },
        mounted() {
            if (this.table) {
                this.order = this.getDefaultSort(this.table)
                if (this.type === "content") {
                    this.getRecords()
                }
            }
            // set data from props (should be able to load a tab state through props)
        },
        components: {
            'content-filter': require('./Filter'),
            'query': require('./Query'),
            'results-table': require('./ResultsTable'),
            'results-footer': require('./ResultsFooter.vue'),
            'structure-table': require('./StructureTable')
        },
        computed: {
            columns: function() {
                let columns = []
                if (this.type !== 'query' && this.table) {
                    columns = _.map(this.state.tables[this.table].schema, 'column_name')
                }
                return columns
            },
            loaded: function() {
                return this.type === 'query' || this.state.tables[this.table] && this.state.tables[this.table].schema !== null
            }
        },
        methods: {
            beforeQuery(query) {
                this.processing = true
                if (query) {
                    this.sql = query
                    this.pushHistory(query)
                }
            },
            afterQuery() {
                this.processing = false
            },
            beforeCustomQuery(query) {
                this.executed = true
                this.processing = true
                this.$emit('beforeCustomQuery', query)
                if (query) {
                    this.customQuery = query
                } else {
                    this.records = []
                }
            },
            afterCustomQuery(query) {
                this.$emit('afterCustomQuery', query)
                this.processing = false
                if (this.result) {
                    this.records = this.result
                }
            },
            currentPage() {
                return this.pagination.current_page
            },
            handleSizeChange(size) {
                this.pagination.per_page = size
                this.refresh()
            },
            filterWhere(where) {
                this.where = where
                this.getRecords()
            },
            getTabIndex(key, value) {
                return this.$parent.getTabIndex(key, value)
            },
            getRecords(page) {
                let sql = this.makeSelect(this.table, this.where, null, this.order)
                this.beforeQuery(sql)
                this.executed = true
                console.log('Getting records:', sql)
                if (typeof page === "undefined") {
                    page = this.currentPage()
                }
                return this.selectQuery(sql, page).then(response => {
                    if (this.result !== null) {
                        this.records = this.result
                    }
                    this.afterQuery()
                    this.$emit('loaded')
                })
            },
            recordCount() {
                return this.records ? this.records.length : 0
            },
            refresh(e) {
                if (this.type === "query") {
                    this.$refs.customQuery.run()
                } else {
                    this.beforeQuery()
                    this.loadTable(this.table, true).then(() => {
                        this.afterQuery()
                        this.bus.$emit('tabRefreshed', this.state.tables[this.table])
                        this.getRecords()
                    })
                }
            },
            insertRow(data) {
                let query = this.makeInsert(this.table, data)
                this.beforeQuery(query)
                return this.insertQuery(query, data).then(() => {
                    this.afterQuery()
                    this.setInsertingRow(false)
                    this.getRecords()
                })
            },
            updateRow(payload) {
                let where = {}
                let primaryKey = this.state.tables[this.table].primaryKey
                where[primaryKey] = payload.primaryKey
                let query = this.makeUpdate(this.table, payload.data, where)
                this.beforeQuery(query)
                return this.updateQuery(query, payload.data).then(() => {
                    this.afterQuery()
                    this.setEditingRow(null)
                    this.getRecords()
                })
            },
            deleteRow(primaryKey) {
                this.$confirm('This will permanently delete the row. Continue?', 'Warning', {
                    confirmButtonText: 'OK',
                    cancelButtonText: 'Cancel',
                    type: 'warning'
                }).then(() => {
                    let where = {}
                    where[this.primaryKey()] = primaryKey
                    let query = this.makeDelete(this.table, where)
                    this.beforeQuery(query)
                    this.deleteQuery(query, where).then(() => {
                        this.afterQuery()
                        this.getRecords(this.currentPage()).then(() => {
                            this.editingRow = null
                            // this.state.setProcessing(false) // not a function... ?
                            this.$message({
                                type: 'success',
                                message: 'Delete completed'
                            })
                        })
                    })
                }).catch(() => {
                    this.$message({
                        type: 'info',
                        message: 'Delete canceled'
                    })
                })
            },
            pushHistory(query) {
                if (query !== this.history[this.history.length-1]) {
                    this.history.push(query)
                    if (this.history.length > 15) {
                        this.history = this.history.slice(0, 16)
                    }
                }
            },
            tableConfig() {
                return this.state.tables[this.table]
            },
            indexes() {
                return this.state.tables[this.table].indexes
            },
            schema() {
                return this.state.tables[this.table].schema
            },
            primaryKey() {
                return this.state.tables[this.table].primaryKey
            },
            foreignKeys() {
                return this.state.tables[this.table].foreignKeys
            },
            setInsertingRow(row) {
                this.editingRow = false
                this.insertingRow = !! row
            },
            setEditingRow(row) {
                this.insertingRow = false
                this.editingRow = row
            },
            sortColumn(column) {
                if (typeof this.order === "string" && this.order === column) {
                    this.order = [column, 'DESC']
                } else if (this.order && this.order.constructor === Array && this.order[0] === column) {
                    this.order = this.getDefaultSort(this.table)
                } else {
                    this.order = column
                }
                this.getRecords()
            },
            error(error) {
                this.$emit('error', error)
            },
            success() {
                this.$emit('success')
            },
            titleCase(string) {
                return string.replace(/_/g, ' ').replace(/(^[a-z])|(\s+[a-z])/g, txt => txt.toUpperCase())
            }
        }
    }
</script>
