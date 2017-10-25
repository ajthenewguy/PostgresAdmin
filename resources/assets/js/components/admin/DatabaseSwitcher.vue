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
            }
        }
    }
</script>
<style>
    #database-switcher input {
        height: 26px;
    }
    .navbar {
        min-height: 40px;
    }
    .navbar-brand {
        height: 40px;
        padding: 10px 12px;
    }
    @media (min-width: 768px) {
        .navbar-nav > li > a {
            padding-top: 10px;
            padding-bottom: 10px;
        }
    }
</style>