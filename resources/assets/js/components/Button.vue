<template>
    <span>
        <a v-if="tag === 'a'" :class="buttonClass" @click.prevent="$emit('click', $event)" :href="href" role="button" :disabled="disabled" v-html="label"><slot></slot></a>
        <button v-if="tag === 'button'" :class="buttonClass" @click.prevent="$emit('click', $event)" :type="buttonType" :disabled="disabled" v-html="label"><slot></slot></button>
        <input v-if="tag === 'input'" :class="buttonClass" @click.prevent="$emit('click', $event)" :type="buttonType" :disabled="disabled" :value="label"></input>
    </span>
</template>
<script>
    export default {
        props: {
            disabled: {
                type: Boolean,
                default: false
            },
            href: {
                type: String,
                default: '#'
            },
            icon: {
                type: String,
                default: ''
            },
            size: {
                type: String,
                default: ''
            },
            tag: {
                type: String,
                default: 'button'
            },
            text: {
                type: String,
                required: true
            },
            type: {
                type: String,
                default: 'default'
            },
            submit: {
                type: Boolean,
                default: false
            }
        },
        computed: {
            buttonClass: function () {
                return 'btn btn-' + this.type + this.buttonSize
            },
            buttonSize: function () {
                return this.size ? ' btn-' + this.size : ''
            },
            buttonType: function () {
                return this.submit ? 'submit' : 'button'
            },
            label: function() {
                let text = ''
                if (this.icon) {
                    text += '<span class="glyphicon glyphicon-' + this.icon + '" aria-hidden="true"></span> &nbsp;'
                }
                text += (this.$slots.default ? undefined : this.text)
                return text
            }
        },
        mounted() {
            this.init()
        },
        methods: {
            init() {
                // eslint-disable-next-line
//                console.log(this.text, this.$slots.default)
            }
        }
    }
</script>