<template>
    <transition name="fade">
<!--
        | Column Name | Type | Length | Not Null | Default Value |
        ----------------------------------------------------------
        | id          | int8 | 19     | true     | nextval()     |
-->
        <table v-if="table && schema" class="table table-hover table-striped table-condensed">
            <thead>
                <tr>
                    <th v-for="(value, name) in schema[0]">{{ name }}</th>
                </tr>
            </thead>
            <tbody>
            <tr v-for="fields in schema">
                <td v-for="(value, name) in fields">{{ value }}</td>
            </tr>
            </tbody>
        </table>
        <div v-else>
            <div v-if="!processing" class="empty">
                No table selected
            </div>
        </div>
    </transition>
</template>

<script>
    export default {
        props: [ 'table', 'schema', 'processing' ]
    }
</script>