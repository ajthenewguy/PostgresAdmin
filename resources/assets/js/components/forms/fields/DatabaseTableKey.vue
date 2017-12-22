<template>
    <div class="row field" :class="{ 'bg-danger': key_deletes.hasOwnProperty(column_name), 'bg-success': is_new }" :id="'field_' + column_name">
        <fieldset class="col-md-10" :disabled="key_deletes.hasOwnProperty(column_name)">
			<component
                    v-for="(field, subkey) in formfields"
                    :is="field.component"
                    :key="subkey"
                    :control="field.control"
                    :name="field.name"
                    :label="field.label"
                    :rules="field.rules"
                    :type="field.type"
                    :options="field.options"
                    :length="field.length"
                    :placeholder="field.placeholder"
                    :value="field.value"
                    :group-size="field.groupSize ? field.groupSize : ''"
                    :layout="layout"
                    :footer="false"
                    @input="input_value => { onInput(input_value, column_name, field.name ) }"
            />
        </fieldset>
        <div class="col-md-2">
            <v-button v-if="showDropButton()" @click.prevent="dropKey" size="sm" text="Drop"></v-button>
            <v-button v-if="showKeepButton()" @click.prevent="cancelDropKey" size="sm" type="primary" text="Keep"></v-button>
            <v-button v-if="showCancelButton()" @click.prevent="cancelNewKey" size="sm" text="Discard"></v-button>
        </div>
    </div>
</template>
<script>
    export default {
        props: [ 'name', 'formfields', 'layout', 'key_deletes', 'is_new' ],
        mixins: [require('../../../mixins/PostgresMixin.vue')],
        components: {
            'field': require('./Field')
        },
        methods: {
            showDropButton() {
                return (! this.is_new) && (! this.key_deletes.hasOwnProperty(this.name))
            },
            showKeepButton() {
                return (! this.is_new) && this.key_deletes.hasOwnProperty(this.name)
            },
            showCancelButton() {
                return !! this.is_new
            },
            dropKey() {
                this.$emit('dropKey', this.name)
            },
            cancelDropKey() {
                this.$emit('cancelDropKey', this.name)
            },
            cancelNewKey() {
                this.$emit('cancelNewKey', this.name)
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
                        alter.name = value
                        break
                    }
                    case 'columns[]': {
						if (! alter.hasOwnProperty('columns')) {
							alter.columns = []
						}
                        alter.columns = value
                        break
                    }
                    case 'primary_key[]': {
                        alter.primary_key = value
                        break
                    }
                }
                this.$emit('input', alter)
            }
        }
    }
</script>
<style>
    .row.field {
        border-bottom: 1px solid #f5f5f5;
        padding: 15px 0 5px 0;
    }
    .row.field:last-of-type {
        /*border-bottom: 1px solid #f5f5f5;*/
        margin-bottom: 15px;
    }
</style>
