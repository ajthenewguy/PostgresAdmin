<template>
    <tr class="success">
        <td v-if="tab !== 'query'" class="rowButtons">
            <div>
                <div class="btn-group" role="toolbar" aria-label="...">
                    <button key="cancel" @click="$emit('cancelInsertingRow')" type="button" class="btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                    </button>
                    <button key="save" @click="saveRow" type="button" class="btn btn-default btn-xs">
                        <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </td>
        <td v-for="column in schema">
            <component
                    :is="getFormComponent(column.column_name)"
                    v-model="insertRow[column.column_name]"
                    :placeholder="getFieldDefault(column.column_name)"
                    :type="getTypeAttr(column.column_name)"
            />
        </td>
    </tr>
</template>

<script>
    import _ from 'lodash'
    export default {
        props: [
            'tab',
            'table',
            'schema'
        ],
        mixins: [require('../../mixins/PostgresMixin.vue')],
        data() {
            return {
                store: window.store,
                state: window.store.state,
                insertRow: {}
            }
        },
        methods: {
            saveRow() {
                this.$emit('insertRow', this.insertRow)
            },
            getFieldDefault(column) {
                let config = this.getColumn(this.table, column)
                return config.default_value ? config.default_value : (config.nullable === "YES" ? "<null>" : "")
            },
            getFormComponent(column) {
                let config = this.getColumn(this.table, column)
                return this.interfaceFacet(config.type)
            },
            getTypeAttr(column) {
                let config = this.getColumn(column)
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
            }
        }
    }
</script>