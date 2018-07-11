<script>
export default {
    mounted() {
        this.init()
    },
    data() {
        return {
            bus: window.bus,
            cacheData: {},
            queueData: {},
            debounceUpdate: true,
            initialized: false,
            namespace: '',
            namespace_key: '__NAMESPACE__',
            server: '',
            endpoint: '/session',
            init() {
                if (!this.initialized) {
                    this.getNamespace().then(() => {
                        this.initialized = true
                    })
                }
            },
            getNamespace() {
                if (this.namespace_key !== '') {
                    return this.get(this.namespace_key, true).then(key => {
                        this.namespace = key
                        return key
                    })
                } else {
                    return Promise.resolve('')
                }
            },
            setNamespace(key) {
                this.namespace = key
                return this.set(this.namespace_key, key)
                return key
            },
            cache(key, value, skipNamespaceDecoration) {
                let argCount = arguments.length
                let returnVal = null
                if (argCount === 1) {
                    if (this.cacheHas(key, skipNamespaceDecoration)) {
                        key = this.cacheSessionKey(key, skipNamespaceDecoration)
                        returnVal = this.cacheData[key]
                    }
                } else if (argCount === 2) {
                    if (value === null) {
                        if (this.cacheHas(key, skipNamespaceDecoration)) {
                            key = this.cacheSessionKey(key, skipNamespaceDecoration)
                            delete this.cacheData[key]
                        }
                    } else {
                        key = this.cacheSessionKey(key, skipNamespaceDecoration)
                        this.cacheData[key] = value
                        returnVal = true
                    }
                }
                return returnVal
            },
            cacheHas(key, skipNamespaceDecoration) {
                if (!skipNamespaceDecoration) {
                    key = this.cacheSessionKey(key)
                }
                return this.cacheData.hasOwnProperty(key)
            },
            cacheSessionKey(key, skipNamespaceDecoration) {
                let appNamespace = 'session_'
                if (key.constructor === Array) {
                    let newKey = []
                    _.forEach(key, function(value) {
                        if (!skipNamespaceDecoration) {
                            value = appNamespace + value
                        }
                        newKey.push(value)
                    });
                    return newKey.join(',')
                }
                if (!skipNamespaceDecoration) {
                    if (this.namespace !== '' && !key.includes(this.namespace)) {
                        key = this.namespace + key
                    }
                    if (!key.includes(appNamespace)) {
                        key = appNamespace + key
                    }
                }

                return key
            },
            get(key, skipNamespaceDecoration) {
                if (this.cacheHas(key, skipNamespaceDecoration)) {
                    return Promise.resolve(this.cache(key))
                }
                return axios.get(this.server + this.endpoint, { params: { key: key }}).then(response => {
                    this.cache(key, response.data)
                    return response.data
                }).catch(error => {
                    if (error.response.status === 404) {
                        return Promise.reject('Setting "' + key + '" not found')
                    }
                    this.onError(error)
                })
            },
            set(key, value) {
                if (this.debounceUpdate) {
                    return this.queue(key, value)
                } else {
                    return axios.post(this.server + this.endpoint, { key: key, value: value }).then(response => {
                        this.cache(key, value)
                        return true
                    }).catch(error => {
                        this.onError(error)
                    })
                }

            },
            onError(error) {
                let message = this.parseError(error)
                if (message) {
                    console.error(message)
                    if (typeof message === "string") {
                        alert(message)
                    }
                }
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
            queue(key, value) {
                this.queueData[key] = value
                return this.transmit()
            },
            transmit: _.debounce(function() {
                if (Object.keys(this.queueData).length > 0) {
                    return axios.post(this.server + this.endpoint, { data: this.queueData }).then(response => {
                        return response
                    }).catch(error => {
                        this.onError(error)
                    })
                } else {
                    return Promise.resolve(null)
                }
            }, 1000, { 'leading': true })
        }
    }
}
</script>
