<template>
    <div class="results table-responsive">
        <transition name="fade">
            <table v-if="(tab === 'query' || tab === 'content' && table)" class="table table-hover table-striped table-condensed">
                <thead>
                <tr v-if="tab === 'query'">
                    <th v-for="(value, name) in records[0]">
                        <!--<span v-html="keyIcon(name)"></span>-->
                        {{ name }}
                    </th>
                </tr>
                <tr v-else-if="table">
                    <th v-for="(value, name) in schema">
                        <span v-html="keyIcon(value['column_name'])"></span>
                        <a href="#" @click.prevent="$emit('sortColumn', value['column_name'])">{{ value['column_name'] }}<span v-html="sortIcon(value['column_name'])" style="margin-left:5px"></span></a>
                        <br>
                        <small>{{ getDataTypeDisplay(value['type']) }}</small>
                    </th>
                    <th>
                        <button @click="$emit('insertingRow', true)" type="button" class="btn btn-default btn-xs" aria-label="Insert Row">
                            <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                        </button>
                    </th>
                </tr>
                </thead>
                <tbody v-if="!processing"> <!--v-if="records && records.length > 0"-->
                    <insert-table-row
                            v-if="table && insertingRow"
                            :tab="tab"
                            :table="table"
                            :schema="schema"
                            :table-primary-key="tablePrimaryKey"
                            :processing="processing"
                            :inserting-row="insertingRow"
                            @cancelInsertingRow="$emit('insertingRow', false)"
                            @insertRow="insertRow"
                    />
                    <result-table-row
                            v-if="records"
                            v-for="row in records"
                            :key="row[tablePrimaryKey]"
                            :tab="tab"
                            :table="table"
                            :schema="schema"
                            :table-primary-key="tablePrimaryKey"
                            :row="row"
                            :processing="processing"
                            :editing-row="editingRow"
                            @editingRow="$emit('editingRow', row[tablePrimaryKey])"
                            @cancelEditingRow="$emit('editingRow', null)"
                            @updateRow="updateRow"
                            @deleteRow="$emit('deleteRow', row[tablePrimaryKey])"
                    />
                    <tr v-if="!processing">
                        <td
                                v-if="(tab === 'query' || tab === 'content') && (! records || records.length < 1)"
                                :colspan="colspan"
                        >
                            <div class="empty">
                                <span>No records found</span>
                            </div>
                        </td>
                        <td
                                v-if="(tab === 'structure' || tab === 'content') && ! table"
                                :colspan="colspan"
                        >
                        </td>

                    </tr>
                </tbody>
            </table>
            <div v-else class="empty">
                <span>No table selected</span>
            </div>
        </transition>
    </div>
</template>

<script>
    export default {
        props: [
            'tab',
            'table',
            'order',
            'schema',
            'records',
            'tablePrimaryKey',
            'processing',
            'insertingRow',
            'editingRow',
            'customQuery'
        ],
        components: {
            'insert-table-row': require('./InsertTableRow'),
            'result-table-row': require('./ResultTableRow')
        },
        computed: {
            // a computed getter
            colspan: function () {
                return this.schema ? (Object.keys(this.schema).length + (this.tab === 'content' ? 1 : 0)) : 2
            }
        },
        methods: {
            getColumn(column) {
                return this.$parent.getColumn(column)
            },
            getColumnForeignKey(column) {
                return this.$parent.getColumnForeignKey(column)
            },
            getDataTypeDisplay(input) {
                let output = input || '_'
                if (output.includes('timestamp ')) {
                    output = output.split(' ')[0]
                }
                if (output === "character varying") {
                    output = "varchar"
                }
                return output
            },
            insertRow(data) {
                this.$emit('insertRow', data)
            },
            updateRow(data) {
                this.$emit('updateRow', data)
            },
            keyIcon(column) {
                let icon = ''
                let foreign_key = this.getColumnForeignKey(column)
                if (column === this.tablePrimaryKey) {
                    icon = '<span class="glyphicon glyphicon-star" aria-hidden="true"></span>'
                } else if (foreign_key) {
                    icon = '<span class="glyphicon glyphicon-star-empty" aria-hidden="true"></span>'
                }
                return icon
            },
            sortIcon(column) {
                let icon = ''
                if (typeof this.order === "string" && this.order === column) {
                    icon = '<span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>'
                } else if (this.order && this.order.constructor === Array && this.order[0] === column) {
                    icon = '<span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>'
                }
                return icon
            }
        }
    }
</script>

<style lang="scss">
    .table th,
    .table .rowButtons {
        white-space: nowrap;
    }
    .results .table td input {
        border: 0;
        height: 20px;
        padding: 3px;
    }
</style>