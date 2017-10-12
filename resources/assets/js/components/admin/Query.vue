<template>
    <div>
        <textarea v-model="query" class="form-control" rows="2" id="query_input">{{ sql }}</textarea>
        <button @click="executeQuery" type="button" class="btn btn-default btn-sm">
            <span class="glyphicon glyphicon-flash" aria-hidden="true"></span> Query
        </button>
    </div>
</template>
<script>
    export default {
        props: [ 'sql' ],
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
                if (this.query) {
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
    }
</script>
<style>
    #query_input {
        width: 100%;
        font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
    }
</style>