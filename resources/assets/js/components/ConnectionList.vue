<template>
    <div>
        <div class="btn-group" role="group" aria-label="...">
            <v-button @click="$emit('showAddConnection')" size="small" icon="plus" :disabled="route === 'add'"></v-button>
            <v-button @click="$emit('refreshConnections')" size="small" icon="refresh" :disabled="state.processing"></v-button>
        </div>
        <ul v-if="connections && connections.length" class="list-group">
            <li v-for="(value, key) in connections" :key="value.name" class="list-group-item">
                <div class="btn-group btn-group-xs pull-right" role="group" aria-label="...">
                    <button type="button" class="btn btn-default" @click="connect(value.name)">
                        <span :class="util.icon('link')" aria-hidden="true"></span>
                        <span class="button-title">Connect</span>
                    </button>
                    <!--<button type="button" class="btn btn-default" @click.blur="openTable(value)">-->
                        <!--<span :class="util.icon('content')" aria-hidden="true"></span>-->
                        <!--<span class="button-title">Contents</span>-->
                    <!--</button>-->
                </div>
                <span class="title"><p>{{ value.name }}</p></span>
            </li>
        </ul>
    </div>
</template>

<script>
    export default {
        props: [ 'connections', 'table', 'route' ],
        data() {
            return {
                store: window.store,
                state: window.store.state,
                util: window.util
            }
        },
        methods: {
            connect(name) {
                this.$emit('connect', name)
            },
            beforeEnter: function (el) {
                el.style.opacity = 0
                el.style.height = 0
            },
            enter: function (el, done) {
                var delay = el.dataset.index * 150
                setTimeout(function () {
                    Velocity(
                        el,
                        { opacity: 1, height: '1.6em' },
                        { complete: done }
                    )
                }, delay)
            },
            leave: function (el, done) {
                var delay = el.dataset.index * 150
                setTimeout(function () {
                    Velocity(
                        el,
                        { opacity: 0, height: 0 },
                        { complete: done }
                    )
                }, delay)
            }
        }
    }
</script>

<style lang="scss">
    .list-group {
        border-top: 1px solid #e6e5e5;
        border-bottom: 1px solid #e6e5e5;
        margin-bottom: 5px;
        overflow: auto;
        white-space: nowrap;
    }
    .list-group li:first-child {
        border-top: none;
    }
    .list-group > * {
        height: 30px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .title p
    {
        margin: 0;
        padding: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
        min-height: 16px;
    }
    .title p:hover
    {
        background: #fff;
        position: relative;
        z-index: 1;
        display: inline-block;
    }
    .button-title {
        display: none
    }
    .list-group-item {
        padding: 3px 3px 3px 8px;
        button:hover {
            .button-title {
                display: inline
            }
        }
    }
</style>