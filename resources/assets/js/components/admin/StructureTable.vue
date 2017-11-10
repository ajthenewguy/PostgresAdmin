<template>
    <div>
        <br>
        <div v-if="!editing">
            <v-button @click.prevent="editStructure" tag="a" icon="pencil" text="Edit" size="sm"></v-button>
            <el-table
                    v-if="table && schema"
                    :data="schema"
                    border
                    style="width: 100%">
                <el-table-column
                        v-for="(value, name) in schema[0]"
                        :key="name"
                        :prop="name"
                        :label="$parent.titleCase(name)"
                >
                    <template slot-scope="scope">
                        {{ scope.row[name] }} <span v-if="name === 'column_name'" v-html="keyIcon(scope.row[name])"></span>
                    </template>
                </el-table-column>
            </el-table>
        </div>
        <div v-else>
            <app-form layout="horizontal">
                <template slot="header">
                    <v-button @click.prevent="showModal = true" icon="plus" text="Column" size="sm"></v-button>
                </template>
                <database-column-field
                        v-for="(fieldset, key) in fieldsets"
                        :key="fieldset.name"
                        :is_new="fieldset.is_new"
                        :column_name="fieldset.name"
                        :formfields="fieldset.fields"
                        :schema_deletes="schema_deletes"
                        :schema_changes="schema_changes"
                        layout="horizontal"
                        @dropColumn="dropColumn"
                        @cancelDropColumn="cancelDropColumn"
                        @cancelNewColumn="cancelNewColumn"
                        @input="onInput"
                />
                <template slot="footer">
                    <v-button @click.prevent="onSubmit" type="primary" text="Submit"></v-button>
                    <v-button @click.prevent="cancelEditStructure" text="Cancel"></v-button>
                </template>
            </app-form>
            <modal v-if="showModal" @close="showModal = false">
                <h3 slot="header">Add New Column</h3>
                <template slot="body">
                    <app-form>
                        <field
                            :label="'Column Name'"
                            :name="'column_name'"
                            :control="'el-input'"
                            :id="'newColumnName'"
                            :placeholder="'required'"
                            :autofocus="true"
                            :value="newColumnName"
                            @input="e => { newColumnName = e.target.value }"
                        ></field>
                        <template slot="footer"></template>
                    </app-form>
                </template>
                <template slot="footer">
                    <v-button @click.prevent="onSubmitNewColumn" type="primary" text="Submit"></v-button>
                    <v-button @click.prevent="showModal = false" text="Cancel"></v-button>
                </template>
            </modal>
        </div>
    </div>
</template>

