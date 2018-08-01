<template>
    <div id="query-wrapper">
        <textarea
            v-show="inputFocus"
            ref="queryTextarea"
            @blur="blur"
            v-model="query"
            class="form-control"
            rows="2"
            id="query_input"></textarea>
        <div-edit
            v-show="!inputFocus"
            ref="queryDiv"
            v-model="query"
            id="query_input"
            language="SQL"
            @focus="focus"
        />
        <div class="btn-group">
            <button @click="run" type="button" class="btn btn-default btn-sm">
                <span class="glyphicon glyphicon-flash" aria-hidden="true"></span> Run Query
            </button>
            <button v-if="history.length > 1" type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                History <span class="caret"></span>
            </button>
            <ul v-if="history.length > 1" class="dropdown-menu">
                <li v-for="priorQuery in reversedHistory">
                    <a href="#" @click.prevent="setQuery(priorQuery)">{{ priorQuery }}</a>
                </li>
            </ul>
        </div>
    </div>
</template>
<script>
    export default {
        props: [ 'sql', 'history', 'loadedTables', 'processing' ],
        data() {
            return {
                query: null,
                autocomplete: {
                    options: this.loadedTables.concat(['SELECT', 'FROM', 'WHERE', 'AND']),
                    selector: 'textarea#query_input',
                    acceptKeys: [$.asuggestKeys.SPACE], // [$.asuggestKeys.RETURN, $.asuggestKeys.SPACE] : SHIFT, CTRL, ALT, LEFT, UP, RIGHT, DOWN, DEL, TAB, RETURN, ESC, COMMA, PAGEUP, PAGEDOWN, BACKSPACE and SPACE
                    cycleOnTab: false,
                    endingSymbols: ' ', // "space" is default
                    minChunkSize: 1,
                    delimeters: '\n ' // array of chars
                },
                inputFocus: false,
                inputHeight: null,
                observer: null,
                stripHtml: true
            }
        },
        mounted() {
            if (this.sql) {
                this.query = this.sql
            }
            this.init()
            // this.sampleInputHeight()
        },
        computed: {
            reversedHistory: function () {
                return this.history.slice().reverse()
            }
        },
        methods: {
            bindResizeOberver() {
                this.observer = setInterval(() => {
                    this.sampleInputHeight()
                }, 100)
            },
            blur() {
                this.inputFocus = false
                clearInterval(this.observer)
                this.$nextTick(() => {
                    this.$refs.queryDiv.format()
                    $('code#query_input').height(this.inputHeight)
                })
            },
            focus() {
                this.inputFocus = true
                this.bindResizeOberver()
                this.$nextTick(() => {
                    this.init()
                    this.$refs.queryTextarea.focus()
                })
            },
            queryInputElement() {
                return $('#query_input')
            },
            resultsTableElement() {
                return $('#query_input').closest('.tab-pane.query').find('.results-table-container')
            },
            run() {
                let query = this.query

                if (this.stripHtml) {
                    let doc = new DOMParser().parseFromString(query, 'text/html')
                    query = doc.body.textContent || ""
                }

                this.$emit('customQuery', query)
                if (query) {
                    this.$emit('beforeQuery', query)
                    let parsedQuery = this.$parent.parseSql(query)
                    // eslint-disable-next-line
                    console.log('@todo: use 3rd party lib', parsedQuery)
                    return this.executeParsedQuery(parsedQuery).then(() => {
                        this.$emit('afterQuery')
                    })
                } else {
                    return Promise.resolve(null)
                }
            },
            sampleInputHeight() {
                return this.$nextTick(() => {
                    let height = this.queryInputElement().height()
                    if (this.inputHeight !== height) {
                        this.inputHeight = height
                        this.resultsTableElement().css('paddingTop', (this.inputHeight + 70) + 'px')
                    }
                })
            },
            executeParsedQuery(parsedQuery) {
                switch(parsedQuery.verb) {
                    case 'SELECT': {
                        // eslint-disable-next-line
                        console.log(this.query)
                        return this.$parent.selectQuery(this.query).then(this.afterRun)
                        break
                    }
                    case 'INSERT': {
                        return this.$parent.insertQuery(this.query).then(this.afterRun)
                        break
                    }
                    case 'UPDATE': {
                        return this.$parent.updateQuery(this.query).then(this.afterRun)
                        break
                    }
                    case 'DELETE': {
                        return this.$parent.deleteQuery(this.query).then(this.afterRun)
                        break
                    }
                    default: {
                        return this.$parent.executeQuery(this.query).then(this.afterRun)
                        break
                    }
                }
            },
            afterRun() {
                this.$emit('afterQuery')
                this.$emit('success', this.response)
            },
            setQuery(priorQuery) {
                this.query = priorQuery
                this.$nextTick(() => {
                    this.$refs.queryDiv.format()
                })
            }
        },
        mixins: [require('../../mixins/AutoComplete')],
        components: {
            'div-edit': require('./EditableDiv')
        }
    }
</script>
<style>
    #query-wrapper {
        background-color: #fff;
        padding: 10px;
        position: absolute;
        width: 100%;
    }
    #query_input {
        font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
        resize: vertical;
        line-height: 22px;
        height: 60px;
        width: 100%;
        white-space: normal;
    }
</style>