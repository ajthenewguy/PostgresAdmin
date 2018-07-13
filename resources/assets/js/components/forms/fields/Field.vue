<template>
    <div :class="wrapperClass">
        <label
            v-if="label.length > 0 && ((control !== 'input' && control !== 'el-input') || type !== 'checkbox')"
            :class="labelClass"
            :for="input_id"
            v-html="label"
        />
        <div v-if="hasColumn" :class="columnClass">
            <field
                   :control="control"
                   :multiple="multiple"
                   :id="input_id"
                   :name="name"
                   :label="label"
                   :rules="rules"
                   :type="type"
                   :options="options"
                   :placeholder="placeholder"
                   :value="value"
                   :autofocus="autofocus"
                   :disabled="disabled"
                   :class="{'input': (type !== 'checkbox' && type !== 'radio'), 'form-control': (type !== 'checkbox' && type !== 'radio' && ! control.startsWith('el-')), 'is-danger': errors.has(name) }"
                   @input="$emit('input', $event)"
            />
            <span v-show="errors.has(name)" class="help is-danger">{{ errors.first(name) }}</span>
        </div>
        <template v-else>
            <field
                    :control="control"
                    :multiple="multiple"
                    :id="input_id"
                    :name="name"
                    :label="label"
                    :rules="rules"
                    :type="type"
                    :options="options"
                    :placeholder="placeholder"
                    :value="value"
                    :autofocus="autofocus"
                    :disabled="disabled"
                    :class="{'input': (type !== 'checkbox' && type !== 'radio'), 'form-control': (type !== 'checkbox' && type !== 'radio' && ! control.startsWith('el-')), 'is-danger': errors.has(name) }"
                    @input="$emit('input', $event)"
            />
            <span v-show="errors.has(name)" class="help is-danger">{{ errors.first(name) }}</span>
        </template>
        <hr v-if="footer" />
    </div>
</template>
<script>
    export default {
        props: {
            autofocus: {
                type: Boolean,
                default: false
            },
            columnWidth: null,
            control:  {
                type: String,
                default: 'input'
            },
            containerClass: {
                type: String
            },
            disabled: {
                type: Boolean,
                default: false
            },
            footer: {
                type: Boolean,
                default: false
            },
            groupSize: {
                type: String,
                default: ''
            },
            id: {
                type: String
            },
            label: {
                type: String,
                default: ''
            },
            layout: {
                type: String,
                default: ''
            },
            multiple: {
                type: Boolean,
                default: false
            },
            name: {
                type: String,
                required: true
            },
            options: Array,
            placeholder: {
                type: String,
                default: ''
            },
            rules: {
                type: String,
                default: ''
            },
            type: {
                type: String,
                default: 'text'
            },
            value: null
        },
        components: {
            'field': require('../../Field')
        },
        data() {
            return {
                bus: window.bus,
                util: window.util,
                store: window.store,
                state: window.store.state,
                input_id: null,
                input_value: null
            }
        },
        computed: {
            wrapperClass: function () {
                // if inline - add col-md-
                let label_class = 'form-group'
                if (this.groupSize) {
                    label_class += ' ' + this.groupSize
                }
                if (this.columnWidth) {
                    label_class += ' col-sm-' + this.columnWidth
                }
                if (this.containerClass) {
                    label_class += ' ' + this.containerClass
                }
                return label_class
            },
            labelClass: function () {
                return {
                    'col-sm-2 control-label': this.layout === 'horizontal'
                }
            },
            columnClass: function () {
                let column_class = ''
                if (this.layout === 'horizontal') {
                    if (this.type !== 'checkbox' && this.type !== 'radio') {
                        column_class = 'col-sm-10'
                    } else if (this.type === 'checkbox' || this.type === 'radio' || this.type === 'button' || this.type === 'action') {
                        column_class = 'col-sm-offset-2'
                    }
                }
                return column_class
            },
            hasColumn() {
                return this.isHorizontal
            },
            isHorizontal: function () {
                return this.layout === 'horizontal'
            },
            isInline: function () {
                return this.layout === 'inline'
            }
        },
        mounted() {
            this.init()
        },
        watch: {
            errors: {
                handler() {
                    this.$emit('error', this.errors)
                },
                deep: true
            }
        },
        methods: {
            init() {
                this.input_id = this.id || this.util.uuid()
            },
            uuid() {
                return Math.random().toString(36).substring(2) + (new Date()).getTime().toString(36)
            }
        }
    }
</script>