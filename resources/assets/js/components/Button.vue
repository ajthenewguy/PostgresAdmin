<template>
    <a v-if="tag === 'a'" :class="buttonClass" @click.prevent="$emit('click', $event)" :href="href" role="button" :disabled="disabled" v-html="label"><slot></slot></a>
    <button v-else-if="tag === 'button'" :class="buttonClass" @click.prevent="$emit('click', $event)" :type="buttonType" :disabled="disabled" v-html="label"><slot></slot></button>
    <input v-else-if="tag === 'input'" :class="buttonClass" @click.prevent="$emit('click', $event)" :type="buttonType" :disabled="disabled" :value="label"></input>
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
                required: false
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
                let class_string = ''
                switch (this.size) {
                    case 'lg':
                    case 'large': {
                        class_string = ' btn-lg'
                        break
                    }
                    case 'sm':
                    case 'small': {
                        class_string = ' btn-sm'
                        break
                    }
                    case 'xs':
                    case 'extra-small': {
                        class_string = ' btn-xs'
                        break
                    }
                }
                return class_string
            },
            buttonType: function () {
                return this.submit ? 'submit' : 'button'
            },
            label: function() {
                let text = ''
                text += (this.$slots.default ? this.$slots.default[0].text : (this.text ? this.text : ''))
                if (this.icon) {
                    text = '<span class="glyphicon glyphicon-' + this.icon + '" aria-hidden="true"></span>' + (text ? ' ' + text : '')
                }
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