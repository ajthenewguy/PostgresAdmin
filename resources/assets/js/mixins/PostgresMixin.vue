<script>
    export default {
        data() {
            return {
                customQuery: false,
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
                response: null,
                result: null,
                schema: null,
                server: 'http://postgres:5433',
                sql: '',
                tablePrimaryKey: '',
                tablePrimaryKeyFormat: ''
            }
        },
        methods: {
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
                                sql += ' ' + property + ' = \'' + bindings[property] + '\''
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
            makeSelect(table, where, conjunction, order) {
                let sql = 'SELECT * FROM ' + table
                if (where) {
                    sql += ' ' + this.makeBindingsWhere(where, conjunction)
                }
                if (! order) {
                    order = this.tablePrimaryKey
                }
                if (order) {
                    sql += ' ORDER BY ' + this.makeOrderBy(order)
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
            makeOrderBy(order) {
                if (!order) {
                    order = ''
                }
                if (order.constructor === Array) {
                    if (order[0].constructor === Array) {
                        let ordering_count = order.length
                        for (let i = 0; i < ordering_count; i++) {
                            order[i] = order[i].join(' ')
                        }
                        order = order.join(', ')
                    } else {
                        order = order.join(' ')
                    }
                }
                return order
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
                this.customQuery = false
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
                    "column_name, data_type AS type, character_maximum_length AS length, column_default AS default_value, is_nullable AS nullable, is_updatable AS mutable " +
                    "FROM information_schema.columns WHERE " +
                    "table_name = '" + table + "'"
                this.customQuery = false
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
                if (this.customQuery) {
                    this.history.push(this.sql)
                }
                if (this.history.length > 15) {
                    this.history = this.history.slice(0, 16)
                }
            },
            querySuccess(response) {
                this.response = response
                this.result = this.parseResponse(response)
            },
            queryError(error) {
                let message = this.parseError(error)
                this.errors.push(message)
                console.error(message)
                if (typeof message === "string") {
                    alert(message)
                }
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
            },
            parseSql(sql) {
                let query = null
                let flags = {
                    table: false,
                    values: false,
                    where: false,
                    order: false
                }
                let lastFlag = null
                let skip = false
                let subQuery = []
                let token = ''
                let tokens = []
                let tokenLength = 0
                if (sql) {
                    tokens = sql.split(' ')
                    tokenLength = tokens.length
                    query = {
                        verb: '',
                        columns: [],
                        table: '',
                        values: [],
                        where: [],
                        order: [],
                        sub: null
                    }
                    query.verb = tokens.shift().toUpperCase()
                    tokenLength = tokens.length
//                    switch(query.verb) {
//                        case 'SELECT': {
                    for (let i = 0; i < tokenLength; i++) {
                        token = tokens[i]


                        // SELECT * FROM table WHERE id=2 AND id<>1 ORDER BY id LIMIT 1


                        // DELETE FROM films WHERE producer_id IN (SELECT id FROM producers WHERE name = 'foo');

                        if (query.verb == 'UPDATE') {
                            flags.table = true
                            lastFlag = 'table'
                            skip = true
                        }

                        switch(token.toUpperCase()) {
                            case 'INTO':
                            case 'FROM': {
                                flags.table = true
                                lastFlag = 'table'
                                skip = true
                                break
                            }
                            case 'SET': {
                                flags.values = true
                                lastFlag = 'set'
                                skip = true
                                break
                            }
                            case 'VALUES': {
                                flags.values = true
                                lastFlag = 'values'
                                skip = true
                                break
                            }
                            case 'WHERE': {
                                flags.where = true
                                lastFlag = 'where'
                                skip = true
                                break
                            }
                            case 'ORDER': {
                                flags.order = true
                                lastFlag = 'order'
                                i++
                                skip = true
                                break
                            }
                            case 'IN': {
                                let j = i
                                skip = true
                                i++
                                j++
                                if (tokens[j] === '(') {
                                    for (; j < tokens.length; j++, i++) {
                                        if (token !== ')') {
                                            subQuery.push(tokens[j])
                                        } else {
                                            query.sub = this.parseSql(subQuery.join(' '))
                                            break
                                        }
                                    }
                                } else {
                                    this.validationError('Sub queries must be enclosed in parentheses')
                                    return
                                }
                                break
                            }
                        }

                        if (skip) {
                            skip = false
                            continue
                        }

                        // INSERT INTO films
//                                    VALUES ('UA502', 'Bananas', 105, DEFAULT, 'Comedy', '82 minutes');


                        // INSERT INTO films (code, title, did, date_prod, kind)
//                                    VALUES ('T_601', 'Yojimbo', 106, DEFAULT, 'Drama');


                        // UPDATE weather SET (temp_lo, temp_hi, prcp) = (temp_lo+1, temp_lo+15, DEFAULT)
//                                    WHERE city = 'San Francisco' AND date = '2003-07-03';

                        switch(lastFlag) {
                            case null: {
                                query.columns.push(token)
                                break
                            }
                            case 'table': {
                                query.table = token

                                if (tokens[i+1] === '(') {
                                    let j = i
                                    i++
                                    j++
                                    for (; j < tokens.length; j++, i++) {
                                        if (token !== ')') {
                                            query.columns.push(tokens[j])
                                        } else {
                                            break
                                        }
                                    }
                                }
                                break
                            }
                            case 'set':
                            case 'values': {
                                let j = i
                                if (tokens[j] === '(') {
                                    i++
                                    j++
                                }
                                for (; j < tokens.length; j++, i++) {
                                    if (token !== ')') {
                                        query.values.push(tokens[j])
                                    } else {
                                        break
                                    }
                                }
                                break
                            }
                            case 'where': {
                                console.log(query)
                                query.where.push(token)
                                break
                            }
                            case 'order': {
                                query.order.push(token)
                                break
                            }
                        }
                    }
                }
                return query
            }
        }
    }
</script>
