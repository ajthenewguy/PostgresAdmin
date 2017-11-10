<template>
    <fieldset>
        <slot>
            <field v-for="(field, key) in formfields"
                   :key="key"
                   :control="field.control"
                   :multiple="field.multiple"
                   :name="field.name"
                   :label="field.label"
                   :rules="field.rules"
                   :type="field.type"
                   :options="field.options"
                   :placeholder="field.placeholder"
                   :value="field.value"
                   :layout="field.layout || layout"
                   :column-width="field.columnWidth"
                   :footer="false"
                   :autofocus="field.autofocus"
                   @input="$emit('input', $event)"
            />
            <hr v-if="footer" />
        </slot>
    </fieldset>
</template>
<script>
    export default {
        props: [ 'formfields', 'layout', 'footer' ],
        components: {
            'field': require('./fields/Field')
        }
    }
</script>