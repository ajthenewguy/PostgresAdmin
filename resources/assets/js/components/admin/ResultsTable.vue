<template>
    <div>
        <div class="results table-responsive" v-if="(tab === 'query' || tab === 'content' && table)">
            <table class="table table-hover table-striped table-condensed">
                <thead>
                <tr v-if="tab === 'query'">
                    <th v-for="(value, name) in records[0]">
                        <!--<span v-html="keyIcon(name)"></span>-->
                        {{ name }}
                    </th>
                </tr>
                <tr v-else-if="tableConfig">
                    <th>
                        <button @click="$emit('insertingRow', true)" type="button" class="btn btn-default btn-xs" aria-label="Insert Row">
                            <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
                        </button>
                    </th>
                    <th v-for="(value, name) in tableConfig.schema">
                        <span v-html="keyIcon(value['column_name'])"></span>
                        <a href="#" @click.prevent="$emit('sortColumn', value['column_name'])">{{ value['column_name'] }}<span v-html="sortIcon(value['column_name'])" style="margin-left:5px"></span></a>
                        <br>
                        <small>{{ getDataTypeDisplay(value['type']) }}</small>
                    </th>
                </tr>
                </thead>
                <transition name="fade">
                    <tbody v-if="!state.processing">
                        <insert-table-row
                                v-if="table && insertingRow"
                                :tab="tab"
                                :table="table"
                                :schema="tableConfig.schema"
                                @cancelInsertingRow="$emit('insertingRow', false)"
                                @insertRow="insertRow"
                        />
                        <result-table-row
                                v-if="records"
                                v-for="row in records"
                                :key="(tableConfig? row[tableConfig.primaryKey] : undefined)"
                                :tab="tab"
                                :table="table"
                                :table-config="tableConfig"
                                :row="row"
                                :editing-row="editingRow"
                                @editingRow="$emit('editingRow', row[tableConfig.primaryKey])"
                                @cancelEditingRow="$emit('editingRow', null)"
                                @updateRow="updateRow"
                                @deleteRow="$emit('deleteRow', row[tableConfig.primaryKey])"
                        />
                        <tr v-if="!state.processing">
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
                </transition>
            </table>
        </div>
        <div v-else class="empty">
            <span>No table selected</span>
        </div>
    </div>
</template>

<script>
    export default {
        props: [
            'tab',
            'table',
            'tableConfig',
            'order',
            'records',
            'insertingRow',
            'editingRow'
        ],
        mixins: [require('../../mixins/PostgresMixin.vue')],
        data() {
            return {
                store: window.store,
                state: window.store.state
            }
        },
        components: {
            'insert-table-row': require('./InsertTableRow'),
            'result-table-row': require('./ResultTableRow')
        },
        computed: {
            colspan: function () {
                return this.tableConfig ? (Object.keys(this.tableConfig.schema).length + (this.tab === 'content' ? 1 : 0)) : 2
            }
        },
        methods: {
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
                if (this.tableConfig.schema) {
                    if (column === this.tableConfig.primaryKey) {
                        icon = '<span class="glyphicon glyphicon-star" aria-hidden="true"></span>'
                    } else if (_.find(this.tableConfig.foreignKeys, ['column_name', column])) {
                        icon = '<span class="glyphicon glyphicon-star-empty" aria-hidden="true"></span>'
                    }
                }
                return icon
            },
            sortIcon(column) {
                let icon = ''
                if (this.order) {
                    if (typeof this.order === "string" && this.order === column) {
                        icon = '<span class="glyphicon glyphicon-sort-by-attributes" aria-hidden="true"></span>'
                    } else if (this.order && this.order.constructor === Array && this.order[0] === column) {
                        icon = '<span class="glyphicon glyphicon-sort-by-attributes-alt" aria-hidden="true"></span>'
                    }
                }
                return icon
            },
            schema() {
                return this.tableConfig.schema
            }
        }
    }
</script>

<style lang="scss">
    .table th,
    .table .rowButtons {
        white-space: nowrap;
    }
    .results {
        .table {
            margin-bottom: 0;
            td {
                input {
                    border: 0;
                    height: 20px;
                    padding: 3px;
                }
                span {
                    white-space: nowrap;
                }
            }
        }
    }
    .fade-enter-active, .fade-leave-active {
        transition: opacity .5s
    }
    .fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
        opacity: 0
    }
</style>