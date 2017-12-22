<template>
    <div v-if="!editing" class="tab-pane-content">
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
                    :label="util.titleCase(name)"
            >
                <template slot-scope="scope">
                    {{ scope.row[name] }} <span v-if="name === 'column_name'" v-html="keyIcon(scope.row[name])"></span>
                </template>
            </el-table-column>
        </el-table>
		<indices-table
				:table="table"
				:table-foreign-keys="foreignKeys"
				@refresh="$emit('refresh', $event)"
		/>
    </div>
	<table v-else class="layout">
        <thead>
			<div class="content top flush-bottom">
				<div class="row">
					<div class="col-md-12">&nbsp;</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<field
							layout="horizontal"
							:label="'Table'"
							:name="'table_name'"
							:control="'el-input'"
							:id="'newTableName'"
							:placeholder="'required'"
							:autofocus="true"
							:value="newTableName"
							@input="e => { newTableName = e.target.value }"
						/>
					</div>
					<div class="col-md-6">&nbsp;</div>
				</div>
				<div class="row">
					<div class="col-md-12">&nbsp;</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<ul class="nav nav-tabs" id="primaryTabContainer">
			                <li
			                        class="nav-tab-item"
			                        v-for="(tab, key) in tabs"
			                        :key="tab.id"
			                        :class="{ active: getTabIndex('id', tab.id) === activeTabIndex() }"
			                >
			                    <a class="nav-tab-item-a" :href="'#tab-' + tab.id" @click.prevent="changeTab(tab.id)" :data-key="tab.id">
			                        {{ tab.title }}
			                    </a>
			                </li>
			            </ul>
					</div>
				</div>
			</div>
		</thead>
		<tbody>
			<!--
			column_fieldsets: [],
			key_fieldsets: [],
			index_fieldsets: [],
			foreign_key_fieldsets: [],


			'database-column-field': require('../forms/fields/DatabaseColumn'),
            'database-table-key-field': require('../forms/fields/DatabaseTableKey'),
            'database-table-index-field': require('../forms/fields/DatabaseTableIndex'),
            'database-table-foreign-key-field': require('../forms/fields/DatabaseTableForeignKey')
			-->
			<div id="columns_tab" class="tab-pane" :class="{ active: getTabIndex('id', 'columns_tab') === showTab }">
				<app-form layout="horizontal" :hide-submit="true">
					<database-column-field
	                        v-for="(fieldset, key) in column_fieldsets"
	                        layout="horizontal"
	                        :key="fieldset.name"
	                        :is_new="fieldset.is_new"
	                        :column_name="fieldset.name"
	                        :formfields="fieldset.fields"
	                        :schema_deletes="schema_deletes"
	                        :schema_changes="schema_changes"
	                        @dropColumn="dropColumn"
	                        @cancelDropColumn="cancelDropColumn"
	                        @cancelNewColumn="cancelNewColumn"
	                        @input="onInputColumns"
	                />
				</app-form>
			</div>
			<div id="keys_tab" class="tab-pane" :class="{ active: getTabIndex('id', 'keys_tab') === showTab }">
				<app-form layout="horizontal" :hide-submit="true">
					<database-table-key-field
	                        v-for="(fieldset, key) in key_fieldsets"
	                        layout="horizontal"
	                        :key="fieldset.name"
	                        :is_new="fieldset.is_new"
	                        :name="fieldset.name"
	                        :formfields="fieldset.fields"
	                        :key_deletes="key_deletes"
	                        @dropKey="dropKey"
	                        @cancelDropKey="cancelDropKey"
	                        @cancelNewKey="cancelNewKey"
	                        @input="onInputKeys"
	                />
				</app-form>
			</div>
			<div id="indices_tab" class="tab-pane" :class="{ active: getTabIndex('id', 'indices_tab') === showTab }">
				<app-form layout="horizontal" :hide-submit="true">
					<database-table-index-field
	                        v-for="(fieldset, key) in index_fieldsets"
	                        layout="horizontal"
	                        :key="fieldset.name"
	                        :is_new="fieldset.is_new"
	                        :name="fieldset.name"
	                        :formfields="fieldset.fields"
	                        :index_deletes="index_deletes"
	                        @dropIndex="dropIndex"
	                        @cancelDropIndex="cancelDropIndex"
	                        @cancelNewIndex="cancelNewIndex"
	                        @input="onInputIndices"
	                />
				</app-form>
			</div>
			<div id="foreign_keys_tab" class="tab-pane" :class="{ active: getTabIndex('id', 'foreign_keys_tab') === showTab }">
				<app-form layout="horizontal" :hide-submit="true">
					<database-table-foreign-key-field
	                        v-for="(fieldset, key) in foreign_key_fieldsets"
	                        layout="horizontal"
	                        :key="fieldset.name"
	                        :is_new="fieldset.is_new"
	                        :name="fieldset.name"
	                        :formfields="fieldset.fields"
	                        :foreign_key_deletes="foreign_key_deletes"
	                        @dropForeignKey="dropForeignKey"
	                        @cancelDropForeignKey="cancelDropForeignKey"
	                        @cancelNewForeignKey="cancelNewForeignKeyy"
	                        @input="onInputForeignKeys"
	                />
				</app-form>
			</div>
		</tbody>
		<tfoot>
			<div class="content bottom">
				<div class="row">
					<div class="col-md-4">
						<v-button @click.prevent="showModal = true" icon="plus" text="Column" size="sm"></v-button>
					</div>
					<div class="col-md-4">
						&nbsp;
					</div>
					<div class="col-md-4 text-right">
			            <v-button @click.prevent="onSubmit" type="primary" text="Submit" size="sm"></v-button>
			            <v-button @click.prevent="cancelEditStructure" text="Cancel" size="sm"></v-button>
					</div>
				</div>
			</div>

			<!-- Add New Column modal -->
			<modal v-if="showModal" @close="showModal = false">
	            <h3 slot="header">Add New Column</h3>
	            <template slot="body">
					<el-alert
						v-show="newColumnError"
						:title="newColumnError"
						type="error"
						show-icon
						@close="newColumnError = ''"
					>
					</el-alert>
	                <app-form :hide-submit="true">
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
	                </app-form>
	            </template>
	            <template slot="footer">
	                <v-button @click.prevent="onSubmitNewColumn" type="primary" text="Submit"></v-button>
	                <v-button @click.prevent="showModal = false" text="Cancel"></v-button>
	            </template>
	        </modal>
			<!-- EOF modal -->
		</tfoot>
	</table>
