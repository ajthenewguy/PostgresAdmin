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
        props: [ 'connection' ],
        data() {
            return {
                fieldsets: [],
                fielddata: {

                }
            }
        },
        mounted() {
            let fields = []
            fields.push({
                name: 'name',
                label: 'Name',
                value: (this.connection ? this.connection.name : ''),
                columnWidth: 12,
                autofocus: true
            })
            this.fieldsets.push({
                fields: fields
            })

            fields = []
            fields.push({
                name: 'host',
                label: 'Host',
                value: (this.connection ? this.connection.host : ''),
                groupSize: 'form-group-sm',
                columnWidth: 6
            })
            fields.push({
                name: 'port',
                label: 'Port',
                value: (this.connection ? this.connection.port : ''),
                groupSize: 'form-group-sm',
                columnWidth: 6
            })
            this.fieldsets.push({
                fields: fields
            })

            fields = []
            fields.push({
                name: 'database',
                label: 'Database',
                value: (this.connection ? this.connection.database : ''),
                groupSize: 'form-group-sm',
                columnWidth: 12
            })
            this.fieldsets.push({
                fields: fields
            })

            fields = []
            fields.push({
                name: 'username',
                label: 'User',
                value: (this.connection ? this.connection.username : ''),
                groupSize: 'form-group-sm',
                columnWidth: 6
            })
            fields.push({
                name: 'password',
                label: 'Password',
                type: 'password',
                value: (this.connection ? this.connection.password : ''),
                groupSize: 'form-group-sm',
                columnWidth: 6
            })
            this.fieldsets.push({
                fields: fields
            })
        }
    }
</script>