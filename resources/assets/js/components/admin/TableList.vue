<template>
    <div v-if="tables" class="list-group">

            <a v-for="(value, key) in computedList" @click.prevent="openTable(value)" :key="value" :class="{ active: table === value }" class="list-group-item" href="#">
                {{ value }}
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
        },
        computed: {
            computedList: function () {
                var vm = this
                if (vm.query) {
                    return this.list.filter(function (item) {
                        return item.toLowerCase().indexOf(vm.query.toLowerCase()) !== -1
                    })
                } else {
                    return this.list
                }
            }
        },
        methods: {
            openTable(table) {
                this.$emit('openTable', table)
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