</template>

<script>
    export default {
        props: [ 'table', 'schema', 'primaryKey', 'foreignKeys', 'indexes', 'processing', 'editMode', 'new' ],
        mixins: [require('../../mixins/PostgresMixin.vue')],
        components: {
            'field': require('../forms/fields/Field'),
            'indices-table': require('./IndicesTable'),
            'database-column-field': require('../forms/fields/DatabaseColumn'),
            'database-table-key-field': require('../forms/fields/DatabaseTableKey'),
            'database-table-index-field': require('../forms/fields/DatabaseTableIndex'),
            'database-table-foreign-key-field': require('../forms/fields/DatabaseTableForeignKey')
        },
        data() {
            return {
                bus: window.bus,
                store: window.store,
                state: window.store.state,
                util: window.util,
                editing: false,
                column_fieldsets: [],
				foreign_key_fieldsets: [],
				index_fieldsets: [],
				key_fieldsets: [],
				foreign_key_deletes: [],
				foreign_key_creates: [],
				index_deletes: [],
				index_creates: [],
				key_deletes: [],
				key_creates: [],
                newColumnName: '',
				newColumnError: '',
                newTableName: this.table,
                schema_changes: [],
                schema_deletes: {},
                schema_creates: [],
                showModal: false,
				showTab: 0,
				tabs: [
					{
	                    id: "columns_tab",
	                    type: "columns",
	                    title: "Columns"
	                }, {
						id: "keys_tab",
						type: "keys",
						title: "Keys"
					}, {
	                    id: "indices_tab",
	                    type: "indices",
	                    title: "Indices"
	                }, {
	                    id: "foreign_keys_tab",
	                    type: "foreign_keys",
	                    title: "Foreign Keys"
	                }
				]
            }
        },
        created() {
            let $this = this
            this.bus.$on('tabRefreshed', function (config) {
                $this.generateColumnFieldsets(config.schema)
                $this.editing = false
            })
        },
        mounted() {
//            this.generateFieldsets()
			if (this.editMode) {
				this.editing = true
			}
        },
        methods: {
            editStructure() {
				if (this.schema) {
					this.generateColumnFieldsets()
				}
                this.editing = true
            },
            cancelDropColumn(column) {
                if (this.isColumnMarkedForDeletion(column)) {
                    Vue.delete(this.schema_deletes, column)
                }
            },
			cancelDropIndex(name) {
				if (this.isIndexMarkedForDeletion(name)) {
					Vue.delete(this.index_deletes, name)
				}
			},
			cancelDropForeignKey(name) {
				if (this.isForeignKeyMarkedForDeletion(name)) {
					Vue.delete(this.foreign_key_deletes, name)
				}
			},
			cancelDropKey(name) {
				if (this.isKeyMarkedForDeletion(name)) {
					Vue.delete(this.key_deletes, name)
				}
			},
            cancelEditStructure() {
                this.editing = false
            },
            cancelNewColumn(column) {
                Vue.delete(this.schema_creates, column)
                Vue.delete(this.column_fieldsets, _.findIndex(this.column_fieldsets, { is_new: true, name: column }))
            },
			cancelNewIndex(name) {
				Vue.delete(this.index_creates, name)
                Vue.delete(this.index_fieldsets, _.findIndex(this.index_fieldsets, { is_new: true, name: name }))
			},
			cancelNewForeignKey(name) {
				Vue.delete(this.foreign_key_creates, name)
                Vue.delete(this.foreign_key_fieldsets, _.findIndex(this.foreign_key_fieldsets, { is_new: true, name: name }))
			},
			cancelNewKey(name) {
				Vue.delete(this.key_creates, name)
                Vue.delete(this.key_fieldsets, _.findIndex(this.key_fieldsets, { is_new: true, name: name }))
			},
            columnExists(column) {
                let exists = false
				let schema_length = 0
				if (this.schema) {
					schema_length = this.schema.length
	                for (let i = 0; i < schema_length; i++) {
	                    if (this.schema[i].column_name === column) {
	                        exists = true
	                    }
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
            dropColumn(name) {
                let $this = this
                let sql = 'DROP COLUMN ' + name
                setTimeout(function () {
                    if (! $this.isColumnMarkedForDeletion(name)) {
                        Vue.set($this.schema_deletes, name, sql)
                    } else if ($this.isNewColumn(name)) {
                        Vue.delete(this.schema_creates, name)
                    }
                }, 50)
            },
			dropForeignKey(name) {
				let $this = this
                let sql = 'DROP CONSTRAINT ' + name
                setTimeout(function () {
                    if (! $this.isForeignKeyMarkedForDeletion(name)) {
                        Vue.set($this.foreign_key_deletes, name, sql)
                    } else if ($this.isNewForeignKey(name)) {
                        Vue.delete(this.foreign_key_creates, name)
                    }
                }, 50)
			},
			dropIndex(name) {
				let $this = this
                let sql = 'DROP INDEX ' + name
                setTimeout(function () {
                    if (! $this.isIndexMarkedForDeletion(name)) {
                        Vue.set($this.index_deletes, name, sql)
                    } else if ($this.isNewIndex(name)) {
                        Vue.delete(this.index_creates, name)
                    }
                }, 50)
			},
			dropKey(name) {
				let $this = this
                let sql = 'DROP CONSTRAINT ' + name
                setTimeout(function () {
                    if (! $this.isKeyMarkedForDeletion(name)) {
                        Vue.set($this.key_deletes, name, sql)
                    } else if ($this.isNewKey(name)) {
                        Vue.delete(this.key_creates, name)
                    }
                }, 50)
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
            generateColumnFieldsets(schema) {
                let fields = []
                let fieldset = []
                let colCount = this.schema.length
                let without_timezone_string = 'without time zone'
                let with_timezone_string = 'with time zone'
                let schema_config = _.clone(schema || this.schema)
                let defaultValueTypeDelimiterPosition = null
                this.column_fieldsets = []
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
                        this.newColumnFieldset(schema_config[i].column_name, schema_config[i])
                    }
                }
                return this.column_fieldsets
            },
			generateForeignKeyFieldsets(config) {
				let fields = []
                let fieldset = []
                let keys_config = _.clone(config || this.foreignKeys)
                let count = keys_config.length
				this.foreign_key_fieldsets = []
				for (let i = 0; i < count; i++) {
					this.newForeignKeyFieldset({
						name: keys_config[i].constraint_name,
						column: keys_config[i].column_name,
						foreign_table: keys_config[i].foreign_table_name,
						foreign_column: keys_config[i].foreign_column_name,
						update_rule: null,
						delete_rule: null
					})
				}

				return this.foreign_key_fieldsets
			},
			generateIndicesFieldsets(config) {
				let fields = []
                let fieldset = []
                let indices_config = _.clone(config || this.indexes)
                let count = indices_config.length
				this.index_fieldsets = []
				for (let i = 0; i < count; i++) {
					this.newIndexFieldset({
						name: indices_config[i].indexname,
						definition: indices_config[i].indexdef,
						columns: null,
						unique: indices_config[i].indexdef.includes('UNIQUE')
					})
				}

				return this.index_fieldsets
			},
			generateKeyFieldsets(keyField) {
				let fields = []
                let fieldset = []
                let colCount = (this.foreignKeys ? this.foreignKeys.length : 0)
                let key_field = _.clone(keyField || this.primaryKey)
				this.key_fieldsets = []
				if (this.primaryKey) {
					this.newKeyFieldset({
						name: this.table + '_pkey',
						columns: [ key_field ]
					})
				}
				return this.key_fieldsets
			},
			indexConfig(name) {
				return _.find(this.indexes, [ 'indexname', name ])
			},
			indexExists(name) {
				let config = this.indexConfig(name)
				return (typeof config !== "undefined")
			},
            isColumnMarkedForDeletion(column) {
                return this.schema_deletes.hasOwnProperty(column)
            },
			isForeignKeyMarkedForDeletion(name) {
                return this.foreign_key_deletes.hasOwnProperty(name)
            },
			isIndexMarkedForDeletion(name) {
                return this.index_deletes.hasOwnProperty(name)
            },
            isKeyMarkedForDeletion(key_name) {
                return this.key_deletes.hasOwnProperty(key_name)
            },
            isNewColumn(column) {
                return this.schema_creates.hasOwnProperty(column)
            },
			isNewForeignKey(name) {
                return this.foreign_key_creates.hasOwnProperty(column)
            },
			isNewIndex(name) {
                return this.index_creates.hasOwnProperty(column)
            },
            isNewKey(key_name) {
                return this.key_creates.hasOwnProperty(column)
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
            newColumnFieldset(column, schema) {
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

                // Nullable constraint
                fieldset.fields.push({
                    component: 'field',
                    name: 'nullable[]',
                    // label: 'Nullable',
                    control: 'input',
                    type: 'checkbox',
					options: [{
                        value: true,
                        text: 'Nullable'
                    }],
                    value: (schema ? schema.nullable : true),
                    groupSize: 'form-group-sm'
                })

                // Auto inc. constraint
                fieldset.fields.push({
                    component: 'field',
                    name: 'auto_inc[]',
                    // label: 'Auto inc',
                    control: 'input',
                    type: 'checkbox',
                    options: [{
                        value: true,
                        text: 'Auto increment'
                    }],
                    value: (schema ? schema.auto_inc : false),
                    groupSize: 'form-group-sm'
                })

                // Unique constraint (column)
                fieldset.fields.push({
                   component: 'field',
                   name: 'unique[]',
                //    label: 'Unique',
                   control: 'input',
                   type: 'checkbox',
                   options: [{
                       value: true,
                       text: 'Unique'
                   }],
                   value: (schema ? schema.unique : false),
                   groupSize: 'form-group-sm'
            	})

            	// Primary key
                fieldset.fields.push({
                   component: 'field',
                   name: 'primary_key[]',
                //    label: 'Primary key',
                   control: 'input',
                   type: 'checkbox',
                   options: [{
                       value: true,
                       text: 'Primary key'
                   }],
                   value: (this.primary_key === column),
                   groupSize: 'form-group-sm'
            	})

                if (! schema) {
                    fieldset.is_new = true
                }
                Vue.set(this.column_fieldsets, this.column_fieldsets.length, fieldset)
            },
            newForeignKeyFieldset(config) {
                let fieldset = {
                    name: (config ? config.name : 'newKey'),
                    fields: []
                }

				// Name
                fieldset.fields.push({
                    component: 'field',
                    name: 'name[]',
                    label: 'Name',
                    rules: 'required',
                    control: 'input',
                    placeholder: 'required',
                    value: (config ? config.name : undefined)
                })

				// Foreign table
                fieldset.fields.push({
                    component: 'field',
                    name: 'foreign_table[]',
                    label: 'Foreign table',
                    rules: 'required',
                    control: 'select',
                    options: this.state.tables,
                    value: (config ? config.foreign_table : undefined),
                    groupSize: 'form-group-sm'
                })

				// Foreign column
                fieldset.fields.push({
                    component: 'field',
                    name: 'foreign_column[]',
                    label: 'Foreign column',
                    rules: 'required',
                    control: 'select',
                    options: (config ? this.loadSchema(config.foreign_table) : undefined),
                    value: (config ? config.foreign_column : undefined),
                    groupSize: 'form-group-sm'
                })

                // Update rule
                fieldset.fields.push({
                    component: 'field',
                    name: 'update_rule[]',
                    label: 'Update rule',
                    control: 'select',
                    value: (config ? config.update_rule : undefined),
                    options: this.foreignKeyRules,
                    groupSize: 'form-group-sm'
                })

				// Delete rule
                fieldset.fields.push({
                    component: 'field',
                    name: 'delete_rule[]',
                    label: 'Delete rule',
                    control: 'select',
                    value: (config ? config.delete_rule : undefined),
                    options: this.foreignKeyRules,
                    groupSize: 'form-group-sm'
                })

                if (! config) {
                    fieldset.is_new = true
                }
                Vue.set(this.key_fieldsets, this.key_fieldsets.length, fieldset)
            },
			newIndexFieldset(config) {
				let fieldset = {
                    name: (config ? config.name : 'newKey'),
                    fields: []
                }

				// Name
                fieldset.fields.push({
                    component: 'field',
                    name: 'name[]',
                    label: 'Name',
                    rules: 'required',
                    control: 'input',
                    placeholder: 'required',
                    value: (config ? config.name : undefined)
                })

                // Columns
                fieldset.fields.push({
                    component: 'multi-input-text',
                    name: 'type[]',
                    label: 'Type',
                    rules: 'required',
                    control: 'select',
                    value: (config ? config.columns : undefined),
                    groupSize: 'form-group-sm'
                })

            	// Unique
                fieldset.fields.push({
                	component: 'field',
                	name: 'unique[]',
                	// label: 'Unique',
                	control: 'input',
                	type: 'checkbox',
                	options: [{
                		value: true,
                		text: 'Unique'
                	}],
                	value: (config ? config.unique : undefined),
                	groupSize: 'form-group-sm'
            	})

                if (! config) {
                    fieldset.is_new = true
                }
                Vue.set(this.index_fieldsets, this.index_fieldsets.length, fieldset)
			},
            newKeyFieldset(config) {
                let fieldset = {
                    name: (config ? config.name : 'newKey'),
                    fields: []
                }

				// Name
                fieldset.fields.push({
                    component: 'field',
                    name: 'name[]',
                    label: 'Name',
                    rules: 'required',
                    control: 'input',
                    placeholder: 'required',
                    value: (config ? config.name : undefined)
                })

                // Columns
                fieldset.fields.push({
                    component: 'multi-input-text',
                    name: 'type[]',
                    label: 'Type',
                    rules: 'required',
                    control: 'select',
                    value: (config ? config.columns : undefined),
                    groupSize: 'form-group-sm'
                })

            	// Primary key
                fieldset.fields.push({
                	component: 'field',
                	name: 'primary_key[]',
                	// label: 'Primary key',
                	control: 'input',
                	type: 'checkbox',
                	options: [{
                		value: true,
                		text: 'Primary key'
                	}],
                	value: (this.primary_key === column),
                	groupSize: 'form-group-sm'
            	})

                if (! config) {
                    fieldset.is_new = true
                }
                Vue.set(this.key_fieldsets, this.key_fieldsets.length, fieldset)
            },
            onInputColumns(config) {
                let column = config.column
                let alter = config.data
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
			onInputIndices(config) {
				let name = config.name
				let column = null
                let columns = config.columns
				let unique = config.unique
				// let column_count = config.columns.length
				// if (column_count > 0) {
				// 	for (let i = 0; i < column_count; i++) {
				// 		column = columns[i]
				// 		if (! this.columnExists(column) && ! this.schema_creates.hasOwnProperty(column)) {
				//
				// 		}
				// 	}
				// }

				// this.isNewIndex(name)


				////////
                // if (this.columnExists(column)) {
                //     if (this.schema_changes.hasOwnProperty(column)) {
                //         Object.assign(this.schema_changes[column], alter)
                //     } else {
                //         alter = this.fillColumnConfigFromSchema(column, alter)
                //         Vue.set(this.schema_changes, column, alter)
                //     }
                //     this.schema_changes = { ...this.schema_changes }
                // } else {
                //     if (this.schema_creates.hasOwnProperty(column)) {
                //         Object.assign(this.schema_creates[column], alter)
                //     } else {
                //         Vue.set(this.schema_creates, column, alter)
                //     }
                //     this.schema_creates = { ...this.schema_creates }
                // }
			},
			onInputForeignKeys(config) {

			},
			onInputKeys(config) {

			},
			generateSql() {
				let sql = []
				let temp_sql = []
				let temp_sql_str = ''

				/*
ALTER TABLE public.accidents DROP number_of_fatalities;

ALTER TABLE public.accidents RENAME TO accidentes;

ALTER TABLE public.accidentes ADD column_13 VARCHAR(128) DEFAULT "red" NOT NULL;

CREATE UNIQUE INDEX accidents_id_uindex ON public.accidentes (id);

ALTER TABLE public.accidentes ADD column_12 INT DEFAULT 45 NOT NULL;

CREATE UNIQUE INDEX accidentes_column_12_uindex ON public.accidentes (column_12);

ALTER TABLE public.accidentes
	ADD CONSTRAINT accidents_documents__fk
	FOREIGN KEY () REFERENCES documents;

ALTER TABLE public.accidentes
	ADD CONSTRAINT accidents_id_pk UNIQUE (id);

ALTER TABLE public.accidentes ALTER COLUMN description TYPE VARCHAR(254) USING description::VARCHAR(254);
ALTER TABLE public.accidentes ALTER COLUMN deleted_at SET DEFAULT null;
ALTER TABLE public.accidentes ALTER COLUMN deleted_at SET NOT NULL;





ALTER TABLE public.accidents DROP number_of_injuries;

ALTER TABLE public.accidents RENAME TO accidentes;

ALTER TABLE public.accidentes ADD column_13 VARCHAR(255) DEFAULT NULL NULL;

CREATE UNIQUE INDEX accidentes_column_13_uindex ON public.accidentes (column_13);

ALTER TABLE public.accidentes
	ADD CONSTRAINT accidentes_applications_id_fk
	FOREIGN KEY (number_of_fatalities) REFERENCES applications (id);

ALTER TABLE public.accidentes ADD column_14 INT DEFAULT 1 NOT NULL;

ALTER TABLE public.accidentes
	ADD CONSTRAINT accidentes_column_13_pk UNIQUE (column_13);

ALTER TABLE public.accidentes ALTER COLUMN description TYPE VARCHAR(128) USING description::VARCHAR(128);
				*/

				if (temp_sql_str = this.parseSchemaDeletes()) {
					sql.push(temp_sql_str)
				}

				if (temp_sql = this.parseSchemaCreates()) {
					sql = sql.concat(temp_sql)
				}

				if (temp_sql = this.parseKeyDeletes()) {
					sql = sql.concat(temp_sql)
				}

				if (temp_sql = this.parseKeyCreates()) {
					sql = sql.concat(temp_sql)
				}

				if (temp_sql = this.parseSchemaChanges()) {
					sql = sql.concat(temp_sql)
				}

				if (this.newTableName !== this.table) {
					sql.push('ALTER TABLE ' + this.table + ' RENAME TO ' + this.newTableName)
				}

				return sql
			},
			parseForeignKeyCreates() {
				// ALTER TABLE tests ADD CONSTRAINT fk_tests_students FOREIGN KEY (highestStudent) REFERENCES students (student_id);
				let sql = []
				let foreign_key_creates_length = this.foreign_key_creates.length
				if (foreign_key_creates_length) {

				}
				return sql
			},
			parseForeignKeyDeletes() {
				// ALTER TABLE "table" DROP CONSTRAINT "accountid_fk";
				let sql = []
				if (Object.keys(this.foreign_key_deletes).length) {
					for (let key in this.foreign_key_deletes) {
                        if (this.foreign_key_deletes.hasOwnProperty(key)) {
                            sql.push('ALTER TABLE ' + this.table + ' ' + this.foreign_key_deletes[key])
                        }
                    }
				}
				return sql
			},
			parseIndexDeletes() {
				// DROP INDEX title_idx;
				let sql = []
				if (Object.keys(this.index_deletes).length) {
					for (let key in this.index_deletes) {
                        if (this.index_deletes.hasOwnProperty(key)) {
                            sql.push(this.index_deletes[key])
                        }
                    }
				}
				return sql
			},
			parseIndexCreates() {
				// CREATE INDEX CONCURRENTLY idx_salary ON employees(last_name, salary);
				let sql = []
				let index_creates_length = this.index_creates.length
				if (index_creates_length) {

				}
				return sql
			},
			parseKeyDeletes() {
				// ALTER TABLE employee DROP CONSTRAINT employee_pkey
				let sql = []
				if (Object.keys(this.key_deletes).length) {
					for (let key in this.key_deletes) {
                        if (this.key_deletes.hasOwnProperty(key)) {
                            sql.push('ALTER TABLE ' + this.table + ' ' + this.key_deletes[key])
                        }
                    }
				}
				return sql
			},
			parseKeyCreates() {
				// ALTER TABLE <table_name> ADD PRIMARY KEY (id);
				let sql = []
				let key_creates_length = this.key_creates.length
				if (key_creates_length) {

				}
				return sql
			},
			parseSchemaChanges() {
				let sql = []
                let alterFragments = []
                let tempAlterFragments = []
                let tempAlterFragmentsLength = 0
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
				return sql
			},
			parseSchemaCreates() {
				let sql = []
				// parse creates into SQL ADD fragments
                if (Object.keys(this.schema_creates).length) {
                    for (let column in this.schema_creates) {
                        if (this.schema_creates.hasOwnProperty(column)) {
                            sql.push('ALTER TABLE ' + this.table + ' ' + this.makeAddColumn(column, this.schema_creates[column]))
                        }
                    }
                }
				return sql
			},
			parseSchemaDeletes() {
				let sql = ''
                let dropFragments = []
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
                        sql = 'ALTER TABLE ' + this.table + ' ' + dropFragments.join(', ')
                    }
                }
				return sql
			},
            onSubmit() {
                let sql = []
                let dropFragments = []
                let alterFragments = []
                let tempAlterFragments = []
                let tempAlterFragmentsLength = 0
				let renameTable = ''

				if (this.newTableName !== this.table) {
					renameTable = 'ALTER TABLE ' + this.table + ' RENAME TO ' + this.newTableName
				}

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

				if (sql.length) {
					return this.executeQuery(sql).then(() => {
	                    this.schema_changes = []
	                    this.schema_deletes = {}
	                    this.schema_creates = []
	                    this.sql = ''
						if (renameTable) {
							this.executeQuery(renameTable).then(() => {
								this.$emit('refresh')
							})
						} else {
							this.$emit('refresh')
						}
	                })
				} else if (renameTable) {
					this.executeQuery(renameTable).then(() => {
						this.$emit('refresh')
					})
				}
            },
            onSubmitNewColumn() {
                let column = this.newColumnName
				this.newColumnError = ''
                if (this.isNewColumn(column)) {
					this.newColumnError = 'That column is already pending creation'
                    return false
                }
                if (this.isColumnMarkedForDeletion(column)) {
					this.newColumnError = 'That column exists and is marked for deletion'
                    return false
                }
                if (this.columnExists(column)) {
					this.newColumnError = 'That column exists'
                    return false
                }

                if (this.newColumnFieldset(column)) {
                    this.showModal = false
                    this.newColumnName = ''

                    if (! this.schema_creates.hasOwnProperty(column)) {
                        Vue.set(this.schema_creates, column, { column_name: column })
                    }
                    this.schema_creates = { ...this.schema_creates }

                    setTimeout(function() {
                        if ($("#field_" + column).length) {
							document.getElementById("field_" + column).scrollIntoView()
                        }
                    }, 50)
                }
            },

			activeTab() {
                let index = this.activeTabIndex()
                return this.tabs[index]
            },
            activeTabIndex(newIndex) {
                if (typeof newIndex !== "undefined" && newIndex > -1 || newIndex === null) {
                    this.showTab = newIndex
                    this.$emit('tabChanged', newIndex)
                }
                return this.showTab
            },
			changeTab(index) {
                let $this = this
                let changeToTab = null
                if (isNaN(index)) {
                    index = this.getTabIndex('id', index)
                }
                if (index < 0) {
                    this.activeTabIndex(null)
                } else {
                    changeToTab = $this.getTab('index', index)
                    if (changeToTab) {
                        $('.nav-tabs a[data-id="' + changeToTab.id + '"]').tab('show')
                        $this.activeTabIndex(index)
                    }
                }
            },
			getTab(key, value) {
                if (typeof key === "object" && typeof value === "undefined") {
                    return _.find(this.tabs, key)
                } else {
                    if (key === 'index') {
                        return this.tabs[value]
                    } else if (key.startsWith('table.')) {
                        let tabCount = this.countTabs()
                        for (let i = 0; i < tabCount; i++) {
                            if (_.get(this.tabs[i], key, null) === value) {
                                return this.tabs[i]
                            }
                        }
                        return undefined
                    }
                }
                return _.find(this.tabs, [ key, value ])
            },
            getTabIndex(key, value) {
                return _.findIndex(this.tabs, [ key, value ])
            },

            keyIcon(column) {
                let icon = ''
                if (this.schema) {
                    if (column === this.primaryKey) {
                        icon = '<span class="' + this.util.icon('primary-key') + '" aria-hidden="true"></span>'
                    } else if (_.find(this.foreignKeys, ['column_name', column])) {
                        icon = '<span class="' + this.util.icon('foreign-key') + '" aria-hidden="true"></span>'
                    }
                }
                return icon
            }
        }
    }
</script>
<style>
	legend {
		border: 0;
	}
    .btn:focus {
        outline: none;
    }
    .el-table__body-wrapper {
        overflow: inherit;
    }
	.el-alert {
		margin-top: -15px;
    	margin-bottom: 15px;
	}
	.tab-pane {
		display: none;
	}
	.tab-pane.active {
		display: inherit;
	}
	table.layout {
		display: -webkit-box;
	    display: -ms-flexbox;
	    display: flex;
	    height: 100%;
	    max-height: 100%;
	    -webkit-box-orient: vertical;
	    -webkit-box-direction: normal;
	    -ms-flex-direction: column;
	    flex-direction: column;
	}
</style>
