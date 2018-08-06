<script>
    import moment from 'moment'
    export default {
        data() {
            return {
                store: window.store,
                state: window.store.state,
                useElementUI: true,
                customQuery: false,
                filterInformationSchema: true,
                filterSystemSchemas: true,
                pagination: {
                    current_page: 1,
                    first_page_url: '',
                    from: null,
                    last_page: null,
                    last_page_url: '',
                    next_page_url: '',
                    path: '',
                    per_page: 25,
                    prev_page_url: '',
                    to: null,
                    total: 0
                },
                requestTime: null,
                requestTimeStart: null,
                response: null,
                result: null,
                sql: '',
                dataTypes: [
					{
                        name: 'ARRAY',
                        aliases: [ 'array', 'arr' ],
                        description: 'collection of values',
                        interfaceFacet: 'multi-input-text'
                    }, {
                        name: 'bigint',
                        aliases: [ 'int8' ],
                        description: 'signed eight-byte integer',
                        interfaceFacet: 'el-input'
                    }, {
                        name: 'bigserial',
                        aliases: [ 'serial8' ],
                        description: 'autoincrementing eight-byte integer',
                        interfaceFacet: 'el-input'
                    }, {
                        name: 'bit',
                        description: 'fixed-length bit string',
                        interfaceFacet: 'el-input',
                        size: 1
                    }, {
                        name: 'bit varying',
                        aliases: [ 'varbit' ],
                        description: 'variable-length bit string',
                        interfaceFacet: 'el-input',
                        size: null
                    }, {
                        name: 'boolean',
                        aliases: [ 'bool' ],
                        description: 'logical Boolean (true/false)',
                        interfaceFacet: 'el-checkbox'
                    }, {
                        name: 'character',
                        aliases: [ 'char' ],
                        description: 'fixed-length character string',
                        interfaceFacet: 'el-input',
                        size: 1
                    }, {
                        name: 'character varying',
                        aliases: [ 'varchar' ],
                        description: 'variable-length character string',
                        interfaceFacet: 'el-input',
                        size: null
                    }, {
                        name: 'cidr',
                        aliases: [ 'ip' ],
                        description: 'IPv4 or IPv6 network address',
                        interfaceFacet: 'el-input'
                    }, {
                        name: 'date',
                        description: 'calendar date (year, month, day)',
                        interfaceFacet: 'el-date-picker'
                    }, {
                        name: 'double precision',
                        aliases: [ 'float8', 'double' ],
                        description: 'double precision floating-point number (8 bytes)',
                        interfaceFacet: 'el-input'
                    }, {
                        name: 'integer',
                        aliases: [ 'int', 'int4' ],
                        description: 'signed four-byte integer',
                        interfaceFacet: 'el-input'
                    }, {
                        name: 'interval',
                        description: 'time span',
                        interfaceFacet: 'el-input',
                        optionLabel: 'Fields',
                        options: [
                            'YEAR',
                            'MONTH',
                            'DAY',
                            'HOUR',
                            'MINUTE',
                            'SECOND',
                            'YEAR TO MONTH',
                            'DAY TO HOUR',
                            'DAY TO MINUTE',
                            'DAY TO SECOND',
                            'HOUR TO MINUTE',
                            'HOUR TO SECOND',
                            'MINUTE TO SECOND'
                        ],
                        precision: 6 // applies only to seconds
                    }, {
                        name: 'json',
                        description: 'textual JSON data',
                        interfaceFacet: 'el-input'
                    }, {
                        name: 'jsonb',
                        description: 'binary JSON data, decomposed',
                        interfaceFacet: 'el-input'
                    }, {
                        name: 'money',
                        description: 'currency amount',
                        interfaceFacet: 'el-input'
                    }, {
                        name: 'numeric',
                        aliases: [ 'decimal' ],
                        description: '',
                        interfaceFacet: 'el-input',
                        precision: null, // (12.3) -> 3
                        scale: 0         // 12.(3) -> 1
                    }, {
                        name: 'real',
                        aliases: [ 'float4' ],
                        description: 'single precision floating-point number (4 bytes)',
                        interfaceFacet: 'el-input'
                    }, {
                        name: 'smallint',
                        aliases: [ 'int2' ],
                        description: 'signed two-byte integer',
                        interfaceFacet: 'el-input'
                    }, {
                        name: 'smallserial',
                        aliases: [ 'serial2' ],
                        description: 'autoincrementing two-byte integer',
                        interfaceFacet: 'el-input'
                    }, {
                        name: 'serial',
                        aliases: [ 'serial4' ],
                        description: 'autoincrementing four-byte integer',
                        interfaceFacet: 'el-input'
                    }, {
                        name: 'text',
                        description: 'variable-length character string',
                        interfaceFacet: 'el-input'
                    }, {
                        name: 'time',
                        aliases: [ 'timetz' ],
                        description: 'time of day',
                        interfaceFacet: 'el-date-picker',
                        optionLabel: 'With time zone',
                        options: [
                            'with time zone',
                            'without time zone'
                        ]
                    }, {
                        name: 'timestamp',
                        aliases: [ 'timestamptz' ],
                        description: 'date and time',
                        interfaceFacet: 'el-date-picker',
                        optionLabel: 'With time zone',
                        options: [
                            'with time zone',
                            'without time zone'
                        ]
                    }, {
                        name: 'uuid',
                        description: 'universally unique identifier',
                        interfaceFacet: 'el-input'
                    }, {
                        name: 'xml',
                        description: 'XML data',
                        interfaceFacet: 'el-input'
                    }
                ],
				foreignKeyRules: [
					{
						text: "no action",
						value: ""
					}, {
						text: "cascade",
						value: "CASCADE"
					}, {
						text: "restrict",
						value: ""
					}, {
						text: "set null",
						value: "SET NULL"
					}, {
						text: "set default",
						value: "SET DEFAULT"
					}
				]
            }
        },
        methods: {
            interfaceFacet(type) {
                let interfaceFacet = 'input'
                if (type.includes('timestamp ') || type.includes('time ')) {
                    type = type.split(' ')[0]
                }
                type = _.find(this.dataTypes, [ 'name', type ])
                if (type) {
                    interfaceFacet = type.interfaceFacet
                    if (this.useElementUI !== true) {
                        switch (type) {
                            case 'el-input':
                            case 'el-checkbox':
                            case 'el-date-picker': {
                                type = 'input'
                                break
                            }
                        }
                    }
                }
                return interfaceFacet
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
            isStringDataType(type) {
                let is_string = false
                if (type.indexOf('(') > -1) {
                    type = type.substring(0, type.indexOf('('))
                }
                switch (type) {
                    case 'bit':
                    case 'bit varying':
                    case 'character':
                    case 'character varying':
                    case 'cidr':
                    case 'date':
                    case 'json':
                    case 'jsonb':
                    case 'time':
                    case 'timestamp':
                    case 'uuid':
                    case 'xml': {
                        is_string = true
                        break
                    }
                }
                return is_string
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
                    if (where.constructor === Object) {
                        sql += ' ' + this.makeBindingsWhere(where, conjunction)
                    } else {
                        if (! where.toUpperCase().includes('WHERE')) {
                            sql += ' WHERE '
                        }
                        sql += ' ' + where + ' '
                    }
                }
                if (! order) {
                    order = this.getTablePrimaryKey(table)
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
                    sql += ' ROW(:' + keysValues[0].join(', :') + ') '
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
                return axios.post('/select', data).then(response => {
                    this.querySuccess(response)
                }).catch(error => {
                    this.result = null
                    this.queryError(error)
                }).then(() => {
                    this.afterRequestInternal()
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
                return axios.post('/insert', data).then(response => {
                    this.querySuccess(response)
                }).catch(error => {
                    this.queryError(error)
                }).then(() => {
                    this.afterRequestInternal()
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
                return axios.post('/update', data).then(response => {
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
                return axios.post('/delete', data).then(response => {
                    this.querySuccess(response)
                }).catch(error => {
                    this.queryError(error)
                }).then(() => {
                    this.afterRequestInternal()
                })
            },
            executeQuery(sql) {
                this.beforeQuery(sql)
                this.beforeRequestInternal()
                return axios.post('/execute', {
                    sql: this.sql
                }).then(response => {
                    this.querySuccess(response)
                }).catch(error => {
                    this.queryError(error)
                }).then(() => {
                    this.afterRequestInternal()
                })
            },
            initTableObject(name) {
                return {
                    name: name,
                    schema: null,
                    primaryKey: null,
                    primaryKeyFormat: null,
                    foreignKeys: null
                }
            },
            loadSchemas() {
                return this.selectQuery("SELECT schema_name AS name FROM information_schema.schemata").then(() => {
                    let schemas = []
                    let schema_count = this.result.length
                    for (let i = 0; i < schema_count; i++) {
                        if (this.filterInformationSchema && this.result[i].name === 'information_schema') {
                            continue
                        }
                        if (this.filterSystemSchemas && this.result[i].name.indexOf('pg_') > -1) {
                            continue
                        }
                        schemas.push(this.result[i].name)
                    }
                    return schemas
                })
            },
            loadTable(table, bustcache) {
                if (typeof table === "object" && table.hasOwnProperty('name')) {
                    table = table.name
                }
                if (bustcache || ! this.state.tables.hasOwnProperty(table) || this.state.tables[table].schema === null) {

                    console.log('loadTable FRESH:', table)

                    return this.loadTableSchema(table)
                } else {

                    console.log('loadTable CACHED:', table)

                    return Promise.resolve(this.state.tables[table])
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
                            this.state.tables[table].primaryKey = this.result[0].attname || 'id'
                            this.state.tables[table].primaryKeyFormat = this.result[0].format_type || ''
                        }
                    })
            },
            loadSchema(table) {

                console.log('PostgresMixin.loadSchema() - deprecate?')

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
                        this.state.tables[table].schema = _.clone(this.result)
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
                        this.state.tables[table].foreignKeys = _.clone(this.result)
                        this.result = null
                    })
            },
            loadTableSchema(table) {
                let trackRequestTime = false
                this.beforeRequestInternal(trackRequestTime)
                return axios.post('/schema', {
                    table: table
                }).then(response => {
                    this.querySuccess(response)

                    if (this.result !== null) {
                        this.state.tables[table] = this.initTableObject(table)
                        if (this.result[0].length) {
                            this.state.tables[table].schema = _.clone(this.result[0])
                        }
                        if (this.result[1].length && Object.keys(this.result[1][0]).length) {
                            this.state.tables[table].primaryKey = this.result[1][0].attname || 'id'
                            this.state.tables[table].primaryKeyFormat = this.result[1][0].format_type || ''
                        }
                        if (this.result[2].length) {
                            this.state.tables[table].foreignKeys = _.clone(this.result[2])
                        }
                        if (this.result[3].length) {
                            this.state.tables[table].indexes = _.clone(this.result[3])
                        }
                    }
                    return this.state.tables[table]
                }).catch(error => {
                    this.queryError(error)
                }).then(() => {
                    this.afterRequestInternal(trackRequestTime)
                })
            },
            getTableSchema(table) {
                let schema = null
                if (this.state.tables[table] && this.state.tables[table].schema) {
                    schema = this.state.tables[table].schema
                }
                return schema
            },
            getTablePrimaryKey(table) {
                if (this.state.tables[table] && this.state.tables[table].hasOwnProperty('primaryKey')) {
                    return this.state.tables[table].primaryKey
                }
            },
            getTablePrimaryKeyFormat(table) {
                if (this.state.tables[table].hasOwnProperty('primaryKeyFormat')) {
                    return this.state.tables[table].primaryKeyFormat
                }
            },
            getTableForeignKeys(table) {
                if (this.state.tables[table].hasOwnProperty('foreignKeys')) {
                    return this.state.tables[table].foreignKeys
                }
            },
            getTableIndexes(table) {
                if (this.state.tables[table].hasOwnProperty('indexes')) {
                    return this.state.tables[table].indexes
                }
            },
            getColumn(table, column) {
                let info = {}
                if (this.state.tables[table] && this.state.tables[table].schema) {
                    let length = this.state.tables[table].schema.length
                    for (let i = 0; i < length; i++) {
                        if (this.state.tables[table].schema[i].column_name === column) {
                            info = this.state.tables[table].schema[i]
                        }
                    }
                }
                return info
            },
            getColumnForeignKey(table, column) {
                let foreign_key = false
                if (this.state.tables[table].foreignKeys) {
                    let length = this.state.tables[table].foreignKeys.length
                    for (let i = 0; i < length; i++) {
                        if (this.state.tables[table].foreignKeys[i].column_name === column) {
                            foreign_key = {
                                name: this.state.tables[table].foreignKeys[i].constraint_name,
                                table: this.state.tables[table].foreignKeys[i].foreign_table_name,
                                column: this.state.tables[table].foreignKeys[i].foreign_column_name
                            }
                        }
                    }
                }
                return foreign_key
            },
            getDefaultSort(table) {
                let sortBy = table.primaryKey
                let sortByFallbacks = [
                    'id', 'created_at', 'updated_at', 'code', 'name', 'reviewed_on'
                ]
                let sortByFallbacksCount = sortByFallbacks.length
                if (table.primaryKeyFormat === "uuid") {
                    let columnCount = table.schema.length
                    if (sortBy === table.primaryKey) {
                        for (let h = 0; h < sortByFallbacksCount; h++) {
                            for (let i = 0; i < columnCount; i++) {
                                if (sortBy === table.primaryKey && sortByFallbacks[h] === table.schema[i].column_name) {
                                    sortBy = table.schema[i].column_name
                                }
                            }
                        }
                    }
                }
                return sortBy
            },
            beforeRequest() {
                ///
            },
            afterRequest() {
                ///
            },
            beforeRequestInternal(track_request_time = true) {
                this.store.setProcessing(true)
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
                this.store.setProcessing(false)
                this.afterRequest()
            },
            beforeQuery(sql) {
                this.sql = sql
            },
            afterQuery() {
                // eslint-disable-next-line
                console.log('afterQuery() ... ')
            },
            querySuccess(response) {
                this.response = response
                this.result = this.parseResponse(response)
            },
            queryError(error) {
                let message = this.parseError(error)
                this.state.errors.push(message)
                console.error(message)
                if (typeof message === "string") {
					this.$notify.error({
						title: 'Database Error',
						message: message,
						type: 'success'
					})
                }
            },
            validationError(message) {
                this.state.errors.push(message)
                console.error(message)
                alert(message)
            },
            parseError(error) {
                let errorText = 'Unknown Error'
                if (error) {
                    errorText = error
                    if (error.response) {
                        errorText = error.response.statusText
                        if (error.response.status === 419 || error.response.status === 401) {
                            this.bus.$emit('expiredSession')
                            return
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
                let nextToken = ''
                let nextNextToken = ''
                let tokens = []
                let tokenLength = 0
                if (sql) {
                    tokens = this.tokenizeSql(sql)
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
                        nextToken = tokens[i + 1]
                        nextNextToken = tokens[i + 2]
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
                                let h = i - 1
                                let j = i
                                let prevToken = tokens[h]
                                let tokenIn = tokens[j]
                                let nextTokenIn = tokens[j + 1]
                                let nextNextTokenIn = tokens[j + 2]
                                let isSubQuery = false
                                let whereIndex = query.where.length
                                skip = true
                                // i++
                                // j++
                                if (nextTokenIn === 'NOT') {
                                    // i++
                                    j++
                                    nextTokenIn = tokens[j + 1]
                                    nextNextTokenIn = tokens[j + 2]
                                }
                                isSubQuery = nextTokenIn && nextTokenIn.toUpperCase() === 'SELECT'
                                if (tokenIn === '(') {
                                    for (; j < tokens.length; j++) {
                                        if (token !== ')' && token !== ',') {
                                            if (isSubQuery) {
                                                subQuery.push(tokenIn)
                                            } else {
                                                if (whereIndex === 0) {
                                                    query.where[whereIndex] = ''
                                                }
                                                query.where[whereIndex] += ' ' + tokenIn
                                            }
                                        } else {
                                            query.sub = this.parseSql(subQuery.join(' '))
                                            break
                                        }
                                    }
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
                                        if (token !== ')' && token !== ',') {
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
                                    if (token !== ')' && token !== ',') {
                                        query.values.push(tokens[j])
                                    } else {
                                        break
                                    }
                                }
                                break
                            }
                            case 'where': {
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
            tokenizeSql(sql) {
                let tokens = []

                sql = this.tokenizeSqlPadAssignmentOperators(sql)
                tokens = sql.split(' ')
                tokens = this.tokenizeSqlCommas(tokens)
                tokens = this.tokenizeSqlStatementTerminator(tokens)
                tokens = this.tokenizeSqlParens(tokens)
                tokens = this.tokenizeSqlStringConcatenation(tokens)

                return tokens
            },
            tokenizeSqlPadAssignmentOperators(sql) {
                let re = /(\S)=(\S)/g
                return sql.replace(re, '$1 = $2')
            },
            tokenizeSqlCommas(tokens) {
                let tokensLength = tokens.length
                for (let i = 0; i < tokensLength; i++) {
                    let token = tokens[i]
                    if (typeof token === 'undefined' || token === '' || token === ',') {
                        continue
                    }
                    if (token.charAt(token.length - 1) === ',') {
                        tokens[i] = token.slice(0, -1)
                        tokens.splice(i + 1, 0, ',')
                    }
                }
                return tokens
            },
            tokenizeSqlParens(tokens) {
                let tokensLength = tokens.length
                let inParens = 0

                // Parenthesis separation
                for (let i = 0; i < tokensLength; i++) {
                    let token = tokens[i]
                    if (typeof token === 'undefined' || token === '' || token === '(' || token === ')') {
                        continue
                    }
                    let tokenLength = token.length
                    let lastChar = token.charAt(tokenLength - 1)

                    if (token.indexOf('(') > -1) {
                        inParens++
                        let subtokens = tokens[i].split('(')
                        tokens[i] = subtokens[0] + '('
                        tokens.splice(i + 1, 0, subtokens[1])
                        continue
                    }
                    if (lastChar === ')' && inParens > 0) {
                        inParens--
                        tokens[i] = tokens[i].slice(0, -1)
                        tokens.splice(i + 1, 0, ')')
                        continue
                    }
                }
                return tokens
            },
            tokenizeSqlStatementTerminator(tokens) {
                let tokensLength = tokens.length
                let lastTokenIndex = tokensLength - 1
                let token = tokens[lastTokenIndex]
                let tokenLength = token.length

                if (token.charAt(tokenLength - 1) === ';') {
                    tokens[lastTokenIndex] = token.slice(0, -1)
                    tokens.splice(tokensLength, 0, ';')
                }
                return tokens
            },
            tokenizeSqlStringConcatenation(tokens) {
                let tokensLength = tokens.length
                let firstBufferEntry = false
                let stringStartIndex = null
                let inString = false
                let buffer = ''

                // String token concatenation
                for (let i = 0; i < tokensLength; i++) {
                    let token = tokens[i]
                    if (typeof token === 'undefined' || token === '') {
                        continue
                    }
                    let tokenLength = token.length
                    let firstChar = token.charAt(0)
                    let lastChar = token.charAt(tokenLength - 1)

                    // short-circuit self-contained string tokens
                    if ((firstChar === '\'' && lastChar === '\'') || firstChar === '"' && lastChar === '"') {
                        continue
                    }

                    if (firstChar === '\'' || firstChar === '"') {
                        if (inString === false) {
                            inString = firstChar
                            stringStartIndex = i
                        } else if (inString === firstChar) {
                            inString = false
                        }
                    }

                    if (inString) {
                        if (buffer.length > 0) {
                            firstBufferEntry = false
                            buffer += ' '
                        } else {
                            firstBufferEntry = true
                        }
                        buffer += '' + token
                        if (!firstBufferEntry) {
                            tokens.splice(i, 1) // start deleting tokens (the first token will be overwritten)
                        }
                        // eslint-disable-next-line
                        console.log('____ :', buffer)
                    }

                    if (lastChar === '\'' || lastChar === '"') {
                        if (inString === lastChar) {
                            tokens[stringStartIndex] = buffer
                            stringStartIndex = null
                            inString = false

                            buffer = ''
                            firstBufferEntry = false
                            stringStartIndex = null
                        }
                    }

                    // if (!inString && buffer.length && stringStartIndex) {
                    //     buffer = ''
                    //     firstBufferEntry = false
                    //     stringStartIndex = null
                    // }
                }
                return tokens
            }
        }
    }
</script>
