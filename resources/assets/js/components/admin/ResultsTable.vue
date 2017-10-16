<template>
    <div class="results table-responsive">
        <transition name="fade">
            <table class="table table-hover table-striped table-condensed">
                <thead>
                <tr v-if="tab === 'query'">
                    <th v-for="(value, name) in records[0]">
                        <!--<span v-html="keyIcon(name)"></span>-->
                        {{ name }}
                    </th>
                </tr>
                <tr v-else>
                    <th v-for="(value, name) in schema">
                        <span v-html="keyIcon(value['column_name'])"></span>
                        <a href="#" @click.prevent="$emit('sortColumn', value['column_name'])">{{ value['column_name'] }}<span v-html="sortIcon(value['column_name'])" style="margin-left:5px"></span></a>
                        <br>
                        <small>{{ getDataTypeDisplay(value['type']) }}</small>
                    </th>
                    <th></th>
                </tr>
                </thead>
                <tbody v-if="records && records.length > 0">
                    <result-table-row
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
                            @updateRow="saveRow"
                            @deleteRow="$emit('deleteRow', row[tablePrimaryKey])"
                    />
                </tbody>
                <tbody v-else>
                    <tr v-if="!processing && ((records && records.length > 0) || table || customQuery)">
                        <td :colspan="schema ? (Object.keys(schema).length + (tab === 'query' ? 1 : 0)) : 2">
                            <div class="empty">
                                <span v-if="(table || tab === 'query')">No records found</span>
                                <span v-else>No table selected</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
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
            'editingRow',
            'customQuery'
        ],
        components: {
            'result-table-row': require('./ResultTableRow')
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
            saveRow(data) {
                console.log('2.', data)
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

<style>
    .table th {
        white-space:nowrap;
    }
</style>