<template>
    <table v-if="tabs.length" class="layout">
        <thead>
            <draggable
                    element="ul"
                    v-model="tabs"
                    class="nav nav-tabs" id="primaryTabContainer"
                    :class="{ stacked: mixedConnection() }"
                    :options="{ draggable: '.nav-tab-item' }"
                    @end="onMoveTab"
            >
                <li
                        class="nav-tab-item"
                        v-for="(tab, key) in tabs"
                        :key="tab.id"
                        :class="{ active: getTabIndex('id', tab.id) === activeTabIndex() }"
                >
                    <a class="nav-tab-item-a" :href="'#tab-' + tab.id" @click.prevent="changeTab(tab.id)" :data-key="tab.id">
                        <span :class="tabIcon(tab.type)" aria-hidden="true"></span> <span class="subtitle" v-show="mixedConnection() && tab.type !== 'query'">{{ tab.connection }}.</span>{{ tab.title }}
                        <button @click="closeTab(tab.id)" class="close closeTab">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </a>
                </li>
                <li class="newTab">
                    <a @click.prevent="addTab('query')" href="">
                        <span :class="tabIcon('add')" aria-hidden="true"></span>
                    </a>
                </li>
            </draggable>
        </thead>
        <tbody>
            <div class="tab-content">
                <tab
                        v-for="(tab, key) in tabs"
                        ref="tab"
                        :key="tab.id"
                        :id="tab.id"
                        :current-tab="activeTabIndex()"
                        :type="tab.type"
                        :table="tab.table"
                        :config="tab.config"
                        :find="tab.where"
                        :loaded-tables="loadedTables"
                        @loaded="$emit('loaded')"
                        @refresh="$emit('refresh', $event)"
                        @openTableRow="$emit('openTableRow', $event)"
                />
            </div>
        </tbody>
    </table>
    <div class="notabs" v-else>
        <div v-if="connected && !state.processing" class="card">
            <div class="card-body text-center">
                <p>To get started select a table from the list or click the "New Query" button to write an SQL query.</p>
                <el-button @click="newTab('query')" type="primary" icon="search">New Query</el-button>
            </div>
        </div>
    </div>
