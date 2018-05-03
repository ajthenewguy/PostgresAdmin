<template>
    <tr :class="{ warning: (tableConfig && tableConfig.primaryKey && editingRow === row[tableConfig.primaryKey]) }">
        <td v-if="tab !== 'query'" class="rowButtons">
            <div>
                <div class="btn-group" role="toolbar" aria-label="..." v-if="(tableConfig && tableConfig.primaryKey && editingRow === row[tableConfig.primaryKey])">
                    <button key="cancel" @click="$emit('cancelEditingRow', null)" type="button" class="btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                    </button>
                    <button key="save" @click="saveRow" type="button" class="btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                    </button>
                    <button key="delete" @click="$emit('deleteRow', row[tableConfig.primaryKey])" type="button" class="btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="btn-group" role="toolbar" aria-label="..." v-else>
                    <button key="edit" @click="editRow" type="button" class="btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
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
            <span v-else v-html="valueDisplay(name)">
                <!--{{ valueDisplay(name) }}-->
            </span>
        </td>
    </tr>
</template>

<script>
    import _ from 'lodash'
    export default {
        props: [
            'tab',
            'table',
            'tableConfig',
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
            saveRow() {
                this.$emit('updateRow', {
                    primaryKey: this.row[this.tableConfig.primaryKey],
                    data: this.updatedValues()
                })
            },
            refreshRow() {
                this.data = _.clone(this.row)
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
                        break
                    }
                    case "text": {
                        return 'text'
                        break
                    }
                    case "timestamp": {
                        return 'datetime'
                        break
                    }
                }
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