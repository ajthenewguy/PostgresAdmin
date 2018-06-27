<script>
    require('../jquery.a-tools-1.4.1')
    require('../jquery.asuggest')
    export default {
        mounted() {
            this.init()
        },
        data() {
            return {
                autocomplete: {
                    options: ['SELECT', 'FROM', 'WHERE', 'AND'],
                    selector: '.el-input__inner',
                    acceptKeys: [$.asuggestKeys.SPACE], // [$.asuggestKeys.RETURN, $.asuggestKeys.SPACE] : SHIFT, CTRL, ALT, LEFT, UP, RIGHT, DOWN, DEL, TAB, RETURN, ESC, COMMA, PAGEUP, PAGEDOWN, BACKSPACE and SPACE
                    cycleOnTab: false,
                    endingSymbols: ' ', // "space" is default
                    minChunkSize: 1,
                    delimeters: '\n ' // array of chars
                }
            }
        },
        methods: {
            init() {
                if (this.autocomplete.selector && this.autocomplete.options.length > 0) {
                    $(this.autocomplete.selector).asuggest(this.autocomplete.options, {
                        'endingSymbols': this.autocomplete.endingSymbols,
                        'stopSuggestionKeys': this.autocomplete.acceptKeys,
                        'minChunkSize': this.autocomplete.minChunkSize,
                        'delimiters': this.autocomplete.delimeters,
                        'cycleOnTab': this.autocomplete.cycleOnTab
                    })
                }
            }
        }
    }
</script>