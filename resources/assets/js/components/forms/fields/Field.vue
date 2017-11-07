<template>
    <!--<div :class="wrapperClass">-->
    <div class="form-group" :class="groupSize">
        <label
                v-if="(control !== 'input' && control !== 'el-input') || type !== 'checkbox'"
                class="col-sm-2 control-label"
                :for="input_id"
                v-html="label"
        />
        <div :class="columnClass">
            <input v-if="(control === 'input' || control === 'el-input') && type === 'text'"
                   v-model="input_value"
                   v-validate="rules"
                   class="form-control"
                   :class="{'input': true, 'is-danger': errors.has(name) }"
                   :id="input_id"
                   :name="name"
                   :type="type"
                   :placeholder="placeholder"
                   @input="input_value => { $emit('input', input_value) }"
            />
            <textarea v-if="control === 'textarea'"
                      v-model="input_value"
                      v-validate="rules"
                      class="form-control"
                      :class="{'input': true, 'is-danger': errors.has(name) }"
                      :id="input_id"
                      :name="name"
                      :placeholder="placeholder"
                      @input="input_value => { $emit('input', input_value) }"
            />

            <template v-if="(control === 'input' || control === 'el-input') && (type === 'checkbox' || type === 'radio')">
                <div v-for="(option, key) in options" :key="key" :class="type">
                    <label :for="name + '-' + key">
                        <input
                                v-model="input_value"
                                :type="type"
                                :id="input_id + '-' + key"
                                :value="option.value"
                                @change="input_value => { $emit('input', input_value) }"
                        /> {{ option.text }}
                    </label>
                </div>
            </template>

            <template v-if="control === 'select'">
                <select
                        v-model="input_value"
                        :id="input_id"
                        :multiple="multiple === true"
                        class="form-control"
                        @input="input_value => { $emit('input', input_value) }"
                >
                    <option v-for="option in options" :key="option.value" :value="option.value">
                        {{ option.text }}
                    </option>
                </select>
            </template>

            <template v-if="control === 'el-select'">
                <el-select
                        v-model="input_value"
                        :id="input_id"
                        :multiple="multiple === true"
                        @input="input_value => { $emit('input', input_value) }"
                >
                    <el-option v-for="option in options" :key="option.value" :label="option.text" :value="option.value" />
                </el-select>
            </template>

            <span v-show="errors.has(name)" class="help is-danger">{{ errors.first(name) }}</span>
        </div>
        <hr v-if="footer" />
    </div>
</template>
<script>
    export default {
        props: {
            control:  {
                type: String,
                default: 'input'
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
                required: true
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
        data() {
            return {
                bus: window.bus,
                store: window.store,
                state: window.store.state,
                input_id: null,
                input_value: null
            }
        },
        computed: {
            wrapperClass: function () {
                return {
                    'form-group': (this.type !== 'checkbox' && this.type !== 'radio'),
                    'checkbox': (this.type === 'checkbox' || this.type === 'radio')
                }
            },
            columnClass: function () {
                return {
                    'col-sm-8': (this.type !== 'checkbox' && this.type !== 'radio'),
                    'col-sm-offset-2': (this.type === 'checkbox' || this.type === 'radio' || this.type === 'button' || this.type === 'action')
                }
            }
        },
        watch: {
            errors: {
                handler() {
                    this.$emit('error', this.errors)
                },
                deep: true
            }
        },
        mounted() {
            this.input_id = this.id || this.uuid()
            this.input_value = this.value
        },
        methods: {
            uuid() {
                return Math.random().toString(36).substring(2) + (new Date()).getTime().toString(36)
            }
        }
    }
</script>