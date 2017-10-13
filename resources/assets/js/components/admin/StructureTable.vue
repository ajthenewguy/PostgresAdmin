<template>
    <div>
        <br>
        <el-table
                v-if="table && schema"
                :data="schema"
                border
                style="width: 100%">
            <el-table-column
                    v-for="(value, name) in schema[0]"
                    :key="name"
                    :prop="name"
                    :label="titleCase(name)"
            />
        </el-table>
        <div v-else>
            <div v-if="!processing" class="empty">
                No table selected
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: [ 'table', 'schema', 'processing' ],
        methods: {
            titleCase(string) {
                return string.replace('_', ' ').replace(/(^[a-z])|(\s+[a-z])/g, txt => txt.toUpperCase())
            }
        }
    }
</script>