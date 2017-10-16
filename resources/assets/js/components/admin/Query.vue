<template>
    <div>
        <br>
        <textarea v-model="query" class="form-control" rows="2" id="query_input">{{ sql }}</textarea>


        <div class="btn-group">
            <button @click="run" type="button" class="btn btn-default btn-sm">
                <span class="glyphicon glyphicon-flash" aria-hidden="true"></span> Run Query
            </button>


            <button v-if="history.length > 1" type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                History <span class="caret"></span>
            </button>
            <ul v-if="history.length > 1" class="dropdown-menu">
                <li v-for="priorQuery in history.reverse()">
                    <a href="#" @click.prevent="query = priorQuery">{{ priorQuery }}</a>
                </li>
            </ul>
        </div>
    </div>
</template>
<script>
    export default {
        props: [ 'sql', 'history' ],
        data() {
            return {
                query: null
            }
        },
        mounted() {
        },
        methods: {
            run() {
                this.$emit('customQuery', this.query)
                if (this.query) {
                    this.$emit('beforeQuery', this.query)

                    let parsedQuery = this.$parent.parseSql(this.query)
                    // eslint-disable-next-line
                    console.log(parsedQuery)

                    switch(parsedQuery.verb) {
                        case 'SELECT': {
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
                }
            },
            afterRun() {
                this.$emit('afterQuery')
                this.$emit('success', this.response)
            }
        }
    }
</script>
<style>
    #query_input {
        width: 100%;
        font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
    }
</style>