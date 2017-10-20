<script>
    import moment from 'moment'
    export default {
        data() {
            return {
                cacheData: {},
                customQuery: false,
                errors: [],
                history: [],
                order: null,
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
                requestTime: null,
                requestTimeStart: null,
                response: null,
                result: null,
                schema: null,
                server: 'http://postgres:5433',
                sql: '',
                tables: [],
                tablePrimaryKey: '',
                tablePrimaryKeyFormat: '',
                tableForeignKeys: null,
                where: null
            }
        },
        methods: {
            cache(key, value) {
                let argCount = arguments.length
                let returnVal = null
                if (argCount === 1) {
                    if (this.cache.hasOwnProperty(key)) {
                        returnVal = this.cacheData[key]
                    }
                } else if (argCount === 2) {
                    if (value === null) {
                        if (this.cacheData.hasOwnProperty(key)) {
                            delete this.cacheData[key]
                        }
                    } else {
                        returnVal = this.cacheData[key]
                    }
                }
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
                    if (where.constructor === Array) {
                        sql += ' ' + this.makeBindingsWhere(where, conjunction)
                    } else {
                        if (! where.toUpperCase().includes('WHERE')) {
                            sql += ' WHERE '
                        }
                        sql += ' ' + where + ' '
                    }
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
                this.beforeRequestInternal()
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
                    data.per_page = perPage
                } else if (this.pagination.per_page) {
                    data.per_page = this.pagination.per_page
                }
                if (pluck) {
                    data.pluck = pluck
                }
                return axios.post(this.server + '/select', data).then(response => {
                    this.afterRequestInternal()
                    this.querySuccess(response)
                }).catch(error => {
                    this.result = null
                    this.queryError(error)
                }).then(() => {
                    this.afterQuery()
                })
            },
            insertQuery(sql, bindings) {
                this.beforeQuery(sql)
                this.beforeRequestInternal()
                let data = {
                    sql: this.sql
                }
                if (bindings) {
                    data.bindings = bindings
                }
                return axios.post(this.server + '/insert', data).then(response => {
                    this.afterRequestInternal()
                    this.querySuccess(response)
                }).catch(error => {
                    this.queryError(error)
                }).then(() => {
                    this.afterQuery()
                })
            },
            updateQuery(sql, bindings) {
                this.beforeQuery(sql)
                this.beforeRequestInternal()
                let data = {
                    sql: this.sql
                }
                if (bindings) {
                    data.bindings = bindings
                }
                return axios.post(this.server + '/update', data).then(response => {
                    this.afterRequestInternal()
                    this.querySuccess(response)
                }).catch(error => {
                    this.queryError(error)
                }).then(() => {
                    this.afterQuery()
                })
            },
            deleteQuery(sql, bindings) {
                this.beforeQuery(sql)
                this.beforeRequestInternal()
                let data = {
                    sql: this.sql
                }
                if (bindings) {
                    data.bindings = bindings
                }
                return axios.post(this.server + '/delete', data).then(response => {
                    this.afterRequestInternal()
                    this.querySuccess(response)
                }).catch(error => {
                    this.queryError(error)
                }).then(() => {
                    this.afterQuery()
                })
            },
            executeQuery(sql) {
                this.beforeQuery(sql)
                this.beforeRequestInternal()
                return axios.post(this.server + '/execute', {
                    sql: this.sql
                }).then(response => {
                    this.afterRequestInternal()
                    this.querySuccess(response)
                }).catch(error => {
                    this.queryError(error)
                }).then(() => {
                    this.afterQuery()
                })
            },
            loadTable(table) {
//                this.customQuery = false
                if (! this.tables.hasOwnProperty(table)) {
                    this.tables[table] = {
                        name: table,
                        schema: null,
                        primaryKey: null,
                        primaryKeyFormat: null,
                        foreignKeys: null
                    }
                    return this.getTableSchema(table).then(() => {
                        if (this.result[0].length) {
                            this.tables[table].schema = _.clone(this.result[0])
                        }
                        if (this.result[1].length && Object.keys(this.result[1][0]).length) {
                            this.tables[table].primaryKey = this.result[1][0].attname || 'id'
                            this.tables[table].primaryKeyFormat = this.result[1][0].format_type || ''
                        }
                        if (this.result[2].length) {
                            this.tables[table].foreignKeys = _.clone(this.result[2])
                        }
                        return this.tables[table]
                    })
                } else {
                    return Promise.resolve(this.tables[table])
                }
            },
            loadPrimaryKey(table) {
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
                            this.tables[table].primaryKey = this.result[0].attname || 'id'
                            this.tables[table].primaryKeyFormat = this.result[0].format_type || ''
                        }
                    })
            },
            loadSchema(table) {
                let sql = "SELECT " +
                    "column_name, data_type AS type, " +
                    "character_maximum_length AS length, " +
                    "column_default AS default_value, " +
                    "is_nullable AS nullable, " +
                    "is_updatable AS mutable " +
                    "FROM information_schema.columns WHERE " +
                    "table_name = '" + table + "'"
                return this.selectQuery(sql)
                    .then(() => {
                        this.tables[table].schema = _.clone(this.result)
                        this.loadForeignKeys(table)
                    })
            },
            loadForeignKeys(table) {
                let sql = "SELECT \
                    tc.constraint_name, kcu.column_name, \
                        ccu.table_name AS foreign_table_name, \
                        ccu.column_name AS foreign_column_name \
                    FROM \
                    information_schema.table_constraints AS tc \
                    JOIN information_schema.key_column_usage AS kcu \
                    ON tc.constraint_name = kcu.constraint_name \
                    JOIN information_schema.constraint_column_usage AS ccu \
                    ON ccu.constraint_name = tc.constraint_name \
                    WHERE constraint_type = 'FOREIGN KEY' AND tc.table_name='" + table + "'"
                return this.selectQuery(sql)
                    .then(() => {
                        this.tables[table].foreignKeys = _.clone(this.result)
                        this.result = null
                    })
            },
            getTableSchema(table) {
                this.beforeRequestInternal(false)
                return axios.post(this.server + '/schema', {
                    table: table
                }).then(response => {
                    this.afterRequestInternal(false)
                    this.querySuccess(response)
                }).catch(error => {
                    this.queryError(error)
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
            getColumnForeignKey(column) {
                let foreign_key = false
                if (this.tableForeignKeys) {
                    let length = this.tableForeignKeys.length
                    for (let i = 0; i < length; i++) {
                        if (this.tableForeignKeys[i].column_name === column) {
                            foreign_key = {
                                name: this.tableForeignKeys[i].constraint_name,
                                table: this.tableForeignKeys[i].foreign_table_name,
                                column: this.tableForeignKeys[i].foreign_column_name
                            }
                        }
                    }
                }
                return foreign_key
            },
            beforeRequest() {
                ///
            },
            afterRequest() {
                ///
            },
            beforeRequestInternal(track_request_time = true) {
                this.processing = true
                if (track_request_time) {
                    this.requestTime = 0
                    this.requestTimeStart = new Date().getTime()
                }
                this.beforeRequest()
            },
            afterRequestInternal(track_request_time = true) {
                if (track_request_time) {
                    this.requestTime = (new Date().getTime() - this.requestTimeStart)
                }
                this.processing = false
                this.afterRequest()
            },
            beforeQuery(sql) {
                this.sql = sql
            },
            afterQuery() {
                //
            },
            pushHistory(query) {
                if (query !== this.history[this.history.length-1]) {
                    this.history.push(query)
                    if (this.history.length > 15) {
                        this.history = this.history.slice(0, 16)
                    }
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
                    for (let i = 0; i < tokenLength; i++) {
                        token = tokens[i]
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
            },
            requestTimeStr(tab) {
                console.log('deprecated')
                let time = ""
                if (this.requestTime[tab]) {
                    if (this.requestTime[tab] > 999) {
                        time = moment.duration(this.requestTime[tab], 'seconds').format("ss") + " s"
                    } else {
                        time = moment.duration(this.requestTime[tab]) + " ms"
                    }
                }
                return time
            }
        }
    }
</script>
