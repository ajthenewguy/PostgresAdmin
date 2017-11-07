<template>
    <div class="row field" :class="{ 'bg-danger': schema_deletes.hasOwnProperty(column_name), 'bg-success': is_new }" :id="'field_' + column_name">
        <fieldset class="col-md-10" :disabled="schema_deletes.hasOwnProperty(column_name)">
            <component
                    v-for="(field, subkey) in formfields"
                    :is="field.component"
                    :key="subkey"
                    :control="field.control"
                    :multiple="field.multiple"
                    :name="field.name"
                    :label="field.label"
                    :rules="field.rules"
                    :type="field.type"
                    :options="field.options"
                    :length="field.length"
                    :placeholder="field.placeholder"
                    :value="field.value"
                    :group-size="field.groupSize ? field.groupSize : ''"
                    :footer="false"
                    @input="input_value => { onInput(input_value, column_name, field.name ) }"
            />
        </fieldset>
        <div class="col-md-2">
            <v-button v-if="showDropButton()" @click.prevent="dropColumn" text="Drop"></v-button>
            <v-button v-if="showKeepButton()" @click.prevent="cancelDropColumn" type="primary" text="Keep"></v-button>
            <v-button v-if="showCancelButton()" @click.prevent="cancelNewColumn" text="Discard"></v-button>
        </div>
    </div>
</template>
<script>
    export default {
        props: [ 'column_name', 'formfields', 'schema_deletes', 'schema_changes', 'is_new' ],
        mixins: [require('../../../mixins/PostgresMixin.vue')],
        components: {
            'field': require('./Field'),
            'database-column-field': require('./DatabaseColumnType')
        },
        data() {
            return {
                store: window.store,
                state: window.store.state
            }
        },
        methods: {
            showDropButton() {
                return (! this.is_new) && (! this.schema_deletes.hasOwnProperty(this.column_name))
            },
            showKeepButton() {
                return (! this.is_new) && this.schema_deletes.hasOwnProperty(this.column_name)
            },
            showCancelButton() {
                return !! this.is_new
            },
            dropColumn() {
                this.$emit('dropColumn', this.column_name)
            },
            cancelDropColumn() {
                this.$emit('cancelDropColumn', this.column_name)
            },
            cancelNewColumn() {
                this.$emit('cancelNewColumn', this.column_name)
            },
            onInput(e, column, name) {
                let precision = null
                let scale = null
                let value = e
                let alter = {}
                if (e.constructor === InputEvent || e.constructor === Event) {
                    value = e.target.value
                }
                switch (name) {
                    case 'name[]': {
                        alter['column_name'] = value
                        break
                    }
                    case 'type[]': {
                        alter['type'] = value.type
                        if (value.type.startsWith('time')) {
                            if (value.hasOwnProperty('with_timezone') && value.with_timezone) {
                                alter['type'] += ' with time zone'
                            } else {
                                alter['type'] += ' without time zone'
                            }
                        }
                        if (value.hasOwnProperty('length') && value['length']) {
                            alter['type'] += '(' + value.length + ')'
                        }
                        if (value.hasOwnProperty('option')) {
                            alter['type'] += ' ' + value.option
                        }
//                        if (value.hasOwnProperty('precision')) {
//                            precision = value.precision
//                        }
//                        if (value.hasOwnProperty('scale')) {
//                            scale = value.scale
//                        }
                        break
                    }
                    case 'default[]': {
                        alter['default_value'] = value
                        break
                    }
                    case 'nullable[]': {
                        alter['not_null'] = value !== 'YES'
                        break
                    }
                }
                this.$emit('input', { column: this.column_name, data: alter })
            }
        }
    }
</script>