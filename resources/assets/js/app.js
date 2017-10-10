
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

Vue.component('primary-content', require('./components/Content.vue'));
Vue.component('admin-content', require('./components/admin/Content.vue'));

Vue.prototype.$http = axios

window.App = new Vue({
    el: '#app',
    methods: {
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
        handleError(error) {
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
            console.log(errorText)
            return errorText
        }
    },
    created() {
        // setup axios
        const { attributes } = document.getElementById('axiosConfig')
        this.baseUrl = attributes.baseUrl.value
        this.accessToken = attributes.accessToken.value
        this.$http.defaults.baseURL = this.baseUrl
        this.$http.defaults.headers.post['Content-Type'] = 'application/json'
        this.$http.defaults.headers.common['Authorization'] = 'Bearer ' + this.accessToken
    }
});
