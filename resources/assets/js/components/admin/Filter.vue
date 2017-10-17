<template>
    <div class="row">
        <div class="col-sm-12">
            <el-input
                id="content-filter"
                placeholder="WHERE clause"
                v-model="where"
                @keyup.enter.native="$emit('filterWhere', where)"
            >
                <el-button
                    slot="append"
                    icon="cstm-filter"
                    @click="$emit('filterWhere', where)"
                />
            </el-input>
        </div>
    </div>
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