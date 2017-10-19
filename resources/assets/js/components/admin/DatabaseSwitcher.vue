<template>
    <div class="form-group">
        <label class="control-label">Database:</label>
        <el-select
                id="database-switcher"
                v-model="database"
                placeholder="Database"
                @change="openDatabase()"
        >
            <el-option
                    v-for="item in databases"
                    :key="item"
                    :label="item"
                    :value="item"
            />
        </el-select>
    </div>
</template>

<script>
    export default {
        props: ['databases', 'selectedDatabase'],
        data() {
            return {
                database: this.selectedDatabase
            }
        },
        methods: {
            openDatabase() {
                window.location = "/" + this.database
//                this.tableQuery = ''
//                return axios.post(this.server + '/switch-database', { database: this.database }).then(response => {
//                    this.tables = response.data.tables
//                    window.history.pushState({ /* stateObj */}, "PostgresAdmin", "/" + this.database);
//                }).catch(error => {
//                    this.result = null
//                    this.queryError(error)
//                })
            }
        }
    }
</script>