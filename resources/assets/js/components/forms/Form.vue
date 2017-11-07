<template>
    <div class="form-wrapper">
        <form :class="{ 'form-horizontal': horizontal }">
            <header>
                <slot name="header"></slot>
            </header>
            <main>
                <slot>
                    <field-set
                            v-if="field_sets"
                            v-for="(form_fields, key) in field_sets"
                            :key="key"
                            :formfields="form_fields"
                            :horizontal="horizontal"
                            :footer="horizontal"
                    />
                </slot>
            </main>
            <footer>
                <slot name="footer">
                    <button @click.prevent="onSubmit" class="btn btn-default" href="" title="Submit" role="button">
                        Submit
                    </button>
                </slot>
            </footer>
        </form>
    </div>
</template>
<script>
    export default {
        props: [ 'fieldsets', 'formfields', 'horizontal' ],
        components: {
            'field': require('./fields/Field'),
            'field-set': require('./Fieldset')
        },
        data() {
            return {
                field_sets: [[]]
            }
        },
        mounted() {
            if (this.fieldsets && this.fieldsets.length > 0) {
                this.field_sets = this.fieldsets
            } else {
                if (this.formfields && this.formfields.length) {
                    for (let i = 0; i < this.formfields.length; i++) {
                        this.field_sets[0].push(this.formfields[i])
                    }
                }
            }
        },
        methods: {
            onSubmit() {
                this.$emit('submit')
            }
        }
    }
</script>