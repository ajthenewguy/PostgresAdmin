<template>
    <tr :class="{ 'info editing': (tableConfig && tableConfig.primaryKey && editingRow === row[tableConfig.primaryKey]) }">
        <td v-if="tab !== 'query'" class="rowButtons">
            <div>
                <span class="index">{{ index }}</span>
                <div class="btn-group" role="toolbar" aria-label="..." v-if="(tableConfig && tableConfig.primaryKey && editingRow === row[tableConfig.primaryKey])">
                    <button key="cancel" @click="cancelEditingRow" type="button" class="btn btn-default btn-xs" tooltip="Cancel">
                        <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                    </button>
                    <button key="save" @click="saveRow" type="button" class="btn btn-default btn-xs" tooltip="Save">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                    </button>
                    <button key="delete" @click="$emit('deleteRow', row[tableConfig.primaryKey])" type="button" class="btn btn-default btn-xs" tooltip="Delete">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="btn-group" role="toolbar" aria-label="..." v-else>
                    <button key="edit" @click="editRow" type="button" class="btn btn-default btn-xs" tooltip="Edit / Delete...">
                        <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </td>
        <td v-if="tab === 'query'">
            <span class="index">{{ index }}</span>
        </td>
        <td v-for="(value, name) in data">
            <span v-if="(tableConfig && tableConfig.primaryKey && editingRow === row[tableConfig.primaryKey])">
                <component
                        :is="getFormComponent(name)"
                        v-model="data[name]"
                        :placeholder="getFieldDefault(name)"
                        :type="getTypeAttr(name)"
                        @keyup.enter.native="saveRow"
                />
            </span>
            <template v-else>
                <template v-if="columnIsForeignKey(name) && data[name]">
                    <p class="pull-right"><a href="#" @click.prevent="openForeignRow(name)"><span class="glyphicon glyphicon-link" aria-hidden="true"></span></a></p>
                    <p class="pull-left" v-html="valueDisplay(name)"></p>
                    <div style="clear: both"></div>
                </template>
                <span v-else v-html="valueDisplay(name)"></span>
            </template>
        </td>
    </tr>
</template>

<script>
    import _ from 'lodash'
    export default {
        props: [
            'index',
            'tab',
            'table',
            'row',
            'insertingRow',
            'editingRow'
        ],
        mixins: [require('../../mixins/PostgresMixin.vue')],
        data() {
            return {
                store: window.store,
                state: window.store.state,
                config: null,
                data: {}
            }
        },
        mounted() {
            this.refreshRow()
        },
        computed: {
            tableConfig: function () {
                return this.$parent.tableConfig
            }
        },
        watch: {
            row: function (newData) {
                this.data = _.clone(newData)
            }
        },
        methods: {
            editRow() {
                this.refreshRow()
                this.$emit('editingRow', this.row[this.tableConfig.primaryKey])
            },
            cancelEditingRow() {
                this.$emit('cancelEditingRow', null)
            },
            saveRow() {
                this.$emit('updateRow', {
                    primaryKey: this.row[this.tableConfig.primaryKey],
                    data: this.updatedValues()
                })
            },
            refreshRow() {
                this.data = _.clone(this.row)
            },
            foreignKeyClick() {
                document.execCommand('selectAll',false,null)
            },
            getFieldDefault(column) {
                let config = this.getColumn(this.table, column)
                return config.default_value ? config.default_value : ""
            },
            getFormComponent(column) {
                let config = this.getColumn(this.table, column)
                return this.interfaceFacet(config.type)
            },
            getTypeAttr(column) {
                let config = this.getColumn(this.table, column)
                let data_type = this.$parent.getDataTypeDisplay(config.type)
                switch(data_type) {
                    case "date": {
                        return 'date'
                    }
                    case "text": {
                        return 'text'
                    }
                    case "timestamp": {
                        return 'datetime'
                    }
                }
            },
            columnIsForeignKey(column) {
                if (this.tableConfig) {
                    let fk = _.find(this.tableConfig.foreignKeys, ['column_name', column])
                    return typeof fk !== "undefined"
                }
                return false
            },
            valueDisplay(column) {
                let value = this.data[column]
                let config = null
                if (value === null) {
                    config = this.getColumn(this.table, column)
                    if (config.nullable === "YES" || config.nullable === true) {
                        value = '<code>&lt;null&gt;</code>'
                    }
                }
                return value
            },
            openForeignRow(column) {
                let foreignKey = _.find(this.tableConfig.foreignKeys, ['column_name', column])
                this.$emit('openTableRow', {
                    table: foreignKey.foreign_table_name,
                    where: foreignKey.foreign_column_name + " = '" + this.row[column] + "'",
                    pos: this.state.activeTab + 1
                });
            },
            updatedValues() {
                var original = this.row
                var changed = {}
                var $this = this
                _.each(this.data, function(val, attr) {
                    if (!_.isEqual(original[attr], val)) {
                        if (typeof val !== "undefined") {
                            if (val === "") {
                                let config = $this.getColumn($this.table, attr)
                                if (config.nullable === "YES" || config.nullable === true) {
                                    val = null
                                }
                            }
                            changed[attr] = val
                        }
                    }
                })
                return changed
            }
        }
    }
</script>
<style>
    a.dblclick {
        color: #636b6f;
        border-bottom: 1px dashed #3097D1;
    }
    a.dblclick:hover {
        text-decoration: none;
    }
    input.dblclick {
        width: 100%;
        background-color: transparent;
    }
</style>