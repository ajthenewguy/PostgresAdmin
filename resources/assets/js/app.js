
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

import _ from 'lodash'

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

// Mixins
// Vue.mixin(require('./mixins/PostgresMixin.vue'))

Vue.component('primary-content', require('./components/Content.vue'));
Vue.component('admin-content', require('./components/admin/Content.vue'));
Vue.component('database-switcher', require('./components/admin/DatabaseSwitcher.vue'));
Vue.component('tabs', require('./components/Tabs.vue'));
Vue.component('tab', require('./components/admin/Tab.vue'));
// Vue.component('results-footer', require('./components/admin/ResultsFooter.vue'));

Vue.prototype._ = _
Vue.prototype.$http = axios

window.store = {
    debug: true,
    state: {
        activeTab: 0,
        closingTab: false,
        errors: [],
        loadingTable: false,
        processing: false,
        tables: []
    },
    uuid() {
        return Math.random().toString(36).substring(2) + (new Date()).getTime().toString(36)
    },
    setProcessing(newValue) {
        this.state.processing = !! newValue
    },
    isProcessing () {
        if (this.debug) console.log('clearMessageAction triggered')
        this.state.message = ''
    },
    titleCase(string) {
        return string.replace(/_/g, ' ').replace(/(^[a-z])|(\s+[a-z])/g, txt => txt.toUpperCase())
    }
}
window.App = new Vue({
    el: '#app',
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
