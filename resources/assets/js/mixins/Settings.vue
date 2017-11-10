<script>
export default {
    data() {
        return {
            cache: {},
            settings: {
                cache(key, value) {
                    let argCount = arguments.length
                    let returnVal = null
                    if (argCount === 1) {
                        if (this.cacheHas(key)) {
                            returnVal = this.cache[key]
                        }
                    } else if (argCount === 2) {
                        if (value === null) {
                            if (this.cacheHas(key)) {
                                delete this.cache[key]
                            }
                        } else {
                            this.cache[key] = value
                            returnVal = true
                        }
                    }
                    return returnVal
                },
                cacheHas(key) {
                    return this.cache.hasOwnProperty(key)
                },
                cacheSettingKey(key) {
                    if (key.constructor === Array) {
                        let newKey = []
                        _.forEach(key, function(value) {
                            newKey.push('setting_' + value)
                        });
                        return newKey.join(',')
                    }
                    return 'setting_' + key
                },
                get(key) {
                    if (this.cacheHas(this.cacheSettingKey(key))) {


                        // eslint-disable-next-line
                        console.log('CACHE HIT "' + key + '":', this.cache(this.cacheSettingKey(key)))


                        return Promise.resolve(this.cache(this.cacheSettingKey(key)))
                    }


                    // eslint-disable-next-line
                    console.log('CACHE MISS "' + key + '"')


                    return axios.get('http://postgres:5433/settings', { params: { key: key }}).then(response => {
                        this.cache(this.cacheSettingKey(key), response.data)
                        return response.data
                    }).catch(error => {
                        if (error.response.status === 404) {
                            return Promise.reject('Setting "' + key + '" not found')
                        }
                        this.onError(error)
                    })
                },
                set(key, value) {
                    let payload = { key: key, value: value }
                    return axios.post('http://postgres:5433/settings', payload).then(response => {
                        this.cache(this.cacheSettingKey(key), null)
                        return true
                    }).catch(error => {
                        this.onError(error)
                    })
                },
                onError(error) {
                    let message = this.parseError(error)
                    console.error(message)
                    if (typeof message === "string") {
                        alert(message)
                    }
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
                }
            }
        }
    }
}
</script>