<script>
    export default {
        data() {
            return {
                errors: [],
                history: [],
                pagination: {
                    current_page: 1,
                    first_page_url: '',
                    from: null,
                    last_page: null,
                    last_page_url: '',
                    next_page_url: '',
                    path: '',
                    per_page: null,
                    prev_page_url: '',
                    to: null,
                    total: 0
                },
                processing: false,
                result: null,
                schema: null,
                server: 'http://postgres:5433',
                sql: ''
            }
        },
        methods: {
            makeBindingsWhere(bindings, conjunction) {
                let sql = ''
                if (! conjunction) {
                    conjunction = 'AND'
                }
                conjunction = ' ' + conjunction.trim() + ' '
                if (bindings) {
                    sql += 'WHERE '
                    for (var property in bindings) {
                        if (bindings.hasOwnProperty(property)) {
                            sql += ' ' + property + ' = :' + property
//                            sql += ' ' + property + ' = ?'
                            sql += conjunction
                        }
                    }
                    if (sql.endsWith(conjunction)) {
                        sql = sql.substr(0, sql.lastIndexOf(conjunction))
                    }
                }
                return sql.trim()
            },
            makeWhere(bindings, conjunction) {
                let sql = ''
                if (! conjunction) {
                    conjunction = 'AND'
                }
                conjunction = ' ' + conjunction.trim() + ' '
                if (bindings) {
                    sql += 'WHERE '
                    for (var property in bindings) {
                        if (bindings.hasOwnProperty(property)) {
                            if (isNaN(bindings[property])) {
                                sql += ' ' + property + ' = "' + bindings[property] + '"'
                            } else {
                                sql += ' ' + property + ' = ' + bindings[property]
                            }
                            sql += conjunction
                        }
                    }
                    if (sql.endsWith(conjunction)) {
                        sql = sql.substr(0, sql.lastIndexOf(conjunction))
                    }
                }
                return sql.trim()
            },
            getKeysValues(data) {
                let keys = []
                let values = []
                for (var property in data) {
                    if (data.hasOwnProperty(property)) {
                        keys.push(property)
                        values.push(data[property])
                    }
                }
                return [ keys, values ]
            },
            makeSelect(table, bindings, conjunction) {
                let sql = 'SELECT * FROM ' + table
                if (bindings) {
                    sql += ' ' + this.makeBindingsWhere(bindings, conjunction)
                }
                return sql.trim()
            },
            makeInsert(table, data) {
                let sql = 'INSERT INTO ' + table
                if (data) {
                    let keysValues = this.getKeysValues(data)
                    sql += ' (' + keysValues[0].join(', ') + ') '
                    sql += 'VALUES'
                    sql += ' (:' + keysValues[0].join(', :') + ') '
//                    sql += ' (' + '?, '.repeat(keysValues[1].length).slice(0, -2) + ') '
                    if (sql.endsWith(', :')) {
                        sql = sql.substr(0, sql.lastIndexOf(', :'))
                    }
                } else {
                    this.validationError('INSERT statements require data')
                }
                return sql.trim()
            },
            makeUpdate(table, data, where, conjunction) {
                let sql = 'UPDATE ' + table + ' SET'
                if (data) {
                    let keysValues = this.getKeysValues(data)
                    sql += ' (' + keysValues[0].join(', ') + ') '
                    sql += '='
                    sql += ' (:' + keysValues[0].join(', :') + ') '
//                    sql += ' (' + '?, '.repeat(keysValues[1].length).slice(0, -2) + ') '
                    if (sql.endsWith(', :')) {
                        sql = sql.substr(0, sql.lastIndexOf(', :'))
                    }
                } else {
                    this.validationError('UPDATE statements require data')
                }
                if (where) {
                    sql += ' ' + this.makeWhere(where, conjunction)
                }
                return sql.trim()
            },
            makeDelete(table, where, conjunction) {
                let sql = 'DELETE FROM ' + table
                if (where) {
                    sql += ' ' + this.makeBindingsWhere(where, conjunction)
                } else {
                    this.validationError('DELETE statements require a WHERE condition object')
                }
                return sql.trim()
            },
            selectQuery(sql, page, bindings, perPage, pluck) {
                this.beforeQuery(sql)
                let data = {
                    sql: this.sql
                }
                if (page) {
                    data.page = page
                }
                if (bindings) {
                    data.bindings = bindings
                }
                if (perPage) {
                    data.perPage = perPage
                }
                if (pluck) {
                    data.pluck = pluck
                }
                return axios.post(this.server + '/select', data).then(response => {
                    this.querySuccess(response)
                }).catch(error => {
                    this.queryError(error)
                }).then(() => {
                    this.afterQuery()
                })
            },
            insertQuery(sql, bindings) {
                this.beforeQuery(sql)
                let data = {
                    sql: this.sql
                }
                if (bindings) {
                    data.bindings = bindings
                }
                return axios.post(this.server + '/insert', data).then(response => {
                    this.querySuccess(response)
                }).catch(error => {
                    this.queryError(error)
                }).then(() => {
                    this.afterQuery()
                })
            },
            updateQuery(sql, bindings) {
                this.beforeQuery(sql)
                let data = {
                    sql: this.sql
                }
                if (bindings) {
                    data.bindings = bindings
                }
                return axios.post(this.server + '/update', data).then(response => {
                    this.querySuccess(response)
                }).catch(error => {
                    this.queryError(error)
                }).then(() => {
                    this.afterQuery()
                })
            },
            deleteQuery(sql, bindings) {
                this.beforeQuery(sql)
                let data = {
                    sql: this.sql
                }
                if (bindings) {
                    data.bindings = bindings
                }
                return axios.post(this.server + '/delete', data).then(response => {
                    this.querySuccess(response)
                }).catch(error => {
                    this.queryError(error)
                }).then(() => {
                    this.afterQuery()
                })
            },
            executeQuery(sql) {
                this.beforeQuery(sql)
                return axios.post(this.server + '/execute', {
                    sql: this.sql
                }).then(response => {
                    this.querySuccess(response)
                }).catch(error => {
                    this.queryError(error)
                }).then(() => {
                    this.afterQuery()
                })
            },
            getPrimaryKey(table) {
                let sql = "SELECT \
                    pg_attribute.attname, \
                    pg_attribute.attlen, \
                    format_type(pg_attribute.atttypid, pg_attribute.atttypmod) \
                FROM pg_index, pg_class, pg_attribute, pg_namespace \
                WHERE \
                pg_class.oid = '" + table + "'::regclass AND \
                indrelid = pg_class.oid AND \
                nspname = 'public' AND \
                pg_class.relnamespace = pg_namespace.oid AND \
                pg_attribute.attrelid = pg_class.oid AND \
                pg_attribute.attnum = any(pg_index.indkey) \
                AND indisprimary"
                return this.selectQuery(sql)
                    .then(() => {
                        if (this.result.length) {
                            this.tablePrimaryKey = this.result[0].attname || 'id'
                            this.tablePrimaryKeyFormat = this.result[0].format_type || ''
                        }
                    })
            },
            getSchema(table) {
                let sql = "SELECT " +
                    "column_name, table_name, data_type, udt_name, column_default, is_nullable, is_updatable " +
                    "FROM information_schema.columns WHERE " +
                    "table_name = '" + table + "'"
                return this.selectQuery(sql)
                    .then(() => {
                        this.schema = _.clone(this.result)
                        this.result = null
                    })
            },
            getColumn(column) {
                let info = {}
                if (this.schema) {
                    let length = this.schema.length
                    for (let i = 0; i < length; i++) {
                        if (this.schema[i].column_name === column) {
                            info = this.schema[i]
                        }
                    }
                }
                return info
            },
            beforeQuery(sql) {
                this.processing = true
                this.sql = sql
            },
            afterQuery() {
                this.processing = false
                this.history.push(this.sql)
                if (this.history.length > 15) {
                    this.history = this.history.slice(0, 16)
                }
            },
            querySuccess(response) {
                this.result = this.parseResponse(response)
            },
            queryError(error) {
                let message = this.parseError(error)
                this.errors.push(message)
                console.error(message)
                alert(message)
            },
            validationError(message) {
                this.errors.push(message)
                console.error(message)
                alert(message)
            },
            parseError(error) {
                let errorText = 'Unknown Error'
                if (error) {
                    errorText = error
                    if (error.response) {
                        errorText = error.response.statusText
                        if (error.response.status === 419) {
                            window.location = '/'
                        }
                        if (error.response.data) {
                            errorText = error.response.data
                            if (error.response.data.message) {
                                errorText = error.response.data.message
                            }
                        }
                    }
                }
                return errorText
            },
            parsePagination(response) {
                let pagination = (({
                   current_page,
                   first_page_url,
                   from,
                   last_page,
                   last_page_url,
                   next_page_url,
                   path,
                   per_page,
                   prev_page_url,
                   to,
                   total
               }) => ({
                    current_page,
                    first_page_url,
                    from,
                    last_page,
                    last_page_url,
                    next_page_url,
                    path,
                    per_page,
                    prev_page_url,
                    to,
                    total
                }))(response.data)
                return pagination
            },
            parseResponse(response) {
                let data = null
                if (response) {
                    if (response.data) {
                        data = response.data
                        if (response.data.current_page) {
                            this.pagination = this.parsePagination(response)
                        }
                        if (response.data.data) {
                            data = response.data.data
                        }
                        if (data !== Array) {
                            data = Object.values(data)
                        }
                    }
                }
                return data
            }
        }
    }
</script>
