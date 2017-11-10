<template>
    <div class="form-wrapper">
        <form :class="formClass">
            <header>
                <slot name="header"></slot>
            </header>
            <main>
                <slot>
                    <field-set
                            v-if="formFieldsets"
                            v-for="(fieldset, key) in formFieldsets"
                            :key="key"
                            :class="fieldset.class"
                            :formfields="fieldset.fields"
                            :layout="layout"
                            :footer="isHorizontal"
                            @input="$emit('input', $event)"
                    />
                </slot>
            </main>
            <footer>
                <slot name="footer">
                    <button @click.prevent="$emit('submit', $event)" class="btn btn-default" href="" title="Submit" role="button">
                        Submit
                    </button>
                </slot>
            </footer>
        </form>
    </div>
</template>
<script>
    export default {
        props: [ 'fieldsets', 'formfields', 'layout' ],
        components: {
            'field': require('./fields/Field'),
            'field-set': require('./Fieldset')
        },
        data() {
            return {
                formFieldsets: [[]]
            }
        },
        watch: {
            fieldsets: function (data) {
                this.makeFields()
            },
            formfields: function (data) {
                this.makeFields()
            }
        },
        computed: {
            formClass: function () {
                return {
                    'form-inline': this.layout === 'inline',
                    'form-horizontal': this.layout === 'horizontal'
                }
            },
            isHorizontal: function () {
                return this.layout === 'horizontal'
            },
            isInline: function () {
                return this.layout === 'inline'
            }
        },
        created() {
            this.makeFields()
        },
//        mounted() {
//            this.makeFields()
//        },
        methods: {
            makeFields() {
                if (this.fieldsets) {
                    this.formFieldsets = this.fieldsets
                }

//            if (this.formfields) {
//                for (let i = 0; i < this.formfields.length; i++) {
//                    this.formFieldsets[0].push(this.formfields[i])
//                }
//            }
                if (this.formfields) {
                    this.formFieldsets[0] = this.formfields
                }
            }
        }
    }
</script>