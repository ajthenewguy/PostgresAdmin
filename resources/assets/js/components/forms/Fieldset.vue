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
                   :footer="false"
            />
            <hr v-if="footer" />
        </slot>
    </fieldset>
</template>
<script>
    export default {
        props: [ 'formfields', 'footer' ],
        components: {
            'field': require('./fields/Field')
        }
    }
</script>