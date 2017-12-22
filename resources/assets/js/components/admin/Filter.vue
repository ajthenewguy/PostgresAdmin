<template>
    <thead>
        <div id="content-filter">
            <el-input
                placeholder="WHERE clause"
                v-model="where"
                class="input-with-select"
                @keyup.enter.native="$emit('filterWhere', where)"
            >
                <el-button
                        slot="append"
                        icon="el-icon-search"
                        @click="$emit('filterWhere', where)"
                />
            </el-input>
        </div>
    </thead>
</template>
<script>
    export default {
        data() {
            return {
                where: null
            }
        }
    }
</script>
<style lang="scss">
    #content-filter {
        margin-top: 5px;
        padding: 0 20px;
        input {
            height: 30px;
        }
    }
    .el-icon-cstm-filter {
        font-family: 'Glyphicons Halflings' !important;
        font-size: inherit;
        font-style: normal;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    .el-icon-cstm-filter:before {
        content: "\e138";
    }
</style>