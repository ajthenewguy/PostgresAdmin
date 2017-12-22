<template>
    <div>
        <br>
        <el-table
                v-if="table && tableForeignKeys && tableForeignKeys[0]"
                :data="tableForeignKeys"
                border
                style="width: 100%">
            <el-table-column
                    v-for="(value, name) in tableForeignKeys[0]"
                    :key="name"
                    :prop="name"
                    :label="util.titleCase(name)"
            />
        </el-table>
    </div>
</template>

<script>
    export default {
        props: [ 'table', 'tableForeignKeys', 'processing' ],
        data() {
            return {
                bus: window.bus,
                store: window.store,
                state: window.store.state,
                util: window.util
			}
		}
    }
</script>
