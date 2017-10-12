<template>
    <tr :class="{ info: editingRow === row[tablePrimaryKey] }">
        <td v-for="(value, name) in row">
            <span v-if="editingRow === row[tablePrimaryKey]">
                <component
                        :is="getFormComponent(name)"
                        v-model="newValues[name]"
                        :placeholder="getFieldDefault(name)"
                        :type="getTypeAttr(name)"
                />
            </span>
            <span v-else>
                {{ value }}
            </span>
        </td>
        <td v-if="tab !== 'query'" class="rowButtons">
            <transition>
                <span v-if="editingRow === row[tablePrimaryKey]">
                    <button key="cancel" @click="$emit('cancelEditingRow', null)" type="button" class="btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                    </button>
                    <button key="save" @click="saveRow" type="button" class="btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                    </button>
                    <button key="delete" @click="$emit('deleteRow', row[tablePrimaryKey])" type="button" class="btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    </button>
                </span>
                <span v-else>
                    <button key="edit" @click="editRow" type="button" class="btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                    </button>
                </span>
            </transition>
        </td>
    </tr>
</template>

<script>
    import _ from 'lodash'
    export default {
        props: [
            'tab',
            'table',
            'schema',
            'row',
            'tablePrimaryKey',
            'processing',
            'editingRow'
        ],
        data() {
            return {
                newValues: {}
            }
        },
        mounted() {
            this.newValues = _.clone(this.row)
        },
        methods: {
            editRow() {
                this.$emit('editingRow', this.row[this.tablePrimaryKey])
            },
            saveRow() {
                this.$emit('updateRow', {
                    primaryKey: this.row[this.tablePrimaryKey],
                    data: this.updatedValues()
                })
            },
            getFieldDefault(column) {
                let config = this.$parent.getColumn(column)
                return config.column_default ? config.column_default : ""
            },
            getColumn(column) {
                return this.$parent.getColumn(column)
            },
            getFormComponent(column) {
                let config = this.$parent.getColumn(column)
                let data_type = this.$parent.getDataTypeDisplay(config.data_type)
                switch(data_type) {
                    case "uuid":
                    case "integer":
                    case "character varying": {
                        return 'el-input'
                        break;
                    }
                    case "date":
                    case "timestamp": {
                        return 'el-date-picker'
                        break;
                    }
                }
            },
            getTypeAttr(column) {
                let config = this.$parent.getColumn(column)
                let data_type = this.$parent.getDataTypeDisplay(config.data_type)
                switch(data_type) {
                    case "date": {
                        return 'date'
                        break;
                    }
                    case "timestamp": {
                        return 'datetime'
                        break;
                    }
                }
            },
            updatedValues() {
                var original = this.row
                var changed = {}
                _.each(this.newValues, function(val, attr) {
                    if (!_.isEqual(original[attr], val)) {
                        changed[attr] = val
                    }
                })
                return changed
            }
        }
    }
</script>