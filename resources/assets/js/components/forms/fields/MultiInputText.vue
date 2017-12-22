<template>
    <div class="row" :class="{ footer: footer }">
		<div class="col-md-2">
			<label class="control-label"
	            v-html="label"
	        />
		</div>
		<div class="col-md-10">
			<div v-for="input in inputs" :key="key">
				<field
		            :id="input.html_id"
		            :name="input.name"
		            :layout="layout"
		            :value="input.value"
					:autofocus="input.id === 0"
		            @input="onInput"
		        />
				<v-button v-if="inputs.length > 1" @click.prevent="onDeleteField(input.id)" size="sm" icon="minus"></v-button>
			</div>
	        <v-button v-if="showAddButton" @click.prevent="onAddField" size="sm" icon="plus"></v-button>
		</div>
    </div>
</template>
<script>
    export default {
        components: {
            'field': require('../../Field') // simple
        },
        mixins: [require('../../../mixins/PostgresMixin.vue')],
        props: {
            control:  {
                type: String,
                default: 'select'
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
                default: 'Type'
            },
            layout: {
                type: String,
                default: ''
            },
            length: {
                type: Number,
                default: null
            },
            name: {
                type: String,
                required: true
            },
            options: {
                type: Array,
                default: function () {
                    return []
                }
            },
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
                default: ''
            },
            unique: {
                type: Boolean,
                default: false
            },
            value: null
        },
        computed: {
            hasLength: function () {
                if (this.dataType) {
                    return this.dataType.hasOwnProperty('size')
                }
                return false
            },
            hasOptions: function () {
                if (this.dataType && this.dataType.name !== 'time' && this.dataType.name !== 'timestamp') {
                    return this.dataType.hasOwnProperty('options')
                }
                return false
            },
            hasPrecision: function () {
                if (this.dataType) {
                    return this.dataType.hasOwnProperty('precision')
                }
                return false
            },
            hasScale: function () {
                if (this.dataType) {
                    return this.dataType.hasOwnProperty('scale')
                }
                return false
            },
            isTime: function () {
                if (typeof this.input_value === "string") {
                    return this.input_value.startsWith('time')
                }
                return false
            },
            showAddButton: function () {
				if (this.length && this.length <= this.input_values.length) {
					return false
				}
				return true
            },
            isHorizontal: function () {
                return this.layout === 'horizontal'
            },
            isInline: function () {
                return this.layout === 'inline'
            }
        },
        data() {
            return {
                bus: window.bus,
                store: window.store,
                state: window.store.state,
				id_pointer: 0,
                inputs: [],
                input_id: null,
                with_timezone: null,
                input_values: []
            }
        },
        mounted() {
            if (this.value !== null) {
                this.input_values = this.value
            }
            this.input_id = this.id || this.uuid()
			this.addField()
        },
        methods: {
			addField(value = "") {
				let name = this.name
				if (name.endsWith('[]')) {
					name = name.substr(0, name.length - 2)
				}
				this.inputs.push({
					id: this.id_pointer,
					name: name,
					html_id: name + '[' + this.id_pointer + ']',
					value: value
				})
				this.id_pointer++
			},
            emitInput() {
                this.$emit('input', this.input_values)
            },
            onAddField() {
                this.addField()
            },
			onDeleteField(id) {
				let input = _.find(this.inputs, [ 'id', id ])
				if (input) {
					_.remove(this.inputs, [ 'id', id ])
				}
			},
            onInput(e) {
				let value = e.target.value
				let validationResult = this.validateInput(value)
				if (validationResult === true) {
					this.input_values.push(e.target.value)
                	this.emitInput()
				} else {
					this.$emit("INPUT_ERROR", validationResult)
				}
            },
			validateInput(value) {
				let input_count = this.input_values.length
				if (this.length && this.length <= input_count) {
					return "OVERFLOW"
				}
				if (this.options && this.options.indexOf(value) < 0) {
					return "ILLEGAL"
				}
				if (this.unique && this.input_values.indexOf(value) > -1) {
					return "DUPLICATE"
				}
				return true
			},
            uuid() {
                return Math.random().toString(36).substring(2) + (new Date()).getTime().toString(36)
            }
        }
    }
</script>
