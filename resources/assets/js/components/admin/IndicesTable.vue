<template>
    <div>
        <br>
        <el-table
                v-if="table && tableForeignKeys"
                :data="tableForeignKeys"
                border
                style="width: 100%">
            <el-table-column
                    v-for="(value, name) in tableForeignKeys[0]"
                    :key="name"
                    :prop="name"
                    :label="$parent.titleCase(name)"
            />
        </el-table>
    </div>
</template>

<script>
    export default {
        props: [ 'table', 'tableForeignKeys', 'processing' ]
    }
</script>