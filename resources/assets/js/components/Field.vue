<template>
    <input v-if="(control === 'input' || control === 'el-input') && (type === 'text' || type === 'password')"
           v-model="input_value"
           v-validate="rules"
           class="form-control"
           :class="{'input': true, 'is-danger': errors.has(name) }"
           :id="input_id"
           :name="name"
           :type="type"
           :placeholder="placeholder"
           :ref="input_id"
           @input="input_value => { $emit('input', input_value) }"
    />
    <textarea v-else-if="control === 'textarea'"
              v-model="input_value"
              v-validate="rules"
              class="form-control"
              :class="{'input': true, 'is-danger': errors.has(name) }"
              :id="input_id"
              :name="name"
              :placeholder="placeholder"
              :ref="input_id"
              @input="input_value => { $emit('input', input_value) }"
    />
    <div v-else-if="(control === 'input' || control === 'el-input') && (type === 'checkbox' || type === 'radio')"
          :class="type">
        <div v-for="(option, key) in options" :key="key">
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

    </div>
    <select v-else-if="control === 'select'"
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
    <el-select v-else-if="control === 'el-select'"
            v-model="input_value"
            :id="input_id"
            :multiple="multiple === true"
            @input="input_value => { $emit('input', input_value) }"
    >
        <el-option v-for="option in options" :key="option.value" :label="option.text" :value="option.value" />
    </el-select>
    <div v-else>
        {{ value }}
    </div>
</template>
<script>
    export default {
        props: {
            autofocus: {
                type: Boolean,
                default: false
            },
            control:  {
                type: String,
                default: 'input'
            },
            footer: {
                type: Boolean,
                default: false
            },
            id: {
                type: String
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
                util: window.util,
                store: window.store,
                state: window.store.state,
                input_id: null,
                input_value: null
            }
        },
        mounted() {
            this.input_id = this.id || this.util.uuid()
            this.input_value = this.value
            if (this.autofocus) {
                this.$nextTick(() => {
                    this.$refs[this.input_id].focus()
                })

            }
        }
    }
</script>