</template>
<script>
    import _ from 'lodash'
    import draggable from 'vuedraggable'
    export default {
        components: { draggable },
        mixins: [require('../mixins/PostgresMixin.vue')],
        props: ['loadedTables'],
        data() {
            return {
                bus: window.bus,
                connected: false,
                tabs: []
            }
        },
        // computed: {
        //
        // },
        mounted() {
            this.bus.$on('App.tabsLoaded', () => {
                this.connected = true
            })
            this.bus.$on('loggedIn', () => {
                this.storeTabs()
                this.refreshTab()
            })
            this.bus.$on('tabRefreshed', (config) => {
                _.forEach(this.tabs, function(tab, key) {
                    if (tab.table === config.name) {
                        tab.config = config
                    }
                })
            })
        },
        methods: {
            activeTab() {
                let index = this.activeTabIndex()
                return this.tabs[index]
            },
            activeTabIndex(newIndex) {
                if (typeof newIndex !== "undefined" && newIndex > -1 || newIndex === null) {
                    this.state.activeTab = newIndex
                    this.$emit('tabChanged', this.activeTab())
                }
                return this.state.activeTab
            },
            addTab() {
                this.changeTab(this.newTab(...arguments))
            },
            changeTab(index, where) {
                let $this = this
                let changeToTab = null
                if (isNaN(index)) {
                    index = this.getTabIndex('id', index)
                }
                if (index < 0) {
                    this.activeTabIndex(null)
                    this.storeSelectedTab(null)
                } else {
                    changeToTab = $this.getTab('index', index)
                    if (changeToTab) {
                        $('.nav-tabs a[data-id="' + changeToTab.id + '"]').tab('show')
                        $this.activeTabIndex(index)
                        this.storeSelectedTab(changeToTab.id)

                        if (where) {
                            this.$nextTick(() => {
                                console.log('filterWhere', index, where)
                                console.log(this.$refs.tab)
                                this.$refs.tab[index].filterWhere(where)

                            })
                        }
                    }
                }
            },
            tabExists(index) {
                if (isNaN(index)) {
                    index = this.getTabIndex('id', index)
                }
                return !!this.getTab('index', index)
            },
            closeTab(index) {
                let $this = this
                let selectTabIndex = this.activeTabIndex()
                if (isNaN(index)) {
                    index = this.getTabIndex('id', index)
                }
                if (index > -1) {
                    _.pullAt(this.tabs, index)
                    while (selectTabIndex >= Object.keys(this.tabs).length) {
                        selectTabIndex -= 1
                    }
                    if (selectTabIndex < 0) {
                        selectTabIndex = 0
                    }
                    setTimeout(function() {
                        $this.changeTab(selectTabIndex)
                        $this.storeTabs()
                    }, 5)
                }
                this.reindexTabs()
                return
            },
            countTabs() {
                return this.tabs.length
            },
            getTab(key, value) {
                if (typeof key === "object" && typeof value === "undefined") {
                    let tabs = {}
                    if (_.has(key, 'where')) {
                        return _.find(this.tabs, key)
                    } else {
                        tabs = _.omitBy(this.tabs, function(t) {
                            return _.has(t, 'where')
                        })
                        return _.find(tabs, key)
                    }
                } else {
                    if (key === 'index') {
                        return this.tabs[value]
                    } else if (key.startsWith('table.')) {
                        let tabCount = this.countTabs()
                        for (let i = 0; i < tabCount; i++) {
                            if (_.get(this.tabs[i], key, null) === value) {
                                return this.tabs[i]
                            }
                        }
                        return undefined
                    }
                }
                return _.find(this.tabs, [ key, value ])
            },
            getTabIndex(key, value) {
                return _.findIndex(this.tabs, [ key, value ])
            },
            loadTabs() {
                let connection = this.state.connection
                return window.session.get(connection+'.tabs').then(tabs => {
                    let promises = []
                    let tabCount = tabs.length
                    if (tabCount > 0) {
                        for (let i = 0; i < tabCount; i++) {
                            if (tabs[i].connection === this.state.connection && !_.find(this.tabs, {'id': tabs[i].id})) {
                                let schema = null
                                let config = tabs[i].config
                                if (!config && tabs[i].connection === this.state.connection) {
                                    schema = this.getTableSchema(tabs[i].table)
                                } else if (config && config.schema !== null) {
                                    schema = config.schema
                                    if (!this.state.tables[tabs[i].table] || this.state.tables[tabs[i].table].schema === null) {
                                        this.state.tables[tabs[i].table] = config
                                    }
                                }
                                if (null === schema) {
                                    promises.push(this.loadTableSchema(tabs[i].table).then(tableConfig => {
                                        this.tabs.push(tabs[i])
                                    }))
                                } else {
                                    this.tabs.push(tabs[i])
                                    promises.push(Promise.resolve(tabs[i]))
                                }
                            }
                        }
                    }

                    return Promise.all(promises).then(() => {
                        this.sortTabs()
                        this.reindexTabs()
                        return this.tabs
                    })
                }).catch(() => {
                    return Promise.resolve([])
                })
            },
            makeTab(tabId, connection, type, title, table, where) {
                let tab = {
                    id: tabId,
                    connection: connection,
                    type: type,
                    title: title,
                    table: table,
                    index: this.tabs.length
                }
                let tableConfig = this.tableConfig(table)
                if (tableConfig) {
                    tab.config = tableConfig
                }
                if (typeof where !== "undefined") {
                    tab.where = where
                }
                if (table) {
                    if (typeof table === "object" && table.hasOwnProperty('name')) {
                        table = table.name
                    }
                    tab.table = table
                }
                return tab
            },
            newTab(type, title, table, where, pos) {
                if (! title) {
                    title = this.titleCase(type)
                }
                let tabId = this.uuid()
                let tab = this.makeTab(tabId, this.state.connection, type, title, table, where)

                if (typeof pos !== "undefined") {
                    this.tabs.splice(pos, 0, tab)
                    this.reindexTabs()
                } else {
                    this.tabs.push(tab)
                }
                if (null === this.activeTabIndex()) {
                    this.activeTabIndex(this.getTabIndex('id', tabId))
                }
                if (type !== 'query') {
                    this.storeTabs()
                }
                return tabId
            },
            onMoveTab(e) {
                let from = e.oldIndex
                let to = e.newIndex
                if (from === this.activeTabIndex()) {
                    this.changeTab(to)
                } else if (to <= this.activeTabIndex()) {
                    this.changeTab(Math.min(this.activeTabIndex() + 1, this.tabs.length - 1))
                } else if (to > this.activeTabIndex() && from < this.activeTabIndex()) {
                    this.changeTab(Math.max(this.activeTabIndex() - 1, 0))
                }
                this.reindexTabs()
                this.storeTabs()
            },
            refreshTab(index) {
                if (typeof index === "undefined") {
                    index = this.activeTabIndex()
                }
                this.$refs['tab'][index].refresh()
            },
            reindexTabs() {
                let tabCount = this.tabs.length
                for (let i = 0; i < tabCount; i++) {
                    this.tabs[i].index = i
                }
            },
            sortTabs() {
                this.tabs = _.orderBy(this.tabs, ['index'])
            },
            storeTabs: _.debounce(function() {
                let connection = this.state.connection
                let tabs = _.filter(this.tabs, function(t) {
                    return t.type !== 'query' && t.connection === connection
                })
                window.session.set(connection+'.tabs', tabs)
            }, 500),
            storeSelectedTab: _.debounce(function(id) {
                let tab = this.getTab('id', id)
                let connection = tab ? tab.connection : this.state.connection
                window.session.set(connection+'.selectedTab', id)
            }, 500),
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
                    default: {
                        return "glyphicon glyphicon-" + type
                    }
                }
            },
            tableConfig(table) {
                let tab = null
                let config = this.state.tables[table]
                if (!config) {
                    tab = this.getTab('table', table)
                    if (tab) {
                        config = tab.config
                    }
                }
                return config
            },
            mixedConnection() {
                let tabLength = this.tabs.length
                let connection = this.state.connection
                for (let i = 0; i < tabLength; i++) {
                    if (this.tabs[i].type !== 'query') {
                        if (connection === null) {
                            connection = this.tabs[i].connection
                        }
                        if (this.tabs[i].connection !== connection) {
                            return true
                        }
                    }
                }
                return false
            },
            titleCase(string) {
                return string.replace(/_/g, ' ').replace(/(^[a-z])|(\s+[a-z])/g, txt => txt.toUpperCase())
            },
            uuid() {
                return Math.random().toString(36).substring(2) + (new Date()).getTime().toString(36)
            }
        }
    }
</script>
<style>
    .tab-content, .tab-pane {
        height: 100%;
        max-height: 100%;
        width: 99.9999%;
    }
    .tab-pane-content {
        overflow-x: hidden;
        overflow-y: auto;
    }
	div.tab-pane-content {
		padding: 10px;
	}
    .results-table-container {
        display: flex;
        height: 99%;
        max-height: 99%;
        flex-direction: column;
    }
    .notabs {
        padding: 10%;
    }
</style>
