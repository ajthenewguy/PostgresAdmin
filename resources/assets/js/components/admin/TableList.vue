<template>
    <div>
        <ul v-if="tables" class="list-group">
            <li v-for="(value, key) in computedList" :key="value" class="list-group-item">
                <div class="btn-group btn-group-xs pull-right" role="group" aria-label="...">
                    <button type="button" class="btn btn-default" @click.prevent="addStructureTab(value)">
                        <span :class="tabIcon('structure')" aria-hidden="true"></span>
                        <span class="button-title">Structure</span>
                    </button>
                    <button type="button" class="btn btn-default" @click.prevent.blur="openTable(value)">
                        <span :class="tabIcon('content')" aria-hidden="true"></span>
                        <span class="button-title">Contents</span>
                    </button>
                </div>
                <span class="title"><p>{{ value }}</p></span>
            </li>
        </ul>
        <a @click.prevent="addTable" class="btn btn-default btn-xs" href="" title="Add new table" role="button">
            <span :class="tabIcon('add')" aria-hidden="true"></span>
        </a>
        <a @click.prevent="$emit('refreshTables')" class="btn btn-default btn-xs" href="" title="Refresh tables" role="button">
            <span :class="tabIcon('refresh')" aria-hidden="true"></span>
        </a>
    </div>
</template>

<script>
    export default {
        props: [ 'tables', 'table', 'query' ],
        data() {
            return {
                list: []
            }
        },
        mounted() {
            this.list = this.tables
            window.addEventListener('resize', this.onWindowResize)
        },
        computed: {
            computedList: function () {
                return this.computeList()
            }
        },
        watch: {
            tables: function (newTables) {
                this.list = newTables
                return this.computeList()
            },
            deep: true
        },
        methods: {
            addTable() {
                alert("This feature will be available soon.")
            },
            openTable(table) {
                this.$emit('openTable', table)
            },
            addStructureTab(table) {
                this.$emit('addStructureTab', table)
            },
            tabIcon(type) {
                switch (type) {
                    case "add": {
                        return "glyphicon glyphicon-plus"
                    }
                    case "query": {
                        return "glyphicon glyphicon-search"
                    }
                    case "content": {
                        return "glyphicon glyphicon-th-list"
                    }
                    case "structure": {
                        return "glyphicon glyphicon-info-sign"
                    }
                    case "refresh": {
                        return "glyphicon glyphicon-refresh"
                    }
                }
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
            computeList() {
                let vm = this
                let list = []
                if (vm.query) {
                    list = this.list.filter(function (item) {
                        return item.toLowerCase().indexOf(vm.query.toLowerCase()) !== -1
                    })
                } else {
                    list = this.list
                }
                this.onWindowResize()
                return list
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
            },
            onWindowResize() {
                $(".list-group").height($(".sidebar").height() - 60)
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