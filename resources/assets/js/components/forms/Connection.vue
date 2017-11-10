<template>
    <app-form
            :fieldsets="fieldsets"
            @input="$emit('input', $event)"
    >
        <template slot="footer">
            <v-button @click.prevent="$emit('submit', $event)" type="primary" text="Submit"></v-button>
            <v-button @click.prevent="$emit('cancel', $event)" type="default" text="Cancel"></v-button>
        </template>
    </app-form>
</template>
<script>
    export default {
        props: [ 'connection', 'route' ],
        data() {
            return {
                fieldsets: []
            }
        },
        watch: {
            connection: function (data) {
                this.fieldsets = this.makeFields(data)
            }
        },
        mounted() {
            this.fieldsets = this.makeFields(this.connection)
        },
        methods: {
            makeFields(data) {
                let fields = []
                let fieldsets = []
                fields.push({
                    name: 'name',
                    label: 'Name',
                    value: (data ? data.name : ''),
                    columnWidth: 12,
                    autofocus: this.route !== 'edit',
                    disabled: this.route === 'edit'
                })
                fieldsets.push({
                    fields: fields
                })

                fields = []
                fields.push({
                    name: 'host',
                    label: 'Host',
                    value: (data ? data.host : ''),
                    groupSize: 'form-group-sm',
                    autofocus: this.route === 'edit',
                    columnWidth: 6
                })
                fields.push({
                    name: 'port',
                    label: 'Port',
                    value: (data ? data.port : ''),
                    groupSize: 'form-group-sm',
                    columnWidth: 6
                })
                fieldsets.push({
                    fields: fields
                })

                fields = []
                fields.push({
                    name: 'database',
                    label: 'Database',
                    value: (data ? data.database : ''),
                    groupSize: 'form-group-sm',
                    columnWidth: 12
                })
                fieldsets.push({
                    fields: fields
                })

                fields = []
                fields.push({
                    name: 'username',
                    label: 'User',
                    value: (data ? data.username : ''),
                    groupSize: 'form-group-sm',
                    columnWidth: 6
                })
                fields.push({
                    name: 'password',
                    label: 'Password',
                    type: 'password',
                    rules: 'required',
                    value: (data ? data.password : ''),
                    groupSize: 'form-group-sm',
                    columnWidth: 6
                })
                fieldsets.push({
                    fields: fields
                })
                return fieldsets
            }
        }
    }
</script>