<script>
    export default {
        props: [ 'table', 'schema', 'primaryKey', 'foreignKeys', 'processing' ],
        mixins: [require('../../mixins/PostgresMixin.vue')],
        components: {
            'field': require('../forms/fields/Field'),
            'database-column-field': require('../forms/fields/DatabaseColumn')
        },
        data() {
            return {
                bus: window.bus,
                store: window.store,
                state: window.store.state,
                editing: false,
                fieldsets: [],
                newColumnName: '',
                schema_changes: [],
                schema_deletes: {},
                schema_creates: [],
                showModal: false
            }
        },
        created() {
            let $this = this
            this.bus.$on('tabRefreshed', function (config) {
                $this.generateFieldsets(config.schema)
                $this.editing = false
            })
        },
        mounted() {
//            this.generateFieldsets()
        },
        methods: {
            addFieldset(column) {
                this.newFieldset(column)
                return true
            },
            editStructure() {
                this.generateFieldsets()
                this.editing = true
            },
            cancelDropColumn(column) {
                if (this.isColumnMarkedForDeletion(column)) {
                    Vue.delete(this.schema_deletes, column)
                }
            },
            cancelEditStructure() {
                this.editing = false
            },
            cancelNewColumn(column) {
                Vue.delete(this.schema_creates, column)
                Vue.delete(this.fieldsets, _.findIndex(this.fieldsets, { is_new: true, name: column }))
            },
            columnExists(column) {
                let exists = false
                let schema_length = this.schema.length
                for (let i = 0; i < schema_length; i++) {
                    if (this.schema[i].column_name === column) {
                        exists = true
                    }
                }
                return exists
            },
            columnSchema(column) {
                let schema = null
                let colCount = this.schema.length
                for (let i = 0; i < colCount; i++) {
                    if (this.schema[i] && this.schema[i].column_name === column) {
                        schema = this.schema[i]
                    }
                }
                return schema
            },
            dropColumn(column) {
                let $this = this
                let sql = 'DROP COLUMN ' + column
                setTimeout(function () {
                    if (! $this.isColumnMarkedForDeletion(column)) {
                        Vue.set($this.schema_deletes, column, sql)
                    } else if ($this.isNewColumn(column)) {
                        Vue.delete(this.schema_creates, column)
                    }
                }, 50)
            },
            generateFieldsets(schema) {
                let fields = []
                let fieldset = []
                let colCount = this.schema.length
                let without_timezone_string = 'without time zone'
                let with_timezone_string = 'with time zone'
                let schema_config = _.clone(schema || this.schema)
                let defaultValueTypeDelimiterPosition = null
                this.fieldsets = []
                for (let i = 0; i < colCount; i++) {
                    if (schema_config[i]) {
                        if (schema_config[i].hasOwnProperty('default_value') && schema_config[i].default_value) {
                            // nextval('grvsteplevel_id_seq'::regclass)
                            // '{\"key\": \"value\"}'::json
                            if (schema_config[i].default_value.startsWith("'")) {
                                defaultValueTypeDelimiterPosition = schema_config[i].default_value.indexOf('::')
                                if (defaultValueTypeDelimiterPosition > -1) {
                                    schema_config[i].default_value = schema_config[i].default_value.substring(0, defaultValueTypeDelimiterPosition)
                                }
                                if (schema_config[i].default_value.endsWith("'")) {
                                    schema_config[i].default_value = schema_config[i].default_value.replace(/^'|'$/g, '')
                                }
                            }
                        }
                        this.newFieldset(schema_config[i].column_name, schema_config[i])
                    }
                }
                return this.fieldsets
            },
            fillColumnConfigFromSchema(column, config) {
                let schema = this.columnSchema(column)
                if (! config.hasOwnProperty('type') && schema.hasOwnProperty('type')) {
                    config.type = schema.type
                    if (schema.hasOwnProperty('length') && schema['length']) {
                        config.type += '(' + schema['length'] + ')'
                    }
                }
                if (! config.hasOwnProperty('default_value') && schema.hasOwnProperty('default_value')) {
                    config.default_value = schema.default_value
                }
                if (! config.hasOwnProperty('not_null') && schema.hasOwnProperty('nullable')) {
                    config.not_null = schema.nullable === "NO"
                }
                return config
            },
            isColumnMarkedForDeletion(column) {
                return this.schema_deletes.hasOwnProperty(column)
            },
            isNewColumn(column) {
                return this.schema_creates.hasOwnProperty(column)
            },
            makeDefaultValueFragment(column, config, newField) {
                let nullable = true
                let fragment = ''
                let schema = this.columnSchema(column)
                if (config.hasOwnProperty('not_null') && config.not_null) {
                    nullable = false
                }
                if (config.hasOwnProperty('default_value') && config.default_value) {
                    if (! schema || ! schema.hasOwnProperty('default_value') || config.default_value !== schema.default_value) {
                        if (this.isStringDataType(config.type)) {
                            fragment = "DEFAULT $structTabDollarQuote$" + config.default_value + "$structTabDollarQuote$"
                        } else {
                            fragment = 'DEFAULT ' + config.default_value
                        }
                    }
                } else if (! nullable) {
                    fragment = 'DEFAULT \'\''
                } else if (! newField) {
                    fragment = 'DROP DEFAULT'
                }
                return fragment
            },
            makeAddColumn(column, config) {
                let fragment = 'ADD COLUMN "' + config.column_name + '" ' + config.type
                let default_fragment = this.makeDefaultValueFragment(column, config, true)
                if (config.hasOwnProperty('not_null') && config.not_null) {
                    fragment += ' NOT NULL'
                }
                if (default_fragment) {
                    fragment += ' ' + default_fragment
                }
                return fragment
            },
            makeAlterColumn(column, config) {
                let fragment = ''
                let fragments = []
                let schema = this.columnSchema(column)
                let default_fragment = null
                if (! schema) {
                    alert('Cannot alter column ' + column + ', schema not found')
                    return fragments
                }
                let not_null = schema.nullable === false || schema.nullable === "NO"
                let alterMaker = function (statement) {
                    if (statement) return 'ALTER COLUMN "' + column + '" ' + statement
                }
                // type
                if (schema.type !== config.type) {
                    fragments.push(alterMaker('TYPE ' + config.type))
                }
                // default value
                if (config.hasOwnProperty('default_value') || schema.default_value !== null) {
                    fragment = this.makeDefaultValueFragment(column, config)
                    if (fragment) fragments.push(alterMaker('SET ' + fragment))
                }
                // nullable
                if (! config.hasOwnProperty('not_null') || not_null !== config.not_null) {
                    fragment = ''
                    if (config.hasOwnProperty('not_null') && config.not_null) {
                        fragment = 'SET NOT NULL'
                        if (! config.hasOwnProperty('default_value') || config.default_value === null) {
                            config.default_value = ''
                        }
                    } else {
                        fragment = 'DROP NOT NULL'
                    }
                    if (fragment) fragments.push(alterMaker(fragment))
                }
                // rename column
                if (config.hasOwnProperty('column_name') && config.column_name.length > 0) {
                    if (column !== config.column_name) {
                        fragments.push('RENAME COLUMN ' + column + ' TO ' + config.column_name)
                    }
                }
                return fragments
            },
            newFieldset(column, schema) {
                let fieldset = {
                    name: column,
                    fields: []
                }
                let typeValue = null
                if (schema) {
                    typeValue = schema.type
                        .replace('without time zone', '')
                        .replace('with time zone', '')
                        .trim()
                }

                // Name
                fieldset.fields.push({
                    component: 'field',
                    name: 'name[]',
                    label: 'Name',
                    rules: 'required',
                    control: 'input',
                    placeholder: 'required',
                    value: column
                })

                // Type
                fieldset.fields.push({
                    component: 'database-column-field',
                    name: 'type[]',
                    label: 'Type',
                    rules: 'required',
                    control: 'select',
                    value: typeValue,
                    length: (schema ? schema.length : undefined),
                    groupSize: 'form-group-sm'
                })

                // Default
                fieldset.fields.push({
                    component: 'field',
                    name: 'default[]',
                    label: 'Default',
                    control: 'input',
                    placeholder: (schema ? (schema.nullable === 'YES' ? 'NULL' : '') : ''),
                    value: (schema ? schema.default_value : undefined),
                    groupSize: 'form-group-sm'
                })

                // Nullable
                fieldset.fields.push({
                    component: 'field',
                    name: 'nullable[]',
                    label: 'Nullable',
                    control: 'input',
                    type: 'radio',
                    options: [{
                        value: 'YES',
                        text: 'Yes'
                    }, {
                        value: 'NO',
                        text: 'No'
                    }],
                    value: (schema ? schema.nullable : 'YES'),
                    groupSize: 'form-group-sm'
                })

                // Unique
//                fieldset.fields.push({
//                    component: 'field',
//                    name: 'unique[]',
//                    label: 'Unique',
//                    control: 'input',
//                    type: 'checkbox',
//                    options: [{
//                        value: 'YES',
//                        text: 'Yes'
//                    }, {
//                        value: 'NO',
//                        text: 'No'
//                    }],
//                    value: (schema ? schema.nullable : undefined),
//                    groupSize: 'form-group-sm'
//                })


                // Primary key


                if (! schema) {
                    fieldset.is_new = true
                }
                Vue.set(this.fieldsets, this.fieldsets.length, fieldset)
            },
            onInput(e) {
                let column = e.column
                let alter = e.data
                if (this.columnExists(column)) {
                    if (this.schema_changes.hasOwnProperty(column)) {
                        Object.assign(this.schema_changes[column], alter)
                    } else {
                        alter = this.fillColumnConfigFromSchema(column, alter)
                        Vue.set(this.schema_changes, column, alter)
                    }
                    this.schema_changes = { ...this.schema_changes }
                } else {
                    if (this.schema_creates.hasOwnProperty(column)) {
                        Object.assign(this.schema_creates[column], alter)
                    } else {
                        Vue.set(this.schema_creates, column, alter)
                    }
                    this.schema_creates = { ...this.schema_creates }
                }
            },
            onSubmit() {
                let sql = []
                let addFragments = []
                let dropFragments = []
                let alterFragments = []
                let tempAlterFragments = []
                let tempAlterFragmentsLength = 0

                // remove updates if column is to be dropped
                if (Object.keys(this.schema_deletes).length) {
                    for (let column in this.schema_deletes) {
                        if (this.schema_deletes.hasOwnProperty(column)) {
                            if (this.schema_changes.hasOwnProperty(column)) {
                                Vue.delete(this.schema_changes, column)
                            } else {
                                dropFragments.push(this.schema_deletes[column])
                            }
                        }
                    }
                    if (dropFragments.length) {
                        sql.push('ALTER TABLE ' + this.table + ' ' + dropFragments.join(', '))
                    }
                }

                // parse updates into SQL ALTER fragments
                if (Object.keys(this.schema_changes).length) {
                    for (let column in this.schema_changes) {
                        if (this.schema_changes.hasOwnProperty(column)) {
                            tempAlterFragments = this.makeAlterColumn(column, this.schema_changes[column])
                            tempAlterFragmentsLength = tempAlterFragments.length
                            if (tempAlterFragmentsLength) {
                                for (let i = 0; i < tempAlterFragmentsLength; i++) {
                                    if (tempAlterFragments[i]) {
                                        if (tempAlterFragments[i].startsWith('ALTER')) {
                                            alterFragments.push(tempAlterFragments[i])
                                        } else if (tempAlterFragments[i].startsWith('RENAME')) {
                                            sql.push('ALTER TABLE ' + this.table + ' ' + tempAlterFragments[i])
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if (alterFragments.length) {
                        sql.push('ALTER TABLE ' + this.table + ' ' + alterFragments.join(', '))
                    }
                }

                // parse creates into SQL ADD fragments
                if (Object.keys(this.schema_creates).length) {
                    for (let column in this.schema_creates) {
                        if (this.schema_creates.hasOwnProperty(column)) {
                            sql.push('ALTER TABLE ' + this.table + ' ' + this.makeAddColumn(column, this.schema_creates[column]))
                        }
                    }
                }

                return this.executeQuery(sql).then(() => {
                    this.schema_changes = []
                    this.schema_deletes = {}
                    this.schema_creates = []
                    this.sql = ''
                    this.$emit('refresh')
                })
            },
            onSubmitNewColumn() {
                let column = _.clone(this.newColumnName)
                if (this.isNewColumn(column)) {
                    alert('That column is already pending creation')
                    return false
                }
                if (this.isColumnMarkedForDeletion(column)) {
                    alert('That column exists and is marked for deletion')
                    return false
                }
                if (this.columnExists(column)) {
                    alert('That column exists')
                    return false
                }

                if (this.addFieldset(column)) {
                    this.showModal = false
                    this.newColumnName = ''

                    if (! this.schema_creates.hasOwnProperty(column)) {
                        Vue.set(this.schema_creates, column, { column_name: column })
                    }
                    this.schema_creates = { ...this.schema_creates }

                    setTimeout(function() {
                        if ($("#field_" + column).length) {
                            let $parentDiv = $('.tab-pane.active .tab-pane-content')
                            let $innerItem = $("#field_" + column)
                            $parentDiv.scrollTop($parentDiv.scrollTop() + $innerItem.position().top)
                        }
                    }, 50)
                }
            },
            keyIcon(column) {
                let icon = ''
                if (this.schema) {
                    if (column === this.primaryKey) {
                        icon = '<span class="glyphicon glyphicon-star" aria-hidden="true"></span>'
                    } else if (_.find(this.foreignKeys, ['column_name', column])) {
                        icon = '<span class="glyphicon glyphicon-star-empty" aria-hidden="true"></span>'
                    }
                }
                return icon
            }
        }
    }
</script>
<style>
    .btn:focus {
        outline: none;
    }
    .row.field {
        border-bottom: 1px solid #ccc;
        padding: 15px 0 5px 0;
    }
    .row.field:last-of-type {
        margin-bottom: 15px;
    }
</style>