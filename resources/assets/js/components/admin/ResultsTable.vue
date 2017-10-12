<template>
    <transition name="fade">
        <table class="table table-hover table-striped table-condensed">
            <thead>
            <tr v-if="tab === 'query'">
                <th v-for="(value, name) in records[0]">
                    <span v-if="name === tablePrimaryKey" class="glyphicon glyphicon-star" aria-hidden="true"></span>
                    {{ name }}
                </th>
            </tr>
            <tr v-else>
                <th v-for="(value, name) in schema">
                    <span v-if="value['column_name'] === tablePrimaryKey" class="glyphicon glyphicon-star" aria-hidden="true"></span>
                    {{ value['column_name'] }}
                    <br>
                    <small>{{ getDataTypeDisplay(value['data_type']) }}</small>
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
</template>

<script>
    export default {
        props: [
            'tab',
            'table',
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
            getDataTypeDisplay(input) {
                let output = input
                if (input.includes('timestamp ')) {
                    output = output.split(' ')[0]
                }
                return output
            },
            saveRow(data) {
                this.$emit('updateRow', data)
            }
        }
    }
</script>