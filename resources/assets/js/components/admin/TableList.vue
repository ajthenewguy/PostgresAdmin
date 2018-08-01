<template>
    <div>
        <ul v-if="tables" class="list-group">
            <li v-for="(value, key) in computedList" :key="value" class="list-group-item" :class="{ selected: value === selectedTable }">
                <div class="btn-group btn-group-xs pull-right" role="group" aria-label="...">
                    <button type="button" class="btn btn-default" @click.prevent="addStructureTab(value)">
                        <span :class="util.icon('structure')" aria-hidden="true"></span>
                        <span class="button-title">Structure</span>
                    </button>
                    <button type="button" class="btn btn-default" @click.prevent.blur="openTable(value)">
                        <span :class="util.icon('content')" aria-hidden="true"></span>
                        <span class="button-title">Contents</span>
                    </button>
                </div>
                <span class="title"><p>{{ value }}</p></span>
            </li>
        </ul>
		<field
				v-if="schemas.length > 0"
				:name="'schema_name'"
				:control="'select'"
				:id="'schema_switcher'"
				:value="schema"
				:options="schemaOptions"
				container-class="small-select"
				:disabled="schema && schemas.length < 2"
				@input="changeSchema"
		/>
        <a @click.prevent="showModal = true" class="btn btn-default btn-xs" href="" title="Add new table" role="button">
            <span :class="util.icon('add')" aria-hidden="true"></span>
        </a>
        <a @click.prevent="$emit('refreshTables')" class="btn btn-default btn-xs" href="" title="Refresh tables" role="button">
            <span :class="util.icon('refresh')" aria-hidden="true"></span>
        </a>
		<modal v-if="showModal" @close="showModal = false">
			<h3 slot="header">Add New Table</h3>
			<template slot="body">
				<el-alert
					v-show="newTableError"
					:title="newTableError"
					type="error"
					show-icon
					@close="newTableError = ''"
				>
				</el-alert>
				<app-form :hide-submit="true">
					<field
						:label="'Table Name'"
						:name="'table_name'"
						:control="'el-input'"
						:id="'newTableName'"
						:placeholder="'required'"
						:autofocus="true"
						:value="newTableName"
						@input="e => { newTableName = e.target.value }"
					></field>
				</app-form>
			</template>
			<template slot="footer">
				<v-button @click.prevent="onSubmitNewTable" type="primary" text="Submit"></v-button>
				<v-button @click.prevent="showModal = false" text="Cancel"></v-button>
			</template>
		</modal>
    </div>
</template>

<script>
    export default {
        components: {
            'field': require('../forms/fields/Field')
        },
        props: [ 'schemas', 'schema', 'selectedTable', 'tables', 'table', 'query' ],
        data() {
            return {
                util: window.util,
                list: this.tables,
                prevList: this.tables,
				newTableError: '',
				newTableName: '',
				showModal: false,
				displayList: true
            }
        },
        mounted() {
            window.addEventListener('resize', this.onWindowResize)
        },
        computed: {
            computedList: function () {
                this.onWindowResize()
                let list = this.computeList()
                if (list.length < 1) {
                    list = this.prevList
                } else {
                    this.prevList = list
                }
                return list
            },
			toggleDisplayClass: function() {
                let className = ''
				if (this.displayList) {
                    className = this.util.icon('menu-left')
				} else {
                    className = this.util.icon('menu-right')
				}
				return className
			},
            schemaOptions: function() {
                let options = []
				let schemaCount = this.schemas.length
				for (let i = 0; i < schemaCount; i++) {
                    options.push({
						value: this.schemas[i],
						text: this.schemas[i]
					})
				}
				return options
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
            openTable(table) {
                this.$emit('openTable', table)
            },
			changeSchema(e) {
                this.$emit('changeSchema', e.target.value)
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
            computeList() {
                let list = this.list
				let query = this.query.toLowerCase()
                if (query) {
                    list = this.list.filter(function (item) {
                        return item.toLowerCase().indexOf(query) !== -1
                    })
                }
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
			onDisplayToggle() {
                this.displayList = ! this.displayList
                this.$emit('toggleDisplay', this.displayList)
			},
			onSubmitNewTable() {
				if (_.findIndex(this.tables, this.newTableName) > -1) {
					this.newTableError = 'That column exists'
					return false
				}
				this.executeQuery(renameTable).then(() => {
					this.$emit('addTab', {
						type: 'structure',
						table: this.newTableName,
						isNew: true
					})
				})
			},
            onWindowResize() {
                $(".sidebar .list-group").height($(".sidebar").height() - 60)
            }
        }
    }
</script>

<style lang="scss">
    .list-group {
        /*border-top: 1px solid #e6e6e6;*/
        /*border-bottom: 1px solid #e6e6e6;*/
        margin-bottom: 5px;
        overflow: auto;
        white-space: nowrap;
		li.selected {
			background-color: #e7e7e7;
		}
        li:hover {
            background-color: #e6e6e6;
        }
        li:first-child {
            /*border-top: none;*/
        }
    }
    .list-group > * {
        height: 30px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sidebar .list-group-item {
        background-color: transparent;
        border: none;
        padding: 3px 10px;
        .btn-group {
            visibility: hidden;
        }
    }
    .sidebar .list-group-item:hover .btn-group {
        visibility: visible;
    }
    .sidebar .title p
    {
        margin: 0;
        padding: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
        min-height: 16px;
    }
    .sidebar .title p:hover
    {
        background: #e6e6e6;
        position: relative;
        z-index: 1;
        display: inline-block;
    }
    .sidebar .button-title {
        display: none
    }
	.sidebar .form-group.small-select {
		border-radius: 3px;
		display: inline-block;
		padding: 1px 1px;
		font-size: 12px;
		line-height: 1.5;
		margin: 0;
		width: 200px;

		select {
			height: 23px;
		}
	}
    .list-group-item {
        padding: 3px 3px 3px 8px;
        button:hover {
            .button-title {
                display: inline
            }
        }
    }

	.slide-leave-active,
	.slide-enter-active {
		transition: 1s;
	}
	.slide-enter {
		transform: translate(100%, 0);
	}
	.slide-leave-to {
		transform: translate(-100%, 0);
	}
</style>
