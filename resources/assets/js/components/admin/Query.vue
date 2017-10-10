<template>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">Console</div>

                <div class="panel-body">
                    <textarea v-model="query" class="form-control" rows="2" id="query_input"></textarea>
                    <button @click="executeQuery" type="button" class="btn btn-default btn-sm">
                        <span class="glyphicon glyphicon-flash" aria-hidden="true"></span> Query
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
//        props: [ 'tables' ],
        data() {
            return {
                query: null
            }
        },
        mounted() {
        },
        methods: {
            executeQuery() {
                this.$emit('customQuery', this.query)
                this.$emit('beforeQuery', this.query)
                axios.post('http://postgres:5433/query', {
                    input: this.query
                }).then(response => {
                    this.$emit('afterQuery')
                    this.$emit('success', response)
                }).catch(error => {
                    if (error.message === "Request failed with status code 419") {
                        window.location = '/'
                    }
                    this.$emit('error', error)
                })
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