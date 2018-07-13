<template>
    <code contenteditable="true" @focus="$emit('focus', $event)" @blur="blur" @mouseleave="format" @paste="paste" class="form-control syntax" :id="id"></code>
</template>

<script>
    import Syntax from 'syntax'
    export default {
        props: ['value', 'id', 'language'],
        data() {
            return {
                _onPaste_StripFormatting_IEPaste: false
            }
        },
        watch: {
            value: function(newVal) {
                this.$el.innerText = newVal
            }
        },
        mounted() {
            this.$el.innerText = this.value
        },
        methods: {
            blur(event) {
                this.input(event)
                this.$emit('blur', event)
            },
            input(event) {
                this.$emit('input', event.target.innerText)
                this.format()
            },
            paste(event) {
                if (event.originalEvent && event.originalEvent.clipboardData && event.originalEvent.clipboardData.getData) {
                    event.preventDefault()
                    let text = event.originalEvent.clipboardData.getData('text/plain')
                    window.document.execCommand('insertText', false, text)
                }
                else if (event.clipboardData && event.clipboardData.getData) {
                    event.preventDefault()
                    let text = event.clipboardData.getData('text/plain')
                    window.document.execCommand('insertText', false, text)
                }
                else if (window.clipboardData && window.clipboardData.getData) {
                    // Stop stack overflow
                    if (!this._onPaste_StripFormatting_IEPaste) {
                        this._onPaste_StripFormatting_IEPaste = true
                        event.preventDefault()
                        window.document.execCommand('ms-pasteTextOnly', false)
                    }
                    this._onPaste_StripFormatting_IEPaste = false
                }
            },
            format() {
                if (this.language) {
                    if (this.language.toLowerCase() === 'sql') {
                        this.formatSql()
                    }
                }
            },
            formatSql() {
                let syntax = new Syntax({
                    language: "sql",
                    cssPrefix: ""
                })
                syntax.richtext(this.$el.innerText)
                this.$el.innerHTML = syntax.html()
            }
        }
    }
</script>

<style>
    .syntax {
        background-color: #ffffff;
        white-space: pre;
        line-height: 1.15;
    }

    .syntax .marker {
        border-top: 1px solid #f0f0d0;
        background-color: #ffffe0;
        border-bottom: 1px solid #f0f0d0;
    }
    .syntax .anchor {
        color: #cc3333;
    }
    .syntax .comment {
        font-style: italic;
        color: #999999;
    }
    .syntax .keyword {
        font-weight: bold;
        color: #6699cc;
    }
    .syntax .literal {
        color: #c08030;
    }
</style>