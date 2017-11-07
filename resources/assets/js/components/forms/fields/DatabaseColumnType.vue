<template>
    <div>
        <field
            :id="input_id"
            :name="name"
            :control="control"
            :label="label"
            :options="typeOptions"
            :group-size="groupSize"
            :value="value"
            @input="onSelect"
        />
        <field v-if="isTimestamp || isTime"
               :control="(control.startsWith('el-') ? 'el-input' : 'input')"
               :label="'With time zone'"
               :name="'with_timezone-' + name"
               type="radio"
               :options="timezoneOptions"
               :group-size="groupSize"
               :value="with_timezone"
               @input="onTimezoneInput"
        />
        <field v-if="hasLength"
               :control="(control.startsWith('el-') ? 'el-input' : 'input')"
               :label="'Length'"
               :name="'size-' + name"
               :group-size="groupSize"
               :value="length"
               @input="onSizeInput"
        />
        <!--<field v-if="hasPrecision"-->
               <!--:control="(control.startsWith('el-') ? 'el-input' : 'input')"-->
               <!--:label="'Precision'"-->
               <!--:name="'precision-' + name"-->
               <!--:group-size="groupSize"-->
               <!--:value="precision"-->
               <!--@input="onPrecisionInput"-->
        <!--/>-->
        <!--<field v-if="hasScale"-->
               <!--:control="(control.startsWith('el-') ? 'el-input' : 'input')"-->
               <!--:label="'Scale'"-->
               <!--:name="'scale-' + name"-->
               <!--:group-size="groupSize"-->
               <!--:value="scale"-->
               <!--@input="onScaleInput"-->
        <!--/>-->
        <field v-if="hasOptions"
               :control="control"
               :label="dataType && dataType.optionLabel ? dataType.optionLabel : ''"
               :multiple="multiple === true"
               :name="'options-' + name"
               :options="dataType ? dataType.options : undefined"
               :group-size="groupSize"
               @change="onSelectOption"
        />
        <hr v-if="footer" />
    </div>
</template>
<script>
    let without_timezone_string = 'without time zone'
    let with_timezone_string = 'with time zone'

    export default {
        components: {
            'field': require('./Field')
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
            length: {
                type: Number,
                default: null
            },
            multiple: {
                type: Boolean,
                default: false
            },
            name: {
                type: String,
                required: true
            },
            options: {
                type: Array,
                default: function () {
                    return [{
                        value: '',
                        text: 'Select...'
                    }]
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
            isTimestamp: function () {
                if (typeof this.input_value === "string") {
                    return this.input_value.startsWith('timestamp')
                }
                return false
            },
            typeOptions: function () {
                return _.map(this.dataTypes, function (dataType) {
                    return {
                        value: dataType.name,
                        text: dataType.name
                    }
                })
            }
        },
        data() {
            return {
                bus: window.bus,
                store: window.store,
                state: window.store.state,
                dataType: null,
                fieldData: {},
                input_id: null,
                input_value: null,
                with_timezone: null,
                input_value: null,
                timezoneOptions: [
                    {
                        value: true,
                        text: with_timezone_string
                    }, {
                        value: false,
                        text: without_timezone_string
                    }
                ]
            }
        },
        mounted() {
            if (this.value !== null) {
                this.input_value = this.value
                this.dataType = this.getDataType('name', this.value)
                this.fieldData.type = this.value
            }
            this.input_id = this.id || this.uuid()
            this.with_timezone = this.includesTimezone()
        },
        methods: {
            emitInput() {
                this.$emit('input', this.fieldData)
            },
            getDataType(key, value) {
                return _.find(this.dataTypes, [ key, value ])
            },
            includesTimezone() {
                if (typeof this.input_value === "string") {
                    this.input_value.includes(with_timezone_string)
                }
                return false
            },
            onPrecisionInput(e) {
                this.fieldData.precision = e.target.value
                this.emitInput()
            },
            onScaleInput(e) {
                this.fieldData.scale = e.target.value
                this.emitInput()
            },
            onSizeInput(e) {
                this.fieldData.length = e.target.value
                this.emitInput()
            },
            onTimezoneInput(e) {
                if (this.isTimestamp || this.isTime) {
                    this.fieldData.with_timezone = (e.target.value === 'true')
                } else {
                    delete this.fieldData.with_timezone
                }
                this.emitInput()
            },
            onSelect(e) {
                this.input_value = e.target.value
                this.dataType = this.getDataType('name', this.input_value)
                this.with_timezone = this.includesTimezone()
                this.fieldData.type = this.input_value
                this.emitInput()
            },
            onSelectOption(option) {
                this.fieldData.option = option
                this.emitInput()
            },
            uuid() {
                return Math.random().toString(36).substring(2) + (new Date()).getTime().toString(36)
            }
        }
    }
</script>