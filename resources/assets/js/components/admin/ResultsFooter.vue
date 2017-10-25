<template>
    <div class="row">
        <div class="col-sm-2">
            <span v-if="state.processing">
                <span class="glyphicon glyphicon-refresh spinning"></span>
            </span>
            <span v-else-if="table">
                <a @click.prevent="$emit('refresh')" href="#"><span class="glyphicon glyphicon-refresh"></span></a>
            </span>
            <span v-if="requestTime" class="request-time">
                {{ requestTimeStr }}
            </span>
        </div>
        <div class="col-sm-10 text-right">
            <el-pagination
                    v-if="pagination && records && records.length"
                    layout="sizes, total, prev, pager, next"
                    :current-page.sync="pagination.current_page"
                    :total="pagination.total"
                    :page-count="pagination.last_page"
                    :page-size="pagination.per_page"
                    :page-sizes="[25, 50, 100, 250, 500]"
                    @size-change="handleSizeChange"
                    @current-change="handleCurrentChange"
            />
        </div>
    </div>
</template>
<script>
    import moment from 'moment'
    export default {
        props: [ 'pagination', 'records', 'requestTime', 'tab', 'table'  ],
        data() {
            return {
                store: window.store,
                state: window.store.state
            }
        },
        computed: {
            requestTimeStr: function () {
                let time = ""
                if (this.requestTime) {
                    if (this.requestTime > 999) {
                        time = moment.duration(this.requestTime, 'seconds').format("ss") + " s"
                    } else {
                        time = moment.duration(this.requestTime) + " ms"
                    }
                }
                return time
            }
        },
        methods: {
            handleSizeChange(val) {
                this.$emit('changePerPage', val)
            },
            handleCurrentChange(val) {
                this.$emit('changePage', val)
            }
        }
    }
</script>