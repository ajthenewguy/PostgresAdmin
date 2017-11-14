<template>
    <div class="tab-pane" :class="{ active: getTabIndex('id', id) === currentTab }" :id="'tab-' + id">
        <template v-if="type === 'structure'">
            <div v-if="table && table.schema" class="tab-pane-content">
                <structure-table
                        :table="table.name"
                        :schema="table.schema"
                        :primary-key="table.primaryKey"
                        :foreign-keys="table.foreignKeys"
                        @refresh="$emit('refresh', $event)"
                />
                <indices-table
                        :table="table.name"
                        :table-foreign-keys="table.foreignKeys"
                        @refresh="$emit('refresh', $event)"
                />
            </div>
            <div v-else>
                <div v-if="!state.processing" class="empty">
                    No table selected
                </div>
            </div>
        </template>
        <query
                v-if="type === 'query'"
                :sql="customQuery"
                :history="history"
                @beforeQuery="beforeQuery"
                @customQuery="beforeCustomQuery"
                @success="success"
                @afterQuery="afterCustomQuery"
                @error="queryError"
        />
        <table v-if="type === 'query' || type === 'content'" class="results-table-container">
            <content-filter
                    v-if="type === 'content' && (records && records.length > 0) || where"
                    id="contentFilter"
                    @filterWhere="filterWhere"
            />
            <results-table
                    :class="{ 'tab-pane-content': type !== 'query' }"
                    v-show="type === 'query' || type === 'content'"
                    :tab="type"
                    :table="(table ? table.name : null)"
                    :table-config="(table ? table : null)"
                    :order="order"
                    :records="records"
                    :editing-row="editingRow"
                    :inserting-row="insertingRow"
                    @sortColumn="sortColumn"
                    @insertingRow="setInsertingRow"
                    @editingRow="setEditingRow"
                    @insertRow="insertRow"
                    @updateRow="updateRow"
                    @deleteRow="deleteRow"
                    @refresh="$emit('refresh', $event)"
            />
            <results-footer
                    v-if="type === 'query' || type === 'content'"
                    id="tabFooter"
                    :pagination="(type === 'content' ? pagination : false)"
                    :records="records"
                    :request-time="requestTime"
                    :tab="type"
                    :table="(type === 'content' ? table.name : false)"
                    @refresh="refresh"
                    @changePage="getRecords"
                    @changePerPage="handleSizeChange"
            />
        </table>

    </div>
</template>
<script>
    export default {
        props: [ 'id', 'currentTab', 'type', 'table' ],
        mixins: [require('../../mixins/PostgresMixin.vue')],
        data() {
            return {
                store: window.store,
                state: window.store.state,
                editingRow: null,
                filter: null,
                history: [],
                insertingRow: false,
                order: null,
                records: [],
                where: null,
            }
        },
        mounted() {
            if (this.table) {
                this.order = this.getDefaultSort(this.table)
                if (this.type === "content") {
                    this.getRecords()
                }
            }
        },
        components: {
            'content-filter': require('./Filter'),
            'query': require('./Query'),
            'results-table': require('./ResultsTable'),
            'results-footer': require('./ResultsFooter.vue'),
            'structure-table': require('./StructureTable'),
            'indices-table': require('./IndicesTable'),

        },
        methods: {
            beforeQuery(query) {
                this.sql = query // !important
                this.pushHistory(query)
            },
            beforeCustomQuery(query) {
                this.$emit('beforeCustomQuery', query)
                if (query) {
                    this.customQuery = query
                } else {
                    this.records = []
                }
            },
            afterCustomQuery(query) {
                this.$emit('afterCustomQuery', query)
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
                let sql = this.makeSelect(this.table.name, this.where, null, this.order)
                console.log(sql)
                if (typeof page === "undefined") {
                    page = this.currentPage()
                }
                return this.selectQuery(sql, page).then(response => {
                    this.records = this.result
                    this.$emit('loaded')
                })
            },
            recordCount() {
                return this.records ? this.records.length : 0
            },
            refresh() {
                this.getRecords()
            },
            insertRow(data) {
                return this.insertQuery(this.makeInsert(this.table.name, data), data).then(() => {
                    this.setInsertingRow(false)
                    this.getRecords()
                })
            },
            updateRow(payload) {
                let where = {}
//                this.loadTable(this.table).then(config => {
                    where[this.table.primaryKey] = payload.primaryKey
                    return this.updateQuery(this.makeUpdate(this.table.name, payload.data, where), payload.data).then(() => {
                        this.setEditingRow(null)
                        this.getRecords()
                    })
//                })
            },
            deleteRow(primaryKey) {
                if (confirm('Delete this row?')) {
                    let where = {}
//                    this.loadTable(this.table).then(config => {
                        where[this.table.primaryKey] = primaryKey
                        this.deleteQuery(this.makeDelete(this.table.name, where), where).then(() => {
                            this.getRecords(this.currentPage()).then(() => {
                                this.editingRow = null
                                this.state.setProcessing(false)
                            })
                        })
//                    })
                }
            },
            pushHistory(query) {
                if (query !== this.history[this.history.length-1]) {
                    this.history.push(query)
                    if (this.history.length > 15) {
                        this.history = this.history.slice(0, 16)
                    }
                }
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