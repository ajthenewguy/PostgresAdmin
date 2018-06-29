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
        props: ['columns', 'find'],
        created: function () {

        },
        data() {
            return {
                where: this.find,
                autocomplete: {
                    options: this.columns || Object.keys(window.store.state.tables),
                    selector: '.el-input__inner',
                    acceptKeys: [$.asuggestKeys.SPACE], // [$.asuggestKeys.RETURN, $.asuggestKeys.SPACE] : SHIFT, CTRL, ALT, LEFT, UP, RIGHT, DOWN, DEL, TAB, RETURN, ESC, COMMA, PAGEUP, PAGEDOWN, BACKSPACE and SPACE
                    cycleOnTab: false,
                    endingSymbols: ' ', // "space" is default
                    minChunkSize: 1,
                    delimeters: '\n ' // array of chars
                },
                store: window.store,
                state: window.store.state,
                util: window.util
            }
        },
        watch: {
            find: function() {
                if (this.find !== null) {
                    this.where = this.find
                    // eslint-disable-next-line
                    console.log(this.find, this.where)
                }
            }
        },
        mixins: [require('../../mixins/AutoComplete')]
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