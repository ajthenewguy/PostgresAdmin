<template>
    <table v-if="tabs.length" class="layout">
        <thead>
            <draggable
                    element="ul"
                    v-model="tabs"
                    class="nav nav-tabs" id="primaryTabContainer"
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
                        <span :class="tabIcon(tab.type)" aria-hidden="true"></span> {{ tab.title }}
                        <button @click="closeTab(tab.id)" class="close closeTab">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </a>
                </li>
                <li class="newTab">
                    <a @click.prevent="addTab('query')" href="">
                        <span :class="tabIcon('query')" aria-hidden="true"></span>
                    </a>
                </li>
            </draggable>
        </thead>
        <tbody>
            <div class="tab-content">
                <tab
                        v-for="(tab, key) in tabs"
                        :key="tab.id"
                        :id="tab.id"
                        :current-tab="activeTabIndex()"
                        :type="tab.type"
                        :table="tab.table"
                        @loaded="$emit('loaded')"
                        @refresh="$emit('refresh', $event)"
                />
            </div>
        </tbody>
    </table>
    <div class="notabs" v-else>
        <el-button @click="newTab('query')" type="primary" icon="search">New Query</el-button>
    </div>
</template>
<script>
    import draggable from 'vuedraggable'
    export default {
        components: { draggable },
        props: [],
        data() {
            return {
                bus: window.bus,
                store: window.store,
                state: window.store.state,
                tabs: []
            }
        },
        created() {
            let $this = this
            this.bus.$on('tabRefreshed', function (config) {
                let selectTabIndex = $this.activeTabIndex()
                $this.tabs[selectTabIndex].table = config
            })
            this.bus.$on('databaseConnected', function (config) {
                $this.tabs = []
                $this.addTab('query')
            });
        },
        methods: {
            activeTab() {
                let index = this.activeTabIndex()
                return this.tabs[index]
            },
            activeTabIndex(newIndex) {
                if (typeof newIndex !== "undefined" && newIndex > -1 || newIndex === null) {
                    this.state.activeTab = newIndex
                    this.$emit('tabChanged', newIndex)
                }
                return this.state.activeTab
            },
            addTab() {
                this.changeTab(this.newTab(...arguments))
            },
            changeTab(index) {
                let $this = this
                let changeToTab = null
                if (isNaN(index)) {
                    index = this.getTabIndex('id', index)
                }
                if (index < 0) {
                    this.activeTabIndex(null)
                } else {
//                    setTimeout(function() {
                        changeToTab = $this.getTab('index', index)
                        if (changeToTab) {
                            $('.nav-tabs a[data-id="' + changeToTab.id + '"]').tab('show')
                            $this.activeTabIndex(index)
                        }
//                    }, 5)
                }
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
                    }, 5)
                }
                return
            },
            countTabs() {
                return this.tabs.length
            },
            getTab(key, value) {
                if (typeof key === "object" && typeof value === "undefined") {
                    return _.find(this.tabs, key)
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
            newTab(type, title, table) {
                if (! title) {
                    title = this.titleCase(type)
                }
                let tabId = this.uuid()
                let tab = {
                    id: tabId,
                    type: type,
                    title: title,
                    table: table
                }
                this.tabs.push(tab)
                if (null === this.activeTabIndex()) {
                    this.activeTabIndex(this.getTabIndex('id', tabId))
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
                }
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
    table.layout {
        display: flex;
        height: 100%;
        flex-direction: column;
    }
    table.layout tbody {
        flex: 1;
        overflow-x: hidden;
        overflow-y: auto;
    }
    .tab-content, .tab-pane {
        height: 100%;
        max-height: 100%;
    }
    .tab-pane-content {
        overflow-x: hidden;
        overflow-y: auto;
    }
    .results-table-container {
        display: flex;
        height: 99%;
        max-height: 99%;
        flex-direction: column;
    }
    .notabs {
        margin: 15px 0;
    }
</style>