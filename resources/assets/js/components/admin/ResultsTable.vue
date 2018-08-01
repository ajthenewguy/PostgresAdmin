<template>
    <tbody style="flex: 1; overflow: auto" class="fixed-table-container hidden-head">
        <div class="results table-responsive" v-if="(tab === 'query' || tab === 'content' && table)" :style="tableWrapperStyleLoading">
            <table class="table table-hover table-condensed table-striped" :id="id">
                <thead>
                <template v-if="tab === 'query'">
                    <tr>
                        <th v-for="(value, name) in records[0]">
                            <div class="th-inner">{{ name }}</div>
                        </th>
                    </tr>
                </template>
                <tr v-else-if="tableConfig !== null">
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
                <tbody v-if="records && records.length > 0" :class="{ 'loading': processing }">
                    <insert-table-row
                            v-if="table && insertingRow"
                            :tab="tab"
                            :table="table"
                            :schema="tableConfig !== null && tableConfig.schema"
                            @cancelInsertingRow="$emit('insertingRow', false)"
                            @insertRow="insertRow"
                    />
                    <result-table-row
                            v-show="records"
                            v-for="row in records"
                            :key="(tableConfig? row[tableConfig.primaryKey] : undefined)"
                            :tab="tab"
                            :table="table"
                            :table-config="tableConfig"
                            :row="row"
                            :editing-row="editingRow"
                            @openTableRow="$emit('openTableRow', $event)"
                            @editingRow="$emit('editingRow', row[tableConfig.primaryKey])"
                            @cancelEditingRow="$emit('editingRow', null)"
                            @updateRow="updateRow"
                            @deleteRow="$emit('deleteRow', row[tableConfig.primaryKey])"
                    />
                </tbody>
                <tbody v-else>
                    <insert-table-row
                            v-if="table && insertingRow"
                            :tab="tab"
                            :table="table"
                            :schema="tableConfig !== null && tableConfig.schema"
                            @cancelInsertingRow="$emit('insertingRow', false)"
                            @insertRow="insertRow"
                    />
                    <tr>
                        <td :colspan="colspan">
                            <div class="empty"><span v-if="processing">Processing...</span><span v-else>No records</span></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="empty">
            <span>No table selected</span>
        </div>
    </tbody>
</template>

<script>
    export default {
        props: [
            'id',
            'tab',
            'table',
            'tableConfig',
            'order',
            'processing',
            'records',
            'insertingRow',
            'editingRow'
        ],
        mixins: [require('../../mixins/PostgresMixin.vue')],
        data() {
            return {
                // store: window.store,
                // state: window.store.state,
                // util: window.util
            }
        },
        components: {
            'insert-table-row': require('./InsertTableRow'),
            'result-table-row': require('./ResultTableRow')
        },
        computed: {
            colspan: function () {
                let span = 2
                if (this.getTableSchema(this.table) !== null) {
                    span = Object.keys(this.getTableSchema(this.table)).length
                    if (this.tab === 'content') span++
                }
                return span
            },
            tableWrapperStyleLoading: function () {
                return (this.processing ? 'overflow: hidden;' : '')
            }
        },
        mounted() {
            $(function () {
                $('[data-toggle="tooltip"]').tooltip()
            })
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
                if (this.tableConfig) {
                    if (this.tableConfig.schema) {
                        if (column === this.tableConfig.primaryKey) {
                            icon = '<span class="glyphicon glyphicon-star" aria-hidden="true"></span>'
                        } else if (_.find(this.tableConfig.foreignKeys, ['column_name', column])) {
                            icon = '<span class="glyphicon glyphicon-star-empty" aria-hidden="true"></span>'
                        }
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
                let schema = {}
                if (this.tableConfig !== null) {
                    schema = this.tableConfig.schema
                }
                return schema
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
        height: 100%;

        .table {
            font-size: 13px;
            margin-bottom: 0;

            tbody > tr > td {
                border-top: none;
            }
            td {
                border-right: 1px dotted #ddd;
                height: 26px;

                input {
                    border: 0;
                    height: 20px;
                    padding: 3px;
                }
                span {
                    white-space: nowrap;
                }
            }
            td.rowButtons > div {
                width: 24px;
                max-width: 75px;
            }
            .editing td.rowButtons > div {
                width: 75px;
                max-width: 75px;
            }
            .rowButtons > * {
                visibility: hidden;
            }
            .rowButtons div.btn-group {
                position: fixed;
                z-index: 1000;
            }
            tr:hover, tr.editing {
                .rowButtons > * {
                    visibility: visible;
                }
            }
            tr.warning, tr.success {
                .rowButtons > * {
                    width: 75px;
                }
            }
            label {
                margin: 0;
            }
            .el-input {
                font-size: inherit;
            }
            .el-date-editor.el-input,
            .el-date-editor.el-input__inner {
                width: 170px;
            }
            .el-input__prefix {
                left: 1px;
            }
            .el-input__icon {
                width: 22px;
                line-height: 20px;
            }
            .el-input--prefix .el-input__inner,
            .el-input--suffix .el-input__inner {
                padding-left: 22px;
            }
        }
        .table-condensed > tbody > tr > td {
            padding: 2px 3px;
        }
        .table-condensed > tbody > tr > td:first-of-type {
            padding: 2px 2px 2px 7px;
        }
    }
    .fade-enter-active, .fade-leave-active {
        transition: opacity .5s
    }
    .fade-enter, .fade-leave-to {
        opacity: 0
    }
    [tooltip]:before {
        position: absolute;
        content: attr(tooltip);
        opacity: 0;
        bottom: 20px;
        left: 0;
        background: #000;
        color: #fff;
        border: 1px solid transparent;
        border-radius: 4px;
        padding: 4px 8px;
        z-index: 999;
        /* hovering the tooltip itself dismisses it */
        pointer-events: none;
    }
    [tooltip]:hover:before {
        opacity: 1;
    }

    .tab-pane.query .table-responsive {
        overflow-x: visible;
    }
</style>