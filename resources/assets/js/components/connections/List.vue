<template>
    <div>
        <div class="btn-group" role="group" aria-label="...">
            <v-button v-if="route !== 'add'" @click="$emit('showAddConnection')" size="small" icon="plus" text="Add Connection" :disabled="state.processing"></v-button>
            <v-button @click="$emit('refreshConnections')" size="small" icon="refresh" :text="( route === 'index' ? 'Refresh' : '' )" :disabled="state.processing"></v-button>
        </div>
        <ul v-if="connections && connections.length" class="list-group">
            <li v-for="(value, key) in connections" :key="value.name" class="list-group-item" :class="{ 'bg-info': value.name === connection }">
                <div class="btn-group btn-group-xs pull-right" role="group" aria-label="...">
                    <span v-if="value.name === connection">
                        <small>
                            <strong v-if="route === 'index'">Connected: </strong>{{ currentConnection.username }}@{{ currentConnection.host }}
                        </small>
                    </span>
                    <v-button v-if="value.name !== connection" @click="connect(value.name)" icon="link" :text="( route === 'index' ? 'Connect' : '' )"></v-button>
                    <v-button v-if="value.name !== connection" @click="editConnection(value.name)" icon="pencil" :text="( route === 'index' ? 'Edit' : '' )"></v-button>
                    <v-button v-if="value.name !== connection" @click="deleteConnection(value.name)" icon="trash" :text="( route === 'index' ? 'Delete' : '' )"></v-button>
                </div>
                <span class="title"><p>{{ value.name }}</p></span>
            </li>
        </ul>
    </div>
</template>

<script>
    export default {
        props: [ 'connection', 'connections', 'table', 'route' ],
        data() {
            return {
                store: window.store,
                state: window.store.state,
                util: window.util
            }
        },
        computed: {
            currentConnection: function () {
                return _.find(this.connections, [ 'name', this.connection ])
            }
        },
        methods: {
            connect(name) {
                this.$emit('attemptConnect', name)
            },
            editConnection(name) {
                this.$emit('editConnection', name)
            },
            deleteConnection(name) {
                this.$emit('deleteConnection', name)
            }
        }
    }
</script>
