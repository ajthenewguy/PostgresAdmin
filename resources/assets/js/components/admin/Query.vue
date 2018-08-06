<template>
    <thead>
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
    </thead>
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
                stripHtml: true,
                verb: null
            }
        },
        mounted() {
            if (this.sql) {
                this.query = this.sql
            }
            this.init()
        },
        computed: {
            reversedHistory: function () {
                return this.history.slice().reverse()
            }
        },
        methods: {
            blur() {
                this.inputFocus = false
            },
            focus() {
                this.inputFocus = true
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

                    // console.log('@todo: use 3rd party lib', parsedQuery)

                    return this.executeParsedQuery(parsedQuery).then(() => {
                        this.$emit('afterQuery')
                    })
                } else {
                    return Promise.resolve(null)
                }
            },
            executeParsedQuery(parsedQuery) {
                this.verb = parsedQuery.verb
                switch(parsedQuery.verb) {
                    case 'SELECT': {
                        return this.$parent.selectQuery(this.query).then(this.afterRun)
                    }
                    case 'INSERT': {
                        return this.$parent.insertQuery(this.query).then(this.afterRun)
                    }
                    case 'UPDATE': {
                        return this.$parent.updateQuery(this.query).then(this.afterRun)
                    }
                    case 'DELETE': {
                        return this.$parent.deleteQuery(this.query).then(this.afterRun)
                    }
                    default: {
                        return this.$parent.executeQuery(this.query).then(this.afterRun)
                    }
                }
            },
            afterRun() {
                this.$emit('afterQuery')
                this.$emit('success', this.$parent.response)
                if (this.verb === 'SELECT') {
                    this.setPagination()
                }
                this.verb = null
            },
            setQuery(priorQuery) {
                this.query = priorQuery
                this.$nextTick(() => {
                    this.$refs.queryDiv.format()
                })
            },
            setPagination() {
                this.$parent.setPagination()
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
        padding: 10px 10px 5px 10px;
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